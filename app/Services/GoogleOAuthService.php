<?php
/**
 * GLOBALO — Google OAuth 2.0 (implémentation native cURL, sans dépendance Composer)
 *
 * Flow :
 *   1. getAuthUrl()       → URL de consentement Google
 *   2. exchangeCode()     → échange le code contre un access_token
 *   3. getUserInfo()      → récupère email, name, picture depuis Google
 */

declare(strict_types=1);

namespace App\Services;

class GoogleOAuthService
{
    private const AUTH_URL     = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL    = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $this->clientId     = defined('GOOGLE_CLIENT_ID')     ? GOOGLE_CLIENT_ID     : '';
        $this->clientSecret = defined('GOOGLE_CLIENT_SECRET') ? GOOGLE_CLIENT_SECRET : '';
        $this->redirectUri  = rtrim(defined('BASE_URL') ? BASE_URL : '', '/') . '/auth/google-callback';
    }

    /** Vérifie si les identifiants sont configurés. */
    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    /** Retourne l'URL de redirection vers Google Consent Screen. */
    public function getAuthUrl(string $state): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id'             => $this->clientId,
            'redirect_uri'          => $this->redirectUri,
            'response_type'         => 'code',
            'scope'                 => 'openid email profile',
            'state'                 => $state,
            'access_type'           => 'online',
            'prompt'                => 'select_account',
        ]);
    }

    /**
     * Échange le code d'autorisation contre un access_token.
     * @return array{access_token:string,...}|null null en cas d'échec
     */
    public function exchangeCode(string $code): ?array
    {
        $data = [
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code',
        ];

        return $this->curlPost(self::TOKEN_URL, http_build_query($data), [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
    }

    /**
     * Récupère le profil Google via l'access_token.
     * @return array{sub:string, email:string, name:string, given_name:string, family_name:string, picture:string, email_verified:bool}|null
     */
    public function getUserInfo(string $accessToken): ?array
    {
        $ch = curl_init(self::USERINFO_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($err || !is_string($body)) {
            error_log('[GoogleOAuth] getUserInfo cURL error: ' . $err);
            return null;
        }

        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /** POST cURL avec corps encodé, retourne le JSON décodé ou null. */
    private function curlPost(string $url, string $body, array $headers = []): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err || !is_string($response)) {
            error_log('[GoogleOAuth] curlPost error: ' . $err);
            return null;
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }
}
