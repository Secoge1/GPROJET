<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ChatbotConfigModel;
use App\Models\ChatbotConversationModel;
use App\Models\ChatbotMessageModel;
use App\Models\DemandeModel;
use App\Models\ParametreModel;
use App\Core\Auth;

class ChatbotService
{
    private const QUICK_ACTIONS = ['find_expert', 'post_request', 'my_sessions', 'support'];

    private ChatbotConfigModel $configModel;
    private ChatbotConversationModel $convModel;
    private ChatbotMessageModel $msgModel;
    private OpenAiService $openAi;
    private ExpertMatchingService $matching;
    private DemandeModel $demandeModel;
    private ParametreModel $paramModel;

    public function __construct()
    {
        $this->configModel = new ChatbotConfigModel();
        $this->convModel = new ChatbotConversationModel();
        $this->msgModel = new ChatbotMessageModel();
        $paramModel = new ParametreModel();
        $apiKey     = $paramModel->get('chatbot_openai_api_key') ?: (defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '');
        $provider   = $paramModel->get('chatbot_ai_provider', 'openai');
        $this->openAi = new OpenAiService($apiKey ?: null, '', 600, $provider);
        $this->matching = new ExpertMatchingService();
        $this->demandeModel = new DemandeModel();
        $this->paramModel = new ParametreModel();
    }

    /**
     * Process user message and return reply with optional experts and quick_actions.
     *
     * @return array{reply: string, intent: string, experts: array, quick_actions: array, conversation_id: int, conversation_uid: string, created_demande_id: int|null}
     */
    public function process(string $message, ?string $conversationUid = null): array
    {
        $conversationUid = $conversationUid ?: $this->generateConversationUid();
        $userId = Auth::id();
        $conv = $this->convModel->getOrCreate($conversationUid, $userId, session_id());
        $convId = (int) $conv['id'];

        $this->msgModel->add($convId, 'user', $message);

        // Sans clé OpenAI : réponses par mots-clés (toujours implémentées)
        if (!$this->openAi->isConfigured()) {
            return $this->processFallback($message, $convId, $conversationUid);
        }

        $history = $this->msgModel->getHistory($convId, defined('CHATBOT_MAX_HISTORY') ? CHATBOT_MAX_HISTORY : 20);
        $systemPrompt = $this->buildSystemPrompt();
        $messages = $this->historyToMessages($history);
        $messages[] = ['role' => 'user', 'content' => $message];

        $result = $this->openAi->chat($messages, $systemPrompt);
        $intent = $result['intent'] ?? 'general_question';
        $extracted = $result['extracted'] ?? [];
        $reply = $result['content'];

        // Si OpenAI a renvoyé un message d'erreur générique, utiliser le fallback
        if ($reply === '' || $this->isGenericErrorMessage($reply)) {
            $fallback = $this->processFallback($message, $convId, $conversationUid);
            $reply = $fallback['reply'];
            $intent = $fallback['intent'];
            $experts = $fallback['experts'];
            $this->msgModel->add($convId, 'assistant', $reply, $intent, ['experts' => $experts, 'quick_actions' => self::QUICK_ACTIONS]);
            return [
                'reply' => $reply,
                'intent' => $intent,
                'experts' => $experts,
                'quick_actions' => self::QUICK_ACTIONS,
                'conversation_id' => $convId,
                'conversation_uid' => $conversationUid,
                'created_demande_id' => null,
            ];
        }

        $experts = [];
        $createdDemandeId = null;
        $context = $this->convModel->getContext($convId);

        // Dispatch by intent
        if ($intent === 'find_expert' || $this->detectFindExpertFromReply($reply, $message)) {
            $competenceId = $this->matching->resolveCompetenceFromText($message);
            if ($competenceId === null && !empty($extracted['category'])) {
                $competenceId = $this->matching->resolveCompetenceFromText($extracted['category']);
            }
            $maxRate = isset($extracted['max_budget']) ? (float) $extracted['max_budget'] : null;
            $experts = $this->matching->search($competenceId, true, null, $maxRate, $message, 10);
            $reply = $this->formatFindExpertReply($experts, $reply);
        } elseif ($intent === 'create_task') {
            $contextTask = $context['create_task'] ?? [];
            $duration = $extracted['duration_hours'] ?? $contextTask['duration_hours'] ?? null;
            $budget = $extracted['budget'] ?? $contextTask['budget'] ?? null;
            $title = $extracted['title'] ?? $contextTask['title'] ?? $message;

            if ($userId && Auth::isClient()) {
                if ($duration !== null && (float) $duration > 0) {
                    $competenceId = $this->matching->resolveCompetenceFromText($title);
                    $demandeId = $this->demandeModel->create([
                        'client_id' => $userId,
                        'titre' => mb_substr($title, 0, 200),
                        'description' => $message,
                        'competence_id' => $competenceId,
                        'duree_estimee_heures' => (float) $duration,
                        'urgence' => 'normale',
                    ]);
                    $createdDemandeId = $demandeId;

                    // Notifier experts/professeurs (in-app + push)
                    $cbNotifTitre      = mb_substr($title, 0, 200);
                    $cbNotifCompetence = $competenceId ? (int) $competenceId : null;
                    $cbDemandeId       = $demandeId;
                    register_shutdown_function(static function () use ($cbDemandeId, $cbNotifTitre, $cbNotifCompetence): void {
                        try {
                            (new \App\Models\NotificationModel())
                                ->batchNotifyExpertsNouvelleDemandeInApp($cbDemandeId, $cbNotifTitre, $cbNotifCompetence);
                        } catch (\Throwable $e) {}
                        try {
                            (new \App\Services\PushNotificationService())
                                ->notifyNouvelleDemandeAuxExperts($cbDemandeId, $cbNotifTitre, $cbNotifCompetence);
                        } catch (\Throwable $e) {}
                    });

                    $reply = "Votre demande a été créée. Vous pouvez la consulter dans « Mes demandes ».";
                    $context['create_task'] = [];
                } else {
                    $context['create_task'] = ['title' => $title, 'message' => $message];
                    try {
                        $reply = $this->configModel->getDefaultResponse('create_task') ?: "Pour créer la demande, indiquez la durée estimée en heures (ex: 2).";
                    } catch (\Throwable $e) {
                        $reply = "Pour créer la demande, indiquez la durée estimée en heures (ex: 2).";
                    }
                }
            } else {
                $reply = "Connectez-vous en tant que client pour créer une demande, ou indiquez la durée estimée (en heures) pour que je la prépare.";
            }
            $this->convModel->updateContext($convId, $context);
        } elseif (in_array($intent, ['help_payment', 'help_withdrawal', 'help_booking', 'help_commission'], true)) {
            try {
                $help = $this->configModel->getHelpContent(str_replace('help_', '', $intent));
            } catch (\Throwable $e) {
                $help = null;
            }
            if ($help) {
                $reply = $help;
            }
        }

        $this->msgModel->add($convId, 'assistant', $reply, $intent, [
            'experts' => $experts,
            'quick_actions' => self::QUICK_ACTIONS,
        ]);
        $payload = ['experts' => $experts, 'quick_actions' => self::QUICK_ACTIONS];
        if ($createdDemandeId !== null) {
            $payload['created_demande_id'] = $createdDemandeId;
        }

        return [
            'reply' => $reply,
            'intent' => $intent,
            'experts' => $experts,
            'quick_actions' => self::QUICK_ACTIONS,
            'conversation_id' => $convId,
            'conversation_uid' => $conversationUid,
            'created_demande_id' => $createdDemandeId,
        ];
    }

