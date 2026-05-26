<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Filtre le contenu des messages pour bloquer l'échange de coordonnées (téléphone, email, liens).
 */
class MessageContentFilterService
{
    /** Détecte numéros de téléphone (avec ou sans espaces, points, tirets). */
    private const PATTERN_PHONE = '/(?:\+?\d{1,4}[\s.-]?)?(?:\(?\d{2,4}\)?[\s.-]?)?\d{2,4}[\s.-]?\d{2,4}[\s.-]?\d{2,4}(?:[\s.-]?\d{2,4})?/';

    /** Détecte adresses email. */
    private const PATTERN_EMAIL = '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/';

    /** Détecte URLs (http, https, www). */
    private const PATTERN_URL = '/(?:https?:\/\/|www\.)[^\s<>"\']+/i';

    /**
     * Vérifie si le texte contient des coordonnées ou liens interdits.
     * @return array{allowed: bool, reason: string}
     */
    public static function check(string $contenu): array
    {
        $text = trim($contenu);
        if ($text === '') {
            return ['allowed' => true, 'reason' => ''];
        }

        if (preg_match(self::PATTERN_PHONE, $text)) {
            return ['allowed' => false, 'reason' => 'L\'échange de numéros de téléphone n\'est pas autorisé dans les conversations.'];
        }
        if (preg_match(self::PATTERN_EMAIL, $text)) {
            return ['allowed' => false, 'reason' => 'L\'échange d\'adresses email n\'est pas autorisé dans les conversations.'];
        }
        if (preg_match(self::PATTERN_URL, $text)) {
            return ['allowed' => false, 'reason' => 'L\'envoi de liens externes n\'est pas autorisé dans les conversations.'];
        }

        return ['allowed' => true, 'reason' => ''];
    }
}
