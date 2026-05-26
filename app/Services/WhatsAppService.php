<?php

declare(strict_types=1);

namespace App\Services;

/**
 * WhatsApp Cloud API (Meta) — Envoi de messages
 * Doc : https://developers.facebook.com/docs/whatsapp/cloud-api
 */
class WhatsAppService
{
    private string $accessToken;
    private string $phoneNumberId;
    private string $apiVersion;

    private const BASE_URL = 'https://graph.facebook.com';

    public function __construct()
    {
        $this->accessToken   = getenv('WHATSAPP_ACCESS_TOKEN')   ?: (defined('WHATSAPP_ACCESS_TOKEN')   ? WHATSAPP_ACCESS_TOKEN   : '');
        $this->phoneNumberId = getenv('WHATSAPP_PHONE_NUMBER_ID') ?: (defined('WHATSAPP_PHONE_NUMBER_ID') ? WHATSAPP_PHONE_NUMBER_ID : '');
        $this->apiVersion    = 'v19.0';
    }

    public function isConfigured(): bool
    {
        return $this->accessToken !== '' && $this->phoneNumberId !== '';
    }

    /**
     * Envoie un message texte à un numéro WhatsApp
     *
     * @param string $to    Numéro au format international sans + (ex: 22376000000)
     * @param string $text  Message à envoyer
     */
    public function sendText(string $to, string $text): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }

        // WhatsApp limite à 4096 caractères
        if (strlen($text) > 4000) {
            $text = substr($text, 0, 3997) . '...';
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->sanitizePhone($to),
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => $text,
            ],
        ];

        return $this->post('/messages', $payload);
    }

    /**
     * Envoie un message avec boutons de réponse rapide
     *
     * @param string $to
     * @param string $body     Corps du message
     * @param array  $buttons  [['id'=>'btn1','title'=>'Trouver un expert'], ...]  (max 3)
     */
    public function sendButtons(string $to, string $body, array $buttons): bool
    {
        if (!$this->isConfigured() || empty($buttons)) {
            return $this->sendText($to, $body);
        }

        $rows = array_slice($buttons, 0, 3);
        $btns = array_map(fn($b) => [
            'type'  => 'reply',
            'reply' => [
                'id'    => $b['id']    ?? uniqid(),
                'title' => substr($b['title'] ?? 'Option', 0, 20),
            ],
        ], $rows);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->sanitizePhone($to),
            'type'              => 'interactive',
            'interactive'       => [
                'type' => 'button',
                'body' => ['text' => $body],
                'action' => ['buttons' => $btns],
            ],
        ];

        return $this->post('/messages', $payload);
    }

    /**
     * Envoie un message avec liste de choix (menu)
     *
     * @param string $to
     * @param string $body
     * @param string $buttonLabel  Label du bouton d'ouverture du menu
     * @param array  $sections     [['title'=>'...', 'rows'=>[['id'=>'...','title'=>'...','description'=>'...']]]]
     */
    public function sendList(string $to, string $body, string $buttonLabel, array $sections): bool
    {
        if (!$this->isConfigured()) {
            return $this->sendText($to, $body);
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->sanitizePhone($to),
            'type'              => 'interactive',
            'interactive'       => [
                'type' => 'list',
                'body' => ['text' => $body],
                'action' => [
                    'button'   => substr($buttonLabel, 0, 20),
                    'sections' => $sections,
                ],
            ],
        ];

        return $this->post('/messages', $payload);
    }

    /**
     * Marque un message comme "lu"
     */
    public function markAsRead(string $messageId): bool
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status'            => 'read',
            'message_id'        => $messageId,
        ];
        return $this->post('/messages', $payload);
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers privés
    // ──────────────────────────────────────────────────────────────

    private function post(string $path, array $payload): bool
    {
        $url = self::BASE_URL . '/' . $this->apiVersion . '/' . $this->phoneNumberId . $path;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->accessToken,
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response) {
            $data = json_decode((string)$response, true);
            if (!empty($data['error'])) {
                error_log('[WhatsApp] Erreur API Meta : ' . json_encode($data['error']));
                return false;
            }
        }

        return $httpCode >= 200 && $httpCode < 300;
    }

    private function sanitizePhone(string $phone): string
    {
        // Enlever espaces, tirets, +
        $clean = preg_replace('/[^0-9]/', '', $phone);
        // Ajouter 221 (Sénégal) si numéro sans indicatif (commence par 7 ou 3)
        if (strlen($clean) === 9 && in_array($clean[0], ['7', '3'], true)) {
            $clean = '221' . $clean;
        }
        return $clean;
    }
}
