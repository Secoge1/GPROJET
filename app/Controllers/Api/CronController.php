<?php
/**
 * GLOBALO — Endpoint API pour les tâches cron déclenchées par GitHub Actions.
 *
 * Routes disponibles (toutes protégées par X-Cron-Secret) :
 *   POST /api/cron/social             → Publication sociale automatique
 *   POST /api/cron/test-social        → Aperçu publication sociale (dry-run)
 *   POST /api/cron/assistant-emails   → Emails IA proactifs (quotidien)
 *   POST /api/cron/profia-relance     → PROFIA : relances profils Clients & Experts (tous les 3 jours)
 *   POST /api/cron/aria-relance       → ARIA : relances inscriptions Profs & Étudiants (tous les 3 jours)
 */

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Models\ParametreModel;
use App\Services\SocialPublisherService;
use App\Services\ProactiveAssistantMailerService;
use App\Services\ProfiaRelanceService;
use App\Services\AriaRelanceService;

class CronController extends Controller
{
    /**
     * POST /api/cron/social
     * Déclenché par GitHub Actions (ou tout webhook externe) via :
     *   curl -X POST https://globalo.secogesarl.com/api/cron/social \
     *        -H "X-Cron-Secret: <secret>"
     *
     * Optionnel : body JSON { "sujet": "..." } pour forcer un sujet.
     */
    public function social(): void
    {
        $this->verifyCronSecret();

        $input  = $this->readJsonInput();
        $sujet  = !empty($input['sujet']) ? trim($input['sujet']) : null;

        try {
            $publisher = new SocialPublisherService();
            $result    = $publisher->publierAuto($sujet);

            $this->json(['ok' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            error_log('[CronController::social] ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/cron/test-social
     * Génère et retourne l'aperçu du post IA SANS publier (pour tester en admin).
     * Body JSON : { "sujet": "...", "secret": "..." }
     */
    public function testSocial(): void
    {
        $this->verifyCronSecret();

        $input = $this->readJsonInput();
        $sujet = trim($input['sujet'] ?? '');
        if (!$sujet) {
            $this->json(['ok' => false, 'error' => 'sujet requis'], 400);
            return;
        }

        try {
            $publisher = new SocialPublisherService();
            $apercu    = $publisher->genererApercu($sujet);
            $this->json(['ok' => true, 'apercu' => $apercu]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/cron/assistant-emails
     * Déclenche l'assistant IA d'emails proactifs.
     * Body JSON optionnel : { "dry_run": true }
     */
    public function assistantEmails(): void
    {
        $this->verifyCronSecret();
        $input = $this->readJsonInput();
        $dryRun = !empty($input['dry_run']);

        try {
            $service = new ProactiveAssistantMailerService();
            $result = $service->run($dryRun);
            $this->json(['ok' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            error_log('[CronController::assistantEmails] ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/cron/profia-relance
     * PROFIA — Envoi automatique de relances pour les profils incomplets.
     * Déclenché tous les 3 jours par GitHub Actions.
     * Body JSON optionnel : { "dry_run": true }
     */
    public function profiaRelance(): void
    {
        $this->verifyCronSecret();
        $input  = $this->readJsonInput();
        $dryRun = !empty($input['dry_run']);

        try {
            $service = new ProfiaRelanceService();
            $result  = $service->run($dryRun);
            $this->json(['ok' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            error_log('[CronController::profiaRelance] ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/cron/aria-relance
     * ARIA — Envoi automatique de relances pour les inscriptions incomplètes
     * (Professeurs & Étudiants). Déclenché tous les 3 jours par GitHub Actions.
     * Body JSON optionnel : { "dry_run": true }
     */
    public function ariaRelance(): void
    {
        $this->verifyCronSecret();
        $input  = $this->readJsonInput();
        $dryRun = !empty($input['dry_run']);

        try {
            $service = new AriaRelanceService();
            $result  = $service->run($dryRun);
            $this->json(['ok' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            error_log('[CronController::ariaRelance] ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ──────────────────────────────────────────────────────────────

    private function verifyCronSecret(): void
    {
        // Secret attendu : priorité à la variable d'environnement, puis à la base de données.
        $envSecret   = (string) (getenv('CRON_SECRET') ?: '');
        $paramSecret = $envSecret !== '' ? $envSecret : (string) (new ParametreModel())->get('cron_secret', '');

        $headerSecret = $_SERVER['HTTP_X_CRON_SECRET'] ?? '';
        $bodySecret   = $this->readJsonInput()['secret'] ?? '';
        $provided     = $headerSecret ?: $bodySecret;

        if ($paramSecret === '' || !hash_equals($paramSecret, $provided)) {
            $this->json(['error' => 'Unauthorized'], 401);
            exit;
        }
    }

    private function readJsonInput(): array
    {
        static $input = null;
        if ($input === null) {
            $raw   = file_get_contents('php://input');
            $input = ($raw && ($d = json_decode($raw, true)) && is_array($d)) ? $d : [];
        }
        return $input;
    }
}
