<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AssistantEmailEventModel;
use App\Models\ParametreModel;

/**
 * PROFIA — Service de relance automatique des profils incomplets.
 *
 * Détecte les Experts et Clients dont le profil est incomplet et leur envoie
 * un email professionnel personnalisé, avec une fréquence maximale de 1 envoi
 * par profil tous les 3 jours (72 heures).
 *
 * Codes de raison PROFIA :
 *   profia_expert_no_title    → Expert sans titre professionnel
 *   profia_expert_no_desc     → Expert sans description (< 30 caractères)
 *   profia_expert_no_rate     → Expert sans tarif horaire
 *   profia_client_no_avatar   → Client sans photo de profil
 *   profia_client_no_phone    → Client sans téléphone
 *   profia_expert_low_score   → Expert avec score global < 40 %
 */
class ProfiaRelanceService
{
    /** Délai minimal entre deux relances pour un même profil (heures) */
    private const COOLDOWN_HOURS = 72;

    /** Limite de candidats par catégorie pour éviter les surcharges */
    private const BATCH_LIMIT = 50;

    private \PDO $db;
    private MailerService $mailer;
    private AssistantEmailEventModel $events;
    private OpenAiService $ai;
    private string $platformName;

    public function __construct()
    {
        $this->db     = Database::getInstance();
        $this->mailer = new MailerService();
        $this->events = new AssistantEmailEventModel();
        $this->events->ensureTable();

        $param              = new ParametreModel();
        $this->platformName = (string) $param->get('plateforme_nom', 'GLOBALO');

        // Même cascade de providers que RhAiService : Gemini > Mistral > OpenAI
        $geminiKey  = getenv('GEMINI_API_KEY')  ?: (defined('GEMINI_API_KEY')  ? GEMINI_API_KEY  : '');
        $mistralKey = getenv('MISTRAL_API_KEY') ?: (defined('MISTRAL_API_KEY') ? MISTRAL_API_KEY : '');
        $openaiKey  = getenv('OPENAI_API_KEY')  ?: (defined('OPENAI_API_KEY')  ? OPENAI_API_KEY  : '');

        if ($geminiKey !== '') {
            $this->ai = new OpenAiService($geminiKey, '', 400, 'gemini');
        } elseif ($mistralKey !== '') {
            $this->ai = new OpenAiService($mistralKey, '', 400, 'mistral');
        } elseif ($openaiKey !== '') {
            $this->ai = new OpenAiService($openaiKey, '', 400, 'openai');
        } else {
            $key = (string) (new ParametreModel())->get('chatbot_openai_api_key', '');
            $provider = (string) (new ParametreModel())->get('chatbot_ai_provider', 'openai');
            $this->ai = new OpenAiService($key ?: null, '', 400, $provider);
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POINT D'ENTRÉE PRINCIPAL
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * @return array{processed:int,sent:int,skipped:int,errors:int,dry_run:bool,details:array}
     */
    public function run(bool $dryRun = false): array
    {
        $candidates = $this->collectCandidates();
        $processed  = 0;
        $sent       = 0;
        $skipped    = 0;
        $errors     = 0;
        $details    = [];

        foreach ($candidates as $candidate) {
            $processed++;
            $userId     = (int) $candidate['id'];
            $reasonCode = (string) $candidate['reason_code'];
            $email      = (string) ($candidate['email'] ?? '');

            // Toujours respecter le cooldown de 72h (3 jours)
            if ($this->events->wasSentRecently($userId, $reasonCode, self::COOLDOWN_HOURS)) {
                $skipped++;
                $details[] = ['user_id' => $userId, 'reason' => $reasonCode, 'status' => 'skipped_cooldown'];
                continue;
            }

            if ($email === '') {
                $skipped++;
                $details[] = ['user_id' => $userId, 'reason' => $reasonCode, 'status' => 'skipped_no_email'];
                continue;
            }

            [$subject, $body] = $this->buildEmail($candidate);
            $displayName = $this->displayName($candidate);

            if ($dryRun) {
                $details[] = [
                    'user_id' => $userId,
                    'reason'  => $reasonCode,
                    'status'  => 'dry_run',
                    'subject' => $subject,
                    'to'      => $email,
                ];
                continue;
            }

            try {
                $loginUrl = rtrim(BASE_URL ?? '', '/') . '/auth/connexion';
                $ok = $this->mailer->sendNotification(
                    $email,
                    $displayName ?: 'Utilisateur',
                    $subject,
                    $body,
                    $loginUrl,
                    'Compléter mon profil'
                );

                $status = $ok ? 'sent' : 'failed';
                if ($ok) {
                    $sent++;
                } else {
                    $errors++;
                }

                $this->events->logSent(
                    $userId,
                    $reasonCode,
                    ['subject' => $subject, 'body' => $body, 'role' => $candidate['role'] ?? ''],
                    $email,
                    $displayName ?: null,
                    $subject,
                    $status
                );

                $details[] = ['user_id' => $userId, 'reason' => $reasonCode, 'status' => $status];

            } catch (\Throwable $e) {
                $errors++;
                $this->events->logSent(
                    $userId,
                    $reasonCode,
                    ['error' => $e->getMessage()],
                    $email,
                    $displayName ?: null,
                    $subject,
                    'failed'
                );
                $details[] = [
                    'user_id' => $userId,
                    'reason'  => $reasonCode,
                    'status'  => 'exception',
                    'error'   => $e->getMessage(),
                ];
            }
        }

        return [
            'processed' => $processed,
            'sent'      => $sent,
            'skipped'   => $skipped,
            'errors'    => $errors,
            'dry_run'   => $dryRun,
            'details'   => $details,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // COLLECTE DES CANDIDATS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Retourne la liste des utilisateurs éligibles à une relance PROFIA.
     *
     * @return array<int,array<string,mixed>>
     */
    private function collectCandidates(): array
    {
        $candidates = [];
        $seen       = [];

        $this->addExpertsWithoutTitle($candidates, $seen);
        $this->addExpertsWithoutDescription($candidates, $seen);
        $this->addExpertsWithoutRate($candidates, $seen);
        $this->addClientsWithoutAvatar($candidates, $seen);
        $this->addClientsWithoutPhone($candidates, $seen);
        $this->addLowScoreExperts($candidates, $seen);

        return $candidates;
    }

    /** Experts inscrits depuis > 48h sans titre professionnel */
    private function addExpertsWithoutTitle(array &$out, array &$seen): void
    {
        $sql = "SELECT u.id, u.email, u.prenom, u.nom, u.role, u.created_at
                FROM utilisateurs u
                INNER JOIN profils_experts p ON p.utilisateur_id = u.id
                WHERE u.actif = 1
                  AND u.role = 'expert'
                  AND u.created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                  AND (p.titre IS NULL OR TRIM(p.titre) = '')
                LIMIT " . self::BATCH_LIMIT;
        $this->appendRows($this->fetch($sql), 'profia_expert_no_title', $out, $seen);
    }

    /** Experts sans description (ou description < 30 caractères) */
    private function addExpertsWithoutDescription(array &$out, array &$seen): void
    {
        $sql = "SELECT u.id, u.email, u.prenom, u.nom, u.role, u.created_at
                FROM utilisateurs u
                INNER JOIN profils_experts p ON p.utilisateur_id = u.id
                WHERE u.actif = 1
                  AND u.role = 'expert'
                  AND u.created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                  AND (p.description IS NULL OR CHAR_LENGTH(TRIM(p.description)) < 30)
                LIMIT " . self::BATCH_LIMIT;
        $this->appendRows($this->fetch($sql), 'profia_expert_no_desc', $out, $seen);
    }

    /** Experts sans tarif horaire défini */
    private function addExpertsWithoutRate(array &$out, array &$seen): void
    {
        $sql = "SELECT u.id, u.email, u.prenom, u.nom, u.role, u.created_at
                FROM utilisateurs u
                INNER JOIN profils_experts p ON p.utilisateur_id = u.id
                WHERE u.actif = 1
                  AND u.role = 'expert'
                  AND u.created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                  AND (p.tarif_horaire IS NULL OR p.tarif_horaire <= 0)
                LIMIT " . self::BATCH_LIMIT;
        $this->appendRows($this->fetch($sql), 'profia_expert_no_rate', $out, $seen);
    }

    /** Clients sans avatar depuis > 48h */
    private function addClientsWithoutAvatar(array &$out, array &$seen): void
    {
        $sql = "SELECT id, email, prenom, nom, role, created_at
                FROM utilisateurs
                WHERE actif = 1
                  AND role = 'client'
                  AND created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                  AND (avatar IS NULL OR TRIM(avatar) = '' OR avatar = 'default.png')
                LIMIT " . self::BATCH_LIMIT;
        $this->appendRows($this->fetch($sql), 'profia_client_no_avatar', $out, $seen);
    }

    /** Clients sans numéro de téléphone */
    private function addClientsWithoutPhone(array &$out, array &$seen): void
    {
        $sql = "SELECT id, email, prenom, nom, role, created_at
                FROM utilisateurs
                WHERE actif = 1
                  AND role = 'client'
                  AND created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                  AND (telephone IS NULL OR TRIM(telephone) = '')
                LIMIT " . self::BATCH_LIMIT;
        $this->appendRows($this->fetch($sql), 'profia_client_no_phone', $out, $seen);
    }

    /** Experts avec score global < 40 % (pas de titre ET pas de description ET pas de tarif) */
    private function addLowScoreExperts(array &$out, array &$seen): void
    {
        $sql = "SELECT u.id, u.email, u.prenom, u.nom, u.role, u.created_at
                FROM utilisateurs u
                INNER JOIN profils_experts p ON p.utilisateur_id = u.id
                WHERE u.actif = 1
                  AND u.role = 'expert'
                  AND u.created_at <= DATE_SUB(NOW(), INTERVAL 72 HOUR)
                  AND (
                      (CASE WHEN p.titre IS NOT NULL AND TRIM(p.titre) != '' THEN 30 ELSE 0 END)
                    + (CASE WHEN p.description IS NOT NULL AND CHAR_LENGTH(TRIM(p.description)) >= 30 THEN 30 ELSE 0 END)
                    + (CASE WHEN p.tarif_horaire IS NOT NULL AND p.tarif_horaire > 0 THEN 20 ELSE 0 END)
                    + (CASE WHEN u.avatar IS NOT NULL AND TRIM(u.avatar) != '' AND u.avatar != 'default.png' THEN 20 ELSE 0 END)
                  ) < 40
                LIMIT " . self::BATCH_LIMIT;
        $this->appendRows($this->fetch($sql), 'profia_expert_low_score', $out, $seen);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GÉNÉRATION DES EMAILS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Génère [sujet, corps] via IA ou en fallback statique.
     *
     * @param array<string,mixed> $user
     * @return array{0:string,1:string}
     */
    private function buildEmail(array $user): array
    {
        $reasonCode  = (string) ($user['reason_code'] ?? '');
        $displayName = $this->displayName($user);
        $fallback    = $this->fallbackEmail($reasonCode);

        if (!$this->ai->isConfigured()) {
            return $fallback;
        }

        try {
            $missingInfo = $this->labelForReason($reasonCode);
            $role        = (string) ($user['role'] ?? 'utilisateur');
            $roleLabel   = $role === 'expert' ? 'expert' : 'client';

            $prompt = "Tu es PROFIA, l'analyste profils IA de {$this->platformName}, une plateforme de mise en relation d'experts et de clients en Afrique de l'Ouest.\n"
                . "Rédige un email de relance professionnel, chaleureux et bref (3 phrases max pour le corps) en français.\n"
                . "Format STRICT — ne dévie pas :\n"
                . "SUBJECT: <sujet accrocheur ≤ 12 mots>\n"
                . "BODY: <corps de l'email, salutation incluse>\n\n"
                . "Contexte : {$roleLabel} prénommé(e) « {$displayName} », information manquante = « {$missingInfo} ».\n"
                . "Ton : bienveillant, encourageant, professionnel. Inclure un appel à l'action clair.";

            $raw = trim($this->ai->generate($prompt, 300));

            if ($raw === '') {
                return $fallback;
            }

            $subject = '';
            $body    = '';

            if (preg_match('/SUBJECT:\s*(.+)/i', $raw, $m)) {
                $subject = trim($m[1]);
            }
            if (preg_match('/BODY:\s*([\s\S]+)/i', $raw, $m)) {
                $body = trim($m[1]);
            }

            if ($subject === '' || $body === '') {
                return $fallback;
            }

            return [$subject, $body];

        } catch (\Throwable $e) {
            return $fallback;
        }
    }

    /**
     * Messages statiques de secours, professionnels et personnalisés par raison.
     *
     * @return array{0:string,1:string}
     */
    private function fallbackEmail(string $reasonCode): array
    {
        $p = $this->platformName;

        switch ($reasonCode) {
            case 'profia_expert_no_title':
                return [
                    "Votre titre professionnel manque sur {$p}",
                    "Bonjour,\n\nVotre profil expert sur {$p} est en ligne, mais il lui manque encore un titre professionnel. "
                    . "Ce titre est la première chose que les clients remarquent : il augmente significativement vos chances d'être contacté.\n\n"
                    . "Prenez 2 minutes pour l'ajouter depuis votre espace personnel.",
                ];

            case 'profia_expert_no_desc':
                return [
                    "Décrivez votre expertise pour attirer plus de clients — {$p}",
                    "Bonjour,\n\nLes clients de {$p} cherchent des experts dont ils comprennent les compétences et l'expérience. "
                    . "Votre profil est visible, mais votre description est encore vide ou trop courte.\n\n"
                    . "Une description soignée de 3 à 5 lignes peut multiplier vos mises en relation par deux. Connectez-vous pour la rédiger.",
                ];

            case 'profia_expert_no_rate':
                return [
                    "Définissez votre tarif pour recevoir des missions sur {$p}",
                    "Bonjour,\n\nVotre profil expert sur {$p} n'affiche pas encore de tarif horaire. "
                    . "Sans tarif visible, les clients hésitent à vous contacter. "
                    . "Définissez votre tarif (même approximatif) pour apparaître dans les résultats de recherche filtrés par budget.\n\n"
                    . "Connectez-vous dès maintenant pour le renseigner.",
                ];

            case 'profia_client_no_avatar':
                return [
                    "Ajoutez une photo pour compléter votre profil {$p}",
                    "Bonjour,\n\nLes profils avec photo inspirent davantage confiance aux experts de {$p}. "
                    . "Votre compte est actif, mais votre photo de profil est encore manquante.\n\n"
                    . "Ajoutez-en une en quelques secondes depuis vos paramètres de compte.",
                ];

            case 'profia_client_no_phone':
                return [
                    "Un numéro de téléphone pour sécuriser votre compte {$p}",
                    "Bonjour,\n\nPour faciliter la communication avec les experts et sécuriser votre compte {$p}, "
                    . "nous vous recommandons d'ajouter votre numéro de téléphone.\n\n"
                    . "Cette information reste confidentielle et n'est partagée qu'avec vos experts en mission.",
                ];

            case 'profia_expert_low_score':
                return [
                    "Votre profil {$p} peut être bien plus percutant",
                    "Bonjour,\n\nNotre analyse montre que votre profil expert sur {$p} est incomplet sur plusieurs points essentiels "
                    . "(titre, description, tarif, photo). Un profil optimisé attire en moyenne 3× plus de clients.\n\n"
                    . "Consacrez 5 minutes à votre profil aujourd'hui — chaque détail compte pour décrocher votre prochaine mission.",
                ];

            default:
                return [
                    "Complétez votre profil {$p} pour maximiser vos opportunités",
                    "Bonjour,\n\nVotre profil sur {$p} comporte encore quelques informations manquantes. "
                    . "Un profil complet améliore votre visibilité et augmente vos chances de connexion avec la bonne personne.\n\n"
                    . "Connectez-vous pour finaliser votre profil dès maintenant.",
                ];
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // UTILITAIRES
    // ──────────────────────────────────────────────────────────────────────────

    private function labelForReason(string $reasonCode): string
    {
        $labels = [
            'profia_expert_no_title'   => 'titre professionnel',
            'profia_expert_no_desc'    => 'description de l\'expertise',
            'profia_expert_no_rate'    => 'tarif horaire',
            'profia_client_no_avatar'  => 'photo de profil',
            'profia_client_no_phone'   => 'numéro de téléphone',
            'profia_expert_low_score'  => 'plusieurs informations essentielles (titre, description, tarif, photo)',
        ];
        return $labels[$reasonCode] ?? 'informations de profil';
    }

    /** @param array<string,mixed> $user */
    private function displayName(array $user): string
    {
        return trim((string) ($user['prenom'] ?? '') . ' ' . (string) ($user['nom'] ?? ''));
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @param array<int,array<string,mixed>> $out
     * @param array<string,bool>             $seen
     */
    private function appendRows(array $rows, string $reasonCode, array &$out, array &$seen): void
    {
        foreach ($rows as $row) {
            $key = $row['id'] . '|' . $reasonCode;
            if (!isset($seen[$key])) {
                $seen[$key]        = true;
                $row['reason_code'] = $reasonCode;
                $out[]             = $row;
            }
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function fetch(string $sql): array
    {
        try {
            return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('[ProfiaRelanceService] SQL error: ' . $e->getMessage());
            return [];
        }
    }
}
