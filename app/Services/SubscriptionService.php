<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\AbonnementModel;
use App\Models\ParametreModel;

/**
 * Abonnements : plan gratuit ou paiement via PayTech (paytech.sn) lorsque configuré ;
 * sinon repli possible sur les chemins legacy InTouch / TouchPay.
 */
class SubscriptionService
{
    private ParametreModel $parametres;
    private AbonnementModel $abonnementModel;

    public function __construct()
    {
        $this->parametres = new ParametreModel();
        $this->abonnementModel = new AbonnementModel();
    }

    public function isModeAbonnement(): bool
    {
        $mode = $this->parametres->get('monetisation_mode', MONETISATION_MODE_DEFAULT);
        return $mode === 'abonnement';
    }

    public function getProvider(): string
    {
        $dbVal = $this->parametres->get('abonnement_provider', ABONNEMENT_PROVIDER_DEFAULT);
        $p = strtolower(trim((string) ($dbVal !== null && $dbVal !== '' ? $dbVal : 'gratuit')));
        if ($p === '') {
            $p = 'gratuit';
        }
        if (in_array($p, ['wave', 'wave_api', 'jemenipay', 'digitalpaye'], true)) {
            return 'intouch';
        }

        return $p;
    }

    /** Plan gratuit proposé (pas de paiement). */
    public function isPlanGratuitActif(): bool
    {
        $dbVal = $this->parametres->get('abonnement_plan_gratuit_actif', null);
        if ($dbVal !== null) {
            return $dbVal === '1';
        }
        // Si aucune valeur en base, déduire depuis le provider :
        // provider 'gratuit' → plan gratuit actif ; provider payant → plan gratuit inactif
        return $this->getProvider() === 'gratuit';
    }

    /**
     * Tarifs abonnement (FCFA/mois) : Client 1000, Expert 1500, Étudiant 500, Professeur 1000.
     * L'inscription est gratuite ; seul l'abonnement est payant.
     */

    /** Prix abonnement client (2 500 FCFA/mois — plan Jɛmɛnipay). */
    public function getPrixClientXof(): float
    {
        $constante = defined('ABONNEMENT_PRIX_CLIENT_XOF') ? (float) ABONNEMENT_PRIX_CLIENT_XOF : 2500.0;
        $db = (float) $this->parametres->get('abonnement_prix_client_xof', (string) $constante);
        return $db > 0 ? $db : $constante;
    }

    /** Prix abonnement expert (3 000 FCFA/mois — plan Jɛmɛnipay). */
    public function getPrixExpertXof(): float
    {
        $constante = defined('ABONNEMENT_PRIX_EXPERT_XOF') ? (float) ABONNEMENT_PRIX_EXPERT_XOF : 3000.0;
        $db = (float) $this->parametres->get('abonnement_prix_expert_xof', (string) $constante);
        return $db > 0 ? $db : $constante;
    }

    /** Prix abonnement étudiant (2 000 FCFA/mois — plan Jɛmɛnipay). */
    public function getPrixEtudiantXof(): float
    {
        $constante = defined('ABONNEMENT_PRIX_ETUDIANT_XOF') ? (float) ABONNEMENT_PRIX_ETUDIANT_XOF : 2000.0;
        $db = (float) $this->parametres->get('abonnement_prix_etudiant_xof', (string) $constante);
        return $db > 0 ? $db : $constante;
    }

    /** Prix abonnement professeur d'université (3 000 FCFA/mois — plan Jɛmɛnipay). */
    public function getPrixProfesseurXof(): float
    {
        $constante = defined('ABONNEMENT_PRIX_PROFESSEUR_XOF') ? (float) ABONNEMENT_PRIX_PROFESSEUR_XOF : 3000.0;
        $db = (float) $this->parametres->get('abonnement_prix_professeur_xof', (string) $constante);
        return $db > 0 ? $db : $constante;
    }

    public function getDureeJours(): int
    {
        $constante = defined('ABONNEMENT_DUREE_JOURS') ? (int) ABONNEMENT_DUREE_JOURS : 30;
        $db = (int) $this->parametres->get('abonnement_duree_jours', (string) $constante);
        return $db > 0 ? $db : $constante;
    }

    /**
     * Retourne true si l'inscription pour ce type d'utilisateur nécessite
     * une redirection vers une page de paiement (checkout MM/PayTech).
     * Retourne false si l'abonnement est gratuit ou si aucun paiement n'est requis.
     * Utilisé dans AuthController pour ne déclencher souscrire() qu'en cas de paiement réel.
     */
    public function needsPaymentRedirect(string $type): bool
    {
        // Plan gratuit explicitement activé en base → pas de paiement
        if ($this->isPlanGratuitActif()) {
            return false;
        }
        $provider = $this->getProvider();
        // Provider non-checkout → pas de redirection possible
        if (!in_array($provider, ['intouch', 'paytech'], true)) {
            return false;
        }
        // Provider checkout : vérifier le prix selon le type
        if ($type === 'expert') {
            $prix = $this->getPrixExpertXof();
        } elseif ($type === 'etudiant') {
            $prix = $this->getPrixEtudiantXof();
        } elseif ($type === 'professeur') {
            $prix = $this->getPrixProfesseurXof();
        } else {
            $prix = $this->getPrixClientXof();
        }
        return $prix > 0;
    }

    /** Vérifie si un utilisateur a un abonnement actif. */
    public function hasAbonnementActif(int $utilisateurId, string $type): bool
    {
        return $this->abonnementModel->hasAbonnementActif($utilisateurId, $type);
    }

