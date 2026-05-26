# Monétisation GLOBALO

## Activation

1. Exécuter la migration SQL :
   ```bash
   mysql -u root -p globalo < database/migration_monetisation.sql
   ```
   Ou depuis phpMyAdmin : importer `migration_monetisation.sql`.

2. Si MySQL signale "Duplicate column" sur `paiements.statut_escrow` ou `libere_at`, les colonnes existent déjà — ignorer l’erreur.

## Ce qui a été ajouté

### 1. Commission automatique
- **Commission par défaut** : configurable en Admin → Paramètres (ex. 15 %, 20 %, 25 %).
- **Commission experts premium** : taux réduit pour les experts certifiés (`profils_experts.certifie = 1`).
- **Commission par pays** : possible via la table `commission_config` (type = `pays`, `pays_code` = ISO 2 lettres).

### 2. Portefeuille et escrow
- Le **client paie** → l’argent est débité de son portefeuille et crédité au **solde plateforme** (table `solde_plateforme`).
- Le paiement est enregistré en **escrow** (`statut_escrow = 'bloque'`).
- À la **fin de la mission** (expert clique « Terminer »), le système **libère** automatiquement le montant net vers le portefeuille de l’expert.
- La commission reste sur le solde plateforme.

### 3. Litiges
- Table `litiges` : un litige peut bloquer la libération.
- Si un litige est ouvert sur une réservation, `releaseToExpert()` ne fait rien tant que le litige n’est pas clos.
- L’admin peut utiliser `PaymentService::refund()` pour rembourser le client (à brancher sur une interface « Gérer litiges » si besoin).

### 4. Admin
- **Revenus** (`/admin/revenus`) : commissions sur la période (jour / semaine / mois), volume, nombre de transactions, solde plateforme, experts les plus actifs.
- **Paramètres** : commission défaut, commission premium, devise (ex. XOF/FCFA). Moyen de paiement : **Wave Money Mobile** uniquement pour l’instant (dépôts, paiements, retraits).

## Flux résumé

1. Client paie (réservation acceptée) → débit client, crédit plateforme, enregistrement paiement en escrow.
2. Mission terminée par l’expert → débit plateforme (montant net), crédit expert, paiement marqué « libéré ».
3. En cas de litige → admin peut déclencher un remboursement client via `PaymentService::refund($reservationId)`.

## Sécurité

- Toutes les opérations de portefeuille et de paiement passent par des **transactions** (beginTransaction / commit / rollBack).
- Les montants sont arrondis à 2 décimales.
- Le débit du solde plateforme avant crédit expert évite les incohérences.
