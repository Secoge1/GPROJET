<?php
/**
 * GLOBALO — PushNotificationService
 * Web Push conforme RFC 8291 (chiffrement) + VAPID via minishlink/web-push.
 */

namespace App\Services;

use App\Core\Database;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    /** @var \PDO */
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Enregistre ou met à jour un abonnement push pour un utilisateur.
     */
    public function subscribe(int $userId, string $endpoint, string $p256dh, string $authKey, string $userAgent = ''): bool
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth_key, user_agent)
                VALUES (:uid, :ep, :p256dh, :auth, :ua)
                ON DUPLICATE KEY UPDATE
                    user_id    = VALUES(user_id),
                    p256dh     = VALUES(p256dh),
                    auth_key   = VALUES(auth_key),
                    user_agent = VALUES(user_agent),
                    updated_at = NOW()
            ");
            $stmt->execute([
                ':uid'   => $userId,
                ':ep'    => $endpoint,
                ':p256dh'=> $p256dh,
                ':auth'  => $authKey,
                ':ua'    => substr($userAgent, 0, 500),
            ]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Supprime un abonnement push par endpoint.
     */
    public function unsubscribe(string $endpoint): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
            $stmt->execute([$endpoint]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Supprime tous les abonnements d'un utilisateur.
     */
    public function unsubscribeUser(int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM push_subscriptions WHERE user_id = ?");
            $stmt->execute([$userId]);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Envoie une notification suite à une ligne `notifications` (in-app).
     * Normalise l’URL (BASE_URL si relative).
     */
    public function notifyAfterInAppNotification(int $userId, string $titre, string $contenu, string $lien, int $inAppNotificationId): int
    {
        $base = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';
        $body = $contenu !== ''
            ? trim(preg_replace('/\s+/u', ' ', strip_tags($contenu)))
            : $titre;
        if ($body === '') {
            $body = $titre;
        }
        $body = mb_substr($body, 0, 500);
        $url = $this->absoluteUrl($lien, $base);

        return $this->sendToUser($userId, $titre, $body, $url, '', $inAppNotificationId);
    }

    /**
     * Envoie une notification push à un utilisateur donné (tous ses appareils).
     *
     * @param string $url    URL absolue ou chemin à ouvrir au clic
     * @param int|null $inAppNotificationId Pour le tag côté SW (dédup)
     */
    public function sendToUser(int $userId, string $title, string $body, string $url = '/', string $icon = '', ?int $inAppNotificationId = null): int
    {
        $subs = $this->getSubscriptionsByUser($userId);
        return $this->sendToSubscriptions($subs, $title, $body, $url, $icon, $inAppNotificationId);
    }

    /**
     * Envoie une notification à tous les abonnés (broadcast).
     */
    public function sendToAll(string $title, string $body, string $url = '/', string $icon = ''): int
    {
        $subs = $this->getAllSubscriptions();
        return $this->sendToSubscriptions($subs, $title, $body, $url, $icon, null);
    }

    /**
     * Envoie une notification à un rôle spécifique.
     */
    public function sendToRole(string $role, string $title, string $body, string $url = '/', string $icon = ''): int
    {
        $subs = $this->getSubscriptionsByRole($role);
        return $this->sendToSubscriptions($subs, $title, $body, $url, $icon, null);
    }

    /**
     * Notifie tous les experts/prestataires/professeurs abonnés au push
     * qu'une nouvelle demande est disponible.
     *
     * Si $competenceId est fourni, seuls les experts ayant cette compétence
     * (+ tous les professeurs) reçoivent la notification.
     */
    public function notifyNouvelleDemandeAuxExperts(int $demandeId, string $titre, ?int $competenceId): int
    {
        $base  = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';
        $url   = $base . '/expert/demandes';
        $body  = mb_substr(trim($titre), 0, 200);
        $subs  = $this->getSubscriptionsForNewDemande($competenceId);

        if (empty($subs)) {
            return 0;
        }

        return $this->sendToSubscriptions(
            $subs,
            'Nouvelle mission disponible',
            $body,
            $url,
            '',
            null
        );
    }

    /**
     * Récupère les abonnements push des experts/prestataires/professeurs
     * éligibles pour la compétence donnée (ou tous si null).
     *
     * @return list<array{endpoint:string,p256dh:string,auth_key:string}>
     */
    private function getSubscriptionsForNewDemande(?int $competenceId): array
    {
        try {
            if ($competenceId && $competenceId > 0) {
                // Experts ayant la compétence + tous les professeurs
                $stmt = $this->db->prepare("
                    SELECT DISTINCT ps.endpoint, ps.p256dh, ps.auth_key
                    FROM push_subscriptions ps
                    JOIN utilisateurs u ON u.id = ps.user_id
                    LEFT JOIN profils_experts pe ON pe.utilisateur_id = ps.user_id
                    LEFT JOIN expert_competences ec
                           ON ec.expert_id = pe.id AND ec.competence_id = :cid
                    WHERE u.role IN ('expert', 'prestataire', 'professeur')
                      AND (ec.competence_id IS NOT NULL OR u.role = 'professeur')
                    LIMIT 500
                ");
                $stmt->execute([':cid' => $competenceId]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT ps.endpoint, ps.p256dh, ps.auth_key
                    FROM push_subscriptions ps
                    JOIN utilisateurs u ON u.id = ps.user_id
                    WHERE u.role IN ('expert', 'prestataire', 'professeur')
                    LIMIT 500
                ");
                $stmt->execute();
            }
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function absoluteUrl(string $lien, string $base): string
    {
        $lien = trim($lien);
        if ($lien === '') {
            return $base !== '' ? $base . '/' : '/';
        }
        if (preg_match('#^https?://#i', $lien)) {
            return $lien;
        }
        if ($base === '') {
            return '/' . ltrim($lien, '/');
        }
        return $base . '/' . ltrim($lien, '/');
    }

    /**
     * @param list<array{endpoint:string,p256dh:string,auth_key:string}> $subs
     */
    private function sendToSubscriptions(array $subs, string $title, string $body, string $url, string $icon, ?int $inAppNotificationId): int
    {
        $webPush = $this->createWebPush();
        if ($webPush === null) {
            return 0;
        }

        $base = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';
        $iconUrl = $icon !== '' ? $icon : ($base !== '' ? $base . '/assets/images/logo.png' : '/assets/images/logo.png');
        $badgeUrl = $base !== '' ? $base . '/assets/icons/icon.svg' : '/assets/icons/icon.svg';

        $payload = [
            'title' => $title,
            'body'  => $body,
            'url'   => $url,
            'icon'  => $iconUrl,
            'badge' => $badgeUrl,
        ];
        if ($inAppNotificationId !== null && $inAppNotificationId > 0) {
            $payload['id'] = $inAppNotificationId;
        }
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonPayload === false) {
            return 0;
        }

        $sent    = 0;
        $expired = [];

        foreach ($subs as $sub) {
            $endpoint = $sub['endpoint'] ?? '';
            foreach (['aes128gcm', 'aesgcm'] as $encoding) {
                $subscription = $this->makeSubscription($sub, $encoding);
                if ($subscription === null) {
                    continue;
                }
                try {
                    $report = $webPush->sendOneNotification($subscription, $jsonPayload, ['TTL' => 86400, 'urgency' => 'normal']);
                } catch (\Throwable $e) {
                    continue;
                }
                $code = $report->getResponse() ? $report->getResponse()->getStatusCode() : 0;
                if (in_array($code, [404, 410], true)) {
                    $expired[] = $endpoint;
                    break;
                }
                if ($report->isSuccess() && $code >= 200 && $code < 300) {
                    $sent++;
                    break;
                }
            }
        }

        foreach ($expired as $ep) {
            $this->unsubscribe($ep);
        }

        return $sent;
    }

    /**
     * @param array{endpoint:string,p256dh:string,auth_key:string} $sub
     */
    private function makeSubscription(array $sub, string $encoding): ?Subscription
    {
        $endpoint = trim($sub['endpoint'] ?? '');
        $p256dh   = trim($sub['p256dh'] ?? '');
        $auth     = trim($sub['auth_key'] ?? '');
        if ($endpoint === '' || $p256dh === '' || $auth === '') {
            return null;
        }
        try {
            return Subscription::create([
                'endpoint'        => $endpoint,
                'keys'            => ['p256dh' => $p256dh, 'auth' => $auth],
                'contentEncoding' => $encoding,
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function createWebPush(): ?WebPush
    {
        if (!class_exists(WebPush::class)) {
            return null;
        }
        $pub = defined('VAPID_PUBLIC_KEY') ? (string) VAPID_PUBLIC_KEY : '';
        $prv = defined('VAPID_PRIVATE_KEY') ? (string) VAPID_PRIVATE_KEY : '';
        $sub = defined('VAPID_SUBJECT') ? (string) VAPID_SUBJECT : 'mailto:admin@example.com';
        if ($pub === '' || $prv === '') {
            return null;
        }
        try {
            return new WebPush([
                'VAPID' => [
                    'subject'    => $sub,
                    'publicKey'  => $pub,
                    'privateKey' => $prv,
                ],
            ], ['TTL' => 86400], 15);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getSubscriptionsByUser(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT endpoint, p256dh, auth_key FROM push_subscriptions WHERE user_id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getAllSubscriptions(): array
    {
        try {
            $stmt = $this->db->prepare('SELECT endpoint, p256dh, auth_key FROM push_subscriptions');
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getSubscriptionsByRole(string $role): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT ps.endpoint, ps.p256dh, ps.auth_key
                FROM push_subscriptions ps
                JOIN utilisateurs u ON u.id = ps.user_id
                WHERE u.role = ?
            ");
            $stmt->execute([$role]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
