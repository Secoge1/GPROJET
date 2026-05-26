<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DemandeModel;
use App\Models\ProfilExpertModel;
use App\Models\ProfilProfesseurModel;
use App\Models\ReservationModel;
use App\Models\UtilisateurModel;

/**
 * Suggestions après création / sur fiche demande ouverte :
 * experts (texte + compétence + historique résa + pays), professeurs, missions proches, liens utiles.
 */
final class DemandeRecommendationService
{
    private ReservationModel $reservationModel;

    public function __construct(?ReservationModel $reservationModel = null)
    {
        $this->reservationModel = $reservationModel ?? new ReservationModel();
    }

    /**
     * @param array<string, mixed> $demande ligne DemandeModel::find (avec competence_nom)
     *
     * @return array{
     *   experts: list<array<string, mixed>>,
     *   professeurs: list<array<string, mixed>>,
     *   similar_demandes: list<array<string, mixed>>,
     *   service_links: list<array{label: string, path: string}>,
     *   competence_effective_id: ?int,
     *   competence_inferred: bool
     * }
     */
    public function build(array $demande, int $clientId): array
    {
        $text = trim((string) ($demande['titre'] ?? '') . ' ' . (string) ($demande['description'] ?? ''));
        $matching = new ExpertMatchingService();

        $competenceEffective = !empty($demande['competence_id']) ? (int) $demande['competence_id'] : null;
        if ($competenceEffective !== null && $competenceEffective <= 0) {
            $competenceEffective = null;
        }
        $inferred = false;
        if ($competenceEffective === null && $text !== '') {
            $resolved = $matching->resolveCompetenceFromText($text);
            if ($resolved !== null && $resolved > 0) {
                $competenceEffective = $resolved;
                $inferred = true;
            }
        }

        $keywords = self::extractKeywords($text);
        $searchPhrase = \count($keywords) > 0 ? implode(' ', \array_slice($keywords, 0, 6)) : null;

        $expertModel = new ProfilExpertModel();
        $candidates = $expertModel->getListDisponibles($competenceEffective, $searchPhrase, 45);
        if (\count($candidates) < 6) {
            $candidates = self::mergeExpertsById($candidates, $expertModel->getListDisponibles($competenceEffective, null, 45));
        }
        if (\count($candidates) < 6 && $competenceEffective !== null) {
            $candidates = self::mergeExpertsById($candidates, $expertModel->getListDisponibles(null, $searchPhrase, 35));
        }
        if (\count($candidates) < 4) {
            $candidates = self::mergeExpertsById($candidates, $expertModel->getListDisponibles(null, null, 30));
        }

        $userRow = (new UtilisateurModel())->find($clientId);
        $clientPays = trim((string) ($userRow['pays'] ?? ''));
        $bookedExpertIds = $this->reservationModel->getExpertProfilIdsUsedByClient($clientId);

        foreach ($candidates as $k => $ex) {
            $hay = mb_strtolower(
                (string) ($ex['titre'] ?? '') . ' ' . (string) ($ex['description'] ?? '') . ' '
                . (string) ($ex['prenom'] ?? '') . ' ' . (string) ($ex['nom'] ?? '')
            );
            $extra = self::keywordOverlapScore($keywords, $hay);
            if (\in_array((int) ($ex['id'] ?? 0), $bookedExpertIds, true)) {
                $extra += 10.0;
            }
            $euPays = trim((string) ($ex['pays'] ?? ''));
            if ($clientPays !== '' && $euPays !== '' && mb_strtolower($clientPays) === mb_strtolower($euPays)) {
                $extra += 6.0;
            }
            $candidates[$k]['_reco_ctx_bonus'] = $extra;
        }

        $expertsRanked = ExpertReservationRecommendation::scoreAndSort($candidates, $demande);
        foreach ($expertsRanked as &$ex) {
            $bonus = (float) ($ex['_reco_ctx_bonus'] ?? 0);
            unset($ex['_reco_ctx_bonus']);
            if ((float) ($ex['tarif_horaire'] ?? 0) > 0) {
                $ex['recommendation_score'] = min(100.0, (float) ($ex['recommendation_score'] ?? 0) + $bonus * 0.35);
                if (\in_array((int) ($ex['id'] ?? 0), $bookedExpertIds, true)) {
                    $prev = trim((string) ($ex['recommendation_reason'] ?? ''));
                    $ex['recommendation_reason'] = $prev !== ''
                        ? 'Expert déjà réservé — ' . $prev
                        : 'Expert avec qui vous avez déjà réservé';
                } else {
                    $euPays = trim((string) ($ex['pays'] ?? ''));
                    if ($bonus >= 6 && $euPays !== '' && $clientPays !== '' && mb_strtolower($clientPays) === mb_strtolower($euPays)) {
                        $prev = trim((string) ($ex['recommendation_reason'] ?? ''));
                        $ex['recommendation_reason'] = $prev !== '' ? $prev . ' · Même pays' : 'Profil basé dans votre pays';
                    }
                }
            }
        }
        unset($ex);

        usort($expertsRanked, static function (array $a, array $b): int {
            $sa = (float) ($a['recommendation_score'] ?? 0);
            $sb = (float) ($b['recommendation_score'] ?? 0);
            if (abs($sa - $sb) > 0.001) {
                return $sa > $sb ? -1 : 1;
            }
            $ta = (float) ($a['tarif_horaire'] ?? 0);
            $tb = (float) ($b['tarif_horaire'] ?? 0);
            if ($ta > 0 && $tb > 0 && abs($ta - $tb) > 0.01) {
                return $ta < $tb ? -1 : 1;
            }

            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        $rank = 0;
        foreach ($expertsRanked as &$row) {
            $row['recommendation_is_top'] = false;
            $row['recommendation_rank'] = null;
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

        $expertsTop = \array_slice(
            \array_values(\array_filter(
                $expertsRanked,
                static fn (array $e): bool => (float) ($e['tarif_horaire'] ?? 0) > 0
            )),
            0,
            6
        );

        $profModel = new ProfilProfesseurModel();
        $profs = $profModel->getListDisponibles(null, $searchPhrase, 10);
        if (\count($profs) < 3 && $searchPhrase !== null) {
            $profs = self::mergeProfsById($profs, $profModel->getListDisponibles(null, null, 8));
        }
        $profs = \array_slice($profs, 0, 4);

        $forSimilarComp = $competenceEffective ?? (!empty($demande['competence_id']) ? (int) $demande['competence_id'] : null);
        $similar = (new DemandeModel())->getSimilarOuvertesForDiscovery((int) ($demande['id'] ?? 0), $forSimilarComp, 5);

        $cidLink = $competenceEffective ?? (!empty($demande['competence_id']) ? (int) $demande['competence_id'] : 0);

        return [
            'experts'               => $expertsTop,
            'professeurs'           => $profs,
            'similar_demandes'      => $similar,
            'service_links'         => self::serviceLinks((int) $cidLink),
            'competence_effective_id' => $competenceEffective,
            'competence_inferred'   => $inferred,
        ];
    }

    /**
     * @return list<array{label: string, path: string}>
     */
    private static function serviceLinks(int $competenceId): array
    {
        if ($competenceId > 0) {
            return [
                ['label' => 'Experts dans ce domaine', 'path' => '/experts?competence=' . $competenceId],
                ['label' => 'Professeurs', 'path' => '/professeurs'],
                ['label' => 'Demandes ouvertes', 'path' => '/demandes'],
            ];
        }

        return [
            ['label' => 'Tous les experts', 'path' => '/experts'],
            ['label' => 'Professeurs', 'path' => '/professeurs'],
            ['label' => 'Demandes ouvertes', 'path' => '/demandes'],
        ];
    }

    /**
     * @param list<string> $keywords
     */
    private static function keywordOverlapScore(array $keywords, string $haystackLower): float
    {
        if ($keywords === [] || $haystackLower === '') {
            return 0.0;
        }
        $n = 0;
        foreach ($keywords as $kw) {
            if ($kw !== '' && mb_strpos($haystackLower, $kw) !== false) {
                $n++;
            }
        }

        return min(12.0, $n * 2.5);
    }

    /**
     * @return list<string>
     */
    private static function extractKeywords(string $text): array
    {
        if ($text === '') {
            return [];
        }
        $text = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? '');
        $stop = ['le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'et', 'ou', 'en', 'au', 'aux', 'pour', 'par', 'sur', 'avec', 'sans', 'dans', 'est', 'son', 'sa', 'ses', 'ce', 'cette', 'mon', 'ma', 'mes', 'vous', 'nous', 'ils', 'elles', 'a', 'ont', 'être', 'etre', 'faire', 'plus', 'moins', 'très', 'tres', 'besoin', 'aide', 'merci', 'qui', 'que', 'quoi', 'donc', 'car', 'je', 'tu', 'il', 'ne', 'pas', 'une'];
        $parts = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return [];
        }
        $out = [];
        foreach ($parts as $p) {
            if (mb_strlen($p) < 2) {
                continue;
            }
            if (\in_array($p, $stop, true)) {
                continue;
            }
            $out[] = $p;
        }

        return \array_values(\array_unique($out));
    }

    /**
     * @param array<int, array<string, mixed>> $a
     * @param array<int, array<string, mixed>> $b
     *
     * @return array<int, array<string, mixed>>
     */
    private static function mergeExpertsById(array $a, array $b): array
    {
        $byId = [];
        foreach ([$a, $b] as $list) {
            foreach ($list as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id > 0 && !isset($byId[$id])) {
                    $byId[$id] = $row;
                }
            }
        }

        return \array_values($byId);
    }

    /**
     * @param array<int, array<string, mixed>> $a
     * @param array<int, array<string, mixed>> $b
     *
     * @return array<int, array<string, mixed>>
     */
    private static function mergeProfsById(array $a, array $b): array
    {
        $byId = [];
        foreach ([$a, $b] as $list) {
            foreach ($list as $row) {
                $id = (int) ($row['id'] ?? 0);
                if ($id > 0 && !isset($byId[$id])) {
                    $byId[$id] = $row;
                }
            }
        }

        return \array_values($byId);
    }
}
