<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProfilExpertModel;
use App\Models\CompetenceModel;

/**
 * Expert search for chatbot: filter by category (competence), availability, rating, hourly rate.
 */
class ExpertMatchingService
{
    private ProfilExpertModel $expertModel;
    private CompetenceModel $competenceModel;

    public function __construct()
    {
        $this->expertModel = new ProfilExpertModel();
        $this->competenceModel = new CompetenceModel();
    }

    /**
     * Map natural language to competence_id (e.g. "Flutter" -> Development, "design" -> Design graphique).
     */
    public function resolveCompetenceFromText(string $text): ?int
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }
        // Direct slug or name match
        $comp = $this->competenceModel->findBySlugOrName($text);
        if ($comp) {
            return (int) $comp['id'];
        }
        // Simple keywords -> slug hints (extend as needed)
        $hints = [
            'flutter' => 'developpement-web',
            'développement' => 'developpement-web',
            'dev web' => 'developpement-web',
            'design' => 'design-graphique',
            'logo' => 'design-graphique',
            'excel' => 'excel',
            'compta' => 'comptabilite',
            'traduction' => 'traduction',
            'présentation' => 'presentations',
            'rapport' => 'rapports-analyses',
        ];
        $lower = mb_strtolower($text);
        foreach ($hints as $keyword => $slug) {
            if (str_contains($lower, $keyword)) {
                $comp = $this->competenceModel->findBySlugOrName($slug);
                if ($comp) {
                    return (int) $comp['id'];
                }
            }
        }
        return null;
    }

    /**
     * Search experts with optional filters.
     *
     * @param int|null $competenceId Filter by competence (null = any)
     * @param bool $availableOnly Only disponible = 1
     * @param float|null $minRating Minimum note_moyenne
     * @param float|null $maxHourlyRate Maximum tarif_horaire
     * @param string|null $search Free text search on titre, description, name
     * @param int $limit Max results
     * @return array List of experts with id, name, titre, note_moyenne, nombre_avis, tarif_horaire, disponible
     */
    public function search(
        ?int $competenceId = null,
        bool $availableOnly = true,
        ?float $minRating = null,
        ?float $maxHourlyRate = null,
        ?string $search = null,
        int $limit = 10
    ): array {
        $normalizedSearch = $search !== null ? trim($search) : null;
        if ($normalizedSearch === '') {
            $normalizedSearch = null;
        }

        // 1) Recherche ciblée (avec texte) quand disponible.
        $experts = $this->expertModel->getListDisponibles($competenceId, $normalizedSearch, $limit * 2);
        // 2) Fallback robuste : si 0 résultat, ne pas bloquer sur le texte libre.
        if (empty($experts) && $normalizedSearch !== null) {
            $experts = $this->expertModel->getListDisponibles($competenceId, null, $limit * 2);
        }
        // 3) Fallback ultime : ignorer aussi la compétence si elle est trop restrictive.
        if (empty($experts) && $competenceId !== null) {
            $experts = $this->expertModel->getListDisponibles(null, null, $limit * 2);
        }

        $out = [];
        foreach ($experts as $e) {
            if ($availableOnly && empty($e['disponible'])) {
                continue;
            }
            if ($minRating !== null && isset($e['note_moyenne']) && (float) $e['note_moyenne'] < $minRating) {
                continue;
            }
            if ($maxHourlyRate !== null && isset($e['tarif_horaire']) && (float) $e['tarif_horaire'] > $maxHourlyRate) {
                continue;
            }
            $out[] = [
                'id' => (int) $e['id'],
                'slug' => $e['slug'] ?? 'expert-' . (int) $e['id'],
                'name' => trim(($e['prenom'] ?? '') . ' ' . ($e['nom'] ?? '')),
                'titre' => $e['titre'] ?? '',
                'note_moyenne' => $e['note_moyenne'] !== null ? round((float) $e['note_moyenne'], 1) : null,
                'nombre_avis' => (int) ($e['nombre_avis'] ?? 0),
                'tarif_horaire' => (float) ($e['tarif_horaire'] ?? 0),
                'disponible' => !empty($e['disponible']),
            ];
            if (count($out) >= $limit) {
                break;
            }
        }
        return $out;
    }
}