    private function generateConversationUid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    private function buildSystemPrompt(): string
    {
        try {
            $base = $this->configModel->getSystemPrompt();
        } catch (\Throwable $e) {
            $base = 'Tu es l\'assistant de GLOBALO. Réponds en français.';
        }
        try {
            $competences = (new \App\Models\CompetenceModel())->getActivesForChatbot();
        } catch (\Throwable $e) {
            $competences = [];
        }
        $cats = array_map(fn($c) => $c['nom'] . ' (slug: ' . $c['slug'] . ')', $competences);
        $base .= "\n\nCatégories d'experts disponibles: " . implode(', ', array_slice($cats, 0, 20));
        $base .= "\n\nRéponds en français. Si tu détectes une intention, écris en première ligne: INTENT: <intent> avec intent parmi: find_expert, create_task, help_payment, help_withdrawal, help_booking, help_commission, my_sessions, general_question.";
        $base .= "\nSi tu extrais des infos (durée, budget, catégorie), ajoute une ligne: EXTRACTED: {\"duration_hours\": ..., \"budget\": ..., \"category\": \"...\"}.";
        return $base;
    }

    /** @param array<int, array{role: string, content: string}> $history */
    private function historyToMessages(array $history): array
    {
        $out = [];
        foreach ($history as $h) {
            $role = $h['role'] === 'assistant' ? 'assistant' : 'user';
            $out[] = ['role' => $role, 'content' => $h['content']];
        }
        return $out;
    }

    private function detectFindExpertFromReply(string $reply, string $message): bool
    {
        $m = mb_strtolower($message);
        return str_contains($m, 'expert') || str_contains($m, 'trouve') || str_contains($m, 'find') || str_contains($m, 'aide') || str_contains($m, 'help with');
    }

    /**
     * Message pour l'intention find_expert : adapté au nombre d'experts (évite "Voici des profils" quand la liste est vide).
     */
    private function formatFindExpertReply(array $experts, string $currentReply): string
    {
        $n = count($experts);
        if ($n === 0) {
            return 'Aucun expert disponible pour le moment pour cette recherche. Précisez votre besoin (ex. développement web, design) ou consultez le catalogue des experts depuis la page d\'accueil.';
        }
        if ($currentReply !== '' && strpos(mb_strtolower($currentReply), 'expert') !== false && (strpos(mb_strtolower($currentReply), 'voici') !== false || strpos(mb_strtolower($currentReply), 'profils') !== false)) {
            return $currentReply;
        }
        try {
            $default = $this->configModel->getDefaultResponse('find_expert');
        } catch (\Throwable $e) {
            $default = null;
        }
        if ($default && (strpos(mb_strtolower($default), 'voici') !== false || strpos(mb_strtolower($default), 'profils') !== false)) {
            return $default;
        }
        return $default ?: ($n === 1 ? 'Voici un expert disponible.' : 'Voici ' . $n . ' experts disponibles.');
    }

