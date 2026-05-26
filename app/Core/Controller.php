<?php
/**
 * GLOBALO - Contrôleur de base
 */

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected Router $router;
    protected bool $isMobileView = false;

    public function __construct(Router $router)
    {
        $this->router = $router;
        $forceDesktop = isset($_COOKIE['view_mode']) && $_COOKIE['view_mode'] === 'desktop';
        // Cookie « version bureau » : utile pour naviguer sur mobile, mais pour le paiement
        // (abonnement PayTech / InTouch) la vue desktop dans layout_dashboard est illisible.
        // On ré-applique donc le layout mobile lorsque le terminal est réellement mobile.
        if ($forceDesktop && $this->detectMobile()) {
            $cPath = str_replace(['App\\', 'Admin\\', 'Api\\'], '', $router->getController());
            if (preg_match('/(Paytech|Intouch|Abonnement)/', $cPath)) {
                $forceDesktop = false;
            }
        }
        $this->isMobileView = ($router->isApp() || ($this->detectMobile() && !$forceDesktop)) && !$router->isAdmin();
    }

    protected function detectMobile(): bool
    {
        // Indication explicite (Chrome / Edge) même si l’UA est en mode « ordinateur ».
        if (!empty($_SERVER['HTTP_SEC_CH_UA_MOBILE']) && trim((string) $_SERVER['HTTP_SEC_CH_UA_MOBILE'], "? \t") === '1') {
            return true;
        }
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return (bool) preg_match('/Mobile|Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i', $ua);
    }

    protected function render(string $view, array $data = []): void
    {
        $data['navActive'] = $this->getNavActive();
        $data['lang'] = \App\Core\Lang::getLocale();
        if (!isset($data['seo'])) {
            $data['seo'] = \App\Services\SeoService::forPage('default', ['title' => $data['pageTitle'] ?? 'GLOBALO']);
        }
        $controllerName = str_replace(['App\\', 'Admin\\', 'Api\\'], '', $this->router->getController());
        if (stripos($controllerName, 'Admin') !== false && !isset($data['adminSection'])) {
            $data['adminSection'] = $this->router->getAction();
        }
        extract($data);
        $appPath = defined('APP_PATH') ? (realpath(APP_PATH) ?: rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, APP_PATH), DIRECTORY_SEPARATOR)) : (ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
        $folder = $this->isMobileView ? 'mobile' : 'desktop';
        $controller = trim(str_replace(['App\\', 'Admin\\', 'Api\\'], '', $this->router->getController()), ' \\');
        // Contrôleurs App (mobile) : mapper vers les dossiers de vues existants
        if (stripos($controller, 'Demandes') !== false) {
            $controller = 'Client';
        } elseif (stripos($controller, 'Missions') !== false) {
            $controller = 'Expert';
        } elseif (stripos($controller, 'Home') !== false) {
            $controller = 'Home';
        } elseif (stripos($controller, 'Messages') !== false) {
            $controller = 'Messages';
        } elseif (stripos($controller, 'Profil') !== false) {
            $controller = 'Client';
        }
        $viewPathNormalized = $appPath . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $view . '.php';
        if (!is_file($viewPathNormalized) && defined('ROOT_PATH')) {
            $viewPathNormalized = (realpath(ROOT_PATH) ?: ROOT_PATH) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $view . '.php';
        }
        // Fallback vers la vue desktop si la vue mobile est introuvable
        $usingDesktopFallback = false;
        if (!is_file($viewPathNormalized) && $this->isMobileView) {
            $desktopPath = $appPath . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'desktop' . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $view . '.php';
            if (!is_file($desktopPath) && defined('ROOT_PATH')) {
                $desktopPath = (realpath(ROOT_PATH) ?: ROOT_PATH) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'desktop' . DIRECTORY_SEPARATOR . $controller . DIRECTORY_SEPARATOR . $view . '.php';
            }
            if (is_file($desktopPath)) {
                $viewPathNormalized  = $desktopPath;
                $usingDesktopFallback = true;
            }
        }
        // Fallback pour les vues d'erreur (errors/404, errors/500) : chercher dans Views/errors/ sans sous-dossier contrôleur
        $isErrorViewFallback = false;
        if (!is_file($viewPathNormalized) && preg_match('#^errors/#', $view)) {
            $viewPathNormalized = $appPath . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';
            $isErrorViewFallback = is_file($viewPathNormalized);
        }
        if (!is_file($viewPathNormalized) && preg_match('#^errors/#', $view) && defined('ROOT_PATH')) {
            $viewPathNormalized = (realpath(ROOT_PATH) ?: ROOT_PATH) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';
            $isErrorViewFallback = is_file($viewPathNormalized);
        }
        if (!is_file($viewPathNormalized)) {
            throw new \RuntimeException("Vue introuvable : {$view}. Chemin : " . $viewPathNormalized);
        }
        ob_start();
        require $viewPathNormalized;
        $content = ob_get_clean();
        // Les vues dans Views/errors/ sont des pages complètes (HTML entier) : pas de layout
        if ($isErrorViewFallback) {
            echo $content;
            return;
        }
        $isMobileLayout = $this->isMobileView && !$usingDesktopFallback;
        $layout = $isMobileLayout ? 'layout_mobile' : 'layout_desktop';
        if (stripos($controller, 'Admin') !== false || $controller === 'Rh') {
            $layout = 'layout_admin';
        } elseif (!$isMobileLayout && in_array($controller, ['Client', 'Expert', 'Etudiant', 'Abonnement', 'Messages', 'Intouch'], true)) {
            $layout = 'layout_dashboard';
        } elseif (!$isMobileLayout && $controller === 'Paytech') {
            $layout = (!empty($_layout_paytech_standalone)) ? 'layout_desktop' : 'layout_dashboard';
        } elseif (!$isMobileLayout && in_array($controller, ['Experts', 'Professeurs'], true) && \App\Core\Auth::check()) {
            $layout = 'layout_dashboard';
        }
        $layoutFullNormalized = $appPath . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . $layout . '.php';
        if (!is_file($layoutFullNormalized)) {
            $layoutFullNormalized = (defined('ROOT_PATH') ? (realpath(ROOT_PATH) ?: ROOT_PATH) : dirname($appPath)) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . $layout . '.php';
        }
        $navBadgeMessages = 0;
        $navBadgeReservations = 0;
        $navBadgeDemandes = 0;
        $navBadgeDemandesPublic = 0;
        $navBadgeExercices = 0;
        // Badge demandes ouvertes (public) — requête directe via PDO singleton
        if (in_array($layout, ['layout_desktop', 'layout_mobile', 'layout_dashboard'], true)) {
            try {
                $_demPubStmt = \App\Core\Database::getInstance()->query(
                    "SELECT COUNT(*) FROM demandes_assistance WHERE statut = 'ouverte'"
                );
                $navBadgeDemandesPublic = $_demPubStmt ? (int) $_demPubStmt->fetchColumn() : 0;
                unset($_demPubStmt);
            } catch (\Throwable $e) {
                error_log('[GLOBALO] navBadgeDemandesPublic error: ' . $e->getMessage());
                $navBadgeDemandesPublic = 0;
            }
        }
        if (in_array($layout, ['layout_dashboard', 'layout_mobile'], true) && \App\Core\Auth::check()) {
            if ($layout === 'layout_mobile') {
                $nm = new \App\Models\NotificationModel();
                $uid = (int) \App\Core\Auth::id();
                $navBadgeMessages = $nm->countNonLuesByType($uid, 'message_chat');
                $navBadgeReservations = $nm->countNonLuesByTypes($uid, \App\Models\NotificationModel::typesReservationOuMission());
            }
            // Badge demandes actives — uniquement pour les clients
            if (\App\Core\Auth::role() === 'client') {
                try {
                    $navBadgeDemandes = (new \App\Models\DemandeModel())->countActiveByClient((int) \App\Core\Auth::id());
                } catch (\Throwable $e) {
                    $navBadgeDemandes = 0;
                }
            }
            // Badge exercices disponibles — uniquement pour les professeurs (sidebar)
            if (\App\Core\Auth::role() === 'professeur') {
                $cacheKeyEx = '_nav_badge_exercices_' . (int) \App\Core\Auth::id();
                $cacheTsKeyEx = $cacheKeyEx . '_ts';
                if (!isset($_SESSION[$cacheKeyEx]) || (time() - (int) ($_SESSION[$cacheTsKeyEx] ?? 0)) > 120) {
                    try {
                        $_SESSION[$cacheKeyEx]   = count((new \App\Models\ExerciceModel())->getOuvertsPourProfesseur([], 100));
                        $_SESSION[$cacheTsKeyEx] = time();
                    } catch (\Throwable $e) {
                        $_SESSION[$cacheKeyEx] = 0;
                    }
                }
                $navBadgeExercices = (int) ($_SESSION[$cacheKeyEx] ?? 0);
            }
            $um = new \App\Models\UtilisateurModel();
            $uid = (int) \App\Core\Auth::id();
            if (empty($user) || !is_array($user)) {
                $row = $um->find($uid);
                if ($row) {
                    $user = [
                        'id' => (int) $row['id'],
                        'role' => (string) ($row['role'] ?? ''),
                        'prenom' => (string) ($row['prenom'] ?? ''),
                        'nom' => (string) ($row['nom'] ?? ''),
                        'avatar' => $row['avatar'] ?? null,
                    ];
                } else {
                    $user = [
                        'id' => $uid,
                        'role' => (string) (\App\Core\Auth::role() ?? ''),
                        'prenom' => '',
                        'nom' => '',
                        'avatar' => null,
                    ];
                }
            } elseif (empty($user['role']) || empty($user['avatar'])) {
                $row = $um->find($uid);
                if ($row) {
                    if (empty($user['role'])) {
                        $user['role'] = (string) ($row['role'] ?? \App\Core\Auth::role() ?? '');
                    }
                    if (empty($user['avatar']) && !empty($row['avatar'])) {
                        $user['avatar'] = $row['avatar'];
                    }
                    if (empty($user['prenom'])) {
                        $user['prenom'] = (string) ($row['prenom'] ?? '');
                    }
                    if (empty($user['nom'])) {
                        $user['nom'] = (string) ($row['nom'] ?? '');
                    }
                }
            }
        }
        $showMobileHeaderSearch = false;
        $mobileExpertsSearchUrl = '';
        $mobileSmartSearchAppAttr = '0';
        $headerSearchQ = '';
        $headerSearchCompetence = 0;
        if ($layout === 'layout_mobile' && $this->router->isApp()) {
            $navForSearch = (string) ($data['navActive'] ?? '');
            // Barre de recherche uniquement sur la page d'accueil de l'app
            $showMobileHeaderSearch = ($navForSearch === 'accueil');
            $mobileExpertsSearchUrl = rtrim(BASE_URL, '/') . '/app/experts';
            $mobileSmartSearchAppAttr = '1';
            if (isset($_GET['q'])) {
                $headerSearchQ = \App\Core\Security::escape(trim((string) $_GET['q']));
            }
            if (!empty($_GET['competence'])) {
                $headerSearchCompetence = (int) $_GET['competence'];
            }
        }
        $dispoPrompt = null;
        if (in_array($layout, ['layout_dashboard', 'layout_mobile'], true) && \App\Core\Auth::check()) {
            $roleDispo = (string) \App\Core\Auth::role();
            if (in_array($roleDispo, ['expert', 'professeur'], true)) {
                $dispoPrompt = \App\Services\PrestataireDisponibilitePromptService::getForCurrentUser($this->router->isApp());
            }
        }
        require $layoutFullNormalized;
    }

    /**
     * Identifiant de la page courante pour le menu (layout).
     * Valeurs possibles : accueil, apropos, contact, experts, connexion, inscription, demandes, missions, messages, profil.
     */
    protected function getNavActive(): string
    {
        $controller = $this->router->getController();
        $action = $this->router->getAction();
        if (strpos($controller, 'Admin') !== false) {
            return 'admin';
        }
        if (stripos($controller, 'Home') !== false) {
            if ($action === 'index') return 'accueil';
            if ($action === 'apropos') return 'apropos';
            if ($action === 'contact') return 'contact';
            if ($action === 'demandesPublic') return 'demandes_public';
        }
        if (stripos($controller, 'Experts') !== false) {
            return 'experts';
        }
        if (stripos($controller, 'Professeurs') !== false) {
            return 'professeurs';
        }
        if (stripos($controller, 'Demandes') !== false || stripos($controller, 'Client') !== false) {
            if (stripos($controller, 'Demandes') !== false && $action === 'index') return 'demandes';
            if ($action === 'index') return $this->isMobileView ? 'accueil' : 'client';
            if ($action === 'compte') return 'compte';
            if ($action === 'urgence' || $action === 'urgenceAttente') return 'urgence';
            if (in_array($action, ['reserver', 'nouvelleDemande'], true)) {
                return 'demandes';
            }
            if ($action === 'demandes') {
                return 'demandes';
            }
            if ($action === 'reservations') return 'reservations';
            if ($action === 'portefeuille') return 'portefeuille';
            return 'client';
        }
        if (stripos($controller, 'Missions') !== false || stripos($controller, 'Expert') !== false) {
            if ($action === 'index') return $this->isMobileView ? 'accueil' : 'expert';
            if ($action === 'compte') return 'compte';
            if ($action === 'demandes') return 'demandes';
            if ($action === 'urgences') return 'urgences';
            if (in_array($action, ['missions', 'prestations', 'index'], true)) return 'missions';
            if ($action === 'reservations') return 'reservations';
            if ($action === 'revenus') return 'revenus';
            if ($action === 'retrait') return 'retrait';
            if ($action === 'profil') return 'profil';
            return 'expert';
        }
        if ((stripos($controller, 'Messages') !== false || stripos($controller, 'Profil') !== false) && strpos($controller, 'Api') === false) {
            return stripos($controller, 'Profil') !== false ? 'profil' : 'messages';
        }
        if (stripos($controller, 'Auth') !== false) {
            if ($action === 'connexion') return 'connexion';
            if ($action === 'inscription') return 'inscription';
            return 'auth';
        }
        if (stripos($controller, 'Abonnement') !== false) {
            return 'abonnement';
        }
        if (stripos($controller, 'Intouch') !== false) {
            return 'abonnement';
        }
        if (stripos($controller, 'Paytech') !== false) {
            if (in_array($action, ['depot', 'initierDepot', 'historique', 'succes', 'echec'], true)) {
                return 'portefeuille';
            }

            return 'abonnement';
        }
        if (stripos($controller, 'Etudiant') !== false) {
            if ($action === 'index') {
                return \App\Core\Auth::role() === 'professeur' ? 'professeur' : 'etudiant';
            }
            if ($action === 'exercices') return 'exercices';
            if ($action === 'exercicesDisponibles') return 'exercices';
            if ($action === 'nouvelExercice' || $action === 'detailExercice') return 'exercices';
            if ($action === 'matieres') return 'matieres';
            if ($action === 'profil') return 'profil';
            if ($action === 'compte') return 'compte';
            if ($action === 'portefeuille') return 'portefeuille';
            if ($action === 'retrait' || $action === 'retraitChoix') return 'retrait';
            if ($action === 'reserverProfesseur') return 'etudiant';
            return 'etudiant';
        }
        return '';
    }

    protected function getViewPath(string $view): string
    {
        $folder = $this->isMobileView ? 'mobile' : 'desktop';
        $controller = trim(str_replace(['App\\', 'Admin\\', 'Api\\'], '', $this->router->getController()), ' \\');
        if (stripos($controller, 'Demandes') !== false) {
            $controller = 'Client';
        } elseif (stripos($controller, 'Missions') !== false) {
            $controller = 'Expert';
        } elseif (stripos($controller, 'Home') !== false) {
            $controller = 'Home';
        } elseif (stripos($controller, 'Messages') !== false) {
            $controller = 'Messages';
        } elseif (stripos($controller, 'Profil') !== false) {
            $controller = 'Client';
        }
        $base = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, APP_PATH), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . $folder . DIRECTORY_SEPARATOR . $controller;
        return $base . DIRECTORY_SEPARATOR . $view . '.php';
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $url, int $code = 302): void
    {
        if (strpos($url, 'http') !== 0) {
            $url = rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
        }
        header('Location: ' . $url, true, $code);
        exit;
    }
}
