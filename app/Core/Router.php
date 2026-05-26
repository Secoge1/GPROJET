<?php
/**
 * GLOBALO - Routeur simple (controller/action)
 */

declare(strict_types=1);

namespace App\Core;

class Router
{
    private string $controller = 'Home';
    private string $action = 'index';
    private array $params = [];
    private string $prefix = '';

    public function __construct()
    {
        $uri = $this->getUri();
        $segments = array_filter(explode('/', trim($uri, '/')));
        $this->parseSegments($segments);
    }

    private function getUri(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $uri = '';
        if (isset($_GET['url'])) {
            $uri = '/' . trim((string) $_GET['url'], '/');
        } else {
            $request = $_SERVER['REQUEST_URI'] ?? '/';
            $base = str_replace('\\', '/', dirname($script));
            if ($base !== '/' && strpos($request, $base) === 0) {
                $request = substr($request, strlen($base));
            }
            $request = parse_url($request, PHP_URL_PATH);
            $uri = $request ?: '/';
        }
        // En production sous /public/, l'URL reçue peut contenir "public/..." : normaliser pour le routage
        if (preg_match('#^/public(/.*)?$#', $uri)) {
            $uri = substr($uri, 7) ?: '/'; // enlever "/public"
        }
        // Si l'URI commence par le chemin "parent" de l'app (ex: /globalo sans /public),
        // le retirer pour éviter d'interpréter "globalo" comme contrôleur (vue desktop/Globalo/introuvable)
        if ($uri !== '/' && $uri !== '' && defined('BASE_URL')) {
            $basePath = parse_url(BASE_URL, PHP_URL_PATH);
            if ($basePath === false) {
                $basePath = '';
            }
            $basePath = $basePath ? rtrim($basePath, '/') : '';
            $parentPath = ($basePath !== '' && preg_match('#/public$#', $basePath)) ? preg_replace('#/public$#', '', $basePath) : '';
            if ($parentPath !== '' && strpos($uri, $parentPath) === 0) {
                $after = substr($uri, strlen($parentPath));
                $uri = ($after === '' || $after === '/') ? '/' : $after;
            }
        }
        return $uri;
    }

