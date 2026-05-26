<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\PaiementModel;
use App\Models\PortefeuilleModel;
use App\Models\SoldePlateformeModel;
use App\Models\ReservationModel;
use App\Models\CommissionConfigModel;
use App\Models\ProfilExpertModel;

/**
 * Paiements sécurisés type marketplace :
 * - Client paie → argent en escrow (solde plateforme), commission déduite, expert pas encore crédité
 * - Mission terminée → libération vers portefeuille expert
 * - Litige → remboursement client
 */
class PaymentService
{
    private PaiementModel $paiementModel;
    private PortefeuilleModel $portefeuilleModel;
    private SoldePlateformeModel $soldePlateforme;
    private ReservationModel $reservationModel;
    private CommissionConfigModel $commissionConfig;
    private ProfilExpertModel $profilExpertModel;

    public function __construct()
    {
        $this->paiementModel = new PaiementModel();
        $this->portefeuilleModel = new PortefeuilleModel();
        $this->soldePlateforme = new SoldePlateformeModel();
        $this->reservationModel = new ReservationModel();
        $this->commissionConfig = new CommissionConfigModel();
        $this->profilExpertModel = new ProfilExpertModel();
    }

    /**
     * Calcule commission et montant net expert pour un montant TTC.
     * @return array{commission_pourcent: float, commission: float, montant_net: float}
     */
    public function calculerCommission(int $expertProfilId, float $montantTotal, ?string $paysCode = null): array
    {
        $profil = $this->profilExpertModel->find($expertProfilId);
        $estPremium = $profil && !empty($profil['certifie']);
        $pourcent = $this->commissionConfig->getPourcent($expertProfilId, (bool) $estPremium, $paysCode);
        $commission = round($montantTotal * ($pourcent / 100), 2);
        $montantNet = round($montantTotal - $commission, 2);
        return [
            'commission_pourcent' => $pourcent,
            'commission' => $commission,
            'montant_net' => $montantNet,
        ];
    }

    /**
     * Paiement client → escrow (débit client, crédit plateforme, enregistrement paiement en "bloque").
     * À appeler quand le client paie une réservation acceptée.
     */
    public function processPaiementEscrow(int $reservationId, int $clientId): array
    {
        $reservation = $this->reservationModel->find($reservationId);
        if (!$reservation || (int) $reservation['client_id'] !== $clientId) {
            return ['ok' => false, 'error' => 'Réservation invalide'];
        }
        if ($reservation['statut'] !== 'acceptee') {
            return ['ok' => false, 'error' => 'Réservation non acceptée'];
        }
        $montant = (float) $reservation['montant_total'];
        $expertProfilId = (int) $reservation['expert_id'];

        // Récupérer le pays du client pour appliquer la règle de commission par pays si configurée
        $paysCode = null;
        try {
            $stmtPays = \App\Core\Database::getInstance()->prepare("SELECT pays FROM utilisateurs WHERE id = ? LIMIT 1");
            $stmtPays->execute([$clientId]);
            $row = $stmtPays->fetch(\PDO::FETCH_ASSOC);
            $paysCode = !empty($row['pays']) ? (string) $row['pays'] : null;
        } catch (\Throwable $e) {
            // Colonne 'pays' absente (migration non appliquée) : pas de règle par pays
        }

        $calc = $this->calculerCommission($expertProfilId, $montant, $paysCode);
        $soldeClient = $this->portefeuilleModel->getSolde($clientId);
        if ($soldeClient < $montant) {
            return ['ok' => false, 'error' => 'Solde insuffisant'];
        }
        if ($this->paiementModel->getByReservation($reservationId)) {
            return ['ok' => false, 'error' => 'Paiement déjà effectué'];
        }

        $db = \App\Core\Database::getInstance();
        $db->beginTransaction();
        try {
            if (!$this->portefeuilleModel->debiter($clientId, $montant, "reservation_{$reservationId}")) {
                throw new \RuntimeException('Débit client impossible');
            }
            if (!$this->soldePlateforme->crediter($montant)) {
                throw new \RuntimeException('Crédit plateforme impossible');
            }
            $this->paiementModel->create([
                'reservation_id' => $reservationId,
                'client_id' => $clientId,
                'expert_id' => $expertProfilId,
                'type' => 'paiement_session',
                'montant' => $montant,
                'commission_plateforme' => $calc['commission'],
                'montant_net_expert' => $calc['montant_net'],
                'statut' => 'en_attente',
                'statut_escrow' => 'bloque',
            ]);
            $this->reservationModel->updateStatut($reservationId, 'en_cours');
            $db->commit();
            return [
                'ok' => true,
                'commission' => $calc['commission'],
                'montant_net_expert' => $calc['montant_net'],
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Libération vers l'expert (mission terminée, pas de litige).
     */
    public function releaseToExpert(int $reservationId): array
    {
        $paiement = $this->paiementModel->getByReservation($reservationId);
        if (!$paiement || ($paiement['statut_escrow'] ?? '') !== 'bloque') {
            return ['ok' => false, 'error' => 'Aucun paiement en escrow'];
        }
        if ($this->litigeOuvert($reservationId)) {
            return ['ok' => false, 'error' => 'Litige ouvert'];
        }
        $montantNet = (float) $paiement['montant_net_expert'];
        $expertProfilId = (int) $paiement['expert_id'];
        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("SELECT utilisateur_id FROM profils_experts WHERE id = ?");
        $stmt->execute([$expertProfilId]);
        $expertUserId = (int) $stmt->fetchColumn();
        if (!$expertUserId) {
            return ['ok' => false, 'error' => 'Expert inconnu'];
        }

        $db->beginTransaction();
        try {
            if (!$this->soldePlateforme->debiter($montantNet)) {
                throw new \RuntimeException('Débit plateforme impossible');
            }
            if (!$this->portefeuilleModel->crediter($expertUserId, $montantNet)) {
                throw new \RuntimeException('Crédit expert impossible');
            }
            $this->paiementModel->update($paiement['id'], [
                'statut' => 'effectue',
                'statut_escrow' => 'libere',
                'libere_at' => date('Y-m-d H:i:s'),
            ]);
            $db->commit();
            return ['ok' => true];
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Remboursement client (litige ou annulation).
     */
    public function refund(int $reservationId): array
    {
        $paiement = $this->paiementModel->getByReservation($reservationId);
        if (!$paiement || ($paiement['statut_escrow'] ?? '') !== 'bloque') {
            return ['ok' => false, 'error' => 'Aucun paiement en escrow à rembourser'];
        }
        $montant = (float) $paiement['montant'];
        $clientId = (int) $paiement['client_id'];

        $db = \App\Core\Database::getInstance();
        $db->beginTransaction();
        try {
            if (!$this->soldePlateforme->debiter($montant)) {
                throw new \RuntimeException('Débit plateforme impossible');
            }
            if (!$this->portefeuilleModel->crediter($clientId, $montant)) {
                throw new \RuntimeException('Crédit client impossible');
            }
            $this->paiementModel->update($paiement['id'], [
                'statut' => 'rembourse',
                'statut_escrow' => 'rembourse',
            ]);
            $db->commit();
            return ['ok' => true];
        } catch (\Throwable $e) {
            $db->rollBack();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function litigeOuvert(int $reservationId): bool
    {
        $stmt = \App\Core\Database::getInstance()->prepare("SELECT 1 FROM litiges WHERE reservation_id = ? AND statut = 'ouvert' LIMIT 1");
        $stmt->execute([$reservationId]);
        return (bool) $stmt->fetch();
    }
}
