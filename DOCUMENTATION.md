# Documentation du Projet

## Structure du Projet

```
GloboProject/
├── app/
│   ├── Controllers/        # Contrôleurs MVC (ClientController, ExpertController, AbonnementController, …)
│   ├── Core/               # Noyau du framework (Router, Controller, Model, Auth, Database, Security)
│   ├── Helpers/            # Fonctions utilitaires globales
│   ├── Lang/               # Fichiers de traduction
│   ├── Models/             # Modèles PDO (PortefeuilleModel, PaiementModel, AbonnementModel, …)
│   ├── Services/           # Services métier (SubscriptionService, IntouchPaymentService, …)
│   ├── Tools/              # Outils internes
│   └── Views/
│       ├── desktop/        # Templates PHP pour navigateur desktop
│       └── mobile/         # Templates PHP pour navigateur mobile
├── config/
│   ├── config.php          # Constantes et paramètres de l'application
│   └── config.sample.env   # Modèle de variables d'environnement
├── database/               # Migrations et schéma SQL
├── public/                 # Ressources web accessibles (CSS, JS, images)
├── cache/                  # Cache applicatif
├── data/                   # Données persistées hors base
├── uploads/                # Fichiers téléversés
├── tests/                  # Tests automatisés
├── tools/                  # Scripts utilitaires CLI
├── cron/                   # Scripts de tâches planifiées
└── index.php               # Point d'entrée unique (front controller)
```

---

## Architecture Générale

- **Framework** : PHP maison (custom), sans dépendance à Laravel/Symfony.
- **Pattern** : MVC strict — `Router` → `Controller` → `Model` → `View`.
- **Routeur** : `app/Core/Router.php` — analyse les segments d'URL et dispatche vers `{Nom}Controller::{action}()`.
- **Authentification** : `app/Core/Auth` — rôles : `client`, `expert`, `professeur`, `etudiant`, `admin`.
- **Base de données** : PDO MySQL via `app/Core/Database` (singleton).
- **Vues** : Templates PHP purs, avec détection automatique desktop/mobile via `expertPathPrefix()`.
- **Services** : Couche métier découplée des contrôleurs (`SubscriptionService`, `IntouchPaymentService`, …).
- **Sécurité** : Tokens CSRF validés via `Security::validateCsrf()`, sanitisation via `Security::sanitizeString()`.

---

## Fonctionnalité 1 — Portefeuille / Mobile Money

### Routes

```
GET  /client/portefeuille   →  ClientController::portefeuille()
GET  /app/portefeuille      →  ClientController::portefeuille()   (vue mobile)
```

**Définition dans le routeur** (`app/Core/Router.php`, ligne 96) :
```php
'portefeuille' => ['Client', 'portefeuille']
```

### Contrôleur

**Fichier** : `app/Controllers/ClientController.php`, méthode `portefeuille()` (ligne 605)

```php
public function portefeuille(): void
{
    $portefeuilleModel = new \App\Models\PortefeuilleModel();
    $p     = $portefeuilleModel->getOrCreateForUser(Auth::id());
    $solde = (float) ($p['solde'] ?? 0);

    $transactions = (new \App\Models\PaiementModel())->getByClient(Auth::id(), 20);

    // Dépôts Wave en attente (table `transactions`)
    $stmt = $db->prepare("
        SELECT payment_id, amount, total_amount, status, transaction_code, created_at
        FROM transactions
        WHERE user_id = ? AND type = 'depot_portefeuille'
        ORDER BY created_at DESC LIMIT 5
    ");

    $this->render('portefeuille', [
        'solde'       => $solde,
        'transactions'=> $transactions,
        'wave_depots' => $waveDepotsPending,
    ]);
}
```

### Interactions base de données

| Modèle | Table | Opération |
|--------|-------|-----------|
| `PortefeuilleModel` | `portefeuilles` | `getOrCreateForUser()` — lit ou crée le portefeuille |
| `PaiementModel` | `paiements` | `getByClient($id, 20)` — 20 dernières transactions |
| PDO direct | `transactions` | Dépôts Wave `type = 'depot_portefeuille'` en attente |

