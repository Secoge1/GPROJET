<?php
/**
 * GLOBALO - Modèle Profil Professeur (affichage public, réservation par étudiants)
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ProfilProfesseurModel extends Model
{
    protected string $table = 'profils_professeurs';

    /** Liste des professeurs disponibles et validés. Filtre optionnel par matière. */
    public function getListDisponibles(?int $matiereId = null, ?string $search = null, int $limit = 50): array
    {
        $sql = "SELECT pp.*, u.prenom, u.nom, u.avatar, u.pays, u.email_verifie
                FROM {$this->table} pp
                JOIN utilisateurs u ON u.id = pp.utilisateur_id
                WHERE pp.disponible = 1 AND pp.valide_par_admin = 1 AND u.actif = 1";
        $params = [];
        if ($matiereId > 0) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM professeur_matieres pm
                WHERE pm.profil_professeur_id = pp.id AND pm.matiere_id = ?
            )";
            $params[] = $matiereId;
        }
        if ($search !== null && $search !== '') {
            $q = '%' . $search . '%';
            $sql .= " AND (pp.titre LIKE ? OR pp.description LIKE ? OR u.prenom LIKE ? OR u.nom LIKE ?)";
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
        }
        $sql .= " ORDER BY pp.note_moyenne DESC, pp.nombre_avis DESC LIMIT " . (int) $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Détail professeur public par id profil. */
    public function getByIdPublic(int $id): ?array
    {
        $sql = "SELECT pp.*, u.prenom, u.nom, u.avatar, u.pays, u.email_verifie
                FROM {$this->table} pp
                JOIN utilisateurs u ON u.id = pp.utilisateur_id
                WHERE pp.id = ? AND u.actif = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Matières affichées sur la fiche publique (table professeur_matieres).
     * @throws \PDOException si la table n'existe pas encore (migration non appliquée).
     */
    public function getMatieresForProfil(int $profilProfesseurId): array
    {
        $sql = "SELECT mu.nom, mu.categorie, mu.slug
                FROM professeur_matieres pm
                JOIN matieres_universitaires mu ON mu.id = pm.matiere_id
                WHERE pm.profil_professeur_id = ?
                ORDER BY mu.categorie, mu.nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$profilProfesseurId]);
        return $stmt->fetchAll();
    }

    /** Remplace les matières liées au profil (admin ou espace prof à terme). */
    public function replaceMatieres(int $profilProfesseurId, array $matiereIds): void
    {
        $this->db->prepare('DELETE FROM professeur_matieres WHERE profil_professeur_id = ?')->execute([$profilProfesseurId]);
        $stmt = $this->db->prepare('INSERT INTO professeur_matieres (profil_professeur_id, matiere_id) VALUES (?, ?)');
        foreach (array_unique(array_filter($matiereIds)) as $mid) {
            $mid = (int) $mid;
            if ($mid > 0) {
                $stmt->execute([$profilProfesseurId, $mid]);
            }
        }
    }

    public function getByUtilisateurId(int $utilisateurId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE utilisateur_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateurId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /** Récupère ou crée le profil professeur pour un utilisateur. */
    public function getOrCreateForUser(int $utilisateurId): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE utilisateur_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateurId]);
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }
        $userStmt = $this->db->prepare("SELECT prenom, nom FROM utilisateurs WHERE id = ? AND role = 'professeur' LIMIT 1");
        $userStmt->execute([$utilisateurId]);
        $user = $userStmt->fetch();
        if (!$user) {
            return null;
        }
        $titre = 'Professeur - ' . trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
        $now = date('Y-m-d H:i:s');
        $insert = $this->db->prepare(
            "INSERT INTO {$this->table} (utilisateur_id, titre, tarif_horaire, disponible, valide_par_admin, created_at, updated_at)
             VALUES (?, ?, 0, 0, 0, ?, ?)"
        );
        $insert->execute([$utilisateurId, $titre, $now, $now]);
        $id = (int) $this->db->lastInsertId();
        return $this->getByIdPublic($id) ?: null;
    }

    /** Détail profil + utilisateur (admin, sans filtre validation). */
    public function getByIdWithUser(int $id): ?array
    {
        $sql = "SELECT pp.*, u.email, u.prenom, u.nom, u.avatar, u.actif AS user_actif
                FROM {$this->table} pp
                JOIN utilisateurs u ON u.id = pp.utilisateur_id
                WHERE pp.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** IDs des matières liées (pour formulaires admin). */
    public function getMatiereIdsForProfil(int $profilProfesseurId): array
    {
        $stmt = $this->db->prepare('SELECT matiere_id FROM professeur_matieres WHERE profil_professeur_id = ?');
        $stmt->execute([$profilProfesseurId]);
        return array_map('intval', array_column($stmt->fetchAll(), 'matiere_id'));
    }

    /** Liste admin sans sous-requête matières (si table `professeur_matieres` absente). */
    public function getAllForAdminBasic(): array
    {
        $sql = "SELECT pp.*, u.email, u.prenom, u.nom, u.avatar FROM {$this->table} pp
                JOIN utilisateurs u ON u.id = pp.utilisateur_id
                ORDER BY pp.valide_par_admin ASC, pp.created_at DESC";
        $rows = $this->db->query($sql)->fetchAll();
        foreach ($rows as &$r) {
            $r['nb_matieres'] = 0;
        }
        unset($r);
        return $rows;
    }

    /** Liste tous les professeurs pour l'admin (avec nb de matières renseignées). */
    public function getAllForAdmin(): array
    {
        $sql = "SELECT pp.*, u.email, u.prenom, u.nom, u.avatar,
                COALESCE((SELECT COUNT(*) FROM professeur_matieres pm WHERE pm.profil_professeur_id = pp.id), 0) AS nb_matieres
                FROM {$this->table} pp
                JOIN utilisateurs u ON u.id = pp.utilisateur_id
                ORDER BY pp.valide_par_admin ASC, pp.created_at DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /** Met à jour le profil professeur. */
    public function updateProfil(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $allowed = ['titre', 'description', 'tarif_horaire', 'disponible', 'valide_par_admin', 'rappel_disponibilite_vu', 'updated_at'];
        $data = array_intersect_key($data, array_flip($allowed));
        if (empty($data)) {
            return false;
        }
        return $this->update($id, $data);
    }
}
