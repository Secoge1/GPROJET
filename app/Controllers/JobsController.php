<?php
/**
 * GLOBALO - Pages publiques des missions (jobs) par slug SEO
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Models\DemandeModel;
use App\Models\ProfilExpertModel;
use App\Services\SeoService;

class JobsController extends Controller
{
    private DemandeModel $demandeModel;
    private ProfilExpertModel $profilModel;

    public function __construct(Router $router)
    {
        parent::__construct($router);
        $this->demandeModel = new DemandeModel();
        $this->profilModel = new ProfilExpertModel();
    }

    /** Page publique job par slug (/jobs/flutter-bug-fix). */
    public function show(string $slug): void
    {
        $job = $this->demandeModel->getBySlug($slug);
        if (!$job) {
            header('HTTP/1.0 404 Not Found');
            $this->render('errors/404', ['pageTitle' => 'Mission introuvable']);
            return;
        }
        \App\Models\GrowthPageViewModel::recordView('job', (int) $job['id']);
        $competenceId = !empty($job['competence_id']) ? (int) $job['competence_id'] : null;
        $relatedExperts = $competenceId ? $this->profilModel->getListDisponibles($competenceId, null) : [];
        $baseUrl = rtrim(BASE_URL ?? '', '/');
        $pageUrl = $baseUrl . '/jobs/' . $slug;
        $description = !empty($job['description']) ? mb_substr(strip_tags($job['description']), 0, 160) : ($job['titre'] ?? 'Mission sur Globalo.');
        $this->render('show', [
            'pageTitle' => \App\Core\Security::escape($job['titre'] ?? 'Mission') . ' — GLOBALO',
            'seo' => SeoService::forPage('job', [
                'title' => $job['titre'] ?? 'Mission',
                'description' => $description,
                'canonical' => $pageUrl,
                'slug' => $slug,
                'created_at' => $job['created_at'] ?? null,
            ]),
            'user' => Auth::check() ? ['id' => Auth::id(), 'role' => Auth::role()] : null,
            'job' => $job,
            'relatedExperts' => array_slice($relatedExperts, 0, 6),
            'pageUrl' => $pageUrl,
        ]);
    }
}