**Requête clé — transactions Wave** :
```sql
SELECT payment_id, amount, total_amount, status, transaction_code, created_at
FROM transactions
WHERE user_id = ? AND type = 'depot_portefeuille'
ORDER BY created_at DESC
LIMIT 5
```

**Requête clé — historique paiements** (`PaiementModel::getByClient`) :
```sql
SELECT p.*, r.id AS reservation_id
FROM paiements p
LEFT JOIN reservations r ON r.id = p.reservation_id
WHERE p.client_id = ?
ORDER BY p.created_at DESC
LIMIT 20
```

### Logique métier

1. Récupère (ou crée) le portefeuille de l'utilisateur connecté.
2. Charge les 20 dernières transactions de la table `paiements`.
3. Charge les 5 derniers dépôts Wave Mobile Money en attente depuis `transactions`.
4. Rend la vue avec : solde courant, historique, statut dépôts Wave.

### Variables clés

| Variable | Type | Rôle |
|----------|------|------|
| `$solde` | `float` | Solde actuel du portefeuille en XOF |
| `$transactions` | `array` | Historique des 20 derniers mouvements |
| `$waveDepotsPending` | `array` | Dépôts Wave en attente de confirmation |

---

## Fonctionnalité 2 — Abonnement

### Routes

```
GET   /abonnement             →  AbonnementController::index()
POST  /abonnement/souscrire   →  AbonnementController::souscrire()
GET   /abonnement/callback    →  AbonnementController::callback()
```

**Définition dans le routeur** (`app/Core/Router.php`, lignes 165–171) :
```php
if (strtolower($segments[0]) === 'abonnement') {
    $this->controller = 'Abonnement';
    $this->action = !empty($segments[1]) ? $this->toCamelCase($segments[1]) : 'index';
}
```

### Contrôleur

**Fichier** : `app/Controllers/AbonnementController.php`

**Authentification** : tous les rôles (`client`, `expert`, `professeur`, `etudiant`).

**`index()` — Affichage** (ligne 27) :
```php
public function index(): void
{
    // Détermine le type d'abonnement selon le rôle connecté
    $type       = match($role) { 'expert' => 'expert', 'professeur' => 'professeur',
                                 'etudiant' => 'etudiant', default => 'client' };
    $abonnement = $this->subscriptionService->getAbonnementActif($userId, $type);
    $prixXof    = $this->subscriptionService->getPrix{Type}Xof();   // ex: getPrixExpertXof()
    // Rend la vue avec statut, prix, fournisseur de paiement
}
```

**`souscrire()` — Souscription POST** (ligne 75) :
```php
public function souscrire(): void
{
    Security::validateCsrf();
    $result = $this->subscriptionService->souscrire($userId, $type, 'auto');
    if (!empty($result['redirect'])) {
        $_SESSION['pending_abo_ref']     = $result['client_reference'];
        $_SESSION['pending_abo_user_id'] = $userId;
        header('Location: ' . $result['redirect']);  // → InTouch/Wave
        exit;
    }
    // Sinon : abonnement gratuit activé directement
}
```

**`callback()` — Retour paiement** (ligne 113) :
```php
public function callback(): void
{
    // Vérification CSRF : ref en session doit correspondre au paramètre GET
    if ($pendingRef !== $ref || $pendingUserId !== Auth::id()) { /* rejet */ }
    unset($_SESSION['pending_abo_ref'], $_SESSION['pending_abo_user_id']); // usage unique
    $this->subscriptionService->confirmerPaiement($provider, $ref, $success);
}
```

### Interactions base de données

| Modèle/Service | Table | Opération |
|----------------|-------|-----------|
| `AbonnementModel::getActifByUser()` | `abonnements` | Récupère l'abonnement actif |
| `AbonnementModel::createGratuit()` | `abonnements` | Crée un abonnement gratuit (365 jours) |
| `AbonnementModel::createFromPayment()` | `abonnements` | Crée un abonnement après paiement |
| `AbonnementModel::renewFromPayment()` | `abonnements` | Renouvelle depuis la date de fin existante |
| `AbonnementModel::expireOld()` | `abonnements` | Passe en `expire` les abonnements périmés |
| `ParametreModel::get()` | `parametres` | Lit les clés `abonnement_provider`, `abonnement_prix_*`, `monetisation_mode` |

