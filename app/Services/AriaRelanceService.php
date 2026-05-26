<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AssistantEmailEventModel;
use App\Models\ParametreModel;

/**
 * ARIA — Service de relance automatique des inscriptions incomplètes.
 *
 * Détecte les Professeurs et Étudiants dont le profil est incomplet ou
 * en attente de validation, et leur envoie un email professionnel personnalisé
 * avec une fréquence maximale d'une relance par profil tous les 3 jours (72h).
 *
 * Codes de raison ARIA :
 *   aria_prof_no_desc          → Professeur sans description (ou trop courte)
 *   aria_prof_no_rate          → Professeur sans tarif horaire défini
 *   aria_prof_not_available    → Professeur non disponible depuis > 7 jours
 *   aria_prof_pending_long     → Professeur en attente de validation depuis > 5 jours
 *   aria_etud_no_university    → Étudiant sans université renseignée
 *   aria_etud_no_filiere       → Étudiant sans filière renseignée
 *   aria_etud_no_bio           → Étudiant sans présentation/bio
 *   aria_unverified_email      → Email non vérifié (profs & étudiants) après 24h
 */
class AriaRelanceService
{
    /** Délai minimal entre deux relances pour un même profil/raison (heures) */
    private const COOLDOWN_HOURS = 72;

    /** Limite de candidats par catégorie */
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
            $key      = (string) $param->get('chatbot_openai_api_key', '');
            $provider = (string) $param->get('chatbot_ai_provider', 'openai');
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
                    'Compléter mon inscription'
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
                    ['subject' => $subject, 'role' => $candidate['role'] ?? ''],
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
     * @return array<int,array<string,mixed>>
     */
    private function collectCandidates(): array
    {
        $candidates = [];
        $seen       = [];

        // Emails non vérifiés (profs & étudiants) après 24h
        $this->addUnverifiedEmails($candidates, $seen);

        // Professeurs
        $this->addProfsWithoutDescription($candidates, $seen);
        $this->addProfsWithoutRate($candidates, $seen);
        $this->addProfsNotAvailable($candidates, $seen);
        $this->addProfsPendingValidationLong($candidates, $seen);

        // Étudiants
        $this->addEtudiantsWithoutUniversity($candidates, $seen);
        $this->addEtudiantsWithoutFiliere($candidates, $seen);
        $this->addEtudiantsWithoutBio($candidates, $seen);

        return $candidates;
    }

    /** Profs & étudiants avec email non vérifié après 24h */
    private function addUnverifiedEmails(array &$out, array &$seen): void
    {
        $sql = "SELECT id, email, prenom, nom, role, created_at
                FROM utilisateurs
                WHERE actif = 1
                  AND role IN ('professeur', 'etudiant')
                  AND (email_verifie IS NULL OR email_verifie = 0)
                  AND created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                LIMIT " . self::BATCH_LIMIT;
        $this->appendRows($this->fetch($sql), 'aria_unverified_email', $out, $seen);
    }

    /** Professeurs sans description de profil (ou < 30 caractères) */
    private function addProfsWithoutDescription(array &$out, array &$seen): void
    {
        $sql = "SELECT u.id, u.email, u.prenom, u.nom, u.role, u.created_at
                FROM utilisateurs u
                INNER JOIN profils_professeurs p ON p.utilisateur_id = u.id
                WHERE u.actif = 1
                  AND u.role = 'professeur'
                  AND u.created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                  AND (p.description IS NULL OR CHAR_LENGTH(TRIM(p.description)) < 30)
                LIMIT " . self::BATCH_LIMIT;
        $this->appendRows($this->fetch($sql), 'aria_prof_no_desc', $out, $seen);
    }

    /** Professeurs sans tarif horaire défini (= 0 ou NULL) */
    private function addProfsWithoutRate(array &$out, array &$seen): void
    {
        $sql = "SELECT u.id, u.email, u.prenom, u.nom, u.role, u.created_at
                FROM utilisateurs u
                INNER JOIN profils_professeurs p ON p.utilisateur_id = u.id
                WHERE u.actif = 1
                  AND u.role = 'professeur'
                  AND u.created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                  AND (p.tarif_horaire IS NULL OR p.tarif_horaire <= 0)
                LIMIT " . self::BATCH_LIMIT;
        $this->appendRows($this->fetch($sql), 'aria_prof_no_rate', $out, $seen);
    }

