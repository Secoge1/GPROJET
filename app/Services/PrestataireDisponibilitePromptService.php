<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Models\ProfilExpertModel;
use App\Models\ProfilProfesseurModel;

/**
 * Popup de rappel « passez en disponible » pour experts et professeurs validés.
 */
class PrestataireDisponibilitePromptService
{
    private static ?bool $columnExperts = null;
    private static ?bool $columnProfs = null;

    /** @return array<string, mixed>|null Données pour la modale, ou null si rien à afficher */
    public static function getForCurrentUser(bool $isApp = false): ?array
    {
        if (!Auth::check()) {
            return null;
        }
        $role = (string) Auth::role();
        $userId = (int) Auth::id();
        if ($role === 'expert') {
            return self::buildForExpert($userId, $isApp);
        }
        if ($role === 'professeur') {
            return self::buildForProfesseur($userId, $isApp);
        }

        return null;
    }

    /** @return array{toggle: string, dismiss: string} */
    private static function apiUrls(bool $isApp): array
    {
        $base = rtrim(BASE_URL ?? '', '/');
        if ($isApp) {
            return [
                'toggle'  => $base . '/app/prestataire-disponibilite',
                'dismiss' => $base . '/app/prestataire-disponibilite-rappel',
            ];
        }

        return [
            'toggle'  => $base . '/prestataire/disponibilite',
            'dismiss' => $base . '/prestataire/disponibilite/rappel',
        ];
    }

    /** @return array<string, mixed>|null */
    private static function buildForExpert(int $userId, bool $isApp): ?array
    {
        $profil = (new ProfilExpertModel())->getByUtilisateurId($userId);
        if (!$profil || empty($profil['valide_par_admin'])) {
            return null;
        }
        if (self::shouldHideRappel($profil, 'profils_experts')) {
            return null;
        }

        $disponible = !empty($profil['disponible']);
        $base = rtrim(BASE_URL ?? '', '/');
        $api = self::apiUrls($isApp);

        return [
            'role'           => 'expert',
            'profil_id'      => (int) $profil['id'],
            'disponible'     => $disponible,
            'toggle_url'     => $api['toggle'],
            'dismiss_url'    => $api['dismiss'],
            'profil_url'     => $base . '/expert/profil',
            'dashboard_url'  => $isApp ? $base . '/app' : $base . '/expert',
        ];
    }

    /** @return array<string, mixed>|null */
    private static function buildForProfesseur(int $userId, bool $isApp): ?array
    {
        $profil = (new ProfilProfesseurModel())->getByUtilisateurId($userId);
        if (!$profil || empty($profil['valide_par_admin'])) {
            return null;
        }
        if (self::shouldHideRappel($profil, 'profils_professeurs')) {
            return null;
        }

        $disponible = !empty($profil['disponible']);
        $base = rtrim(BASE_URL ?? '', '/');
        $api = self::apiUrls($isApp);

        return [
            'role'           => 'professeur',
            'profil_id'      => (int) $profil['id'],
            'disponible'     => $disponible,
            'toggle_url'     => $api['toggle'],
            'dismiss_url'    => $api['dismiss'],
            'profil_url'     => $base . '/professeur/profil',
            'dashboard_url'  => $isApp ? $base . '/app/professeur' : $base . '/professeur',
        ];
    }

    /** @param array<string, mixed> $profil */
    private static function shouldHideRappel(array $profil, string $table): bool
    {
        if (!self::hasRappelColumn($table)) {
            return !empty($_SESSION['prestataire_dispo_rappel_vu']);
        }

        return !empty($profil['rappel_disponibilite_vu']);
    }

    public static function hasRappelColumn(string $table): bool
    {
        if ($table === 'profils_experts') {
            if (self::$columnExperts !== null) {
                return self::$columnExperts;
            }
            self::$columnExperts = self::probeColumn($table, 'rappel_disponibilite_vu');

            return self::$columnExperts;
        }
        if ($table === 'profils_professeurs') {
            if (self::$columnProfs !== null) {
                return self::$columnProfs;
            }
            self::$columnProfs = self::probeColumn($table, 'rappel_disponibilite_vu');

            return self::$columnProfs;
        }

        return false;
    }

    private static function probeColumn(string $table, string $column): bool
    {
        try {
            $db = \App\Core\Database::getInstance();
            $stmt = $db->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute([$table, $column]);

            return (int) $stmt->fetchColumn() > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function markRappelVu(string $role, int $profilId): void
    {
        $_SESSION['prestataire_dispo_rappel_vu'] = true;
        if ($role === 'expert' && self::hasRappelColumn('profils_experts')) {
            (new ProfilExpertModel())->update($profilId, ['rappel_disponibilite_vu' => 1]);
        }
        if ($role === 'professeur' && self::hasRappelColumn('profils_professeurs')) {
            (new ProfilProfesseurModel())->updateProfil($profilId, ['rappel_disponibilite_vu' => 1]);
        }
    }

    public static function resetRappelAfterValidation(string $role, int $profilId): void
    {
        unset($_SESSION['prestataire_dispo_rappel_vu']);
        if ($role === 'expert' && self::hasRappelColumn('profils_experts')) {
            (new ProfilExpertModel())->update($profilId, ['rappel_disponibilite_vu' => 0]);
        }
        if ($role === 'professeur' && self::hasRappelColumn('profils_professeurs')) {
            (new ProfilProfesseurModel())->updateProfil($profilId, ['rappel_disponibilite_vu' => 0]);
        }
    }
}
