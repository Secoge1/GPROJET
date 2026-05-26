<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Models\RhModel;
use App\Services\RhAiService;

/**
 * API JSON pour les agents IA de l'Espace RH
 * Routes : /api/rh/chat, /api/rh/analyse, /api/rh/save-recommandation
 */
class RhAiController extends Controller
{
    private RhModel     $rhModel;
    private RhAiService $aiService;

    public function __construct(Router $router)
    {
        parent::__construct($router);
        Auth::requireRole('admin');
        $this->rhModel   = new RhModel();
        $this->aiService = new RhAiService();
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * POST /api/rh/chat
     * Corps JSON : { agent_type, message, session_id, context? }
     */
    public function chat(): void
    {
        $body = $this->getJsonBody();

        $agentType = $body['agent_type'] ?? 'manager';
        $message   = trim($body['message'] ?? '');
        $sessionId = $body['session_id'] ?? session_id();

        if (!in_array($agentType, ['inscriptions','profils','marketing','manager'], true)) {
            $this->jsonError('Type d\'agent invalide.', 400);
            return;
        }
        if ($message === '') {
            $this->jsonError('Message vide.', 400);
            return;
        }
        if (strlen($message) > 2000) {
            $this->jsonError('Message trop long (max 2000 caractères).', 400);
            return;
        }

        $adminId = (int) (Auth::id() ?? 0);

        // Charger l'historique de la session
        $history = $this->rhModel->getIaHistory($sessionId, $agentType);
        $messages = array_map(fn($h) => ['role' => $h['role'], 'content' => $h['message']], $history);

        // Ajouter le nouveau message
        $messages[] = ['role' => 'user', 'content' => $message];

        // Contexte données optionnel
        $context = $this->buildContext($agentType);

        // Appel IA
        $result = $this->aiService->chat($agentType, $messages, $context);

        // Sauvegarder les logs
        $this->rhModel->saveIaLog($agentType, $adminId, $sessionId, 'user', $message);
        $this->rhModel->saveIaLog($agentType, $adminId, $sessionId, 'assistant', $result['content']);

        echo json_encode([
            'success'   => true,
            'content'   => $result['content'],
            'agent'     => $result['agent'],
            'session_id'=> $sessionId,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * POST /api/rh/save-recommandation
     */
    public function saveRecommandation(): void
    {
        $body    = $this->getJsonBody();
        $adminId = (int) (Auth::id() ?? 0);

        $titre       = trim($body['titre'] ?? '');
        $description = trim($body['description'] ?? '');
        $segment     = trim($body['segment'] ?? 'global');

        if ($titre === '' || $description === '') {
            $this->jsonError('Titre et description requis.', 400);
            return;
        }

        $ok = $this->rhModel->saveRecommandation([
            ':segment'     => $segment,
            ':titre'       => $titre,
            ':description' => $description,
            ':action_cle'  => $body['action_cle'] ?? null,
            ':priorite'    => (int) ($body['priorite'] ?? 5),
            ':admin_id'    => $adminId,
        ]);

        echo json_encode(['success' => $ok], JSON_UNESCAPED_UNICODE);
    }

    // ──────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────

    private function buildContext(string $agentType): array
    {
        $context = [];
        try {
            $context['stats'] = $this->rhModel->getStatsGlobales();
            if ($agentType === 'inscriptions') {
                $context['inscriptions_stats'] = $this->rhModel->getStatsInscriptions();
            } elseif ($agentType === 'marketing') {
                $context['segments'] = $this->rhModel->getSegmentsMarketing();
            } elseif ($agentType === 'manager') {
                $context = $this->rhModel->getManagerDashboard();
            }
        } catch (\Throwable $e) {
            // Ne pas bloquer si les données ne sont pas disponibles
        }
        return $context;
    }

    private function getJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (!$raw) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function jsonError(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(['success' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    }
}
