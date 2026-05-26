<?php
/**
 * GLOBALO - Modèle Utilisateurs
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class UtilisateurModel extends Model
{
    protected string $table = 'utilisateurs';

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = $data['created_at'];
        return $this->insert($data);
    }

    public function createPortefeuille(int $utilisateurId): void
    {
        $this->db->prepare('INSERT IGNORE INTO portefeuilles (utilisateur_id, solde, devise) VALUES (?, 0, ?)')
            ->execute([$utilisateurId, 'XOF']);
    }

    public function createProfilExpert(int $utilisateurId): int
    {
        $sql = 'INSERT INTO profils_experts (utilisateur_id, titre, description, tarif_horaire) VALUES (?, ?, ?, ?)';
        $this->db->prepare($sql)->execute([$utilisateurId, 'Expert', '', 0]);
        return (int) $this->db->lastInsertId();
    }

    public function createProfilEtudiant(int $utilisateurId): int
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'INSERT INTO profils_etudiants (utilisateur_id, created_at, updated_at) VALUES (?, ?, ?)';
        $this->db->prepare($sql)->execute([$utilisateurId, $now, $now]);
        return (int) $this->db->lastInsertId();
    }

    public function setTokenVerification(int $id, string $token, string $expire): bool
    {
        return $this->update($id, [
            'token_verification' => $token,
            'token_verification_expire' => $expire,
        ]);
    }

    public function findByTokenVerification(string $token): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE token_verification = ? AND token_verification_expire > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function setEmailVerifie(int $id): bool
    {
        return $this->update($id, [
            'email_verifie' => 1,
            'token_verification' => null,
            'token_verification_expire' => null,
        ]);
    }

    public function updateDerniereConnexion(int $id): bool
    {
        return $this->update($id, ['derniere_connexion' => date('Y-m-d H:i:s')]);
    }

    public function setTokenReinitialisation(int $id, string $token): bool
    {
        $expire = date('Y-m-d H:i:s', time() + 3600);
        return $this->update($id, [
            'token_reinitialisation' => $token,
            'token_reinit_expire' => $expire,
        ]);
    }

    public function findByTokenReinit(string $token): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE token_reinitialisation = ? AND token_reinit_expire > NOW() LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function resetPasswordWithToken(string $token, string $hash): bool
    {
        $user = $this->findByTokenReinit($token);
        if (!$user) {
            return false;
        }
        $this->update((int) $user['id'], [
            'mot_de_passe' => $hash,
            'token_reinitialisation' => null,
            'token_reinit_expire' => null,
        ]);
        return true;
    }

    public function countAll(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        return (int) $stmt->fetchColumn();
    }

    public function countByRole(string $role): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE role = ?");
        $stmt->execute([$role]);
        return (int) $stmt->fetchColumn();
    }

    public function getAllWithRole(?string $role = null, int $limit = 200): array
    {
        $sql = "SELECT id, email, role, nom, prenom, actif, created_at FROM {$this->table} WHERE 1=1";
        $params = [];
        if ($role !== null) {
            $sql .= " AND role = ?";
            $params[] = $role;
        }
        $sql .= " ORDER BY created_at DESC LIMIT " . (int) $limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getProfilExpert(int $utilisateurId): ?array
    {
        $sql = 'SELECT * FROM profils_experts WHERE utilisateur_id = ? LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$utilisateurId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Révoque un compte (désactivation après 3 mauvais avis). */
    public function revokeAccount(int $userId): bool
    {
        return $this->update($userId, ['actif' => 0]);
    }

    // ── Google OAuth ─────────────────────────────────────────────────────────

    /** Recherche par ID Google (colonne google_id). */
    public function findByGoogleId(string $googleId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE google_id = ? LIMIT 1");
        $stmt->execute([$googleId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Crée un compte depuis les données Google.
     * Le mot de passe est un hash aléatoire (compte Google pur — connexion uniquement via OAuth).
     */
    public function createFromGoogle(array $g, string $role, string $pays = 'Mali'): int
    {
        $now = date('Y-m-d H:i:s');
        return $this->insert([
            'email'         => $g['email'],
            'google_id'     => $g['sub'],
            'auth_provider' => 'google',
            'prenom'        => $g['given_name']  ?? explode(' ', $g['name'] ?? 'Utilisateur')[0],
            'nom'           => $g['family_name'] ?? (explode(' ', $g['name'] ?? '')[1] ?? ''),
            'role'          => $role,
            'pays'          => $pays,
            'actif'         => 1,
            'email_verifie' => 1,
            'mot_de_passe'  => \App\Core\Security::hashPassword(bin2hex(random_bytes(24))),
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }

    /** Associe un google_id à un compte email existant. */
    public function attachGoogleId(int $userId, string $googleId): bool
    {
        return $this->update($userId, ['google_id' => $googleId, 'auth_provider' => 'google']);
    }
}