    /** Professeurs marqués non disponibles (disponible = 0) depuis > 7 jours */
    private function addProfsNotAvailable(array &$out, array &$seen): void
    {
        $sql = "SELECT u.id, u.email, u.prenom, u.nom, u.role, u.created_at
                FROM utilisateurs u
                INNER JOIN profils_professeurs p ON p.utilisateur_id = u.id
                WHERE u.actif = 1
                  AND u.role = 'professeur'
                  AND p.valide_par_admin = 1
                  AND p.disponible = 0
                  AND u.created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)
                LIMIT " . self::BATCH_LIMIT;
        $this->appendRows($this->fetch($sql), 'aria_prof_not_available', $out, $seen);
    }

    /** Professeurs en attente de validation admin depuis > 5 jours */
    private function addProfsPendingValidationLong(array &$out, array &$seen): void
    {
        $sql = "SELECT u.id, u.email, u.prenom, u.nom, u.role, u.created_at
                FROM utilisateurs u
                INNER JOIN profils_professeurs p ON p.utilisateur_id = u.id
                WHERE u.actif = 1
                  AND u.role = 'professeur'
                  AND p.valide_par_admin = 0
                  AND u.created_at <= DATE_SUB(NOW(), INTERVAL 5 DAY)
                LIMIT " . self::BATCH_LIMIT;
        $this->appendRows($this->fetch($sql), 'aria_prof_pending_long', $out, $seen);
    }

    /** Étudiants sans université renseignée */
    private function addEtudiantsWithoutUniversity(array &$out, array &$seen): void
    {
        if (!$this->tableExists('profils_etudiants')) {
            return;
        }
        $sql = "SELECT u.id, u.email, u.prenom, u.nom, u.role, u.created_at
                FROM utilisateurs u
                INNER JOIN profils_etudiants pe ON pe.utilisateur_id = u.id
                WHERE u.actif = 1
                  AND u.role = 'etudiant'
                  AND u.created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                  AND (pe.universite IS NULL OR TRIM(pe.universite) = '')
                LIMIT " . self::BATCH_LIMIT;
        $this->appendRows($this->fetch($sql), 'aria_etud_no_university', $out, $seen);
    }

    /** Étudiants sans filière renseignée */
    private function addEtudiantsWithoutFiliere(array &$out, array &$seen): void
    {
        if (!$this->tableExists('profils_etudiants')) {
            return;
        }
        $sql = "SELECT u.id, u.email, u.prenom, u.nom, u.role, u.created_at
                FROM utilisateurs u
                INNER JOIN profils_etudiants pe ON pe.utilisateur_id = u.id
                WHERE u.actif = 1
                  AND u.role = 'etudiant'
                  AND u.created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                  AND (pe.filiere IS NULL OR TRIM(pe.filiere) = '')
                LIMIT " . self::BATCH_LIMIT;
        $this->appendRows($this->fetch($sql), 'aria_etud_no_filiere', $out, $seen);
    }

    /** Étudiants sans bio/présentation */
    private function addEtudiantsWithoutBio(array &$out, array &$seen): void
    {
        if (!$this->tableExists('profils_etudiants')) {
            return;
        }
        $sql = "SELECT u.id, u.email, u.prenom, u.nom, u.role, u.created_at
                FROM utilisateurs u
                INNER JOIN profils_etudiants pe ON pe.utilisateur_id = u.id
                WHERE u.actif = 1
                  AND u.role = 'etudiant'
                  AND u.created_at <= DATE_SUB(NOW(), INTERVAL 48 HOUR)
                  AND (pe.bio IS NULL OR CHAR_LENGTH(TRIM(pe.bio)) < 20)
                LIMIT " . self::BATCH_LIMIT;
        $this->appendRows($this->fetch($sql), 'aria_etud_no_bio', $out, $seen);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GÉNÉRATION DES EMAILS
    // ──────────────────────────────────────────────────────────────────────────

    /**
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
            $roleLabel   = $role === 'professeur' ? 'professeur' : 'étudiant';

            $prompt = "Tu es ARIA, l'assistante inscriptions IA de {$this->platformName}, une plateforme éducative en Afrique de l'Ouest.\n"
                . "Rédige un email de relance professionnel, bienveillant et très bref (3 phrases max) en français.\n"
                . "Format STRICT :\n"
                . "SUBJECT: <sujet accrocheur ≤ 12 mots>\n"
                . "BODY: <corps de l'email, salutation incluse>\n\n"
                . "Contexte : {$roleLabel} prénommé(e) « {$displayName} », information manquante = « {$missingInfo} ».\n"
                . "Ton : encourageant, professionnel, adapté au contexte africain. Inclure un appel à l'action clair.";

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
     * @return array{0:string,1:string}
     */
    private function fallbackEmail(string $reasonCode): array
    {
        $p = $this->platformName;

        switch ($reasonCode) {
            case 'aria_unverified_email':
                return [
                    "Vérifiez votre email pour activer votre compte {$p}",
                    "Bonjour,\n\nVotre inscription sur {$p} est presque terminée. "
                    . "Il ne reste qu'une seule étape : vérifier votre adresse email pour activer pleinement votre compte.\n\n"
                    . "Connectez-vous et suivez le lien de vérification pour accéder à toutes les fonctionnalités.",
                ];

            case 'aria_prof_no_desc':
                return [
                    "Décrivez votre profil professeur pour attirer des étudiants — {$p}",
                    "Bonjour,\n\nLes étudiants de {$p} consultent votre profil avant de vous contacter. "
                    . "Votre description est encore vide ou trop courte pour inspirer confiance.\n\n"
                    . "Prenez 5 minutes pour rédiger une présentation de votre parcours et de vos spécialités — c'est ce qui fait la différence.",
                ];

            case 'aria_prof_no_rate':
                return [
                    "Définissez votre tarif pour recevoir des demandes sur {$p}",
                    "Bonjour,\n\nVotre profil professeur sur {$p} n'affiche pas encore de tarif horaire. "
                    . "Sans tarif, les étudiants ne peuvent pas estimer le coût de vos sessions et hésitent à vous solliciter.\n\n"
                    . "Renseignez votre tarif dès maintenant pour être visible dans les recherches filtrées par budget.",
                ];

            case 'aria_prof_not_available':
                return [
                    "Êtes-vous disponible pour de nouvelles sessions ? — {$p}",
                    "Bonjour,\n\nVotre profil sur {$p} indique que vous n'êtes pas disponible. "
                    . "Des étudiants cherchent actuellement un professeur dans votre domaine.\n\n"
                    . "Si vous avez du temps pour de nouvelles sessions, activez votre disponibilité depuis votre espace — ça ne prend que quelques secondes.",
                ];

            case 'aria_prof_pending_long':
                return [
                    "Votre dossier {$p} est en cours d'examen",
                    "Bonjour,\n\nVotre inscription en tant que professeur sur {$p} est bien prise en compte et en cours de validation par notre équipe.\n\n"
                    . "Pour accélérer le processus, assurez-vous que votre profil est complet (description, tarif, photo). "
                    . "Nous vous préviendrons dès validation — merci de votre patience.",
                ];

            case 'aria_etud_no_university':
                return [
                    "Renseignez votre université pour un profil complet — {$p}",
                    "Bonjour,\n\nVotre profil étudiant sur {$p} ne mentionne pas encore votre université. "
                    . "Cette information aide les professeurs à mieux cibler leurs offres d'aide.\n\n"
                    . "Ajoutez-la en quelques secondes depuis votre espace personnel.",
                ];

            case 'aria_etud_no_filiere':
                return [
                    "Quelle est votre filière ? Complétez votre profil {$p}",
                    "Bonjour,\n\nVotre filière n'est pas encore renseignée sur {$p}. "
                    . "En l'indiquant, vous recevrez des suggestions de professeurs adaptées à votre domaine d'études.\n\n"
                    . "Connectez-vous pour compléter cette information.",
                ];

            case 'aria_etud_no_bio':
                return [
                    "Présentez-vous en quelques mots sur {$p}",
                    "Bonjour,\n\nVotre profil étudiant sur {$p} manque d'une présentation personnelle. "
                    . "Une courte bio aide les professeurs à mieux vous connaître et à adapter leur accompagnement.\n\n"
                    . "Rédigez quelques lignes depuis votre espace — c'est rapide et ça fait toute la différence.",
                ];

            default:
                return [
                    "Finalisez votre inscription sur {$p}",
                    "Bonjour,\n\nVotre profil sur {$p} comporte encore quelques informations manquantes. "
                    . "Un dossier complet vous permet d'accéder à toutes les fonctionnalités et d'être mieux mis en relation.\n\n"
                    . "Connectez-vous pour terminer votre inscription.",
                ];
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // UTILITAIRES
    // ──────────────────────────────────────────────────────────────────────────

    private function labelForReason(string $reasonCode): string
    {
        $labels = [
            'aria_unverified_email'   => 'vérification de l\'adresse email',
            'aria_prof_no_desc'       => 'description du profil professeur',
            'aria_prof_no_rate'       => 'tarif horaire des sessions',
            'aria_prof_not_available' => 'disponibilité non activée alors que le profil est validé',
            'aria_prof_pending_long'  => 'profil en attente de validation depuis plus de 5 jours',
            'aria_etud_no_university' => 'université de rattachement',
            'aria_etud_no_filiere'    => 'filière / domaine d\'études',
            'aria_etud_no_bio'        => 'présentation personnelle (bio)',
        ];
        return $labels[$reasonCode] ?? 'informations d\'inscription';
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
                $seen[$key]         = true;
                $row['reason_code'] = $reasonCode;
                $out[]              = $row;
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
            error_log('[AriaRelanceService] SQL error: ' . $e->getMessage());
            return [];
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            return (bool) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
