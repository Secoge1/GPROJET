<?php
/**
 * GLOBALO - Liste et fiche publics des professeurs (étudiants ou visiteurs)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Models\ProfilProfesseurModel;
use App\Models\MatiereModel;
use App\Services\SeoService;

class ProfesseursController extends Controller
{
    private ProfilProfesseurModel $profilModel;
    private MatiereModel $matiereModel;

    public function __construct(Router $router)
    {
        parent::__construct($router);
        $this->profilModel = new ProfilProfesseurModel();
        $this->matiereModel = new MatiereModel();
    }

    /** Base URL liste professeurs (version app /app/professeurs ou site /professeurs). */
    private function publicProfesseursBase(): string
    {
        $base = rtrim(BASE_URL ?? '', '/');
        return $this->router->isApp() ? $base . '/app/professeurs' : $base . '/professeurs';
    }

    public function index(): void
    {
        $params = $this->router->getParams();
        if (!empty($params[0]) && $params[0] !== 'show' && is_numeric($params[0])) {
            $this->show((int) $params[0]);
            return;
        }
        $matiereId = isset($_GET['matiere']) ? (int) $_GET['matiere'] : null;
        $search = isset($_GET['q']) ? trim((string) $_GET['q']) : null;
        if ($matiereId <= 0) {
            $matiereId = null;
        }
        $professeurs = $this->profilModel->getListDisponibles($matiereId, $search ?: null);
        $matieres = $this->matiereModel->getActives();
        $this->render('index', [
            'pageTitle'              => 'Professeurs disponibles - GLOBALO',
            'navActive'              => 'professeurs',
            'seo'                    => SeoService::forPage('professeurs_list'),
            'user'                   => Auth::check() ? ['id' => Auth::id(), 'role' => Auth::role()] : null,
            'professeurs'            => $professeurs,
            'matieres'               => $matieres,
            'filtre_matiere'         => $matiereId,
            'recherche'              => $search,
            'publicProfesseursBase'  => $this->publicProfesseursBase(),
        ]);
    }

    public function show($id): void
    {
        $id = (int) $id;
        $professeur = $this->profilModel->getByIdPublic($id);
        if (!$professeur || empty($professeur['valide_par_admin'])) {
            $this->redirect($this->publicProfesseursBase());
            return;
        }
        try {
            $matieres = $this->profilModel->getMatieresForProfil((int) $professeur['id']);
        } catch (\Throwable $e) {
            $matieres = [];
        }
        $baseUrl = rtrim(BASE_URL ?? '', '/');
        $profileUrl = $baseUrl . '/professeurs/show/' . (int) $professeur['id'];
        $description = !empty($professeur['description'])
            ? mb_substr(strip_tags($professeur['description']), 0, 160)
            : ($professeur['titre'] . '. Réservez une session avec ce professeur sur Globalo.');
        $userData = Auth::check() ? ['id' => Auth::id(), 'role' => Auth::role()] : null;
        $this->render('show', [
            'pageTitle'   => \App\Core\Security::escape($professeur['titre']) . ' - GLOBALO',
            'navActive'   => 'professeurs',
            'seo'         => SeoService::forPage('professeur_profile', [
                'expert_title'   => $professeur['titre'],
                'expert_prenom'  => $professeur['prenom'] ?? '',
                'expert_nom'     => $professeur['nom'] ?? '',
                'description'    => $description,
                'canonical'      => $profileUrl,
                'image'          => !empty($professeur['avatar']) ? $baseUrl . '/uploads/' . ltrim($professeur['avatar'], '/') : null,
                'tarif_horaire'  => $professeur['tarif_horaire'] ?? 0,
            ]),
            'user'        => $userData,
            'isEtudiant'  => $userData !== null && (string) Auth::role() === 'etudiant',
            'professeur'  => $professeur,
            'matieres'               => $matieres,
            'profileUrl'             => $profileUrl,
            'publicProfesseursBase'  => $this->publicProfesseursBase(),
        ]);
    }
}
