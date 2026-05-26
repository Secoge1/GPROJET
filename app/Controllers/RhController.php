<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Core\Database;
use App\Models\RhModel;
use App\Services\RhAiService;
use App\Services\WhatsAppAiService;

/**
 * Espace de Gestion RH avec IA Intégrée — GLOBALO
 * Accès réservé aux administrateurs
 */
class RhController extends Controller
{
    private RhModel     $rhModel;
    private RhAiService $aiService;

    public function __construct(Router $router)
    {
        parent::__construct($router);
        Auth::requireRole('admin');
        $this->rhModel   = new RhModel();
        $this->aiService = new RhAiService();
    }

    /**
     * Dashboard principal de l'Espace RH
     * GET /rh
     */
    public function index(): void
    {
        try {
            $stats = $this->rhModel->getStatsGlobales();
        } catch (\Throwable $e) {
            $stats = ['total_professeurs' => 0, 'total_etudiants' => 0, 'total_clients' => 0, 'total_experts' => 0, 'inscrits_ce_mois' => 0, 'inscrits_cette_semaine' => 0, 'actifs_30j' => 0];
        }
        try {
            $dashboard = $this->rhModel->getManagerDashboard();
        } catch (\Throwable $e) {
            $dashboard = ['alertes' => [], 'stats' => $stats, 'inscriptions_stats' => [], 'segments' => []];
        }
        $agents    = [
            RhAiService::getAgentInfo('inscriptions'),
            RhAiService::getAgentInfo('profils'),
            RhAiService::getAgentInfo('marketing'),
            RhAiService::getAgentInfo('manager'),
        ];

        $this->render('dashboard', [
            'pageTitle'    => 'Espace RH — GLOBALO',
            'navActive'    => 'rh',
            'adminSection' => 'rh',
            'stats'        => $stats,
            'dashboard'    => $dashboard,
            'agents'       => $agents,
            'ia_active'    => $this->aiService->isConfigured(),
            'user'         => ['id' => Auth::id(), 'role' => 'admin'],
            'seo'          => ['description' => 'Espace de gestion RH avec IA intégrée'],
        ]);
    }

    /**
     * Agent IA — Gestion des Inscriptions (Professeurs & Étudiants)
     * GET /rh/inscriptions
     */
    public function inscriptions(): void
    {
        $inscriptionsProfs     = $this->rhModel->getInscriptionsRecentes('professeur', 15);
        $inscriptionsEtudiants = $this->rhModel->getInscriptionsRecentes('etudiant', 15);
        $statsInscriptions     = $this->rhModel->getStatsInscriptions();
        $agentInfo             = RhAiService::getAgentInfo('inscriptions');

        try {
            $welcomeAnalysis = $this->aiService->generateWelcomeAnalysis('inscriptions', [
                'stats'       => $statsInscriptions,
                'total_profs' => count($inscriptionsProfs),
                'total_etuds' => count($inscriptionsEtudiants),
            ]);
        } catch (\Throwable $e) {
            $welcomeAnalysis = '👋 Bonjour ! Je suis **ARIA**. Posez-moi une question sur les inscriptions.';
        }

        $this->render('inscriptions', [
            'pageTitle'             => 'ARIA — Inscriptions RH',
            'navActive'             => 'rh_inscriptions',
            'adminSection'          => 'rh',
            'inscriptionsProfs'     => $inscriptionsProfs,
            'inscriptionsEtudiants' => $inscriptionsEtudiants,
            'statsInscriptions'     => $statsInscriptions,
            'agentInfo'             => $agentInfo,
            'welcomeAnalysis'       => $welcomeAnalysis,
            'ia_active'             => $this->aiService->isConfigured(),
            'user'                  => ['id' => Auth::id(), 'role' => 'admin'],
            'seo'                   => ['description' => 'Gestion IA des inscriptions Professeurs et Étudiants'],
        ]);
    }

