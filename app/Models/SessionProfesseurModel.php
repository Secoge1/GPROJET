<?php
/**
 * GLOBALO - Modèle Sessions Professeur (réservations étudiant ↔ professeur)
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class SessionProfesseurModel extends Model
{
    protected string $table = 'sessions_professeurs';

    /** Crée une session. */
    public function create(array $data): int
    {
        $data['statut'] = 'en_attente';
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = $data['created_at'];
        return $this->insert($data);
    }

    /** Sessions par étudiant. */
    public function getByEtudiant(int $etudiantId, ?int $limit = null): array
    {
        $sql = "SELECT sp.*, pp.titre as professeur_titre, u.prenom, u.nom, mu.nom as matiere_nom
                FROM {$this->table} sp
                JOIN profils_professeurs pp ON pp.utilisateur_id = sp.professeur_id
                JOIN utilisateurs u ON u.id = sp.professeur_id
                LEFT JOIN matieres_universitaires mu ON mu.id = sp.matiere_id
                WHERE sp.etudiant_id = ?
                ORDER BY sp.created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int) $limit;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$etudiantId]);
        return $stmt->fetchAll();
    }

    /** Sessions par professeur. */
    public function getByProfesseur(int $professeurId, ?int $limit = null): array
    {
        $sql = "SELECT sp.*, u.prenom, u.nom, mu.nom as matiere_nom
                FROM {$this->table} sp
                JOIN utilisateurs u ON u.id = sp.etudiant_id
                LEFT JOIN matieres_universitaires mu ON mu.id = sp.matiere_id
                WHERE sp.professeur_id = ?
                ORDER BY sp.created_at DESC";
        if ($limit) {
            $sql .= " LIMIT " . (int) $limit;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$professeurId]);
        return $stmt->fetchAll();
    }

    /** Trouver une session par id. */
    public function find(int $id): ?array
    {
        $sql = "SELECT sp.*, pp.titre as professeur_titre,
                pu.prenom as professeur_prenom, pu.nom as professeur_nom,
                eu.prenom as etudiant_prenom, eu.nom as etudiant_nom,
                mu.nom as matiere_nom
                FROM {$this->table} sp
                JOIN profils_professeurs pp ON pp.utilisateur_id = sp.professeur_id
                JOIN utilisateurs pu ON pu.id = sp.professeur_id
                JOIN utilisateurs eu ON eu.id = sp.etudiant_id
                LEFT JOIN matieres_universitaires mu ON mu.id = sp.matiere_id
                WHERE sp.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Met à jour le statut. */
    public function updateStatut(int $id, string $statut): bool
    {
        $allowed = ['en_attente', 'acceptee', 'en_cours', 'terminee', 'annulee'];
        if (!in_array($statut, $allowed, true)) {
            return false;
        }
        return $this->update($id, ['statut' => $statut]);
    }
}
