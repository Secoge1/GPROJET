<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\UserTrackingModel;

/**
 * Pays / téléphone pour faciliter le checkout PayTech (page hébergée).
 * Détection : géolocalisation IP (CDN) → champ profil utilisateur → indicatif téléphone.
 */
final class PayTechCheckoutAssistant
{
    private const PREFIX_TO_ISO = [
        '221' => 'SN',
        '223' => 'ML',
        '225' => 'CI',
        '227' => 'NE',
        '228' => 'TG',
        '229' => 'BJ',
    ];

    private const ISO_DIAL_PREFIX = [
        'SN' => '221',
        'ML' => '223',
        'CI' => '225',
        'NE' => '227',
        'TG' => '228',
        'BJ' => '229',
    ];

    /**
     * Noms PayTech conformes à la doc (liste des méthodes).
     * @see https://doc.paytech.sn/doc_paytech.php
     */
    private const TARGET_PAYMENT_BY_ISO = [
        'SN' => 'Wave, Orange Money, Free Money',
        'ML' => 'Orange Money ML, Moov Money ML, Wave',
        'CI' => 'Orange Money, Mtn Money CI, Moov Money CI, Wave CI',
        'BJ' => 'Moov Money BJ, Mtn Money BJ',
    ];

    /** Textes d’aide utilisateur selon pays détecté (la page PayTech reste multicarte). */
    private const HUMAN_HINT_BY_ISO = [
        'SN' => 'Sénégal · Wave, Orange Money et Free Money figurent généralement en premier.',
        'ML' => 'Mali · Orange Money ML, Moov Money ML et Wave.',
        'CI' => 'Côte d’Ivoire · Orange Money, MTN, Moov et Wave CI.',
        'NE' => 'Niger · choisissez votre opérateur sur PayTech.',
        'TG' => 'Togo · Moov Money, Tigo Cash, etc.',
        'BJ' => 'Bénin · Moov Money BJ et MTN Money BJ.',
    ];

    private const GENERIC_HINT = 'Sur PayTech, choisissez votre opérateur Mobile Money ou Carte Bancaire. La devise utilisée pour cette commande est le XOF.';

    /** @return array<string,string> indicatif (chiffres, sans +) => libellé pour <select> */
    public function dialOptionsForSelect(): array
    {
        return [
            '223' => 'Mali (+223)',
            '221' => 'Sénégal (+221)',
            '225' => 'Côte d\'Ivoire (+225)',
            '227' => 'Niger (+227)',
            '228' => 'Togo (+228)',
            '229' => 'Bénin (+229)',
        ];
    }

    public function isoForDialDigits(string $dialDigits): ?string
    {
        $d = preg_replace('/\D+/', '', $dialDigits) ?? '';

        return self::PREFIX_TO_ISO[$d] ?? null;
    }

    public function defaultDialForIso(?string $iso2): string
    {
        if ($iso2 === null || $iso2 === '') {
            return '223';
        }
        $u = strtoupper($iso2);

        return self::ISO_DIAL_PREFIX[$u] ?? '223';
    }

    /**
     * @param array{pn:string,nn:string} $pair
     * @return array{dial:string,local:string}
     */
    public function splitPhonePairForForm(array $pair): array
    {
        $pn = trim((string) ($pair['pn'] ?? ''));
        $nn = trim((string) ($pair['nn'] ?? ''));
        if ($pn === '' || !str_starts_with($pn, '+')) {
            return ['dial' => '223', 'local' => preg_replace('/\D+/', '', $nn)];
        }
        [, $len] = $this->guessIsoAndDialLength($pn);
        if ($len <= 0) {
            return ['dial' => '223', 'local' => preg_replace('/\D+/', '', $nn)];
        }
        $dial = substr(substr($pn, 1), 0, $len);

        return ['dial' => $dial, 'local' => $nn !== '' ? $nn : preg_replace('/\D+/', '', substr(substr($pn, 1), $len))];
    }

    private const NAME_TO_ISO = [
        'sénégal' => 'SN', 'senegal' => 'SN', 'sn' => 'SN',
        'mali' => 'ML', 'ml' => 'ML',
        'côte d\'ivoire' => 'CI', 'cote d\'ivoire' => 'CI', 'cote divoire' => 'CI',
        'ivory coast' => 'CI', 'ci' => 'CI',
        'niger' => 'NE', 'ne' => 'NE',
        'togo' => 'TG', 'tg' => 'TG',
        'bénin' => 'BJ', 'benin' => 'BJ', 'bj' => 'BJ',
    ];

