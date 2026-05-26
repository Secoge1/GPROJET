<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ExercicePropositionModel extends Model
{
    protected string $table = 'exercice_propositions';

    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $data['statut'] = 'en_attente';
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        return $this->insert($data);
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT p.*, pp.titre AS prof_titre, u.prenom AS prof_prenom, u.nom AS prof_nom,
                       u.avatar AS prof_avatar, u.id AS prof_utilisateur_id
                FROM {$this->table} p
                JOIN profils_professeurs pp ON pp.id = p.profil_professeur_id
                JOIN utilisateurs u ON u.id = pp.utilisateur_id
                WHERE p.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function getByExercice(int $exerciceId, ?string $statut = null): array
    {
        $sql = "SELECT p.*, pp.titre AS prof_titre, u.prenom AS prof_prenom, u.nom AS prof_nom,
                       u.avatar AS prof_avatar
                FROM {$this->table} p
                JOIN profils_professeurs pp ON pp.id = p.profil_professeur_id
                JOIN utilisateurs u ON u.id = pp.utilisateur_id
                WHERE p.exercice_id = ?";
        $params = [$exerciceId];
        if ($statut !== null) {
            $sql .= ' AND p.statut = ?';
            $params[] = $statut;
        }
        $sql .= ' ORDER BY p.statut = \'en_attente\' DESC, p.created_at ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public function getByExerciceAndProfesseur(int $exerciceId, int $profilProfesseurId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE exercice_id = ? AND profil_professeur_id = ? LIMIT 1"
        );
        $stmt->execute([$exerciceId, $profilProfesseurId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function updateStatut(int $id, string $statut): bool
    {
        if (!in_array($statut, ['en_attente', 'acceptee', 'refusee', 'retiree'], true)) {
            return false;
        }

        return $this->update($id, ['statut' => $statut, 'updated_at' => date('Y-m-d H:i:s')]);
    }

    public function refuserAutresPourExercice(int $exerciceId, int $exceptId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET statut = 'refusee', updated_at = NOW()
             WHERE exercice_id = ? AND id != ? AND statut = 'en_attente'"
        );
        $stmt->execute([$exerciceId, $exceptId]);
    }
}
