<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Client IA multi-provider : OpenAI, Google Gemini, Mistral.
 * Gemini 1.5 Flash = GRATUIT (quota généreux).
 * Mistral Small  = GRATUIT (1 Go tokens/mois sur La Plateforme).
 */
class OpenAiService
{
    private string $apiKey;
    private string $model;
    private int    $maxTokens;
    private string $provider; // openai | gemini | mistral

    /** Map provider → modèle par défaut */
    private const DEFAULT_MODELS = [
        'openai'  => 'gpt-3.5-turbo',
        'gemini'  => 'gemini-2.0-flash',
        'mistral' => 'mistral-small-latest',
    ];

    /** Endpoints */
    private const ENDPOINTS = [
        'openai'  => 'https://api.openai.com/v1/chat/completions',
        'gemini'  => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={key}',
        'mistral' => 'https://api.mistral.ai/v1/chat/completions',
    ];

    public function __construct(
        ?string $apiKey   = null,
        string  $model    = '',
        int     $maxTokens = 600,
        string  $provider  = ''
    ) {
        // Détection automatique du provider si non précisé
        $this->provider  = $provider ?: $this->detectProvider($apiKey ?? '');
        $this->apiKey    = $apiKey ?? $this->resolveApiKey($this->provider);
        $this->model     = $model  ?: self::DEFAULT_MODELS[$this->provider] ?? 'gpt-3.5-turbo';
        $this->maxTokens = $maxTokens;
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function getProvider(): string { return $this->provider; }
    public function getModel(): string    { return $this->model; }

    /**
     * Envoie les messages au provider IA et retourne le contenu + intent extrait.
     *
     * @param  array<int,array{role:string,content:string}> $messages
     * @return array{content:string, intent:string|null, extracted:array|null}
     */
    public function chat(array $messages, string $systemPrompt): array
    {
        if (!$this->isConfigured()) {
            return ['content' => '', 'intent' => 'general_question', 'extracted' => null];
        }

        if ($this->provider === 'gemini') {
            $raw = $this->callGemini($messages, $systemPrompt);
        } elseif ($this->provider === 'mistral') {
            $raw = $this->callOpenAiCompatible($messages, $systemPrompt, self::ENDPOINTS['mistral']);
        } else {
            $raw = $this->callOpenAiCompatible($messages, $systemPrompt, self::ENDPOINTS['openai']);
        }

        return $this->parseResponse($raw);
    }

    /**
     * Génère du texte simple (pour la publication sociale).
     */
    public function generate(string $prompt, int $maxTokens = 500): string
    {
        if (!$this->isConfigured()) {
            return '';
        }
        $result = $this->chat([], $prompt); // prompt en system, pas de messages
        return $result['content'];
    }

    // ──────────────────────────────────────────────────────────────
    // Providers
    // ──────────────────────────────────────────────────────────────

    /** OpenAI-compatible (OpenAI + Mistral) */
    private function callOpenAiCompatible(array $messages, string $systemPrompt, string $endpoint): string
    {
        $payload = [
            'model'      => $this->model,
            'max_tokens' => $this->maxTokens,
            'messages'   => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                array_values($messages)
            ),
        ];

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return '';
        $data = json_decode((string)$response, true);
        return trim($data['choices'][0]['message']['content'] ?? '');
    }

    /** Google Gemini */
    private function callGemini(array $messages, string $systemPrompt): string
    {
        $endpoint = str_replace(
            ['{model}', '{key}'],
            [$this->model, $this->apiKey],
            self::ENDPOINTS['gemini']
        );

        // Gemini accepte systemInstruction séparé
        $contents = [];
        foreach ($messages as $m) {
            $role = $m['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = ['role' => $role, 'parts' => [['text' => $m['content']]]];
        }
        if (empty($contents)) {
            $contents[] = ['role' => 'user', 'parts' => [['text' => $systemPrompt]]];
            $systemPrompt = '';
        }

        $payload = [
            'contents'         => $contents,
            'generationConfig' => ['maxOutputTokens' => $this->maxTokens, 'temperature' => 0.7],
        ];
        if ($systemPrompt !== '') {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemPrompt]]];
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        ]);
        $response  = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError !== '') {
            return 'Erreur réseau : ' . $curlError;
        }
        if (!$response) {
            return 'Aucune réponse du serveur Gemini (HTTP ' . $httpCode . ').';
        }

        $data = json_decode((string)$response, true);

        // Retourner le texte si OK
        $text = trim($data['candidates'][0]['content']['parts'][0]['text'] ?? '');
        if ($text !== '') {
            return $text;
        }

        // Retourner le message d'erreur Gemini pour diagnostic
        if (!empty($data['error']['message'])) {
            return '⚠️ Gemini : ' . $data['error']['message'];
        }

        // Filtres de sécurité Gemini
        if (!empty($data['candidates'][0]['finishReason']) && $data['candidates'][0]['finishReason'] !== 'STOP') {
            return '⚠️ Gemini a bloqué la réponse (raison : ' . $data['candidates'][0]['finishReason'] . ').';
        }

        return '';
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    private function parseResponse(string $raw): array
    {
        if ($raw === '') {
            return ['content' => 'Désolé, le service IA est temporairement indisponible. Réessayez dans quelques instants.', 'intent' => 'general_question', 'extracted' => null];
        }

        // Quota dépassé
        if (strpos($raw, 'RESOURCE_EXHAUSTED') !== false || strpos($raw, 'quota') !== false) {
            return ['content' => '⚠️ **Quota IA dépassé.** Le quota gratuit de votre clé API est épuisé. Activez la facturation sur Google Cloud ou utilisez une clé Mistral gratuite (console.mistral.ai).', 'intent' => 'general_question', 'extracted' => null];
        }

        // Erreur réseau ou API
        if (strpos($raw, 'Erreur réseau') !== false || strpos($raw, 'Erreur cURL') !== false) {
            return ['content' => '⚠️ **Erreur de connexion IA.** ' . $raw, 'intent' => 'general_question', 'extracted' => null];
        }

        // Erreur Gemini avec message
        if (strpos($raw, '⚠️ Gemini :') !== false) {
            return ['content' => $raw, 'intent' => 'general_question', 'extracted' => null];
        }

        $content   = $raw;
        $intent    = null;
        $extracted = null;

        if (preg_match('/^INTENT:\s*(\w+)/im', $content, $m)) {
            $intent  = strtolower($m[1]);
            $content = trim(preg_replace('/^INTENT:\s*\w+\s*\n?/im', '', $content, 1));
        }
        if (preg_match('/EXTRACTED:\s*(\{[^}]+\})/s', $content, $m)) {
            $extracted = json_decode($m[1], true);
            if (is_array($extracted)) {
                $content = trim(str_replace($m[0], '', $content));
            }
        }

        return [
            'content'   => $content ?: 'Je n\'ai pas pu générer une réponse.',
            'intent'    => $intent,
            'extracted' => $extracted,
        ];
    }

    private function detectProvider(string $key): string
    {
        if (strpos($key, 'sk-') === 0) return 'openai';
        if (strpos($key, 'AI') === 0)  return 'gemini';
        return 'openai'; // fallback (Mistral, clés alphanumériques)
    }

    private function resolveApiKey(string $provider): string
    {
        if ($provider === 'gemini') {
            return getenv('GEMINI_API_KEY') ?: (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');
        }
        if ($provider === 'mistral') {
            return getenv('MISTRAL_API_KEY') ?: (defined('MISTRAL_API_KEY') ? MISTRAL_API_KEY : '');
        }
        return getenv('OPENAI_API_KEY') ?: (defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '');
    }
}
