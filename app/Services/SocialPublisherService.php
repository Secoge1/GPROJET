<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ParametreModel;
use App\Core\Database;

/**
 * GLOBALO — Publication automatique IA sur Facebook & LinkedIn.
 *
 * Fonctionne avec :
 *   - Facebook Graph API (gratuit, Page Access Token permanent)
 *   - LinkedIn UGC Posts API v2 (gratuit, Organization)
 *   - IA : Gemini 1.5 Flash (gratuit) ou OpenAI / Mistral
 */
class SocialPublisherService
{
    private ParametreModel $param;
    private OpenAiService  $ai;

    // Sujets programmés par défaut (modifiable en admin)
    private const PLANNING_DEFAUT = [
        'lundi'    => "Comment trouver le bon expert digital en Afrique de l'Ouest avec GLOBALO ?",
        'mercredi' => "Les avantages d'un abonnement GLOBALO pour les professionnels au Mali, Côte d'Ivoire, Sénégal, Bénin et Niger",
        'vendredi' => "Développez votre activité freelance et gagnez plus grâce à GLOBALO",
        'samedi'   => "5 conseils pour rédiger une demande de mission qui attire les meilleurs experts",
    ];

    public function __construct()
    {
        $this->param = new ParametreModel();
        $this->ai    = $this->buildAiService();
    }

    // ──────────────────────────────────────────────────────────────
    // Publication principale
    // ──────────────────────────────────────────────────────────────

    /**
     * Génère et publie un post IA sur tous les réseaux activés.
     * Retourne le résumé des publications.
     */
    public function publierAuto(?string $sujetForce = null): array
    {
        $jourFr  = $this->jourSemaineFr();
        $planning = $this->getPlanningActif();
        $sujet   = $sujetForce ?? ($planning[$jourFr] ?? null);

        if (!$sujet) {
            return ['skipped' => true, 'raison' => "Pas de publication prévue un {$jourFr}."];
        }

        $ton       = $this->param->get('social_ton', 'professionnel et engageant');
        $hashtags  = $this->param->get('social_hashtags', '#GLOBALO #Freelance #AfriqueOuest #Experts #FCFA');
        $contenu   = $this->genererContenu($sujet, $ton, $hashtags);

        if ($contenu === '') {
            return ['error' => 'Échec génération IA.'];
        }

        $result = ['sujet' => $sujet, 'contenu' => $contenu, 'publications' => []];

        // Facebook
        if ($this->param->get('social_fb_enabled', '0') === '1') {
            $fb = $this->publierFacebook($contenu);
            $result['publications']['facebook'] = $fb;
        }

        // LinkedIn
        if ($this->param->get('social_li_enabled', '0') === '1') {
            $li = $this->publierLinkedIn($contenu);
            $result['publications']['linkedin'] = $li;
        }

        // Sauvegarde en base
        $this->logPublication($sujet, $contenu, $result['publications']);

        return $result;
    }

    /**
     * Test : génère le contenu sans publier.
     */
    public function genererApercu(string $sujet): string
    {
        $ton      = $this->param->get('social_ton', 'professionnel');
        $hashtags = $this->param->get('social_hashtags', '#GLOBALO');
        return $this->genererContenu($sujet, $ton, $hashtags);
    }

    // ──────────────────────────────────────────────────────────────
    // Génération IA
    // ──────────────────────────────────────────────────────────────

