<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ProfilExpertModel extends Model
{
    protected string $table = 'profils_experts';

    public function getByUtilisateurId(int $utilisateurId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE utilisateur_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateurId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getCompetences(int $expertId): array
    {
        $sql = "SELECT competence_id FROM expert_competences WHERE expert_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$expertId]);
        return array_column($stmt->fetchAll(), 'competence_id');
    }

    /** @param array<int, string> $niveauxByCompetenceId competence_id => niveau (debutant, intermediaire, avance, expert) */
    public function setCompetences(int $expertId, array $competenceIds, array $niveauxByCompetenceId = []): void
    {
        $allowedNiveaux = ['debutant', 'intermediaire', 'avance', 'expert'];
        $this->db->prepare("DELETE FROM expert_competences WHERE expert_id = ?")->execute([$expertId]);
        $stmt = $this->db->prepare("INSERT INTO expert_competences (expert_id, competence_id, niveau) VALUES (?, ?, ?)");
        foreach ($competenceIds as $cid) {
            if ($cid > 0) {
                $niveau = isset($niveauxByCompetenceId[$cid]) && in_array($niveauxByCompetenceId[$cid], $allowedNiveaux, true)
                    ? $niveauxByCompetenceId[$cid]
                    : 'intermediaire';
                $stmt->execute([$expertId, $cid, $niveau]);
            }
        }
    }

    /** Liste des experts (disponibles, validés). Filtre optionnel par compétence. Inclut competence_niveau si compétence ciblée. */
    public function getListDisponibles(?int $competenceId = null, ?string $search = null, int $limit = 50): array
    {
        if ($competenceId > 0) {
            $sql = "SELECT p.*, u.prenom, u.nom, u.email_verifie, u.avatar, u.pays, ec.niveau AS competence_niveau
                    FROM {$this->table} p
                    JOIN utilisateurs u ON u.id = p.utilisateur_id
                    INNER JOIN expert_competences ec ON ec.expert_id = p.id AND ec.competence_id = ?
                    WHERE p.disponible = 1 AND p.valide_par_admin = 1 AND u.actif = 1";
            $params = [$competenceId];
        } else {
            $sql = "SELECT p.*, u.prenom, u.nom, u.email_verifie, u.avatar, u.pays, NULL AS competence_niveau
                    FROM {$this->table} p
                    JOIN utilisateurs u ON u.id = p.utilisateur_id
                    WHERE p.disponible = 1 AND p.valide_par_admin = 1 AND u.actif = 1";
            $params = [];
        }
        if ($search !== null && $search !== '') {
            $sql .= " AND (p.titre LIKE ? OR p.description LIKE ? OR u.prenom LIKE ? OR u.nom LIKE ?)";
            $q = '%' . $search . '%';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }
        $sql .= " ORDER BY p.note_moyenne DESC, p.nombre_avis DESC LIMIT " . (int) $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Suggestions rapides pour l’autocomplete (profils validés et disponibles).
     *
     * @return list<array<string, mixed>>
     */
    public function searchSuggestions(string $q, int $limit = 6): array
    {
        $q = trim($q);
        if ($q === '' || $limit < 1) {
            return [];
        }
        $like = '%' . $q . '%';
        $lim  = min(20, $limit);
        $sql  = "SELECT p.id, p.titre, p.slug, u.prenom, u.nom
                 FROM {$this->table} p
                 JOIN utilisateurs u ON u.id = p.utilisateur_id
                 WHERE p.disponible = 1 AND p.valide_par_admin = 1 AND u.actif = 1
                 AND (p.titre LIKE ? OR p.description LIKE ? OR u.prenom LIKE ? OR u.nom LIKE ?
                      OR CONCAT(u.prenom, ' ', u.nom) LIKE ?)
                 ORDER BY p.note_moyenne DESC, p.nombre_avis DESC
                 LIMIT {$lim}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$like, $like, $like, $like, $like]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $rows ?: [];
    }

    /** Détail expert public (profil + user) par id profil. */
    public function getByIdPublic(int $id): ?array
    {
        $sql = "SELECT p.*, u.prenom, u.nom, u.email_verifie, u.avatar, u.pays FROM {$this->table} p
                JOIN utilisateurs u ON u.id = p.utilisateur_id
                WHERE p.id = ? AND u.actif = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Expert public par slug (SEO URL /expert/amadou-flutter-developer). */
    public function getBySlug(string $slug): ?array
    {
        try {
            // Pas de filtre valide_par_admin : le profil est visible même avant validation admin.
            // Il n'apparaît dans la liste des experts disponibles qu'une fois validé.
            $sql = "SELECT p.*, u.prenom, u.nom, u.email_verifie, u.avatar, u.pays FROM {$this->table} p
                    JOIN utilisateurs u ON u.id = p.utilisateur_id
                    WHERE p.slug = ? AND u.actif = 1 LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Expert public par id profil (fallback pour URLs legacy expert-{id}). */
    public function getByIdForSlugFallback(int $profilId): ?array
    {
        try {
            $sql = "SELECT p.*, u.prenom, u.nom, u.email_verifie, u.avatar, u.pays FROM {$this->table} p
                    JOIN utilisateurs u ON u.id = p.utilisateur_id
                    WHERE p.id = ? AND u.actif = 1 LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$profilId]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Génère un slug unique à partir du titre et prénom. */
    public static function slugify(string $s): string
    {
        $s = preg_replace('/[^a-z0-9\s\-]/ui', '', $s);
        $s = preg_replace('/[\s\-]+/', '-', trim($s));
        return mb_strtolower($s) ?: 'expert';
    }

    public function getCompetencesNoms(int $expertId): array
    {
        return $this->getCompetencesPublic($expertId);
    }

    /**
     * Compétences / types de professions affichés sur la fiche publique expert.
     *
     * @return list<array{id: int, nom: string, slug: string, categorie: ?string, niveau: string}>
     */
    public function getCompetencesPublic(int $expertId): array
    {
        $sql = "SELECT c.id, c.nom, c.slug, c.categorie, ec.niveau
                FROM competences c
                INNER JOIN expert_competences ec ON ec.competence_id = c.id
                WHERE ec.expert_id = ? AND c.actif = 1
                ORDER BY c.categorie, c.nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$expertId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $rows ?: [];
    }

    public function getAllForAdmin(): array
    {
        $sql = "SELECT p.*, u.email, u.prenom, u.nom, u.avatar AS user_avatar FROM {$this->table} p
                JOIN utilisateurs u ON u.id = p.utilisateur_id
                ORDER BY p.valide_par_admin ASC, p.created_at DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /** Supprime un profil expert (et ses compétences liées). Retourne true si supprimé. */
    public function deleteExpertProfile(int $id): bool
    {
        try {
            $this->db->prepare("DELETE FROM expert_competences WHERE expert_id = ?")->execute([$id]);
            return $this->delete($id);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