**Requête clé — abonnement actif** :
```sql
SELECT * FROM abonnements
WHERE utilisateur_id = ? AND type = ? AND statut = 'actif' AND date_fin >= CURDATE()
ORDER BY date_fin DESC
LIMIT 1
```

**Requête clé — création abonnement gratuit** :
```sql
INSERT INTO abonnements
  (utilisateur_id, type, plan, date_debut, date_fin, statut, payment_provider)
VALUES (?, ?, 'gratuit', ?, ?, 'actif', NULL)
```

### Logique métier (`SubscriptionService::souscrire`)

```
souscrire($userId, $type, plan='auto')
 ├─ plan forcé 'gratuit'  → createGratuit(365 jours)
 ├─ provider = 'gratuit'  → createGratuit(365 jours)
 ├─ provider = 'intouch'  → retourner URL de redirection InTouch/TouchPay
 └─ fallback              → createGratuit(365 jours)
```

**Tarifs par rôle** (configurables en base, `parametres`) :

| Rôle | Prix défaut |
|------|-------------|
| `client` | 2 500 XOF/mois |
| `expert` | 3 000 XOF/mois |
| `etudiant` | 2 000 XOF/mois |
| `professeur` | 3 000 XOF/mois |

### Variables clés

| Variable | Rôle |
|----------|------|
| `$abonnement` | Abonnement actif ou `null` |
| `$type` | Type déduit du rôle (`client`, `expert`, …) |
| `$prixXof` | Prix en FCFA selon le rôle |
| `$planGratuitActif` | Booléen — accès libre sans paiement |
| `$modeAbonnement` | Booléen — mode monétisation activé |
| `pending_abo_ref` (session) | Référence paiement pour CSRF callback |

---

## Fonctionnalité 3 — Revenus Expert

### Route

```
GET  /expert/revenus   →  ExpertController::revenus()
GET  /app/revenus      →  ExpertController::revenus()   (vue mobile)
```

**Définition dans le routeur** (`app/Core/Router.php`, ligne 106) :
```php
'revenus' => ['Expert', 'revenus']
```

### Contrôleur

**Fichier** : `app/Controllers/ExpertController.php`, méthode `revenus()` (ligne 784)

```php
public function revenus(): void
{
    $profil = $this->profilModel->getByUtilisateurId(Auth::id());
    if (!$profil) { $this->redirect('/expert'); return; }

    $solde      = (new PortefeuilleModel())->getSolde(Auth::id());
    $totalGains = (new PaiementModel())->getTotalGainsExpert((int) $profil['id']);
    $transactions = (new PaiementModel())->getByExpert((int) $profil['id']);

    $this->render('revenus', [
        'solde'        => $solde,
        'totalGains'   => $totalGains,
        'transactions' => $transactions,
    ]);
}
```

### Interactions base de données

| Modèle | Table | Opération |
|--------|-------|-----------|
| `ProfilExpertModel::getByUtilisateurId()` | `profils_experts` | Vérifie l'existence du profil expert |
| `PortefeuilleModel::getSolde()` | `portefeuilles` | Solde disponible pour retrait |
| `PaiementModel::getTotalGainsExpert()` | `paiements` | Cumul des gains nets |
| `PaiementModel::getByExpert()` | `paiements` | Historique des transactions |

**Requête — total des gains** :
```sql
SELECT COALESCE(SUM(montant_net_expert), 0)
FROM paiements
WHERE expert_id = ? AND type = 'paiement_session' AND statut = 'effectue'
```

**Requête — historique transactions** :
```sql
SELECT *
FROM paiements
WHERE expert_id = ? AND type IN ('paiement_session', 'retrait')
ORDER BY created_at DESC
```

### Logique métier

1. Vérifie que le profil expert est bien validé (accès refusé sinon).
2. Calcule le solde disponible dans le portefeuille (disponible pour retrait immédiat).
3. Calcule le total cumulé des gains via `montant_net_expert` (après déduction de la commission plateforme).
4. Charge l'historique complet (`paiement_session` + `retrait`).
5. Rend la vue avec les KPIs et un bouton vers `/expert/retrait-choix`.

