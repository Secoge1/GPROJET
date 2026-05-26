<?php
/**
 * GLOBALO - Liste et fiche publics des experts (clients ou visiteurs)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Models\ProfilExpertModel;
use App\Models\CompetenceModel;
use App\Models\AvisModel;
use App\Services\SeoService;

class ExpertsController extends Controller
{
    private ProfilExpertModel $profilModel;
    private CompetenceModel $competenceModel;
    private AvisModel $avisModel;

    public function __construct(Router $router)
    {
        parent::__construct($router);
        $this->profilModel = new ProfilExpertModel();
        $this->competenceModel = new CompetenceModel();
        $this->avisModel = new AvisModel();
    }

    public function index(): void
    {
        $params = $this->router->getParams();
        if (!empty($params[0]) && is_numeric($params[0])) {
            $this->show((int) $params[0]);
            return;
        }
        $competenceId = isset($_GET['competence']) ? (int) $_GET['competence'] : null;
        $search = isset($_GET['q']) ? trim((string) $_GET['q']) : null;
        if ($competenceId <= 0) {
            $competenceId = null;
        }
        $experts = $this->profilModel->getListDisponibles($competenceId, $search ?: null);
        $competences = $this->competenceModel->getActives();
        $this->render('index', [
            'pageTitle'         => 'Experts disponibles - GLOBALO',
            'navActive'         => 'experts',
            'seo'               => SeoService::forPage('experts_list'),
            'user'              => Auth::check() ? ['id' => Auth::id(), 'role' => Auth::role()] : null,
            'isApp'             => $this->router->isApp(),
            'experts'           => $experts,
            'competences'       => $competences,
            'filtre_competence' => $competenceId,
            'recherche'         => $search,
        ]);
    }

    public function show($id): void
    {
        $id = (int) $id;
        $expert = $this->profilModel->getByIdPublic($id);
        if (!$expert) {
            $this->redirect(rtrim(BASE_URL, '/') . '/experts');
            return;
        }
        $competences = $this->profilModel->getCompetencesPublic((int) $expert['id']);
        $avis = $this->avisModel->getByExpert((int) $expert['id']);
        $demandeId = isset($_GET['demande_id']) ? (int) $_GET['demande_id'] : 0;
        $baseUrl = rtrim(BASE_URL ?? '', '/');
        $profileUrl = $baseUrl . '/experts/show/' . (int)$expert['id'];
        $description = !empty($expert['description']) ? mb_substr(strip_tags($expert['description']), 0, 160) : ($expert['titre'] . '. Réservez une session sur Globalo.');
        $userData = Auth::check() ? ['id' => Auth::id(), 'role' => Auth::role()] : null;
        $this->render('show', [
            'pageTitle' => \App\Core\Security::escape($expert['titre']) . ' - GLOBALO',
            'navActive' => 'experts',
            'seo' => SeoService::forPage('expert_profile', [
                'expert_id' => $expert['id'],
                'expert_title' => $expert['titre'],
                'expert_prenom' => $expert['prenom'] ?? '',
                'expert_nom' => $expert['nom'] ?? '',
                'description' => $description,
                'canonical' => $profileUrl,
                'image' => !empty($expert['avatar']) ? $baseUrl . '/uploads/' . ltrim($expert['avatar'], '/') : null,
                'tarif_horaire' => $expert['tarif_horaire'] ?? 0,
            ]),
            'user' => $userData,
            'isClient' => $userData !== null && (string) Auth::role() === 'client',
            'expert' => $expert,
            'competences' => $competences,
            'avis' => $avis,
            'demandeId' => $demandeId,
            'profileUrl' => $profileUrl,
        ]);
    }

    /** Page publique expert par slug SEO (/expert/amadou-flutter-developer). */
    public function profileBySlug(string $slug): void
    {
        $expert = $this->profilModel->getBySlug($slug);

        // Fallback pour les URLs générées sans slug (ex. expert-10 → profils_experts.id = 10)
        if (!$expert && preg_match('/^expert-(\d+)$/i', $slug, $m)) {
            $expert = $this->profilModel->getByIdForSlugFallback((int) $m[1]);
        }

        if (!$expert) {
            header('HTTP/1.0 404 Not Found');
            $this->render('errors/404', ['pageTitle' => 'Expert introuvable']);
            return;
        }

        // Si le profil a un slug propre mais qu'on est arrivé via l'URL de secours, rediriger
        if (!empty($expert['slug']) && $expert['slug'] !== $slug) {
            $baseUrl = rtrim(BASE_URL ?? '', '/');
            header('Location: ' . $baseUrl . '/expert/' . $expert['slug'], true, 301);
            exit;
        }
        $competences = $this->profilModel->getCompetencesPublic((int) $expert['id']);
        $avis = $this->avisModel->getByExpert((int) $expert['id']);
        $demandeId = isset($_GET['demande_id']) ? (int) $_GET['demande_id'] : 0;
        $baseUrl = rtrim(BASE_URL ?? '', '/');
        $profileUrl = $baseUrl . '/expert/' . $slug;
        $description = !empty($expert['description']) ? mb_substr(strip_tags($expert['description']), 0, 160) : ($expert['titre'] . '. Réservez une session sur Globalo.');
        \App\Models\GrowthPageViewModel::recordView('expert', (int) $expert['id']);
        $userData = Auth::check() ? ['id' => Auth::id(), 'role' => Auth::role()] : null;
        $this->render('show', [
            'pageTitle' => \App\Core\Security::escape($expert['titre']) . ' — Expert — GLOBALO',
            'navActive' => 'experts',
            'seo' => SeoService::forPage('expert_profile', [
                'expert_id' => $expert['id'],
                'expert_title' => $expert['titre'],
                'expert_prenom' => $expert['prenom'] ?? '',
                'expert_nom' => $expert['nom'] ?? '',
                'description' => $description,
                'canonical' => $profileUrl,
                'image' => !empty($expert['avatar']) ? $baseUrl . '/uploads/' . ltrim($expert['avatar'], '/') : null,
                'tarif_horaire' => $expert['tarif_horaire'] ?? 0,
            ]),
            'user' => $userData,
            'isClient' => $userData !== null && (string) Auth::role() === 'client',
            'expert' => $expert,
            'competences' => $competences,
            'avis' => $avis,
            'demandeId' => $demandeId,
            'profileUrl' => $profileUrl,
        ]);
    }
}
