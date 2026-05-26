<?php
/**
 * GLOBALO - Accueil et pages publiques
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Services\SeoService;
use App\Services\SubscriptionService;
use App\Models\ParametreModel;

class HomeController extends Controller
{
    public function __construct(Router $router)
    {
        parent::__construct($router);
    }

    public function index(): void
    {
        // Redirection auto vers le dashboard — sauf si l'utilisateur clique délibérément
        // sur "Voir le site" (paramètre ?vue=site) depuis son espace personnel.
        $vueParam = trim((string) ($_GET['vue'] ?? ''));
        if (Auth::check() && $vueParam !== 'site') {
            $role = Auth::role();
            if (in_array($role, ['client', 'expert', 'professeur', 'etudiant', 'admin'], true)) {
                $this->redirect(Auth::dashboardUrl($role));
                return;
            }
        }

        $userData = null;
        if (Auth::check()) {
            $userRow  = (new \App\Models\UtilisateurModel())->find((int) Auth::id());
            $userData = [
                'id'     => Auth::id(),
                'role'   => Auth::role(),
                'prenom' => $userRow['prenom'] ?? '',
                'nom'    => $userRow['nom']    ?? '',
            ];
        }
        $experts     = [];
        $competences = [];
        if (Auth::check()) {
            $experts     = (new \App\Models\ProfilExpertModel())->getListDisponibles(null, null, 3);
            $competences = array_slice((new \App\Models\CompetenceModel())->getActives(), 0, 8);
        }
        $sub = new SubscriptionService();
        $demandesRecentes = (new \App\Models\DemandeModel())->getRecentOuvertesPourPublic(6);
        $placeholdersSearch = json_encode([
            'Que cherchez-vous ? Ex. comptabilité, Excel, rédaction',
            'Une matière ou un domaine : statistiques, droit, maths…',
            'Ex. développement web, design, traduction anglais',
        ], JSON_UNESCAPED_UNICODE);
        $this->render('index', [
            'pageTitle'   => 'GLOBALO - Assistance professionnelle à la demande',
            'seo'         => SeoService::forPage('home'),
            'user'        => $userData,
            'experts'     => $experts,
            'competences' => $competences,
            'navActive'   => 'accueil',
            'demandes_recentes' => $demandesRecentes,
            'prix_client_xof'    => (int) round($sub->getPrixClientXof()),
            'prix_expert_xof'    => (int) round($sub->getPrixExpertXof()),
            'prix_etudiant_xof'  => (int) round($sub->getPrixEtudiantXof()),
            'prix_professeur_xof'=> (int) round($sub->getPrixProfesseurXof()),
            'show_header_smart_search'       => true,
            'home_smart_search_placeholders' => htmlspecialchars($placeholdersSearch, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        ]);
    }

    public function apropos(): void
    {
        $sub = new SubscriptionService();
        $this->render('apropos', [
            'pageTitle'           => 'À propos - GLOBALO',
            'navActive'           => 'apropos',
            'seo'                 => SeoService::forPage('about'),
            'user'                => Auth::check() ? ['id' => Auth::id(), 'role' => Auth::role()] : null,
            'prix_client_xof'     => (int) round($sub->getPrixClientXof()),
            'prix_expert_xof'     => (int) round($sub->getPrixExpertXof()),
            'prix_etudiant_xof'   => (int) round($sub->getPrixEtudiantXof()),
            'prix_professeur_xof' => (int) round($sub->getPrixProfesseurXof()),
        ]);
    }

    public function contact(): void
    {
        $paramModel = new ParametreModel();
        $contactEmail = $paramModel->get('plateforme_email', 'contact@secogesarl.com') ?: 'contact@secogesarl.com';
        $this->render('contact', [
            'pageTitle'    => 'Contact - GLOBALO',
            'navActive'    => 'contact',
            'seo'          => SeoService::forPage('contact'),
            'user'         => Auth::check() ? ['id' => Auth::id(), 'role' => Auth::role()] : null,
            'contactEmail' => $contactEmail,
        ]);
    }

    /** Politique de confidentialité */
    public function confidentialite(): void
    {
        $this->render('confidentialite', [
            'pageTitle' => 'Politique de confidentialité - GLOBALO',
            'navActive' => '',
            'seo'       => SeoService::forPage('default', ['title' => 'Politique de confidentialité - GLOBALO']),
            'user'      => Auth::check() ? ['id' => Auth::id(), 'role' => Auth::role()] : null,
        ]);
    }

    /** Politique de gestion des données personnelles */
    public function donnees(): void
    {
        $this->render('donnees', [
            'pageTitle' => 'Politique de gestion des données - GLOBALO',
            'navActive' => '',
            'seo'       => SeoService::forPage('default', ['title' => 'Politique de gestion des données - GLOBALO']),
            'user'      => Auth::check() ? ['id' => Auth::id(), 'role' => Auth::role()] : null,
        ]);
    }

    /**
     * Pages géolocalisées : /experts/{slug-pays}
     * Ex : /experts/mali  /experts/senegal  /experts/cote-divoire
     */
    public function pays(): void
    {
        $params  = $this->router->getParams();
        $slug    = strtolower(trim($params[0] ?? ''));
        $countries = \App\Services\SeoService::$COUNTRIES;

        if (!isset($countries[$slug])) {
            $this->redirect('/experts');
            return;
        }

        $country  = $countries[$slug];
        $experts  = (new \App\Models\ProfilExpertModel())->getListDisponibles(null, null, 12);
        $userRow  = Auth::check() ? (new \App\Models\UtilisateurModel())->find((int) Auth::id()) : null;
        $user     = $userRow !== null
            ? ['id' => Auth::id(), 'role' => Auth::role(), 'prenom' => $userRow['prenom'] ?? '']
            : null;

        $this->render('pays', [
            'pageTitle'    => 'Experts & Professeurs en ' . $country['name'] . ' — GLOBALO',
            'navActive'    => 'experts',
            'seo'          => \App\Services\SeoService::forPage('country_page', ['country_slug' => $slug]),
            'user'         => $user,
            'country'      => $country,
            'country_slug' => $slug,
            'experts'      => $experts,
        ]);
    }

    /** Page publique « Demandes » : présenter les demandes récentes + CTA pour créer une demande (connexion/inscription) */
    public function demandesPublic(): void
    {
        $baseUrl = rtrim(BASE_URL ?? '', '/');
        $userRow = Auth::check() ? (new \App\Models\UtilisateurModel())->find((int) Auth::id()) : null;
        $user = $userRow !== null
            ? ['id' => Auth::id(), 'role' => Auth::role(), 'prenom' => $userRow['prenom'] ?? '']
            : null;
        $redirectUrl = $baseUrl . '/client/demandes/nouvelle';
        $demandes = (new \App\Models\DemandeModel())->getRecentOuvertesPourPublic(50);
        $this->render('demandes_public', [
            'pageTitle'   => 'Demandes d\'assistance - GLOBALO',
            'navActive'   => 'demandes_public',
            'seo'         => SeoService::forPage('demandes_public'),
            'user'        => $user,
            'redirectUrl' => $redirectUrl,
            'demandes'    => $demandes,
        ]);
    }
}