### Variables clés

| Variable | Rôle |
|----------|------|
| `$solde` | Solde disponible dans le portefeuille |
| `$totalGains` | Cumul total des gains nets depuis le début |
| `$transactions` | Tableau de tous les mouvements financiers |
| `$profil['id']` | ID du profil expert (≠ ID utilisateur) |

---

## Fonctionnalité 4 — Retrait

### Routes

```
GET   /expert/retrait-choix          →  ExpertController::retraitChoix()
GET   /expert/retrait?operateur=XXX  →  ExpertController::retrait()   (formulaire)
POST  /expert/retrait                →  ExpertController::retrait()   (traitement)
```

**Définition dans le routeur** (`app/Core/Router.php`, lignes 107–108) :
```php
'retrait-choix' => ['Expert', 'retraitChoix'],
'retrait'       => ['Expert', 'retrait'],
```

### Contrôleur

**Fichier** : `app/Controllers/ExpertController.php`

**`retraitChoix()` — Étape 1 : choix opérateur** (ligne 808) :
```php
public function retraitChoix(): void
{
    $solde = (new PortefeuilleModel())->getSolde(Auth::id());
    $this->render('retrait_choix', ['solde' => $solde]);
    // Affiche 3 boutons : ORANGE | MOOV | WAVE
    // Chaque bouton → GET /expert/retrait?operateur=ORANGE (ou MOOV ou WAVE)
}
```

**`retrait()` — Étape 2 : formulaire + traitement** (ligne 826) :
```php
public function retrait(): void
{
    $operateur = strtoupper($_POST['operateur'] ?? $_GET['operateur'] ?? '');

    // GET sans opérateur valide → redirection vers retraitChoix
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !self::retraitOperateurValide($operateur)) {
        $this->redirect($choixUrl); return;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validations
        // ...

        $pdo->beginTransaction();
            $portefeuilleModel->debiter(Auth::id(), $montant);     // débit atomique
            $this->retraitModel->create($profil['id'], $montant, 'ORANGE|0612345678');
            (new PaiementModel())->create([
                'type' => 'retrait', 'montant' => $montant, 'statut' => 'en_attente'
            ]);
        $pdo->commit();
    }
}
```

### Interactions base de données

| Modèle | Table | Opération |
|--------|-------|-----------|
| `PortefeuilleModel::getSolde()` | `portefeuilles` | Lecture du solde disponible |
| `PortefeuilleModel::debiter()` | `portefeuilles` | Débit atomique (`WHERE solde >= ?`) |
| `DemandeRetraitModel::getByExpert()` | `demandes_retrait` | Historique des retraits de l'expert |
| `DemandeRetraitModel::create()` | `demandes_retrait` | Création de la demande (statut `en_attente`) |
| `PaiementModel::create()` | `paiements` | Enregistrement du mouvement financier |
| `ParametreModel::get()` | `parametres` | Devise plateforme (`devise_plateforme`, défaut `XOF`) |

**Requête — débit atomique** (anti-découvert concurrentiel) :
```sql
UPDATE portefeuilles
SET solde = solde - ?
WHERE utilisateur_id = ? AND solde >= ?
```

**Requête — création demande de retrait** :
```sql
INSERT INTO demandes_retrait (expert_id, montant, iban, statut)
VALUES (?, ?, ?, 'en_attente')
```

**Requête — historique retraits** :
```sql
SELECT * FROM demandes_retrait
WHERE expert_id = ?
ORDER BY created_at DESC
```

### Logique métier

**Flux en 2 étapes** :

```
Étape 1 : GET /expert/retrait-choix
  └─ Affiche solde + 3 cartes opérateur (ORANGE / MOOV / WAVE)

Étape 2 : GET /expert/retrait?operateur=ORANGE
  └─ Formulaire : montant + numéro Mobile Money

Étape 3 : POST /expert/retrait
  ├─ Validation opérateur ∈ [ORANGE, MOOV, WAVE]
  ├─ Validation montant ≥ 500 XOF
  ├─ Validation montant ≤ solde
  ├─ Validation numéro ≥ 8 caractères
  └─ Transaction atomique :
       ① debiter(portefeuilles)
       ② INSERT demandes_retrait  (statut='en_attente')
       ③ INSERT paiements          (type='retrait', statut='en_attente')
       → Traitement manuel admin sous 24–48h
```