    /** @param array<string, mixed>|null $user Ligne utilisateur (nom, prenom, telephone, pays…) */
    public function resolveCountryIso2(?array $user): ?string
    {
        $fromGeo = UserTrackingModel::getCountryFromRequest();
        if ($fromGeo !== null && strlen($fromGeo) === 2) {
            return strtoupper($fromGeo);
        }

        $pays = isset($user['pays']) ? trim((string) $user['pays']) : '';
        if ($pays !== '') {
            $isoFromProfile = self::countryLabelOrCodeToIso($pays);
            if ($isoFromProfile !== null) {
                return $isoFromProfile;
            }
        }

        return self::guessIsoFromTelephone(isset($user['telephone']) ? (string) $user['telephone'] : '');
    }

    public function contextualHint(?string $iso2): string
    {
        if ($iso2 !== null && $iso2 !== '' && isset(self::HUMAN_HINT_BY_ISO[$iso2])) {
            return self::HUMAN_HINT_BY_ISO[$iso2];
        }

        return self::GENERIC_HINT;
    }

    /**
     * Filtre optionnel envoyé à l’API uniquement lorsque les libellés PayTech sont certains ;
     * sinon retourner null pour laisser toutes les méthodes disponibles sur la page PayTech.
     */
    public function targetPaymentCsvForApi(?string $iso2): ?string
    {
        if ($iso2 === null || $iso2 === '') {
            return null;
        }
        $iso = strtoupper($iso2);
        return self::TARGET_PAYMENT_BY_ISO[$iso] ?? null;
    }

    /**
     * @return array{pn: string, nn: string}|null
     */
    public function normalizePhoneForCheckout(?string $telephoneRaw, ?string $countryIsoFallback): ?array
    {
        $normalized = trim((string) $telephoneRaw);
        if ($normalized === '') {
            return null;
        }

        $digitsOnly = preg_replace('/\D+/', '', $normalized) ?? '';

        // Déjà au format international
        if (str_starts_with($normalized, '+')) {
            $pn = $this->sanitizeInternational($normalized);
            if ($pn === null || strlen($pn) < 5) {
                return null;
            }
            [$iso] = $this->guessIsoAndDialLength($pn);
            if ($iso === null) {
                return null;
            }
            $nn = $this->nationalFromPn($pn, $iso);

            return $nn !== '' ? ['pn' => $pn, 'nn' => $nn] : null;
        }

        // 00…
        if (str_starts_with($digitsOnly, '00') && strlen($digitsOnly) > 4) {
            $pn = '+' . substr($digitsOnly, 2);
            [$iso] = $this->guessIsoAndDialLength($pn);
            if ($iso === null) {
                return null;
            }
            $nn = $this->nationalFromPn($pn, $iso);

            return $nn !== '' ? ['pn' => $pn, 'nn' => $nn] : null;
        }

        // Chiffres seuls sans + : préfixer avec l’indicatif résolu ou fallback pays
        if ($digitsOnly !== '' && ctype_digit($digitsOnly)) {
            $iso       = ($countryIsoFallback !== null && $countryIsoFallback !== '') ? strtoupper($countryIsoFallback) : null;
            $dialDigits = ($iso !== null && isset(self::ISO_DIAL_PREFIX[$iso]))
                ? self::ISO_DIAL_PREFIX[$iso]
                : null;
            if ($dialDigits === null) {
                return null;
            }
            // Indicatif déjà présent (ordre préfixes du plus long au plus court pour éviter les ambiguïtés).
            $prefixKeys = array_keys(self::PREFIX_TO_ISO);
            usort(
                $prefixKeys,
                static fn(string $a, string $b): int => strlen($b) <=> strlen($a)
            );
            foreach ($prefixKeys as $pfx) {
                if (str_starts_with($digitsOnly, $pfx) && strlen($digitsOnly) > strlen($pfx)) {
                    $pn  = '+' . $digitsOnly;
                    $isoKey = self::PREFIX_TO_ISO[$pfx];
                    $nn  = $this->nationalFromPn($pn, $isoKey);

                    return $nn !== '' ? ['pn' => $pn, 'nn' => $nn] : null;
                }
            }
            $pn = '+' . $dialDigits . $digitsOnly;
            $nn = $this->nationalFromPn($pn, $iso);

            return $nn !== '' ? ['pn' => $pn, 'nn' => $nn] : null;
        }

        return null;
    }

    /** Prénom Nom pour pré-remplissage PayTech (paramètre fn). */
    public function fullName(?array $user): string
    {
        if ($user === null) {
            return '';
        }
        $p = trim((string) ($user['prenom'] ?? ''));
        $n = trim((string) ($user['nom'] ?? ''));
        $name = trim($p . ' ' . $n);

        return $name;
    }

