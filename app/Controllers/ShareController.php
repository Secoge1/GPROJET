<?php
/**
 * GLOBALO - Pages partageables (cartes de réussite, etc.)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Models\SessionAchievementModel;
use App\Services\SeoService;

class ShareController extends Controller
{
    /** Page partageable : "Amadou a terminé une session Flutter sur Globalo ⭐⭐⭐⭐⭐" */
    public function achievement(string $id): void
    {
        $achievementId = (int) $id;
        if ($achievementId <= 0) {
            header('HTTP/1.0 404 Not Found');
            $this->render('errors/404', ['pageTitle' => 'Carte introuvable']);
            return;
        }
        $model = new SessionAchievementModel();
        $row = $model->getByIdWithExpert($achievementId);
        if (!$row) {
            header('HTTP/1.0 404 Not Found');
            $this->render('errors/404', ['pageTitle' => 'Carte introuvable']);
            return;
        }
        $baseUrl = rtrim(BASE_URL ?? '', '/');
        $pageUrl = $baseUrl . '/share/achievement/' . $achievementId;
        $prenom = $row['expert_prenom'] ?? 'Expert';
        $titre = $row['titre_session'] ?? $row['expert_titre'] ?? 'Session';
        $note = isset($row['note']) ? (int) $row['note'] : null;
        $stars = $note !== null ? str_repeat('⭐', min(5, max(0, $note))) : '';
        $shareTitle = $prenom . ' a terminé une session ' . $titre . ' sur GLOBALO ' . $stars;
        $description = $shareTitle;
        $this->render('achievement', [
            'pageTitle' => $shareTitle,
            'seo' => SeoService::forPage('default', [
                'title' => $shareTitle,
                'description' => $description,
                'canonical' => $pageUrl,
                'og_title' => $shareTitle,
                'og_description' => $description,
                'og_url' => $pageUrl,
            ]),
            'achievement' => $row,
            'shareTitle' => $shareTitle,
            'pageUrl' => $pageUrl,
            'stars' => $stars,
        ]);
    }
}
