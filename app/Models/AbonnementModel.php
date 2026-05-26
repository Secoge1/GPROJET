<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class AbonnementModel extends Model
{
    protected string $table = 'abonnements';

    /**
     * Retourne l'abonnement actif (date_debut déjà atteinte ET date_fin future).
     * Un abonnement programmé pour demain n'est PAS retourné ici.
     */
    public function getActifByUser(int $utilisateurId, string $type): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table}
             WHERE utilisateur_id = ? AND type = ? AND statut = 'actif'
               AND date_debut <= CURDATE() AND date_fin >= CURDATE()
             ORDER BY date_fin DESC LIMIT 1"
        );
        $stmt->execute([$utilisateurId, $type]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function hasAbonnementActif(int $utilisateurId, string $type): bool
    {
        return $this->getActifByUser($utilisateurId, $type) !== null;
    }

    /**
     * Retourne l'abonnement programmé (date_debut dans le futur) pour un utilisateur.
     * Permet d'afficher « À venir le JJ/MM/AAAA » dans l'espace utilisateur/admin.
     */
    public function getScheduledByUser(int $utilisateurId, string $type): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table}
             WHERE utilisateur_id = ? AND type = ? AND statut = 'actif'
               AND date_debut > CURDATE()
             ORDER BY date_debut ASC LIMIT 1"
        );
        $stmt->execute([$utilisateurId, $type]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** Nombre d'abonnements actifs dont la date de début est dans le futur (= en attente d'activation). */
    public function countScheduled(): int
    {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM {$this->table} WHERE statut = 'actif' AND date_debut > CURDATE()"
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * Crée un abonnement programmé dont le départ est dans $delaiJours jours.
     * La subscription sera effective dès que date_debut <= CURDATE() (le lendemain).
     */
    public function createScheduled(int $utilisateurId, string $type, int $delaiJours = 1, int $dureeJours = 365): int
    {
        $dateDebut = date('Y-m-d', strtotime("+{$delaiJours} days"));
        $dateFin   = date('Y-m-d', strtotime("+{$delaiJours} days +{$dureeJours} days"));
        $this->db->prepare(
            "INSERT INTO {$this->table}
             (utilisateur_id, type, plan, date_debut, date_fin, statut, payment_provider)
             VALUES (?, ?, 'gratuit', ?, ?, 'actif', 'auto_inscription')"
        )->execute([$utilisateurId, $type, $dateDebut, $dateFin]);
        return (int) $this->db->lastInsertId();
    }

    /** Vérifie si l'utilisateur a déjà une ligne active ou programmée (évite les doublons à la vérification email). */
    public function hasAnySubscription(int $utilisateurId, string $type): bool
    {
        $stmt = $this->db->prepare(
            "SELECT id FROM {$this->table}
             WHERE utilisateur_id = ? AND type = ? AND statut = 'actif' AND date_fin >= CURDATE()
             LIMIT 1"
        );
        $stmt->execute([$utilisateurId, $type]);
        return (bool) $stmt->fetchColumn();
    }

    /** Crée un abonnement gratuit (sans paiement externe). */
    public function createGratuit(int $utilisateurId, string $type, int $dureeJours = 365): int
    {
        $dateDebut = date('Y-m-d');
        $dateFin = date('Y-m-d', strtotime("+{$dureeJours} days"));
        $this->db->prepare(
            "INSERT INTO {$this->table} (utilisateur_id, type, plan, date_debut, date_fin, statut, payment_provider) VALUES (?, ?, 'gratuit', ?, ?, 'actif', NULL)"
        )->execute([$utilisateurId, $type, $dateDebut, $dateFin]);
        return (int) $this->db->lastInsertId();
    }

    /** Crée ou met à jour un abonnement après paiement Wave/DigitalPaye. */
    public function createFromPayment(
        int $utilisateurId,
        string $type,
        string $plan,
        string $provider,
        string $externalReference,
        float $montant,
        string $devise = 'XOF',
        int $dureeJours = 30
    ): int {
        $dateDebut = date('Y-m-d');
        $dateFin = date('Y-m-d', strtotime("+{$dureeJours} days"));
        $this->db->prepare(
            "INSERT INTO {$this->table} (utilisateur_id, type, plan, date_debut, date_fin, statut, payment_provider, external_reference, montant_paye, devise) VALUES (?, ?, ?, ?, ?, 'actif', ?, ?, ?, ?)"
        )->execute([$utilisateurId, $type, $plan, $dateDebut, $dateFin, $provider, $externalReference, $montant, $devise]);
        return (int) $this->db->lastInsertId();
    }

    public function getByExternalReference(string $externalReference): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE external_reference = ? LIMIT 1");
        $stmt->execute([$externalReference]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Expire les abonnements dépassés (cron ou appel ponctuel). */
    public function expireOld(): int
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET statut = 'expire' WHERE statut = 'actif' AND date_fin < CURDATE()");
        $stmt->execute();
        return $stmt->rowCount();
    }

    public function getAllForAdmin(int $limit = 500): array
    {
        $stmt = $this->db->prepare("
            SELECT a.*, u.prenom, u.nom, u.email
            FROM {$this->table} a
            JOIN utilisateurs u ON u.id = a.utilisateur_id
            ORDER BY a.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function expirer(int $id): void
    {
        $this->db->prepare("UPDATE {$this->table} SET statut = 'expire' WHERE id = ?")
            ->execute([$id]);
    }

    /** Prolonge la date de fin d'un abonnement (renouvellement Jɛmɛnipay automatique). */
    public function prolonger(int $id, string $nouvelleDateFin): void
    {
        $this->db->prepare(
            "UPDATE {$this->table} SET date_fin = ?, statut = 'actif', updated_at = NOW() WHERE id = ?"
        )->execute([$nouvelleDateFin, $id]);
    }

    /**
     * Renouvellement manuel (nouveau paiement) : prolonge à partir de max(date_fin, aujourd'hui).
     */
    public function renewFromPayment(
        int $id,
        string $newExternalReference,
        float $montant,
        int $dureeJours
    ): void {
        $stmt = $this->db->prepare("SELECT date_fin FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return;
        }
        $today    = date('Y-m-d');
        $baseDate = $row['date_fin'] >= $today ? $row['date_fin'] : $today;
        $newFin   = date('Y-m-d', strtotime($baseDate . " +{$dureeJours} days"));
        $this->db->prepare(
            "UPDATE {$this->table} SET date_fin = ?, statut = 'actif', external_reference = ?, montant_paye = ?, payment_provider = 'intouch', devise = 'XOF', updated_at = NOW() WHERE id = ?"
        )->execute([$newFin, $newExternalReference, $montant, $id]);
    }

    public function countActifs(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE statut = 'actif' AND date_fin >= CURDATE()");
        return (int) $stmt->fetchColumn();
    }
}
