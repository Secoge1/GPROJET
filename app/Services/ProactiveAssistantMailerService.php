<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AssistantEmailEventModel;
use App\Models\ParametreModel;

/**
 * IA proactive : détecte des frictions utilisateur et envoie des emails d'aide.
 */
class ProactiveAssistantMailerService
{
    private \PDO $db;
    private MailerService $mailer;
    private AssistantEmailEventModel $events;
    private OpenAiService $ai;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->mailer = new MailerService();
        $this->events = new AssistantEmailEventModel();
        $this->events->ensureTable();

        $param = new ParametreModel();
        $key = $param->get('chatbot_openai_api_key', '');
        $provider = $param->get('chatbot_ai_provider', 'openai');
        $this->ai = new OpenAiService($key ?: null, '', 350, $provider);
    }

    /**
     * @return array<string,mixed>
     */
    public function run(bool $dryRun = false): array
    {
        $candidates = $this->collectCandidates();
        $processed = 0;
        $sent = 0;
        $skipped = 0;
        $errors = 0;
        $details = [];

        foreach ($candidates as $c) {
            $processed++;
            $userId = (int) $c['id'];
            $reason = (string) $c['reason_code'];

            if ($this->events->wasSentRecently($userId, $reason, 72)) {
                $skipped++;
                $details[] = ['user_id' => $userId, 'reason' => $reason, 'status' => 'skipped_recent'];
                continue;
            }

            [$subject, $message] = $this->buildMessage($c);
            $name = trim((string)($c['prenom'] ?? '') . ' ' . (string)($c['nom'] ?? ''));
            $to = (string)($c['email'] ?? '');
            if ($to === '') {
                $skipped++;
                $details[] = ['user_id' => $userId, 'reason' => $reason, 'status' => 'skipped_no_email'];
                continue;
            }

            if ($dryRun) {
                $details[] = [
                    'user_id' => $userId,
                    'reason' => $reason,
                    'status' => 'dry_run',
                    'subject' => $subject,
                ];
                continue;
            }

            try {
                $ok = $this->mailer->sendNotification(
                    $to,
                    $name !== '' ? $name : 'Utilisateur',
                    $subject,
                    $message,
                    rtrim(BASE_URL ?? '', '/') . '/auth/connexion',
                    'Accéder à mon compte'
                );
                if ($ok) {
                    $sent++;
                    $this->events->logSent(
                        $userId,
                        $reason,
                        ['subject' => $subject, 'message' => $message],
                        $to,
                        ($name !== '' ? $name : null),
                        $subject,
                        'sent'
                    );
                    $details[] = ['user_id' => $userId, 'reason' => $reason, 'status' => 'sent'];
                } else {
                    $errors++;
                    $this->events->logSent(
                        $userId,
                        $reason,
                        ['subject' => $subject, 'message' => $message],
                        $to,
                        ($name !== '' ? $name : null),
                        $subject,
                        'failed'
                    );
                    $details[] = ['user_id' => $userId, 'reason' => $reason, 'status' => 'send_failed'];
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->events->logSent(
                    $userId,
                    $reason,
                    ['subject' => $subject, 'message' => $message, 'error' => $e->getMessage()],
                    $to,
                    ($name !== '' ? $name : null),
                    $subject,
                    'failed'
                );
                $details[] = ['user_id' => $userId, 'reason' => $reason, 'status' => 'exception', 'error' => $e->getMessage()];
            }
        }

        return [
            'processed' => $processed,
            'sent' => $sent,
            'skipped' => $skipped,
            'errors' => $errors,
            'dry_run' => $dryRun,
            'details' => $details,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function collectCandidates(): array
    {
        $out = [];
        $seen = [];

        // 1) Email non vérifié après 24h
        $sqlUnverified = "SELECT id, email, prenom, nom, role, created_at
            FROM utilisateurs
            WHERE actif = 1
              AND (email_verifie IS NULL OR email_verifie = 0)
              AND created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            LIMIT 150";
        foreach ($this->db->query($sqlUnverified)->fetchAll(\PDO::FETCH_ASSOC) as $u) {
            $u['reason_code'] = 'unverified_email';
            $key = $u['id'] . '|' . $u['reason_code'];
            $seen[$key] = true;
            $out[] = $u;
        }

        // 2) Profil de base incomplet
        $sqlProfile = "SELECT id, email, prenom, nom, role, created_at
            FROM utilisateurs
            WHERE actif = 1
              AND email_verifie = 1
              AND created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
              AND (telephone IS NULL OR telephone = '' OR avatar IS NULL OR avatar = '')
            LIMIT 200";
        foreach ($this->db->query($sqlProfile)->fetchAll(\PDO::FETCH_ASSOC) as $u) {
            $u['reason_code'] = 'incomplete_profile';
            $key = $u['id'] . '|' . $u['reason_code'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $u;
            }
        }

        // 3) Experts dont le profil pro semble incomplet
        $sqlExperts = "SELECT u.id, u.email, u.prenom, u.nom, u.role, u.created_at
            FROM utilisateurs u
            JOIN profils_experts p ON p.utilisateur_id = u.id
            WHERE u.actif = 1
              AND u.email_verifie = 1
              AND u.role = 'expert'
              AND u.created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
              AND (
                    p.titre IS NULL OR p.titre = ''
                    OR p.description IS NULL OR p.description = ''
                    OR p.tarif_horaire IS NULL OR p.tarif_horaire <= 0
                  )
            LIMIT 120";
        foreach ($this->db->query($sqlExperts)->fetchAll(\PDO::FETCH_ASSOC) as $u) {
            $u['reason_code'] = 'expert_profile_incomplete';
            $key = $u['id'] . '|' . $u['reason_code'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $out[] = $u;
            }
        }

        // 4) Signaux de difficulté : multiples pages auth/compte/tracking en 24h
        if ($this->tableExists('user_tracking')) {
            $sqlStruggle = "SELECT u.id, u.email, u.prenom, u.nom, u.role, u.created_at, COUNT(*) AS attempts
                FROM user_tracking t
                JOIN utilisateurs u ON u.id = t.utilisateur_id
                WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                  AND (
                    t.page LIKE '%/auth/connexion%'
                    OR t.page LIKE '%/mot-de-passe%'
                    OR t.page LIKE '%/compte%'
                    OR t.page LIKE '%/portefeuille%'
                  )
                  AND u.actif = 1
                GROUP BY u.id, u.email, u.prenom, u.nom, u.role, u.created_at
                HAVING attempts >= 6
                LIMIT 100";
            foreach ($this->db->query($sqlStruggle)->fetchAll(\PDO::FETCH_ASSOC) as $u) {
                $u['reason_code'] = 'usage_difficulty';
                $key = $u['id'] . '|' . $u['reason_code'];
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $out[] = $u;
                }
            }
        }

        return $out;
    }

    /**
     * @param array<string,mixed> $user
     * @return array{0:string,1:string}
     */
    private function buildMessage(array $user): array
    {
        $reason = (string)($user['reason_code'] ?? 'general');
        $name = trim((string)($user['prenom'] ?? '') . ' ' . (string)($user['nom'] ?? ''));
        $platform = (new ParametreModel())->get('plateforme_nom', 'GLOBALO');

        $fallback = $this->fallbackMessage($reason, $platform);
        if (!$this->ai->isConfigured()) {
            return $fallback;
        }

        try {
            $prompt = "Tu es un assistant relation client de {$platform}. ".
                "Rédige un email très court et bienveillant en français, format strict:\n".
                "SUBJECT: ...\nBODY: ...\n".
                "Contexte: utilisateur {$name}, raison={$reason}. ".
                "Objectif: aider concrètement et proposer une action immédiate.";
            $raw = trim($this->ai->generate($prompt, 220));
            if ($raw === '') {
                return $fallback;
            }
            $subject = '';
            $body = '';
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
    private function fallbackMessage(string $reason, string $platform): array
    {
        switch ($reason) {
            case 'unverified_email':
                return [
                    "Finalisez votre inscription sur {$platform}",
                    "Nous avons remarqué que votre adresse email n'est pas encore vérifiée. ".
                    "Cette étape est nécessaire pour sécuriser votre compte et accéder à toutes les fonctionnalités. ".
                    "Connectez-vous pour terminer l'activation en quelques secondes.",
                ];
            case 'incomplete_profile':
                return [
                    "Votre profil {$platform} est presque prêt",
                    "Votre compte est actif, mais certaines informations importantes semblent manquantes (photo, téléphone, etc.). ".
                    "Un profil complet améliore la confiance et accélère les échanges. ".
                    "Connectez-vous pour finaliser votre profil maintenant.",
                ];
            case 'expert_profile_incomplete':
                return [
                    "Optimisez votre profil expert",
                    "Votre profil expert est visible, mais des éléments clés semblent incomplets (titre, description ou tarif). ".
                    "En les complétant, vous augmentez vos chances d'être contacté rapidement. ".
                    "Connectez-vous pour finaliser votre profil expert.",
                ];
            case 'usage_difficulty':
                return [
                    "On vous aide à avancer rapidement",
                    "Nous avons détecté plusieurs tentatives sur certaines pages. ".
                    "Si vous rencontrez un blocage, nous pouvons vous guider étape par étape immédiatement. ".
                    "Connectez-vous puis utilisez le chatbot intégré, ou répondez simplement à cet email.",
                ];
            default:
                return [
                    "Assistance personnalisée {$platform}",
                    "Nous restons disponibles pour vous aider à profiter pleinement de la plateforme. ".
                    "Connectez-vous à votre espace, puis dites-nous où vous bloquez : nous vous assistons immédiatement.",
                ];
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