    /**
     * Agent IA — Gestion des Profils (Clients & Experts)
     * GET /rh/profils
     */
    public function profils(): void
    {
        $profilsExperts = $this->rhModel->getProfilsAvecScore('expert', 15);
        $profilsClients = $this->rhModel->getProfilsAvecScore('client', 15);
        $agentInfo      = RhAiService::getAgentInfo('profils');

        $scoresMoyens = [
            'experts' => !empty($profilsExperts) ? (int) round(array_sum(array_column($profilsExperts, 'score_profil')) / count($profilsExperts)) : 0,
            'clients' => !empty($profilsClients) ? (int) round(array_sum(array_column($profilsClients, 'score_profil')) / count($profilsClients)) : 0,
        ];

        try {
            $welcomeAnalysis = $this->aiService->generateWelcomeAnalysis('profils', [
                'score_moyen_experts' => $scoresMoyens['experts'],
                'score_moyen_clients' => $scoresMoyens['clients'],
                'nb_experts'          => count($profilsExperts),
                'nb_clients'          => count($profilsClients),
            ]);
        } catch (\Throwable $e) {
            $welcomeAnalysis = '👤 Bonjour ! Je suis **PROFIA**. Demandez-moi d\'analyser un profil.';
        }

        $this->render('profils', [
            'pageTitle'       => 'PROFIA — Profils RH',
            'navActive'       => 'rh_profils',
            'adminSection'    => 'rh',
            'profilsExperts'  => $profilsExperts,
            'profilsClients'  => $profilsClients,
            'scoresMoyens'    => $scoresMoyens,
            'agentInfo'       => $agentInfo,
            'welcomeAnalysis' => $welcomeAnalysis,
            'ia_active'       => $this->aiService->isConfigured(),
            'user'            => ['id' => Auth::id(), 'role' => 'admin'],
            'seo'             => ['description' => 'Analyse IA des profils Clients et Experts'],
        ]);
    }

    /**
     * Agent IA — Marketing & Recommandations
     * GET /rh/marketing
     */
    public function marketing(): void
    {
        $segments        = $this->rhModel->getSegmentsMarketing();
        $recommandations = $this->rhModel->getRecommandationsMarketing();
        $agentInfo       = RhAiService::getAgentInfo('marketing');

        try {
            $welcomeAnalysis = $this->aiService->generateWelcomeAnalysis('marketing', [
                'segments'           => $segments,
                'nb_recommandations' => count($recommandations),
            ]);
        } catch (\Throwable $e) {
            $welcomeAnalysis = '📊 Bonjour ! Je suis **MARKIA**. Que souhaitez-vous analyser ?';
        }

        $this->render('marketing', [
            'pageTitle'       => 'MARKIA — Marketing RH',
            'navActive'       => 'rh_marketing',
            'adminSection'    => 'rh',
            'segments'        => $segments,
            'recommandations' => $recommandations,
            'agentInfo'       => $agentInfo,
            'welcomeAnalysis' => $welcomeAnalysis,
            'ia_active'       => $this->aiService->isConfigured(),
            'user'            => ['id' => Auth::id(), 'role' => 'admin'],
            'seo'             => ['description' => 'IA Marketing et recommandations GLOBALO'],
        ]);
    }

    /**
     * Migration BDD — Créer les tables RH
     * GET|POST /rh/migration
     */
    public function migration(): void
    {
        $result = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sqlFile = ROOT_PATH . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migration_rh.sql';
            $sql     = file_get_contents($sqlFile);
            $stmts   = preg_split('/;\s*\n/', $sql);
            $ok      = 0;
            $errors  = [];

            foreach ($stmts as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '' || strpos($stmt, '--') === 0) {
                    continue;
                }
                try {
                    Database::getInstance()->exec($stmt);
                    $ok++;
                } catch (\PDOException $e) {
                    $errors[] = $e->getMessage();
                }
            }

