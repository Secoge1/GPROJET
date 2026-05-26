<?php
/**
 * GLOBALO - Modèle Étudiant (profil, matières maîtrisées)
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class EtudiantModel extends Model
{
    protected string $table = 'profils_etudiants';

    /** Crée un profil étudiant lors de l'inscription. */
    public function createProfil(int $utilisateurId): int
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO profils_etudiants (utilisateur_id, created_at, updated_at) VALUES (?, ?, ?)';
        $this->db->prepare($sql)->execute([$utilisateurId, $now, $now]);
        return (int) $this->db->lastInsertId();
    }

    /** Récupère le profil étudiant complet (avec infos utilisateur). */
    public function getByUserId(int $userId): ?array
    {
        $sql = "SELECT pe.*, u.nom, u.prenom, u.email, u.avatar, u.created_at AS membre_depuis
                FROM profils_etudiants pe
                JOIN utilisateurs u ON u.id = pe.utilisateur_id
                WHERE pe.utilisateur_id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Met à jour le profil étudiant. */
    public function updateProfil(int $userId, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "UPDATE profils_etudiants SET " .
            implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data))) .
            " WHERE utilisateur_id = ?"
        );
        return $stmt->execute([...array_values($data), $userId]);
    }

    /** Récupère les matières maîtrisées par l'étudiant. */
    public function getMatieres(int $userId): array
    {
        $sql = "SELECT em.*, mu.nom AS matiere_nom, mu.filiere, mu.categorie, mu.slug AS matiere_slug
                FROM etudiant_matieres em
                JOIN profils_etudiants pe ON pe.id = em.profil_etudiant_id
                JOIN matieres_universitaires mu ON mu.id = em.matiere_id
                WHERE pe.utilisateur_id = ?
                ORDER BY mu.categorie, mu.nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** Définit les matières maîtrisées (remplace l'existant). */
    public function setMatieres(int $profilId, array $matiereIds, array $niveaux = [], array $notes = []): void
    {
        $this->db->prepare('DELETE FROM etudiant_matieres WHERE profil_etudiant_id = ?')->execute([$profilId]);
        if (empty($matiereIds)) {
            return;
        }
        $niveauxAllowed = ['debutant', 'intermediaire', 'avance', 'expert'];
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            'INSERT INTO etudiant_matieres (profil_etudiant_id, matiere_id, niveau_maitrise, note_obtenue, created_at)
             VALUES (?, ?, ?, ?, ?)'
        );
        foreach ($matiereIds as $matiereId) {
            $mid    = (int) $matiereId;
            $niveau = in_array($niveaux[$mid] ?? '', $niveauxAllowed, true) ? $niveaux[$mid] : 'intermediaire';
            $note   = isset($notes[$mid]) && is_numeric($notes[$mid]) ? round((float)$notes[$mid], 2) : null;
            $stmt->execute([$profilId, $mid, $niveau, $note, $now]);
        }
    }

    /** Ajoute ou met à jour une matière maîtrisée. */
    public function upsertMatiere(int $profilId, int $matiereId, string $niveau = 'intermediaire', ?float $note = null): void
    {
        $sql = "INSERT INTO etudiant_matieres (profil_etudiant_id, matiere_id, niveau_maitrise, note_obtenue, created_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE niveau_maitrise = VALUES(niveau_maitrise), note_obtenue = VALUES(note_obtenue)";
        $this->db->prepare($sql)->execute([$profilId, $matiereId, $niveau, $note]);
    }

    /** Supprime une matière du profil. */
    public function removeMatiere(int $profilId, int $matiereId): void
    {
        $this->db->prepare('DELETE FROM etudiant_matieres WHERE profil_etudiant_id = ? AND matiere_id = ?')
            ->execute([$profilId, $matiereId]);
    }
}