    /** Active manuellement un abonnement (depuis l'admin). */
    public function activerManuellement(int $utilisateurId, string $type, int $dureeJours = 30): bool
    {
        $prix = 0.0;
        if ($type === 'client') {
            $prix = $this->getPrixClientXof();
        } elseif ($type === 'expert') {
            $prix = $this->getPrixExpertXof();
        } elseif ($type === 'professeur') {
            $prix = $this->getPrixProfesseurXof();
        } elseif ($type === 'etudiant') {
            $prix = $this->getPrixEtudiantXof();
        }
        $this->abonnementModel->createFromPayment(
            $utilisateurId,
            $type,
            'premium',
            'manuel_admin',
            'manuel_' . $utilisateurId . '_' . time(),
            $prix,
            'XOF',
            $dureeJours
        );
        return true;
    }

    /**
     * Souscrire à un abonnement (gratuit ou redirection paiement).
     * @return array{ok: bool, redirect?: string, error?: string}
     */
    public function souscrire(int $utilisateurId, string $type, string $plan = 'auto'): array
    {
        if (!in_array($type, ['client', 'expert', 'etudiant', 'professeur'], true)) {
            return ['ok' => false, 'error' => 'Type invalide'];
        }
        // Professeurs : tarif et flux dédiés
        if ($type === 'professeur') {
            $prix = $this->getPrixProfesseurXof();
            if ($plan === 'gratuit' || $prix <= 0) {
                $this->abonnementModel->createGratuit($utilisateurId, 'professeur', 365);
                return ['ok' => true];
            }
            $provider = $this->getProvider();
            if ($provider === 'gratuit' || $this->isPlanGratuitActif()) {
                $this->abonnementModel->createGratuit($utilisateurId, 'professeur', 365);
                return ['ok' => true];
            }
            if ($this->providerUsesMmCheckout($provider)) {
                return ['ok' => true, 'redirect' => $this->intouchAbonnementUrl('professeur')];
            }
            $this->abonnementModel->createGratuit($utilisateurId, 'professeur', 365);
            return ['ok' => true];
        }

        // Plan forcé 'gratuit' uniquement en dev / admin manuel
        if ($plan === 'gratuit') {
            $this->abonnementModel->createGratuit($utilisateurId, $type, 365);
            return ['ok' => true];
        }

        $provider = $this->getProvider();

        // Provider gratuit (test/dev) → accès libre
        if ($provider === 'gratuit' || $this->isPlanGratuitActif()) {
            $this->abonnementModel->createGratuit($utilisateurId, $type, 365);
            return ['ok' => true];
        }

        if ($type === 'client') {
            $prix = $this->getPrixClientXof();
        } elseif ($type === 'expert') {
            $prix = $this->getPrixExpertXof();
        } elseif ($type === 'etudiant') {
            $prix = $this->getPrixEtudiantXof();
        } else {
            $prix = 0.0;
        }
        if ($prix <= 0) {
            $this->abonnementModel->createGratuit($utilisateurId, $type, 365);
            return ['ok' => true];
        }

        if ($this->providerUsesMmCheckout($provider)) {
            return ['ok' => true, 'redirect' => $this->intouchAbonnementUrl($type)];
        }

        // Fallback : accès libre si provider non reconnu
        $this->abonnementModel->createGratuit($utilisateurId, $type, 365);
        return ['ok' => true];
    }

    /** Flux Mobile Money avec checkout externe : InTouch legacy ou PayTech selon configuration. */
    private function providerUsesMmCheckout(string $provider): bool
    {
        return in_array($provider, ['intouch', 'paytech'], true);
    }

    private function intouchAbonnementUrl(string $type): string
    {
        $base = rtrim(BASE_URL ?? '', '/');
        $pay  = new PayTechPaymentService();
        if ($pay->isConfigured()) {
            return $base . $pay->getAbonnementPaymentRelativePath($type);
        }
        $intouch = new IntouchPaymentService();

        return $base . $intouch->getAbonnementPaymentRelativePath($type);
    }

    /** Ancien callback Wave API : conservé pour ne pas casser les URLs bookmarkées (sans effet si ref inconnue). */
    public function confirmerPaiement(string $provider, string $clientReference, bool $succes = true): array
    {
        if (!$succes) {
            return ['ok' => false, 'error' => 'Paiement annulé ou échoué.'];
        }

        return ['ok' => false, 'error' => 'Ce mode de confirmation n’est plus utilisé. Utilisez la passerelle PayTech (IPN) ou les flux encore actifs.'];
    }

    public function getAbonnementActif(int $utilisateurId, string $type): ?array
    {
        return $this->abonnementModel->getActifByUser($utilisateurId, $type);
    }

    /**
     * Retourne l'abonnement programmé (date de début dans le futur) pour l'affichage utilisateur.
     */
    public function getAbonnementScheduled(int $utilisateurId, string $type): ?array
    {
        return $this->abonnementModel->getScheduledByUser($utilisateurId, $type);
    }

    /**
     * Programme automatiquement un abonnement gratuit qui démarrera le lendemain.
     * Appelé lors de la validation email ou du signup Google.
     * Ne fait rien si l'utilisateur a déjà un abonnement actif ou programmé.
     */
    public function planifierPourDemain(int $utilisateurId, string $type): bool
    {
        if (!in_array($type, ['client', 'expert', 'etudiant', 'professeur'], true)) {
            return false;
        }
        // Ne pas créer de doublon
        if ($this->abonnementModel->hasAnySubscription($utilisateurId, $type)) {
            return false;
        }
        $duree = $this->getDureeJours();
        $this->abonnementModel->createScheduled($utilisateurId, $type, 1, $duree);
        return true;
    }
}