            $result = ['success' => empty($errors), 'ok' => $ok, 'errors' => $errors];
        }

        $this->render('migration', [
            'pageTitle'       => 'Migration RH — Tables IA',
            'navActive'       => 'rh',
            'adminSection'    => 'rh',
            'migrationResult' => $result,
            'user'            => ['id' => Auth::id(), 'role' => 'admin'],
            'seo'             => ['description' => 'Migration tables module RH'],
        ]);
    }

    /**
     * WhatsApp IA — Configuration & Statistiques
     * GET /rh/whatsapp
     */
    public function whatsapp(): void
    {
        $stats = [];
        try {
            $waService = new WhatsAppAiService();
            $stats     = $waService->getStats();
        } catch (\Throwable $e) {
            error_log('[RH/WhatsApp] Erreur init WhatsAppAiService : ' . $e->getMessage());
        }

        $baseUrl = rtrim(BASE_URL ?? '', '/');

        $waConfigured = (getenv('WHATSAPP_ACCESS_TOKEN') ?: (defined('WHATSAPP_ACCESS_TOKEN') ? WHATSAPP_ACCESS_TOKEN : '')) !== ''
                     && (getenv('WHATSAPP_PHONE_NUMBER_ID') ?: (defined('WHATSAPP_PHONE_NUMBER_ID') ? WHATSAPP_PHONE_NUMBER_ID : '')) !== '';

        $verifyToken = getenv('WHATSAPP_WEBHOOK_VERIFY_TOKEN')
            ?: (defined('WHATSAPP_WEBHOOK_VERIFY_TOKEN') ? WHATSAPP_WEBHOOK_VERIFY_TOKEN : 'globalo_webhook_2026');

        $this->render('whatsapp', [
            'pageTitle'     => 'WhatsApp IA — GAIA',
            'navActive'     => 'rh_whatsapp',
            'adminSection'  => 'rh',
            'stats'         => $stats,
            'wa_configured' => $waConfigured,
            'webhook_url'   => $baseUrl . '/api/whatsapp/webhook',
            'verify_token'  => $verifyToken,
            'ia_active'     => $this->aiService->isConfigured(),
            'user'          => ['id' => Auth::id(), 'role' => 'admin'],
            'seo'           => ['description' => 'Configuration WhatsApp IA GAIA — GLOBALO'],
        ]);
    }

    /**
     * Migration BDD WhatsApp
     * GET|POST /rh/migration-whatsapp
     */
    public function migrationWhatsapp(): void
    {
        $result = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $sqlFile = ROOT_PATH . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migration_whatsapp.sql';
            $sql     = file_get_contents($sqlFile);
            $stmts   = preg_split('/;\s*\n/', $sql);
            $ok = 0; $errors = [];
            foreach ($stmts as $stmt) {
                $stmt = trim($stmt);
                if ($stmt === '' || strpos($stmt, '--') === 0) continue;
                try {
                    Database::getInstance()->exec($stmt);
                    $ok++;
                } catch (\PDOException $e) {
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        $errors[] = $e->getMessage();
                    }
                }
            }
            $result = ['success' => empty($errors), 'ok' => $ok, 'errors' => $errors];
        }

        $this->render('whatsapp', [
            'pageTitle'      => 'Migration WhatsApp',
            'navActive'      => 'rh_whatsapp',
            'adminSection'   => 'rh',
            'stats'          => [],
            'wa_configured'  => false,
            'webhook_url'    => '',
            'verify_token'   => '',
            'ia_active'      => false,
            'migrationResult'=> $result,
            'user'           => ['id' => Auth::id(), 'role' => 'admin'],
            'seo'            => ['description' => 'Migration BDD WhatsApp'],
        ]);
    }

    /**
     * Agent IA — Manager (vue 360°)
     * GET /rh/manager
     */
    public function manager(): void
    {
        $dashboard = $this->rhModel->getManagerDashboard();
        $agentInfo = RhAiService::getAgentInfo('manager');

        try {
            $welcomeAnalysis = $this->aiService->generateWelcomeAnalysis('manager', $dashboard);
        } catch (\Throwable $e) {
            $welcomeAnalysis = '🎯 Bonjour ! Je suis **MAIA**, votre Manager IA. Comment puis-je vous aider ?';
        }

        $this->render('manager', [
            'pageTitle'       => 'MAIA — Manager IA',
            'navActive'       => 'rh_manager',
            'adminSection'    => 'rh',
            'dashboard'       => $dashboard,
            'agentInfo'       => $agentInfo,
            'welcomeAnalysis' => $welcomeAnalysis,
            'ia_active'       => $this->aiService->isConfigured(),
            'user'            => ['id' => Auth::id(), 'role' => 'admin'],
            'seo'             => ['description' => 'Manager IA — Vue 360° GLOBALO'],
        ]);
    }
}