    private function parseSegments(array $segments): void
    {
        // SEO : traiter sitemap.xml et robots.txt EN PREMIER, avant tout filtrage de nom de fichier
        if (!empty($segments[0])) {
            $firstLower = strtolower((string) $segments[0]);
            if ($firstLower === 'sitemap.xml') {
                $this->controller = 'Seo';
                $this->action     = 'sitemap';
                $this->params     = array_slice($segments, 1);
                return;
            }
            if ($firstLower === 'robots.txt') {
                $this->controller = 'Seo';
                $this->action     = 'robots';
                $this->params     = array_slice($segments, 1);
                return;
            }
        }

        // Ne pas interpréter un nom de fichier (ex. GenerateAdminHash.php) comme contrôleur : l'ignorer et traiter comme accueil
        if (!empty($segments[0]) && strpos((string) $segments[0], '.') !== false) {
            array_shift($segments);
        }
        // API REST
        if (!empty($segments[0]) && strtolower($segments[0]) === 'api') {
            array_shift($segments);
            $this->prefix = 'Api\\';
            // Route API RH : /api/rh/chat, /api/rh/save-recommandation
            if (!empty($segments[0]) && strtolower((string)$segments[0]) === 'rh') {
                array_shift($segments);
                $this->prefix     = 'Api\\';
                $this->controller = 'RhAi';
                $this->action     = !empty($segments[0]) ? $this->toCamelCase((string)$segments[0]) : 'chat';
                $this->params     = array_slice($segments, 1);
                return;
            }
            // Route API WhatsApp : /api/whatsapp/webhook
            if (!empty($segments[0]) && strtolower((string)$segments[0]) === 'whatsapp') {
                array_shift($segments);
                $this->prefix     = 'Api\\';
                $this->controller = 'WhatsApp';
                $action           = !empty($segments[0]) ? strtolower((string)$segments[0]) : 'webhook';
                // GET → verify, POST → receive
                if ($action === 'webhook') {
                    $this->action = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? 'receive' : 'verify';
                } else {
                    $this->action = $this->toCamelCase($action);
                }
                $this->params = array_slice($segments, 1);
                return;
            }
        }
        // Espace RH avec IA : /rh, /rh/inscriptions, /rh/profils, /rh/marketing, /rh/manager, /rh/whatsapp
        if (!empty($segments[0]) && strtolower((string)$segments[0]) === 'rh') {
            array_shift($segments);
            $this->controller = 'Rh';
            $this->action     = !empty($segments[0]) ? $this->toCamelCase((string)$segments[0]) : 'index';
            if (!empty($segments[0])) {
                array_shift($segments);
            }
            $this->params = array_values($segments);
            return;
        }
        // Admin : /admin ou /admin/action ou /admin/action/param
        if (!empty($segments[0]) && strtolower($segments[0]) === 'admin') {
            array_shift($segments);
            $this->controller = 'Admin';
            $this->action = !empty($segments[0]) ? $this->toCamelCase((string) $segments[0]) : 'index';
            if (!empty($segments[0])) {
                array_shift($segments);
            }
            $this->params = array_values($segments);
            return;
        }
        // Mobile (version app) : /app, /app/demandes, /app/missions, etc.
        if (!empty($segments[0]) && strtolower($segments[0]) === 'app') {
            array_shift($segments);
            $this->prefix = 'App\\'; // force isMobileView = true

            // Mapping explicite des routes /app/* vers les bons contrôleurs
            $appSegment = !empty($segments[0]) ? strtolower((string) $segments[0]) : '';
            $appRouteMap = [
                'professeur'            => ['Etudiant', 'index'],
                'exercices-disponibles' => ['Etudiant', 'exercicesDisponibles'],
                'proposer-exercice'     => ['Etudiant', 'proposerExercice'],
                'prendre-exercice'      => ['Etudiant', 'prendreExercice'],
                'corriger'              => ['Etudiant', 'corriger'],
                'compte'                => ['Etudiant', 'compte'],
                'demandes'              => ['App\\Demandes', 'index'],
                'nouvelle'          => ['Client',   'nouvelleDemande'],
                'urgence'           => ['Client',   'urgence'],
                'urgence-attente'   => ['Client',   'urgenceAttente'],
                'reservations'      => ['Client',   'reservations'],
                'portefeuille'      => ['Client',   'portefeuille'],
                'commandes'         => ['Client',   'commandes'],
                'reserver'          => ['Client',   'reserver'],
                'payer'             => ['Client',   'payer'],
                'noter'             => ['Client',   'noter'],
                'missions'              => ['Expert',   'missions'],
                'expert-missions'       => ['Expert',   'missions'],
                'expert-reservations'   => ['Expert',   'reservations'],
                'urgences'          => ['Expert',   'urgences'],
                'expert-demandes'   => ['Expert',   'demandes'],
                'revenus'           => ['Expert',   'revenus'],
                'retrait-choix'     => ['Expert',   'retraitChoix'],
                'retrait'           => ['Expert',   'retrait'],
                'prestations'       => ['Expert',   'prestations'],
                'noter-client'      => ['Expert',   'noterClient'],
                'livraison'         => ['Expert',   'livraison'],
                'en-attente'        => ['Expert',   'enAttente'],
                'prestataire-disponibilite' => ['PrestataireDisponibilite', 'disponibilite'],
                'prestataire-disponibilite-rappel' => ['PrestataireDisponibilite', 'rappel'],
                'proposer-demande'       => ['Expert', 'proposerDemande'],
                'accepter-proposition'       => ['Client', 'accepterProposition'],
                'refuser-proposition'        => ['Client', 'refuserProposition'],
                'confirmer-demande-resolue'  => ['Client', 'confirmerDemandeResolue'],
                'experts'           => ['Experts',     'index'],
                'professeurs'       => ['Professeurs', 'index'],
                'reserver-professeur' => ['Etudiant', 'reserverProfesseur'],
                'messages'          => ['App\\Messages', 'index'],
                'conversation'      => ['App\\Messages', 'conversation'],
                'profil'            => ['App\\Profil', 'index'],
                // Anciennes URLs /app (hors modules actifs) : renvoient vers les pages natives équivalentes
                'renouvellement-abonnement'      => ['Abonnement', 'index'],
                'paiement-client'                => ['Abonnement', 'index'],
                'paiement-expert'               => ['Abonnement', 'index'],
                'paiement-prestataire'          => ['Abonnement', 'index'],
                'paiement-etudiant'             => ['Abonnement', 'index'],
                'paiement-professeur'           => ['Abonnement', 'index'],
                'depot-portefeuille-client'     => ['Client', 'portefeuille'],
                'depot-portefeuille-expert'      => ['Expert', 'revenus'],
                'depot-portefeuille-prestataire' => ['Expert', 'revenus'],
                'depot-portefeuille-etudiant'    => ['Etudiant', 'portefeuille'],
                'depot-portefeuille-professeur'  => ['Etudiant', 'portefeuille'],
                'touchpay-abonnement'       => ['Intouch', 'touchpay'],
                'touchpay-depot'            => ['Intouch', 'touchpayDepot'],
                'touchpay-session'          => ['Intouch', 'touchpaySession'],
                'paytech-abonnement'        => ['Paytech', 'checkout'],
                'paytech-depot'             => ['Paytech', 'depot'],
                'paytech-session'           => ['Paytech', 'paiementSession'],
                'paytech-paiement-reussi'   => ['Paytech', 'paiementReussi'],
                'paytech-paiement-annule'   => ['Paytech', 'paiementAnnule'],
            ];
            if (isset($appRouteMap[$appSegment])) {
                [$this->controller, $this->action] = $appRouteMap[$appSegment];
                $this->params = array_slice($segments, 1);
                // On garde le prefix App\\ pour que isApp() reste true (force mobile)
                return;
            }
            // /app sans segment → accueil mobile (App\Home::index)
            if ($appSegment === '') {
                $this->controller = 'App\\Home';
                $this->action     = 'index';
                $this->params     = [];
                return;
            }
            // Segment inconnu : laisser le routage générique continuer
        }

        // Abonnement : /abonnement, /abonnement/callback, /abonnement/souscrire (client, expert, étudiant, professeur)
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'abonnement') {
            $this->controller = 'Abonnement';
            $this->action = !empty($segments[1]) ? $this->toCamelCase((string) $segments[1]) : 'index';
            $this->params = array_slice($segments, 2);
            return;
        }
        // Préfixe URL legacy (ne plus utiliser) : accueil
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'touchpoint') {
            $this->controller = 'Home';
            $this->action     = 'index';
            $this->params     = [];
            return;
        }
        // URL courte pour codes QR / flyers → inscription (conserve ?ref= & ?role= dans la requête)
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'rejoindre') {
            $this->controller = 'Auth';
            $this->action     = 'rejoindre';
            $this->params     = array_slice($segments, 1);
            return;
        }
        // Espace étudiant connecté : /etudiant, /etudiant/exercices, etc.
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'etudiant') {
            array_shift($segments);
            $this->controller = 'Etudiant';
            $this->action     = !empty($segments[0]) ? $this->toCamelCase((string) $segments[0]) : 'index';
            if (!empty($segments[0])) array_shift($segments);
            $this->params = array_values($segments);
            return;
        }
        // Espace professeur connecté : /professeur, /professeur/exercices-disponibles, etc.
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'professeur') {
            array_shift($segments);
            $this->controller = 'Etudiant';
            $this->action     = !empty($segments[0]) ? $this->toCamelCase((string) $segments[0]) : 'index';
            if (!empty($segments[0])) array_shift($segments);
            $this->params = array_values($segments);
            return;
        }
        // API prestataire : disponibilité (expert / professeur)
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'prestataire' && !empty($segments[1])) {
            $second = strtolower((string) $segments[1]);
            if (in_array($second, ['disponibilite', 'rappel'], true)) {
                $this->controller = 'PrestataireDisponibilite';
                $this->action = $second === 'rappel' ? 'rappel' : 'disponibilite';
                $this->params = [];
                return;
            }
        }
        // Espace expert connecté : /expert/missions, /expert/urgences, etc. (avant la règle profil par slug)
        $expertReservedActions = ['urgences', 'missions', 'reservations', 'revenus', 'retrait-choix', 'retrait', 'prestations', 'reserver', 'urgence-accept', 'accepter', 'refuser', 'terminer', 'noter-client', 'profil', 'demandes', 'proposer-demande', 'abonnement', 'compte', 'livrer', 'en-attente'];
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'expert' && !empty($segments[1])) {
            $second = strtolower((string) $segments[1]);
            if (in_array($second, $expertReservedActions, true)) {
                $this->controller = 'Expert';
                $this->action = $this->toCamelCase((string) $segments[1]);
                $this->params = array_slice($segments, 2);
                return;
            }
            // Growth: public expert profile by slug (/expert/amadou-flutter-developer)
            $this->controller = 'Experts';
            $this->action = 'profileBySlug';
            $this->params = [(string) $segments[1]];
            return;
        }
        // Growth: public job page by slug (/jobs/flutter-bug-fix)
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'jobs' && !empty($segments[1])) {
            $this->controller = 'Jobs';
            $this->action = 'show';
            $this->params = [(string) $segments[1]];
            return;
        }
        // Page publique « Demandes » : créer une demande (invite à s'inscrire / se connecter)
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'demandes' && empty($segments[1])) {
            $this->controller = 'Home';
            $this->action = 'demandesPublic';
            $this->params = [];
            return;
        }

        // PayTech : /paytech/checkout, /paytech/callback, …
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'paytech') {
            $this->controller = 'Paytech';
            $this->action     = !empty($segments[1]) ? $this->toCamelCase((string) $segments[1]) : 'checkout';
            $this->params     = array_slice($segments, 2);
            return;
        }

        // InTouch TouchPay : /intouch/paiement, /intouch/callback, …
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'wave') {
            $segments[0] = 'intouch';
        }
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'intouch') {
            $this->controller = 'Intouch';
            $this->action     = !empty($segments[1]) ? $this->toCamelCase((string) $segments[1]) : 'paiement';
            $this->params     = array_slice($segments, 2);
            return;
        }

        // Pages géolocalisées : /experts/{slug-pays} → Home::pays
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'experts' && !empty($segments[1])) {
            $countrySlug = strtolower((string) $segments[1]);
            $knownCountries = ['mali', 'senegal', 'cote-divoire', 'benin', 'niger'];
            if (in_array($countrySlug, $knownCountries, true)) {
                $this->controller = 'Home';
                $this->action     = 'pays';
                $this->params     = [$countrySlug];
                return;
            }
        }
        // Blog
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'blog') {
            $this->controller = 'Blog';
            $this->action = !empty($segments[1]) ? 'show' : 'index';
            $this->params = array_slice($segments, 1);
            return;
        }
        // Share: achievement card (/share/achievement/123)
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'share' && !empty($segments[1]) && strtolower((string) $segments[1]) === 'achievement' && !empty($segments[2])) {
            $this->controller = 'Share';
            $this->action = 'achievement';
            $this->params = array_slice($segments, 2);
            return;
        }

        // Fichiers uploadés publics : /uploads/{chemin} → FichierController::serveUpload()
        // Activé uniquement si Apache ne sert pas directement public/uploads/ (pas de symlink/jonction).
        if (!empty($segments[0]) && strtolower((string) $segments[0]) === 'uploads') {
            $this->controller = 'Fichier';
            $this->action     = 'serveUpload';
            $this->params     = array_values(array_slice($segments, 1));
            return;
        }

        if (!empty($segments[0])) {
            $this->controller = $this->toPascalCase((string) $segments[0]);
            array_shift($segments);
        }
        if (!empty($segments[0]) && !is_numeric($segments[0])) {
            $this->action = $this->toCamelCase((string) $segments[0]);
            array_shift($segments);
        }
        $this->params = array_values($segments);
    }

    private function toPascalCase(string $str): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $str)));
    }

    private function toCamelCase(string $str): string
    {
        $pascal = $this->toPascalCase($str);
        return lcfirst($pascal);
    }

    public function getController(): string
    {
        return $this->prefix . $this->controller;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function isApi(): bool
    {
        return $this->prefix === 'Api\\';
    }

    public function isAdmin(): bool
    {
        return $this->prefix === 'Admin\\';
    }

    public function isApp(): bool
    {
        return $this->prefix === 'App\\';
    }
}