**Stockage opérateur** : le champ `iban` (34 car.) encode `OPERATEUR|NUMERO` :
```
ORANGE|0612345678   →  stocké dans demandes_retrait.iban
```

### Variables clés

| Variable | Rôle |
|----------|------|
| `$operateur` | Opérateur Mobile Money sélectionné (`ORANGE`, `MOOV`, `WAVE`) |
| `$montant` | Montant à retirer (min 500 XOF) |
| `$numeroMobileMoney` | Numéro de téléphone Mobile Money (min 8 chiffres) |
| `$ibanStocke` | Concaténation `OPERATEUR\|NUMERO` stockée en base |
| `$solde` | Solde disponible avant débit |
| `$demandes` | Historique des demandes de retrait de l'expert |

---

## Tables de Base de Données Impliquées

| Table | Rôle | Relations clés |
|-------|------|----------------|
| `utilisateurs` | Comptes utilisateurs (tous rôles) | Référencée par toutes les tables métier |
| `portefeuilles` | Solde Mobile Money par utilisateur | `utilisateur_id → utilisateurs.id` (unique) |
| `paiements` | Journal financier centralisé (dépôts, sessions, retraits) | `client_id`, `expert_id → utilisateurs.id` ; `reservation_id → reservations.id` |
| `transactions` | Dépôts Wave en attente (état intermédiaire) | `user_id → utilisateurs.id` |
| `abonnements` | Abonnements actifs/expirés par utilisateur | `utilisateur_id → utilisateurs.id` |
| `demandes_retrait` | Demandes de retrait soumises par les experts | `expert_id → profils_experts.id` |
| `profils_experts` | Profil et validation de chaque expert | `utilisateur_id → utilisateurs.id` |
| `reservations` | Réservations de sessions entre client et expert | `client_id`, `expert_id → utilisateurs.id` |
| `parametres` | Configuration dynamique (tarifs, mode monétisation, devise) | — |

### Colonnes notables

**`paiements`** :
```
type    : ENUM('depot', 'paiement_session', 'commission', 'retrait', 'remboursement')
statut  : ENUM('en_attente', 'effectue', 'echoue', 'annule', 'rembourse')
montant_net_expert : DECIMAL(12,2)  -- montant après commission plateforme
statut_escrow      : ENUM('bloque', 'libere', 'rembourse')
```

**`abonnements`** :
```
type    : ENUM('client', 'expert', 'etudiant', 'professeur')
plan    : ENUM('gratuit', 'premium')
statut  : ENUM('actif', 'expire', 'annule')
external_reference : VARCHAR(120)  -- référence fournisseur de paiement
```

**`demandes_retrait`** :
```
iban    : VARCHAR(34)  -- format : 'OPERATEUR|NUMERO' (ex: 'ORANGE|0612345678')
statut  : ENUM('en_attente', 'traitee', 'refusee')
```

---

## Dépendances & Librairies Clés

| Composant | Rôle |
|-----------|------|
| `app/Core/Router` | Routage URL personnalisé, détection desktop/mobile (`isApp()`) |
| `app/Core/Auth` | Session, identité, contrôle de rôle (`requireRole()`) |
| `app/Core/Database` | Singleton PDO MySQL |
| `app/Core/Security` | Validation CSRF (`validateCsrf()`), sanitisation (`sanitizeString()`) |
| `app/Core/Model` | Classe de base : `insert()`, `find()`, `db` PDO |
| `app/Services/SubscriptionService` | Orchestration complète du cycle de vie d'un abonnement |
| `app/Services/IntouchPaymentService` | Intégration paiement InTouch / TouchPay / Wave |
| `app/Services/PaymentService` | Service de paiement générique (dépôts, sessions) |
| **PHP** ≥ 8.0 | `declare(strict_types=1)`, `match`, types de retour `void` |
| **MySQL** / MariaDB | Base de données relationnelle |
| **Sessions PHP** | État de connexion, tokens CSRF, messages flash |