    private function genererContenu(string $sujet, string $ton, string $hashtags): string
    {
        if (!$this->ai->isConfigured()) {
            // Contenu basique sans IA si pas de clé
            return $this->contenuStatique($sujet, $hashtags);
        }

        $prompt = <<<PROMPT
Tu es un expert en marketing digital pour GLOBALO, une plateforme de mise en relation entre clients et experts freelances disponible au Mali, Côte d'Ivoire, Sénégal, Bénin et Niger. La devise est le FCFA.

Rédige un post de réseaux sociaux ({$ton}) sur le sujet suivant : "{$sujet}"

Règles :
- 3 paragraphes maximum, 200 mots max
- Un appel à l'action clair (ex: "Inscrivez-vous gratuitement sur globalo.secogesarl.com")
- Terminer par les hashtags : {$hashtags}
- Langue : français, ton accessible et professionnel
- Pas de markdown (pas de **gras**, pas de # dans le texte)
- Utilise 1 ou 2 émojis pertinents

Ne génère que le texte du post, rien d'autre.
PROMPT;

        $result = $this->ai->chat([], $prompt);
        return trim($result['content'] ?? '');
    }

    private function contenuStatique(string $sujet, string $hashtags): string
    {
        $url = rtrim(BASE_URL ?? 'https://globalo.secogesarl.com', '/');
        return "🌍 {$sujet}\n\nGLOBALO connecte les clients et les experts freelances en Afrique de l'Ouest. Trouvez l'expert qu'il vous faut en quelques clics.\n\n✅ Rejoignez-nous sur {$url}\n\n{$hashtags}";
    }

    // ──────────────────────────────────────────────────────────────
    // Facebook Graph API
    // ──────────────────────────────────────────────────────────────

    public function publierFacebook(string $message, ?string $imageUrl = null): array
    {
        $pageId    = $this->param->get('social_fb_page_id', '');
        $token     = $this->param->get('social_fb_token', '');

        if (!$pageId || !$token) {
            return ['ok' => false, 'error' => 'Configuration Facebook manquante (Page ID / Token).'];
        }

        if ($imageUrl) {
            $endpoint = "https://graph.facebook.com/v19.0/{$pageId}/photos";
            $data     = ['caption' => $message, 'url' => $imageUrl, 'access_token' => $token];
        } else {
            $endpoint = "https://graph.facebook.com/v19.0/{$pageId}/feed";
            $data     = ['message' => $message, 'access_token' => $token];
        }

        $res = $this->httpPost($endpoint, http_build_query($data));
        $ok  = isset($res['id']);

        return ['ok' => $ok, 'post_id' => $res['id'] ?? null, 'error' => $res['error']['message'] ?? ($ok ? null : 'Erreur inconnue')];
    }

    // ──────────────────────────────────────────────────────────────
    // LinkedIn UGC Posts API
    // ──────────────────────────────────────────────────────────────

    public function publierLinkedIn(string $message): array
    {
        $orgId = $this->param->get('social_li_org_id', '');
        $token = $this->param->get('social_li_token', '');

        if (!$orgId || !$token) {
            return ['ok' => false, 'error' => 'Configuration LinkedIn manquante (Org ID / Token).'];
        }

        $body = [
            'author'          => "urn:li:organization:{$orgId}",
            'lifecycleState'  => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary'    => ['text' => $message],
                    'shareMediaCategory' => 'NONE',
                ],
            ],
            'visibility' => ['com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'],
        ];

        $res = $this->httpPost(
            'https://api.linkedin.com/v2/ugcPosts',
            json_encode($body),
            [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'X-Restli-Protocol-Version: 2.0.0',
            ]
        );

        $ok = isset($res['id']);
        return ['ok' => $ok, 'post_id' => $res['id'] ?? null, 'error' => $res['message'] ?? ($ok ? null : 'Erreur inconnue')];
    }

    // ──────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────

    private function buildAiService(): OpenAiService
    {
        $provider = $this->param->get('social_ai_provider', 'gemini');
        $key      = $this->param->get('social_ai_api_key', '')
                    ?: $this->param->get('chatbot_openai_api_key', '');

        return new OpenAiService($key ?: null, '', 500, $provider);
    }

    private function getPlanningActif(): array
    {
        $custom = $this->param->get('social_planning', '');
        if ($custom) {
            $decoded = json_decode($custom, true);
            if (is_array($decoded)) return $decoded;
        }
        return self::PLANNING_DEFAUT;
    }

    private function jourSemaineFr(): string
    {
        $jours = ['Sunday' => 'dimanche', 'Monday' => 'lundi', 'Tuesday' => 'mardi',
                  'Wednesday' => 'mercredi', 'Thursday' => 'jeudi',
                  'Friday' => 'vendredi', 'Saturday' => 'samedi'];
        return $jours[date('l')] ?? strtolower(date('l'));
    }

    private function logPublication(string $sujet, string $contenu, array $publications): void
    {
        try {
            Database::getInstance()->prepare(
                "INSERT INTO social_publications (sujet, contenu, fb_post_id, li_post_id, publie_le)
                 VALUES (?, ?, ?, ?, NOW())"
            )->execute([
                $sujet,
                $contenu,
                $publications['facebook']['post_id'] ?? null,
                $publications['linkedin']['post_id']  ?? null,
            ]);
        } catch (\Throwable $e) {
            error_log('[SocialPublisher] Erreur log: ' . $e->getMessage());
        }
    }

    private function httpPost(string $url, string $body, array $headers = []): array
    {
        if (empty($headers)) {
            $headers = ['Content-Type: application/x-www-form-urlencoded'];
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response) return [];
        $decoded = json_decode((string)$response, true);
        return is_array($decoded) ? $decoded : [];
    }

    // ──────────────────────────────────────────────────────────────
    // Historique
    // ──────────────────────────────────────────────────────────────

    public function getHistorique(int $limit = 20): array
    {
        try {
            return Database::getInstance()
                ->query("SELECT * FROM social_publications ORDER BY publie_le DESC LIMIT {$limit}")
                ->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
