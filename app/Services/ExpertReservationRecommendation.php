<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Classement automatique des experts pour la page « Réserver » (score 0–100).
 *
 * Pondération (documentée pour audit / tests) :
 * - Qualité perçue (note) : jusqu’à 35 pts — note sur 5 normalisée.
 * - Historique (avis) : jusqu’à 10 pts — confiance croissante avec le nombre d’avis.
 * - Rapport qualité / prix : jusqu’à 30 pts — tarif le plus bas parmi les experts réservables (même compétence).
 * - Niveau sur la compétence (ou niveau d’expérience profil) : jusqu’à 20 pts.
 * - Urgence de la demande : bonus jusqu’à 5 pts pour les profils les plus avancés (urgent / très urgent).
 */
final class ExpertReservationRecommendation
{
    private const WEIGHT_NOTE = 35.0;
    private const WEIGHT_AVIS = 10.0;
    private const WEIGHT_PRIX = 30.0;
    private const WEIGHT_NIVEAU = 20.0;
    private const WEIGHT_URGENCE = 5.0;

    /**
     * @param array<int, array<string, mixed>> $experts
     * @param array<string, mixed> $demande
     * @return array<int, array<string, mixed>>
     */
    public static function scoreAndSort(array $experts, array $demande): array
    {
        if ($experts === []) {
            return [];
        }

        $urgence = strtolower(trim((string) ($demande['urgence'] ?? 'normale')));
        $bookable = [];
        foreach ($experts as $e) {
            if ((float) ($e['tarif_horaire'] ?? 0) > 0) {
                $bookable[] = $e;
            }
        }

        $tarifs = array_map(static fn (array $e): float => (float) ($e['tarif_horaire'] ?? 0), $bookable);
        $minTarif = $tarifs !== [] ? min($tarifs) : 0.0;
        $maxTarif = $tarifs !== [] ? max($tarifs) : 0.0;
        $spreadTarif = max($maxTarif - $minTarif, 0.01);

        $scored = [];
        foreach ($experts as $e) {
            $tarif = (float) ($e['tarif_horaire'] ?? 0);
            $note = (float) ($e['note_moyenne'] ?? 0);
            if ($note < 0) {
                $note = 0;
            }
            if ($note > 5) {
                $note = 5;
            }
            $nbAvis = max(0, (int) ($e['nombre_avis'] ?? 0));

            $ptsNote = ($note / 5.0) * self::WEIGHT_NOTE;
            $ptsAvis = min(self::WEIGHT_AVIS, $nbAvis * 0.5);

            $ptsPrix = 0.0;
            if ($tarif > 0 && $bookable !== []) {
                $ptsPrix = (1.0 - ($tarif - $minTarif) / $spreadTarif) * self::WEIGHT_PRIX;
            }

            $niveauStr = self::resolveNiveauString($e);
            $ptsNiveau = (self::niveauToPoints($niveauStr) / 20.0) * self::WEIGHT_NIVEAU;

            $ptsUrgence = self::urgenceBonus($urgence, $niveauStr);

            $score = $ptsNote + $ptsAvis + $ptsPrix + $ptsNiveau + $ptsUrgence;
            if ($tarif <= 0) {
                $score = 0.0;
            }
            if ($score > 100.0) {
                $score = 100.0;
            }

            $e['recommendation_score'] = round($score, 1);
            $e['recommendation_reason'] = self::buildReasonLabel(
                $note,
                $tarif,
                $minTarif,
                $spreadTarif,
                $niveauStr,
                $nbAvis,
                $ptsPrix,
                $ptsNote
            );
            $e['recommendation_is_top'] = false;
            $e['recommendation_rank'] = null;
            $scored[] = $e;
        }

        usort($scored, static function (array $a, array $b): int {
            $sa = (float) ($a['recommendation_score'] ?? 0);
            $sb = (float) ($b['recommendation_score'] ?? 0);
            if ($sa > $sb) {
                return -1;
            }
            if ($sa < $sb) {
                return 1;
            }
            $ta = (float) ($a['tarif_horaire'] ?? 0);
            $tb = (float) ($b['tarif_horaire'] ?? 0);
            if ($ta < $tb) {
                return -1;
            }
            if ($ta > $tb) {
                return 1;
            }
            $ia = (int) ($a['id'] ?? 0);
            $ib = (int) ($b['id'] ?? 0);
            if ($ia < $ib) {
                return -1;
            }
            if ($ia > $ib) {
                return 1;
            }

            return 0;
        });

        $rank = 0;
        foreach ($scored as $i => &$row) {
            if ((float) ($row['tarif_horaire'] ?? 0) <= 0) {
                continue;
            }
            $rank++;
            $row['recommendation_rank'] = $rank;
            if ($rank === 1) {
                $row['recommendation_is_top'] = true;
            }
        }
        unset($row);

        return $scored;
    }

    /**
     * @param array<string, mixed> $expert
     */
    private static function resolveNiveauString(array $expert): string
    {
        $cn = $expert['competence_niveau'] ?? null;
        if (is_string($cn) && $cn !== '') {
            return strtolower(trim($cn));
        }
        $ne = $expert['niveau_experience'] ?? null;
        if (is_string($ne) && $ne !== '') {
            return strtolower(trim($ne));
        }

        return 'intermediaire';
    }

    private static function niveauToPoints(string $niveau): float
    {
        switch ($niveau) {
            case 'debutant':
                return 4.0;
            case 'intermediaire':
                return 10.0;
            case 'avance':
                return 16.0;
            case 'confirme':
                return 14.0;
            case 'expert':
                return 20.0;
            default:
                return 8.0;
        }
    }

    private static function urgenceBonus(string $urgence, string $niveau): float
    {
        if ($urgence === 'normale' || $urgence === '') {
            return 0.0;
        }
        $n = self::niveauToPoints($niveau);
        if ($n < 15.0) {
            return 0.0;
        }
        $mult = $urgence === 'tres_urgent' ? 1.0 : 0.5;

        return min(self::WEIGHT_URGENCE, ($n / 20.0) * self::WEIGHT_URGENCE * $mult);
    }

    private static function buildReasonLabel(
        float $note,
        float $tarif,
        float $minTarif,
        float $spreadTarif,
        string $niveau,
        int $nbAvis,
        float $ptsPrix,
        float $ptsNote
    ): string {
        if ($tarif <= 0) {
            return '';
        }

        $candidates = [];
        if ($note >= 4.5) {
            $candidates['très bien noté'] = $ptsNote;
        } elseif ($note >= 4.0) {
            $candidates['bien noté'] = $ptsNote;
        }
        if ($spreadTarif > 0.01 && $tarif <= $minTarif + 0.01) {
            $candidates['meilleur rapport qualité-prix'] = $ptsPrix + 5;
        } elseif ($ptsPrix >= 20.0) {
            $candidates['tarif compétitif'] = $ptsPrix;
        }
        if ($niveau === 'expert') {
            $candidates['expert sur la compétence'] = 18.0;
        } elseif ($niveau === 'avance' || $niveau === 'confirme') {
            $candidates['profil confirmé'] = 14.0;
        }
        if ($nbAvis >= 5) {
            $candidates['avis clients nombreux'] = min(10.0, $nbAvis * 0.4);
        }

        if ($candidates === []) {
            return 'Profil adapté à votre demande';
        }
        arsort($candidates, SORT_NUMERIC);
        reset($candidates);

        return key($candidates) !== null ? (string) key($candidates) : 'Profil adapté à votre demande';
    }
}