    /**
     * Enrichit l’URL renvoyée par PayTech (query pn, nn, fn, nac).
     *
     * @see https://doc.paytech.sn/doc_paytech.php (pré-remplissage checkout)
     */
    public function appendCheckoutPrefetchParams(string $redirectUrl, ?array $phonePair, ?string $fullName): string
    {
        if ($redirectUrl === '') {
            return $redirectUrl;
        }
        if (!filter_var($redirectUrl, FILTER_VALIDATE_URL)) {
            return $redirectUrl;
        }
        $frag = parse_url($redirectUrl, PHP_URL_FRAGMENT);
        $baseUrl = $frag !== false && $frag !== '' && $frag !== null
            ? substr($redirectUrl, 0, strpos($redirectUrl, '#'))
            : $redirectUrl;
        $parts = parse_url($baseUrl);
        if ($parts === false || empty($parts['scheme'])) {
            return $redirectUrl;
        }
        $existing = [];
        if (!empty($parts['query'])) {
            parse_str((string) $parts['query'], $existing);
        }
        $add = [];

        // PayTech conseille nac=1 pour désactiver l’auto-submit sur carte ; on laisse à 0 pour Mobile Money pré-rempli
        // (l’utilisateur valide encore sur leur interface).
        if ($phonePair !== null && $phonePair['pn'] !== '' && $phonePair['nn'] !== '') {
            $add['pn'] = $phonePair['pn'];
            $add['nn'] = $phonePair['nn'];
            $add['nac'] = '0';
        }
        $fn = trim((string) $fullName);
        if ($fn !== '') {
            $add['fn'] = $fn;
        }
        $merged       = array_merge($existing, $add);
        $parts['query'] = http_build_query($merged);

        return $this->unparseUrl($parts) . ($frag !== false && $frag !== '' && $frag !== null ? '#' . $frag : '');
    }

    private function unparseUrl(array $parsed): string
    {
        $scheme   = $parsed['scheme'] ?? 'https';
        $host     = $parsed['host'] ?? '';
        $port     = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $userInfo = isset($parsed['user']) ? $parsed['user'] . ($parsed['pass'] ?? '') . '@' : '';
        $path     = $parsed['path'] ?? '';
        $query    = isset($parsed['query']) && $parsed['query'] !== '' ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $scheme . '://' . $userInfo . $host . $port . $path . $query . $fragment;
    }

    private static function guessIsoFromTelephone(?string $raw): ?string
    {
        $t = trim((string) $raw);
        if ($t === '') {
            return null;
        }
        $digitsQuick = preg_replace('/\D+/', '', str_replace('+', '', trim($t))) ?? '';
        if (!str_starts_with(trim($t), '+') && str_starts_with($digitsQuick, '00')) {
            $t = '+' . substr($digitsQuick, 2);
        } elseif (!str_starts_with(trim($t), '+') && ctype_digit(str_replace([' ', '-', '(', ')'], '', $t))) {
            return null;
        }

        $pn = self::sanitizeInternationalLite($t);
        if ($pn === null) {
            return null;
        }
        [$iso] = (new self())->guessIsoAndDialLength($pn);

        return $iso;
    }

    private function sanitizeInternational(string $tel): ?string
    {
        return self::sanitizeInternationalLite($tel);
    }

    private static function sanitizeInternationalLite(string $tel): ?string
    {
        $t = preg_replace('/\s+/', '', trim($tel)) ?? '';
        if (!str_starts_with($t, '+')) {
            return null;
        }
        $rest = substr($t, 1);
        $d    = preg_replace('/\D+/', '', $rest) ?? '';
        if ($d === '') {
            return null;
        }

        return '+' . $d;
    }

    /** @return array{0:?string,1:int} ISO et longueur de l’indicatif téléphonique (nombre de chiffres après +) */
    private function guessIsoAndDialLength(string $pn): array
    {
        $digits = preg_replace('/\D/', '', substr($pn, 1)) ?? '';
        for ($len = 3; $len >= 1; $len--) {
            $prefixSlice = substr($digits, 0, $len);
            if ($prefixSlice !== '' && isset(self::PREFIX_TO_ISO[$prefixSlice])) {
                return [self::PREFIX_TO_ISO[$prefixSlice], $len];
            }
        }

        return [null, 0];
    }

    private function nationalFromPn(string $pn, ?string $_isoIgnored): string
    {
        [, $len] = $this->guessIsoAndDialLength($pn);
        if ($len > 0) {
            return substr(substr($pn, 1), $len); // chiffres nationaux après l’indicatif
        }

        return '';
    }

    private static function countryLabelOrCodeToIso(string $label): ?string
    {
        $k = strtolower(trim($label));
        if (strlen($k) === 2 && ctype_alpha($k)) {
            return strtoupper($k);
        }

        return self::NAME_TO_ISO[$k] ?? null;
    }
}
