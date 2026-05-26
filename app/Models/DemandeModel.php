<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class DemandeModel extends Model
{
    protected string $table = 'demandes_assistance';

    public function getByClient(int $clientId, ?int $limit = null): array
    {
        $sql = "SELECT d.*, c.nom as competence_nom FROM {$this->table} d
                LEFT JOIN competences c ON c.id = d.competence_id
                WHERE d.client_id = ? ORDER BY d.created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int) $limit;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    /** Nombre total de demandes ouvertes sur la plateforme — badge nav public. */
    public function countOuvertes(): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE statut = 'ouverte'"
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /** Nombre de demandes actives (non terminées/annulées) pour un client — utilisé par le badge nav. */
    public function countActiveByClient(int $clientId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table}
             WHERE client_id = ?
               AND statut NOT IN ('terminee', 'annulee')"
        );
        $stmt->execute([$clientId]);
        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $data['statut'] = 'ouverte';
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = $data['created_at'];
        return $this->insert($data);
    }

    public function find(int $id): ?array
    {
        $row = parent::find($id);
        if (!$row) {
            return null;
        }
        $stmt = $this->db->prepare("SELECT nom FROM competences WHERE id = ?");
        $stmt->execute([$row['competence_id']]);
        $row['competence_nom'] = $stmt->fetchColumn() ?: null;
        return $row;
    }

    /** Demande (job) publique par slug (SEO URL /jobs/flutter-bug-fix). */
    public function getBySlug(string $slug): ?array
    {
        try {
            $sql = "SELECT d.*, c.nom as competence_nom FROM {$this->table} d
                    LEFT JOIN competences c ON c.id = d.competence_id
                    WHERE d.slug = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getAllForAdmin(int $limit = 300): array
    {
        $stmt = $this->db->prepare("
            SELECT d.*, c.nom as competence_nom,
                   u.prenom as client_prenom, u.nom as client_nom, u.email as client_email
            FROM {$this->table} d
            LEFT JOIN competences c ON c.id = d.competence_id
            JOIN utilisateurs u ON u.id = d.client_id
            ORDER BY d.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function updateStatut(int $id, string $statut): bool
    {
        $allowed = ['ouverte', 'en_cours', 'terminee', 'annulee'];
        if (!in_array($statut, $allowed, true)) {
            error_log('[DemandeModel] updateStatut() : statut invalide "' . $statut . '" pour demande #' . $id);
            return false;
        }
        try {
            $this->db->prepare("UPDATE {$this->table} SET statut = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$statut, $id]);
        } catch (\Throwable $e) {
            $this->db->prepare("UPDATE {$this->table} SET statut = ? WHERE id = ?")
                ->execute([$statut, $id]);
        }
        return true;
    }

    /** Demandes récentes ouvertes pour affichage public (page /demandes) : titre, compétence, date, urgence. */
    public function getRecentOuvertesPourPublic(int $limit = 12): array
    {
        $stmt = $this->db->prepare("
            SELECT d.id, d.titre, d.urgence, d.created_at, c.nom as competence_nom,
                   u.prenom AS client_prenom, u.nom AS client_nom,
                   u.avatar AS client_avatar, u.pays AS client_pays
            FROM {$this->table} d
            LEFT JOIN competences c ON c.id = d.competence_id
            LEFT JOIN utilisateurs u ON u.id = d.client_id
            WHERE d.statut = 'ouverte'
            ORDER BY d.created_at DESC
            LIMIT " . (int) $limit
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Autres demandes encore ouvertes (aperçu anonyme : pas de lien vers le dossier d’un tiers).
     *
     * @return list<array<string, mixed>>
     */
    public function getSimilarOuvertesForDiscovery(int $excludeDemandeId, ?int $competenceId, int $limit = 5): array
    {
        $lim = max(1, min(12, $limit));
        if ($competenceId !== null && $competenceId > 0) {
            $sql = "SELECT d.id, d.titre, d.competence_id, c.nom AS competence_nom
                    FROM {$this->table} d
                    LEFT JOIN competences c ON c.id = d.competence_id
                    WHERE d.statut = 'ouverte' AND d.id != ? AND d.competence_id = ?
                    ORDER BY d.created_at DESC
                    LIMIT {$lim}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$excludeDemandeId, $competenceId]);
            $rows = $stmt->fetchAll();
            if (\is_array($rows) && \count($rows) >= min(3, $lim)) {
                return $rows;
            }
        }

        $sql = "SELECT d.id, d.titre, d.competence_id, c.nom AS competence_nom
                FROM {$this->table} d
                LEFT JOIN competences c ON c.id = d.competence_id
                WHERE d.statut = 'ouverte' AND d.id != ?
                ORDER BY d.created_at DESC
                LIMIT {$lim}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$excludeDemandeId]);

        return $stmt->fetchAll() ?: [];
    }
    public function getOuvertesPourExpert(int $expertProfilId, int $limit = 30): array
    {
        $sql = "SELECT d.*, c.nom as competence_nom, u.prenom as client_prenom, u.nom as client_nom,
                (SELECT dp.id FROM demande_propositions dp WHERE dp.demande_id = d.id AND dp.expert_id = ? LIMIT 1) AS ma_proposition_id,
                (SELECT dp.statut FROM demande_propositions dp WHERE dp.demande_id = d.id AND dp.expert_id = ? LIMIT 1) AS ma_proposition_statut
                FROM {$this->table} d
                LEFT JOIN competences c ON c.id = d.competence_id
                JOIN utilisateurs u ON u.id = d.client_id
                WHERE d.statut = 'ouverte'
                AND (d.competence_id IS NULL OR EXISTS (
                    SELECT 1 FROM expert_competences ec WHERE ec.expert_id = ? AND ec.competence_id = d.competence_id
                ))
                AND NOT EXISTS (
                    SELECT 1 FROM reservations r WHERE r.demande_id = d.id AND r.statut != 'annulee'
                )
                ORDER BY d.urgence DESC, d.created_at DESC LIMIT " . (int) $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$expertProfilId, $expertProfilId, $expertProfilId]);
        return $stmt->fetchAll();
    }

    /** Demande ouverte et correspondant aux compétences de l'expert (sans réservation existante). */
    public function estOuvertePourExpert(int $demandeId, int $expertProfilId): bool
    {
        $sql = "SELECT 1 FROM {$this->table} d
                WHERE d.id = ? AND d.statut = 'ouverte'
                AND (d.competence_id IS NULL OR EXISTS (
                    SELECT 1 FROM expert_competences ec
                    WHERE ec.expert_id = ? AND ec.competence_id = d.competence_id
                ))
                AND NOT EXISTS (
                    SELECT 1 FROM reservations r WHERE r.demande_id = d.id AND r.statut != 'annulee'
                )
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$demandeId, $expertProfilId]);

        return (bool) $stmt->fetchColumn();
    }
}
