<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompetenceModel;
use App\Models\MatiereModel;
use App\Models\ProfilExpertModel;

/**
 * Suggestions pour la recherche intelligente (accueil style Fiverr).
 */
final class SmartSearchService
{
    private string $expertsBase;

    private string $professeursBase;

    private bool $forAppRoutes;

    public function __construct(bool $forAppRoutes = false)
    {
        $this->forAppRoutes = $forAppRoutes;
        $base = rtrim(BASE_URL ?? '', '/');
        if ($forAppRoutes) {
            $this->expertsBase     = $base . '/app/experts';
            $this->professeursBase = $base . '/app/professeurs';
        } else {
            $this->expertsBase     = $base . '/experts';
            $this->professeursBase = $base . '/professeurs';
        }
    }

    /**
     * @return list<array{type: string, label: string, sublabel: string, url: string}>
     */
    public function suggest(string $query): array
    {
        $q = trim($query);
        if (mb_strlen($q) < 2) {
            return $this->popular();
        }

        $items       = [];
        $seenUrls    = [];

        try {
            foreach ((new CompetenceModel())->searchSuggestions($q, 8) as $row) {
                $url = $this->expertsBase . '?competence=' . (int) ($row['id'] ?? 0);
                if (isset($seenUrls[$url])) {
                    continue;
                }
                $seenUrls[$url] = true;
                $items[] = [
                    'type'    => 'competence',
                    'label'   => (string) ($row['nom'] ?? ''),
                    'sublabel'=> 'Compétence · ' . trim((string) ($row['categorie'] ?? '')),
                    'url'     => $url,
                ];
            }
        } catch (\Throwable $e) {
        }

        try {
            foreach ((new ProfilExpertModel())->searchSuggestions($q, 5) as $row) {
                $id   = (int) ($row['id'] ?? 0);
                $slug = trim((string) ($row['slug'] ?? ''));
                if ($this->forAppRoutes) {
                    // /app/experts/{id} (cf. vues mobiles)
                    $url = $this->expertsBase . '/' . $id;
                } elseif ($slug !== '') {
                    $url = rtrim(BASE_URL ?? '', '/') . '/expert/' . rawurlencode($slug);
                } else {
                    $url = $this->expertsBase . '/show/' . $id;
                }
                if (isset($seenUrls[$url])) {
                    continue;
                }
                $seenUrls[$url] = true;
                $label = (string) ($row['titre'] ?? '');
                if ($label === '') {
                    $label = trim((string) ($row['prenom'] ?? '') . ' ' . (string) ($row['nom'] ?? ''));
                }
                $items[] = [
                    'type'     => 'expert',
                    'label'    => $label,
                    'sublabel' => 'Expert · ' . trim((string) ($row['prenom'] ?? '') . ' ' . (string) ($row['nom'] ?? '')),
                    'url'      => $url,
                ];
            }
        } catch (\Throwable $e) {
        }

        try {
            foreach ((new MatiereModel())->searchSuggestions($q, 6) as $row) {
                $url = $this->professeursBase . '?matiere=' . (int) ($row['id'] ?? 0);
                if (isset($seenUrls[$url])) {
                    continue;
                }
                $seenUrls[$url] = true;
                $items[] = [
                    'type'     => 'matiere',
                    'label'    => (string) ($row['nom'] ?? ''),
                    'sublabel' => 'Matière · ' . trim((string) ($row['categorie'] ?? '')),
                    'url'      => $url,
                ];
            }
        } catch (\Throwable $e) {
        }

        if ($items === []) {
            $url = $this->expertsBase . '?q=' . rawurlencode($q);
            if (!isset($seenUrls[$url])) {
                $items[] = [
                    'type'     => 'search',
                    'label'    => 'Voir tous les résultats pour « ' . $q . ' »',
                    'sublabel' => 'Experts',
                    'url'      => $url,
                ];
            }
        }

        return array_slice($items, 0, 12);
    }

    /**
     * @return list<array{type: string, label: string, sublabel: string, url: string}>
     */
    public function popular(): array
    {
        $items    = [];
        $seenUrls = [];

        try {
            foreach ((new CompetenceModel())->getFeaturedForSearch(10) as $row) {
                $url = $this->expertsBase . '?competence=' . (int) ($row['id'] ?? 0);
                if (isset($seenUrls[$url])) {
                    continue;
                }
                $seenUrls[$url] = true;
                $items[] = [
                    'type'     => 'competence',
                    'label'    => (string) ($row['nom'] ?? ''),
                    'sublabel' => 'Populaire · compétence',
                    'url'      => $url,
                ];
            }
        } catch (\Throwable $e) {
        }

        try {
            foreach ((new MatiereModel())->getFeaturedForSearch(6) as $row) {
                $url = $this->professeursBase . '?matiere=' . (int) ($row['id'] ?? 0);
                if (isset($seenUrls[$url])) {
                    continue;
                }
                $seenUrls[$url] = true;
                $items[] = [
                    'type'     => 'matiere',
                    'label'    => (string) ($row['nom'] ?? ''),
                    'sublabel' => 'Matière universitaire',
                    'url'      => $url,
                ];
            }
        } catch (\Throwable $e) {
        }

        return array_slice($items, 0, 14);
    }
}
