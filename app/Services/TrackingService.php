<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Models\UserTrackingModel;

/** Service de tracking des actions utilisateurs (pages vues, etc.). */
class TrackingService
{
    /**
     * Enregistre une vue de page ou une action.
     * À appeler après chaque requête (depuis le front controller ou les contrôleurs).
     */
    public static function logPageView(?string $page = null): void
    {
        try {
            $userId = Auth::check() ? Auth::id() : null;
            $page = $page ?? ($_SERVER['REQUEST_URI'] ?? '');
            $path = (string) parse_url((string) $page, PHP_URL_PATH);

            // Eviter de polluer le tracking avec les pages admin/auth/api.
            if ($path !== '' && (
                strpos($path, '/admin') === 0 ||
                strpos($path, '/api') === 0 ||
                strpos($path, '/auth') === 0
            )) {
                return;
            }
            (new UserTrackingModel())->log($userId, 'page_view', $page);
        } catch (\Throwable $e) {
            // Ne pas faire échouer la requête si le tracking échoue
        }
    }
}
