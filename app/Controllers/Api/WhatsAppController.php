<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Router;
use App\Services\WhatsAppService;
use App\Services\WhatsAppAiService;

/**
 * Webhook WhatsApp Cloud API (Meta)
 *
 * GET  /api/whatsapp/webhook  → Vérification Meta (une seule fois lors du setup)
 * POST /api/whatsapp/webhook  → Réception des messages entrants
 */
class WhatsAppController extends Controller
{
    private WhatsAppService   $whatsapp;
    private WhatsAppAiService $aiService;

    public function __construct(Router $router)
    {
        parent::__construct($router);
        $this->whatsapp  = new WhatsAppService();
        $this->aiService = new WhatsAppAiService();
    }

    /**
     * GET /api/whatsapp/webhook
     * Vérification du webhook par Meta lors de la configuration
     */
    public function verify(): void
    {
        $mode      = $_GET['hub_mode']          ?? $_GET['hub.mode']          ?? '';
        $token     = $_GET['hub_verify_token']  ?? $_GET['hub.verify_token']  ?? '';
        $challenge = $_GET['hub_challenge']     ?? $_GET['hub.challenge']     ?? '';

        $verifyToken = getenv('WHATSAPP_WEBHOOK_VERIFY_TOKEN')
            ?: (defined('WHATSAPP_WEBHOOK_VERIFY_TOKEN') ? WHATSAPP_WEBHOOK_VERIFY_TOKEN : '');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            http_response_code(200);
            echo $challenge;
            exit;
        }

        http_response_code(403);
        echo json_encode(['error' => 'Token de vérification invalide']);
        exit;
    }

    /**
     * POST /api/whatsapp/webhook
     * Réception et traitement des messages WhatsApp
     */
    public function receive(): void
    {
        // Lire le body JSON
        $input = file_get_contents('php://input');
        $data  = json_decode($input, true);

        // Répondre 200 immédiatement à Meta (évite les retentatives)
        http_response_code(200);
        header('Content-Type: application/json');

        if (empty($data) || ($data['object'] ?? '') !== 'whatsapp_business_account') {
            echo json_encode(['status' => 'ignored']);
            exit;
        }

        // Parcourir les entrées
        foreach ($data['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'messages') {
                    continue;
                }

                $value    = $change['value'] ?? [];
                $messages = $value['messages'] ?? [];

                foreach ($messages as $message) {
                    $this->handleMessage($message, $value);
                }
            }
        }

        echo json_encode(['status' => 'ok']);
        exit;
    }

    /**
     * Traite un message individuel
     */
    private function handleMessage(array $message, array $context): void
    {
        $type      = $message['type']       ?? '';
        $messageId = $message['id']         ?? '';
        $from      = $message['from']       ?? '';
        $timestamp = $message['timestamp']  ?? '';

        if (empty($from) || empty($messageId)) {
            return;
        }

        // Marquer comme lu
        $this->whatsapp->markAsRead($messageId);

        // Extraire le texte selon le type de message
        $text = match($type) {
            'text'        => $message['text']['body'] ?? '',
            'interactive' => $message['interactive']['button_reply']['title']
                          ?? $message['interactive']['list_reply']['title']
                          ?? '',
            'button'      => $message['button']['text'] ?? '',
            default       => '',
        };

        if (empty($text)) {
            // Message non-texte (image, audio, etc.) → réponse générique
            $this->whatsapp->sendText($from,
                "Je ne peux traiter que les messages texte pour l'instant. 😊\n\nEnvoyez-moi un message écrit et je vous répondrai avec plaisir !"
            );
            return;
        }

        // Traiter les commandes spéciales
        $lowerText = strtolower(trim($text));
        if (in_array($lowerText, ['stop', 'arrêt', 'arret', 'désabonner', 'desabonner'], true)) {
            $this->whatsapp->sendText($from,
                "Vous avez été désinscrit des notifications GLOBALO. À bientôt ! 👋\n\nEnvoyez n'importe quel message pour recommencer."
            );
            return;
        }

        // Commandes de démarrage
        if (in_array($lowerText, ['bonjour', 'hi', 'hello', 'salut', 'start', 'démarrer', 'demarrer', '0'], true)) {
            $welcome = $this->aiService->getWelcomeMessage();
            $this->whatsapp->sendButtons($from, $welcome, [
                ['id' => 'find_expert',    'title' => '🔍 Trouver un expert'],
                ['id' => 'learn_globalo',  'title' => 'ℹ️ En savoir plus'],
                ['id' => 'contact_human',  'title' => '👤 Parler à un humain'],
            ]);
            return;
        }

        // Boutons rapides spéciaux
        if ($lowerText === 'find_expert' || $lowerText === '🔍 trouver un expert') {
            $this->whatsapp->sendList($from,
                "Quel type d'expert recherchez-vous ? 🎯",
                'Voir les catégories',
                [[
                    'title' => 'Catégories disponibles',
                    'rows'  => [
                        ['id' => 'expert_cours',       'title' => '📚 Cours particuliers',    'description' => 'Maths, français, anglais, informatique...'],
                        ['id' => 'expert_conseil',     'title' => '💼 Conseil business',       'description' => 'Stratégie, juridique, RH...'],
                        ['id' => 'expert_compta',      'title' => '📊 Comptabilité & Finance', 'description' => 'Gestion, fiscalité, audit...'],
                        ['id' => 'expert_formation',   'title' => '🎓 Formation pro',          'description' => 'Certifications, reconversion...'],
                        ['id' => 'expert_informatique','title' => '💻 Informatique',           'description' => 'Dev, réseaux, cybersécurité...'],
                    ],
                ]]
            );
            return;
        }

        if ($lowerText === 'contact_human' || $lowerText === '👤 parler à un humain') {
            $this->whatsapp->sendText($from,
                "Je vais vous mettre en contact avec notre équipe. 👤\n\n📧 Email : globalo@secogesarl.com\n🌐 Site : https://globalo.secogesarl.com\n\nNotre équipe vous répondra dans les 24h ouvrées. Merci pour votre patience ! 🙏"
            );
            return;
        }

        // Traitement IA général
        $response = $this->aiService->processMessage($from, $text, $messageId);

        // Envoi de la réponse
        $this->whatsapp->sendText($from, $response);
    }
}
