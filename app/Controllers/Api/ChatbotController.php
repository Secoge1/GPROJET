<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Auth;
use App\Services\ChatbotService;
use App\Models\ChatbotConversationModel;
use App\Models\ChatbotMessageModel;
use App\Models\ParametreModel;

class ChatbotController extends Controller
{
    /**
     * POST /api/chatbot/send
     * Body: { "message": "...", "conversation_uid": "optional-uuid" }
     */
    public function send(): void
    {
        $input = $this->readJsonInput();
        $message = isset($input['message']) ? trim((string) $input['message']) : '';
        $conversationUid = isset($input['conversation_uid']) ? trim((string) $input['conversation_uid']) : null;

        if ($message === '') {
            $this->json(['error' => 'message is required'], 400);
            return;
        }

        $paramModel = new ParametreModel();
        if ($paramModel->get('chatbot_enabled', '1') !== '1') {
            $this->json([
                'reply' => 'Le chatbot est actuellement désactivé. Réessayez plus tard ou contactez le support.',
                'intent' => 'general_question',
                'experts' => [],
                'quick_actions' => (new ChatbotService())->getQuickActions(),
                'conversation_uid' => $conversationUid ?? null,
            ]);
            return;
        }

        try {
            $service = new ChatbotService();
            $result = $service->process($message, $conversationUid);

            $this->json([
                'reply' => $result['reply'],
                'intent' => $result['intent'],
                'experts' => $result['experts'],
                'quick_actions' => $result['quick_actions'],
                'conversation_uid' => $result['conversation_uid'],
                'created_demande_id' => $result['created_demande_id'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $paramModel = new ParametreModel();
            $reply = 'Le chatbot est temporairement indisponible.';
            if (!$paramModel->get('chatbot_openai_api_key') && !defined('OPENAI_API_KEY')) {
                $reply = 'Le chatbot n\'est pas encore configuré (clé API OpenAI manquante dans Admin > Paramètres > Chatbot).';
            } elseif (strpos($e->getMessage(), 'chatbot_') !== false || strpos($e->getMessage(), 'Table') !== false) {
                $reply = 'Le chatbot n\'est pas encore configuré. Exécutez le script SQL database/chatbot_schema.sql.';
            }
            $this->json([
                'reply' => $reply,
                'intent' => 'general_question',
                'experts' => [],
                'quick_actions' => (new ChatbotService())->getQuickActions(),
                'conversation_uid' => $conversationUid,
            ], 200);
        }
    }

    /**
     * GET /api/chatbot/history?conversation_uid=...
     */
    public function history(): void
    {
        $conversationUid = isset($_GET['conversation_uid']) ? trim((string) $_GET['conversation_uid']) : null;
        if ($conversationUid === '') {
            $this->json(['error' => 'conversation_uid required'], 400);
            return;
        }

        $convModel = new ChatbotConversationModel();
        $conv = $convModel->findByUid($conversationUid);
        if (!$conv) {
            $this->json(['messages' => []]);
            return;
        }

        $msgModel = new ChatbotMessageModel();
        $limit = defined('CHATBOT_MAX_HISTORY') ? CHATBOT_MAX_HISTORY : 50;
        $historyRows = $msgModel->getHistory((int) $conv['id'], $limit);
        $messages = [];
        foreach ($historyRows as $row) {
            $messages[] = [
                'role' => $row['role'],
                'content' => $row['content'],
            ];
        }
        $this->json(['messages' => $messages]);
    }

    /**
     * GET /api/chatbot/quick-actions
     */
    public function quickActions(): void
    {
        $service = new ChatbotService();
        $actions = $service->getQuickActions();
        $labels = [
            'find_expert' => 'Trouver un expert',
            'post_request' => 'Publier une demande',
            'my_sessions' => 'Mes sessions',
            'support' => 'Contacter le support',
        ];
        $list = [];
        foreach ($actions as $id) {
            $list[] = ['id' => $id, 'label' => $labels[$id] ?? $id];
        }
        $this->json(['quick_actions' => $list]);
    }

    private function readJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === '' || $raw === false) {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
