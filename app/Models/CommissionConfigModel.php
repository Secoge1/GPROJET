<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Commissions : défaut, premium (expert certifié), par pays.
 * Priorité : expert premium > pays > défaut.
 */
class CommissionConfigModel extends Model
{
    protected string $table = 'commission_config';

    /**
     * Retourne le % de commission à appliquer.
     * @param int $expertProfilId id profils_experts
     * @param bool $expertEstPremium (certifié)
     * @param string|null $paysCode ISO 3166-1 alpha-2 (ex: SN, FR)
     */
    public function getPourcent(int $expertProfilId, bool $expertEstPremium = false, ?string $paysCode = null): float
    {
        $paramModel = new ParametreModel();
        $mode = $paramModel->get('monetisation_mode', defined('MONETISATION_MODE_DEFAULT') ? MONETISATION_MODE_DEFAULT : 'commission');
        if ($mode === 'abonnement') {
            $provider = $paramModel->get('abonnement_provider', defined('ABONNEMENT_PROVIDER_DEFAULT') ? ABONNEMENT_PROVIDER_DEFAULT : 'gratuit');
            // En mode abonnement PAYANT : l'abonnement est la rémunération → commission 0%
            // En mode abonnement GRATUIT : inscription libre mais commission normale sur les prestations
            if ($provider !== 'gratuit') {
                return 0.0;
            }
            // Mode gratuit : appliquer la commission configurée (ci-dessous)
        }
        if ($expertEstPremium) {
            $stmt = $this->db->prepare("SELECT valeur_pourcent FROM {$this->table} WHERE type = 'premium' AND expert_profil_id = ? AND actif = 1 LIMIT 1");
            $stmt->execute([$expertProfilId]);
            $v = $stmt->fetchColumn();
            if ($v !== false) {
                return (float) $v;
            }
            return (float) (new ParametreModel())->get('commission_premium_pourcent', '10');
        }
        if ($paysCode !== null && $paysCode !== '') {
            $stmt = $this->db->prepare("SELECT valeur_pourcent FROM {$this->table} WHERE type = 'pays' AND pays_code = ? AND actif = 1 LIMIT 1");
            $stmt->execute([strtoupper(substr($paysCode, 0, 2))]);
            $v = $stmt->fetchColumn();
            if ($v !== false) {
                return (float) $v;
            }
        }
        $stmt = $this->db->prepare("SELECT valeur_pourcent FROM {$this->table} WHERE type = 'defaut' AND actif = 1 LIMIT 1");
        $stmt->execute();
        $v = $stmt->fetchColumn();
        if ($v !== false) {
            return (float) $v;
        }
        return (float) (new ParametreModel())->get('commission_pourcent', (string) COMMISSION_DEFAULT);
    }

    public function getDefaut(): float
    {
        $stmt = $this->db->prepare("SELECT valeur_pourcent FROM {$this->table} WHERE type = 'defaut' AND actif = 1 LIMIT 1");
        $stmt->execute();
        $v = $stmt->fetchColumn();
        return $v !== false ? (float) $v : COMMISSION_DEFAULT;
    }

    public function setDefaut(float $pourcent): void
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET valeur_pourcent = ? WHERE type = 'defaut' LIMIT 1");
        $stmt->execute([$pourcent]);
        if ($stmt->rowCount() === 0) {
            $this->insert(['type' => 'defaut', 'valeur_pourcent' => $pourcent]);
        }
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY type, pays_code, expert_profil_id");
        return $stmt->fetchAll();
    }
}
