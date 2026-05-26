<?php
/**
 * GLOBALO — Api\PushController
 * Endpoints Web Push : abonnement / désabonnement / récupération notifications.
 * Routes :
 *   POST /api/push/subscribe
 *   POST /api/push/unsubscribe
 *   GET  /api/push/notifications
 */

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\PushNotificationService;

class PushController extends Controller
{
    private PushNotificationService $pushSvc;

    public function __construct($router)
    {
        parent::__construct($router);
        $this->pushSvc = new PushNotificationService();
    }

    /**
     * POST /api/push/subscribe
     * Corps JSON : { endpoint, keys: { p256dh, auth } }
     */
    public function subscribe(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Non authentifié']);
            return;
        }

        $raw  = file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true);

        $endpoint = trim($data['endpoint'] ?? '');
        $p256dh   = trim($data['keys']['p256dh'] ?? '');
        $auth     = trim($data['keys']['auth']    ?? '');

        if (!$endpoint || !$p256dh || !$auth) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Données manquantes']);
            return;
        }

        $userId = (int) Auth::id();
        $ua     = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $ok = $this->pushSvc->subscribe($userId, $endpoint, $p256dh, $auth, $ua);

        echo json_encode(['ok' => $ok]);
    }

    /**
     * POST /api/push/unsubscribe
     * Corps JSON : { endpoint }
     */
    public function unsubscribe(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Non authentifié']);
            return;
        }

        $raw      = file_get_contents('php://input');
        $data     = json_decode($raw ?: '{}', true);
        $endpoint = trim($data['endpoint'] ?? '');

        if (!$endpoint) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Endpoint manquant']);
            return;
        }

        $ok = $this->pushSvc->unsubscribe($endpoint);
        echo json_encode(['ok' => $ok]);
    }

    /**
     * GET /api/push/notifications
     * Appelé par le Service Worker pour récupérer les notifications en attente.
     * Retourne les N dernières notifications non lues de l'utilisateur.
     */
    public function notifications(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'notifications' => []]);
            return;
        }

        $userId = (int) Auth::id();

        try {
            $db   = \App\Core\Database::getInstance();
            $stmt = $db->prepare("
                SELECT id, type, titre, contenu, lien, created_at
                FROM notifications
                WHERE utilisateur_id = :uid AND lu = 0
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $stmt->execute([':uid' => $userId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            $base = rtrim(defined('BASE_URL') ? (string) BASE_URL : '', '/');
            $notifications = array_map(static function ($row) use ($base) {
                $titre = (string) ($row['titre'] ?? 'GLOBALO');
                $body  = (string) ($row['contenu'] ?? '');
                if ($body === '') {
                    $body = $titre;
                }
                $body = mb_substr(trim(preg_replace('/\s+/u', ' ', strip_tags($body))), 0, 500);
                $lien  = trim((string) ($row['lien'] ?? ''));
                $url   = $lien !== '' && preg_match('#^https?://#i', $lien)
                    ? $lien
                    : ($base !== '' ? $base . '/' . ltrim($lien, '/') : '/' . ltrim($lien, '/'));

                return [
                    'id'    => (int) $row['id'],
                    'title' => mb_substr($titre, 0, 200),
                    'body'  => $body,
                    'url'   => $url !== '' ? $url : ($base !== '' ? $base . '/' : '/'),
                    'icon'  => $base !== '' ? $base . '/assets/images/logo.png' : '/assets/images/logo.png',
                    'badge' => $base !== '' ? $base . '/assets/icons/icon.svg' : '/assets/icons/icon.svg',
                ];
            }, $rows);

            echo json_encode(['ok' => true, 'notifications' => $notifications]);
        } catch (\Throwable $e) {
            echo json_encode(['ok' => true, 'notifications' => []]);
        }
    }
}