    private function isGenericErrorMessage(string $reply): bool
    {
        $r = mb_strtolower($reply);
        return str_contains($r, 'n\'est pas configuré') || str_contains($r, 'temporairement indisponible') || str_contains($r, 'clé api manquante');
    }

    /**
     * Réponses sans OpenAI : détection d'intention par mots-clés + réponses config ou défaut.
     *
     * @return array{reply: string, intent: string, experts: array, quick_actions: array, conversation_id: int, conversation_uid: string, created_demande_id: int|null}
     */
    private function processFallback(string $message, int $convId, string $conversationUid): array
    {
        $intent = $this->detectIntentFromMessage($message);
        $reply = $this->getFallbackReply($intent, $message);
        $experts = [];

        if ($intent === 'find_expert') {
            $competenceId = $this->matching->resolveCompetenceFromText($message);
            $experts = $this->matching->search($competenceId, true, null, null, $message, 10);
            $reply = $this->formatFindExpertReply($experts, $reply);
        } elseif (in_array($intent, ['help_payment', 'help_withdrawal', 'help_booking', 'help_commission'], true)) {
            try {
                $help = $this->configModel->getHelpContent(str_replace('help_', '', $intent));
            } catch (\Throwable $e) {
                $help = null;
            }
            if ($help) {
                $reply = $help;
            } elseif ($reply === '') {
                $reply = 'Consultez la section Aide ou contactez le support pour plus d\'informations.';
            }
        } elseif ($intent === 'create_task') {
            try {
                $reply = $this->configModel->getDefaultResponse('create_task') ?: 'Connectez-vous en tant que client, puis utilisez « Publier une demande » ou indiquez la durée estimée (en heures) pour créer une demande.';
            } catch (\Throwable $e) {
                $reply = 'Connectez-vous en tant que client pour créer une demande depuis votre tableau de bord.';
            }
        } elseif ($intent === 'general_question' && $reply === '') {
            $reply = 'Je peux vous aider à trouver un expert, créer une demande, ou vous expliquer les réservations et paiements. Utilisez les boutons ci-dessous ou posez une question précise.';
        }

        $this->msgModel->add($convId, 'assistant', $reply, $intent, [
            'experts' => $experts,
            'quick_actions' => self::QUICK_ACTIONS,
        ]);

        return [
            'reply' => $reply,
            'intent' => $intent,
            'experts' => $experts,
            'quick_actions' => self::QUICK_ACTIONS,
            'conversation_id' => $convId,
            'conversation_uid' => $conversationUid,
            'created_demande_id' => null,
        ];
    }

    private function detectIntentFromMessage(string $message): string
    {
        $m = mb_strtolower($message);
        if (str_contains($m, 'expert') || str_contains($m, 'trouve') || str_contains($m, 'trouver') || str_contains($m, 'cherche') || str_contains($m, 'find') || str_contains($m, 'développeur') || str_contains($m, 'design')) {
            return 'find_expert';
        }
        if (str_contains($m, 'demande') || str_contains($m, 'publier') || str_contains($m, 'créer') || str_contains($m, 'create') || str_contains($m, 'mission')) {
            return 'create_task';
        }
        if (str_contains($m, 'paiement') || str_contains($m, 'payer') || str_contains($m, 'payment')) {
            return 'help_payment';
        }
        if (str_contains($m, 'retrait') || str_contains($m, 'withdrawal') || str_contains($m, 'virement')) {
            return 'help_withdrawal';
        }
        if (str_contains($m, 'réservation') || str_contains($m, 'reservation') || str_contains($m, 'réserver') || str_contains($m, 'booking')) {
            return 'help_booking';
        }
        if (str_contains($m, 'commission') || str_contains($m, 'frais')) {
            return 'help_commission';
        }
        if (str_contains($m, 'session') || str_contains($m, 'résa') || str_contains($m, 'mes réservations')) {
            return 'my_sessions';
        }
        if (str_contains($m, 'support') || str_contains($m, 'contact') || str_contains($m, 'aide')) {
            return 'support';
        }
        return 'general_question';
    }

    private function getFallbackReply(string $intent, string $message): string
    {
        try {
            if ($intent === 'find_expert') {
                return $this->configModel->getDefaultResponse('find_expert') ?: 'Voici des experts disponibles.';
            }
            if ($intent === 'create_task') {
                return $this->configModel->getDefaultResponse('create_task') ?: 'Pour créer une demande, connectez-vous et indiquez la durée estimée (en heures).';
            }
            if (str_starts_with($intent, 'help_')) {
                $topic = str_replace('help_', '', $intent);
                $help = $this->configModel->getHelpContent($topic);
                if ($help) {
                    return $help;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return '';
    }

    public function getQuickActions(): array
    {
        return self::QUICK_ACTIONS;
    }
}
