<?php

/**
 * GLOBALO — API suggestions de recherche (accueil type autocomplete).
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Services\SmartSearchService;

final class SearchController extends Controller
{
    /** GET /api/search/suggest?q=…&app=1 */
    public function suggest(): void
    {
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $appParam = strtolower((string) ($_GET['app'] ?? ''));
        $forApp   = ($appParam === '1' || $appParam === 'true');

        try {
            $items = (new SmartSearchService($forApp))->suggest($q);
        } catch (\Throwable $e) {
            $items = [];
        }

        $this->json([
            'ok'    => true,
            'items' => $items,
        ]);
    }
}
