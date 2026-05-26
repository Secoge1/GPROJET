# Intégration TouchPay (InTouch) — GLOBALO

Documentation technique complète de l'intégration du système de paiement **InTouch / TouchPay** dans le projet GLOBALO.

---

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Architecture technique](#2-architecture-technique)
3. [Deux modes de paiement](#3-deux-modes-de-paiement)
4. [Configuration requise](#4-configuration-requise)
5. [Fichiers impliqués](#5-fichiers-impliqués)
6. [Routes disponibles](#6-routes-disponibles)
7. [Flux par fonctionnalité](#7-flux-par-fonctionnalité)
   - 7.1 [Abonnement](#71-abonnement)
   - 7.2 [Dépôt portefeuille](#72-dépôt-portefeuille)
   - 7.3 [Paiement direct de session (réservation)](#73-paiement-direct-de-session-réservation)
8. [Table `transactions` — types et états](#8-table-transactions--types-et-états)
9. [Webhook InTouch (`/intouch/callback`)](#9-webhook-intouch-intouchcallback)
10. [Sécurité](#10-sécurité)
11. [Validation manuelle (admin)](#11-validation-manuelle-admin)
12. [Schéma de décision : quel mode utiliser ?](#12-schéma-de-décision--quel-mode-utiliser-)
13. [Variables d'environnement / Constantes](#13-variables-denvironnement--constantes)
14. [Personnalisation avancée](#14-personnalisation-avancée)

---

## 1. Vue d'ensemble

GLOBALO intègre le prestataire de paiement **InTouch** (groupe GUTouch) pour accepter les paiements Mobile Money en Afrique de l'Ouest (Orange Money Mali, Moov Africa, Wave).

Deux interfaces coexistent :

| Interface | Protocole | Cas d'usage |
|---|---|---|
| **API Pay-In (digest)** | cURL PUT HTTP Digest Auth → `api.gutouch.com` | Formulaire côté serveur ; pousse une demande de débit sur le téléphone du client |
| **Widget TouchPay** | Script JS `SendPaymentInfos()` côté navigateur | Page de paiement hébergée par InTouch ; aucun appel serveur au moment du paiement |

Les deux interfaces partagent le même webhook (`POST /intouch/callback`) pour la confirmation asynchrone du paiement.

---

## 2. Architecture technique

```
┌──────────────────────────────────────────────────────────────────┐
│                        GLOBALO (PHP MVC)                         │
│                                                                  │
│  Contrôleur                 Service                  Modèle      │
│  IntouchController  ──────► IntouchPaymentService ──► Transactions│
│  ClientController   ──────►          │                           │
│  ExpertController   ──────►          │                           │
│                                      │                           │
│                         ┌────────────┴────────────┐             │
│                         ▼                         ▼             │
│              API Pay-In (digest)        Widget TouchPay         │
│              PUT api.gutouch.com        JS SendPaymentInfos()   │
│                         │                         │             │
│                         └────────────┬────────────┘             │
│                                      ▼                           │
│                         POST /intouch/callback (webhook)         │
│                         → creditWallet() ou activateAbonnement() │
└──────────────────────────────────────────────────────────────────┘
```

**Couches concernées :**

- `app/Services/IntouchPaymentService.php` — logique métier centrale (toutes les opérations InTouch)
- `app/Controllers/IntouchController.php` — actions HTTP liées aux paiements InTouch
- `app/Controllers/ClientController.php` — pages portefeuille et payer (consomme les flags InTouch)
- `app/Models/TransactionModel.php` — persistance des transactions
- `app/Core/Router.php` — routage des URLs `/intouch/*` et `/app/*`

---

## 3. Deux modes de paiement

### Mode A — API Pay-In (HTTP Digest Auth)

Le serveur PHP envoie une requête **PUT** authentifiée vers l'API InTouch.
InTouch pousse ensuite une notification USSD / push sur le téléphone du client pour qu'il confirme.

```
Client (navigateur)          GLOBALO (PHP)              InTouch API
      │                           │                          │
      │  POST /intouch/initier    │                          │
      │ ──────────────────────►  │                          │
      │                           │  PUT api.gutouch.com     │
      │                           │ ─────────────────────►  │
      │                           │  ◄─ 200 INITIATED ──────│
      │  Redirect /verification   │                          │
      │ ◄──────────────────────── │                          │
      │                           │                          │
      │  (client valide sur son   │  POST /intouch/callback  │
      │   téléphone)              │ ◄────────────────────────│
      │                           │  → creditWallet /        │
      │                           │    activateAbonnement    │
```

**Identifiants requis :** `INTOUCH_API_USERNAME`, `INTOUCH_API_PASSWORD`, `INTOUCH_LOGIN_AGENT`, `INTOUCH_PASSWORD_AGENT`, `INTOUCH_ID`

### Mode B — Widget TouchPay (`SendPaymentInfos`)

Le navigateur charge un script JS depuis `touchpay.gutouch.com`. L'appel `SendPaymentInfos()` ouvre la page de paiement InTouch directement dans le navigateur. Aucun appel serveur PHP au moment du paiement.

```
Client (navigateur)          GLOBALO (PHP)              TouchPay JS
      │                           │                          │
      │  GET /intouch/touchpay-*  │                          │
      │ ──────────────────────►  │                          │
      │ ◄── page HTML + args ──── │                          │
      │                           │                          │
      │  [clic bouton Payer]      │                          │
      │ ─────────────────────────────────────────────────►  │
      │                           │   (paiement dans le      │
      │                           │    widget hébergé        │
      │                           │    par InTouch)          │
      │                           │                          │
      │                           │  POST /intouch/callback  │
      │                           │ ◄───────────────────────  │
      │                           │  → creditWallet /        │
      │                           │    activateAbonnement    │
```

**Identifiants requis :** `INTOUCH_ID`, `TOUCHPAY_SECURE_CODE`

---

## 4. Configuration requise

### Configuration minimale (widget uniquement)

```php
// config.php ou variables d'environnement
define('INTOUCH_ID',           'SECOGXXXX');     // Code agence InTouch
define('TOUCHPAY_SECURE_CODE', 'xxxxxxxx');      // Code sécurité TouchPay
```

Avec cette configuration : **abonnement**, **dépôt portefeuille** et **paiement de session** via le widget sont disponibles.

### Configuration complète (API + widget)

```php
define('INTOUCH_API_USERNAME',  'api_user');
define('INTOUCH_API_PASSWORD',  'api_pass');
define('INTOUCH_LOGIN_AGENT',   'agent_login');
define('INTOUCH_PASSWORD_AGENT','agent_pass');
define('INTOUCH_ID',            'SECOGXXXX');
define('TOUCHPAY_SECURE_CODE',  'xxxxxxxx');
```

Avec cette configuration : **API Pay-In** (formulaire téléphone) disponible en plus du widget.

### Détection automatique du mode

`IntouchPaymentService` détecte automatiquement le mode disponible :

```php
// Vérifier si l'API digest est configurée (Mode A)
$service->isConfigured();              // true si les 5 identifiants API sont présents

// Vérifier si le widget est configuré (Mode B)
$service->isTouchpayWidgetConfigured(); // true si INTOUCH_ID + TOUCHPAY_SECURE_CODE présents

// Fallback automatique : si API absent mais widget présent
$service->canFallbackAbonnementToTouchpayWidget(); // true → utilise widget pour abonnement
```

---

## 5. Fichiers impliqués

```
app/
├── Services/
│   └── IntouchPaymentService.php      Logique métier : createPayment(), prepareTouchpayWidget*(),
│                                      buildTouchpaySendPaymentArgs(), completeFromWebhook(),
│                                      creditWallet(), activateAbonnement(), notifyUser()
│
├── Controllers/
│   ├── IntouchController.php          Actions HTTP InTouch (toutes les routes /intouch/*)
│   └── ClientController.php          Injecte intouch_api_configured et touchpay_configured dans
│                                      les vues portefeuille et payer
│
├── Models/
│   └── TransactionModel.php           CRUD transactions, findByPaymentId(), finalizeIntouchSuccess(),
│                                      createTransaction(), validate(), refuse()
│
├── Core/
│   └── Router.php                     Route /intouch/* → IntouchController (auto via toCamelCase)
│                                      Route /app/touchpay-* → IntouchController (map explicite)
│
└── Views/
    ├── desktop/Intouch/
    │   ├── paiement.php               Formulaire API Pay-In (saisie téléphone + opérateur)
    │   ├── touchpay.php               Widget TouchPay — abonnement
    │   ├── touchpay_depot.php         Widget TouchPay — dépôt portefeuille
    │   ├── touchpay_depot_form.php    Formulaire saisie montant (avant widget dépôt)
    │   ├── touchpay_session.php       Widget TouchPay — paiement direct session
    │   ├── verification.php           Suivi transaction (code à saisir si API Pay-In)
    │   ├── succes.php                 Page de confirmation (tous types)
    │   └── historique.php             Historique paiements utilisateur
    │
    ├── desktop/Client/
    │   ├── portefeuille.php           Affiche boutons InTouch selon configuration
    │   └── payer.php                  Affiche bouton "Payer via TouchPay" si solde insuffisant
    │
    └── mobile/Client/
        ├── portefeuille.php           Idem desktop, version mobile
        └── payer.php                  Idem desktop, version mobile
```

---

## 6. Routes disponibles

### Routes bureau et mobile (auto-routage `/intouch/*`)

| Méthode | URL | Action | Description |
|---|---|---|---|
| GET | `/intouch/paiement/{type}` | `paiement()` | Formulaire API Pay-In abonnement |
| POST | `/intouch/initier` | `initier()` | Soumettre le formulaire API Pay-In abonnement |
| POST | `/intouch/initier-depot` | `initierDepot()` | Soumettre formulaire API Pay-In dépôt portefeuille |
| GET | `/intouch/touchpay/{type}` | `touchpay()` | Page widget TouchPay — abonnement |
| GET | `/intouch/touchpay-depot/{montant?}` | `touchpayDepot()` | Page widget TouchPay — dépôt portefeuille |
| GET | `/intouch/touchpay-session/{reservationId}` | `touchpaySession()` | Page widget TouchPay — paiement session |
| GET | `/intouch/verification/{paymentId}` | `verification()` | Suivi de transaction |
| POST | `/intouch/soumettre` | `soumettre()` | Saisie manuelle du code de transaction |
| GET | `/intouch/succes/{paymentId}` | `succes()` | Page de confirmation finale |
| GET | `/intouch/historique` | `historique()` | Historique des paiements |
| POST | `/intouch/callback` | `callback()` | **Webhook** — notification serveur InTouch |

### Routes application mobile (`/app/*` — map explicite dans Router.php)

| URL | Action déclenchée |
|---|---|
| `/app/touchpay-abonnement` | `IntouchController::touchpay()` |
| `/app/touchpay-depot` | `IntouchController::touchpayDepot()` |
| `/app/touchpay-session` | `IntouchController::touchpaySession()` |

> **Note :** Le contrôleur `Controller::render()` cherche d'abord la vue dans `Views/mobile/` et bascule automatiquement sur `Views/desktop/` si elle est absente. Les vues TouchPay étant uniquement dans `desktop/Intouch/`, elles sont servies aux deux versions.

---

## 7. Flux par fonctionnalité

### 7.1 Abonnement

L'abonnement donne accès à la plateforme (client, expert, étudiant, professeur).

#### Via API Pay-In

```
/abonnement → lien "Payer"
    │
    ▼
GET /intouch/paiement/{type}          # Formulaire (téléphone + opérateur)
    │ POST /intouch/initier
    ▼
IntouchController::initier()
    │ IntouchPaymentService::createPayment($userId, $prix, $commission, $phone, 'abonnement', $type, $operator)
    │   → PUT api.gutouch.com (HTTP Digest)
    │   → transactions (status=pending, type=abonnement, notes=ITP-XXXXXXXX)
    ▼
GET /intouch/verification/{paymentId}  # Client saisit le code reçu sur son téléphone
    │ POST /intouch/soumettre
    ▼
GET /intouch/succes/{paymentId}
    │
    ▼ (parallèlement)
POST /intouch/callback (webhook InTouch)
    │ IntouchPaymentService::completeFromWebhook()
    │   → transactions (status=success)
    │   → AbonnementModel::activate() → abonnements (statut=actif)
    │   → NotificationModel : notifier l'utilisateur
```

#### Via Widget TouchPay

```
/abonnement → lien "Payer"
    │
    ▼
GET /intouch/touchpay/{type}
    │ IntouchPaymentService::prepareTouchpayWidgetAbonnement()
    │   → transactions (status=pending, notes=touchpay_widget_pending)
    │   → buildTouchpaySendPaymentArgs($tx) → tableau args JS
    ▼
Page HTML avec script touchpay.gutouch.com
    │ SendPaymentInfos(paymentId, agencyCode, secureCode, domain, amount, callbackUrl)
    │   → Widget hébergé InTouch dans le navigateur
    ▼
POST /intouch/callback (webhook InTouch)
    │ → transactions (status=success)
    │ → AbonnementModel::activate()
```

---

### 7.2 Dépôt portefeuille

Permet de créditer le solde du portefeuille GLOBALO pour payer des missions.

#### Via API Pay-In (formulaire sur la page portefeuille)

```
GET /client/portefeuille              # Affiche le formulaire si intouch_api_configured=true
    │ POST /intouch/initier-depot
    ▼
IntouchController::initierDepot()
    │ IntouchPaymentService::createPayment($userId, $montant, 0, $phone, 'depot_portefeuille', 'depot', $operator)
    │   → PUT api.gutouch.com
    │   → transactions (type=depot_portefeuille, platform_fee=0)
    ▼
GET /intouch/verification/{paymentId}
    ▼
POST /intouch/callback
    │ → transactions (status=success)
    │ → creditWallet($userId, $amount) → wallets (solde += $amount)
```

#### Via Widget TouchPay (bouton sur la page portefeuille)

```
GET /client/portefeuille              # Affiche le bouton si touchpay_configured=true
    │ Clic bouton "Recharger via TouchPay" → JS redirect
    ▼
GET /intouch/touchpay-depot           # Sans montant → affichage formulaire de saisie
    │ (touchpay_depot_form.php)
    ▼
GET /intouch/touchpay-depot/{montant} # Avec montant
    │ IntouchPaymentService::prepareTouchpayWidgetDepot($userId, $montant)
    │   → transactions (type=depot_portefeuille, abonnement_type=depot_widget, notes=touchpay_widget_pending)
    │   → buildTouchpaySendPaymentArgs($tx)
    ▼
Page touchpay_depot.php — SendPaymentInfos(...)
    ▼
POST /intouch/callback
    │ → transactions (status=success)
    │ → creditWallet($userId, $amount)
```

**Logique d'affichage des boutons dans la vue `portefeuille.php` :**

```php
// Cas 1 : seulement API Pay-In configurée → formulaire classique (téléphone + opérateur)
// Cas 2 : seulement widget configuré → bouton TouchPay avec saisie montant
// Cas 3 : les deux configurés → formulaire classique + bouton TouchPay en alternative
if (!$intouchApiOk && $touchpayOk) {
    // Affiche uniquement le bouton TouchPay
} elseif ($intouchApiOk && $touchpayOk) {
    // Affiche le formulaire API + bouton TouchPay en alternative
}
```

---

### 7.3 Paiement direct de session (réservation)

Permet à un client de payer directement une mission via TouchPay quand son solde est insuffisant, **sans** rechargement préalable.

#### Flux complet

```
GET /client/payer/{reservationId}     # Solde insuffisant
    │ Affiche bouton "Payer directement via TouchPay" (si touchpay_configured)
    ▼
GET /intouch/touchpay-session/{reservationId}
    │ Validation :
    │   → réservation appartient au client authentifié
    │   → statut réservation = 'acceptee'
    │ IntouchPaymentService::prepareTouchpayWidgetSession($userId, $reservationId, $montant)
    │   → Déduplication : réutilise une transaction pendante < 2h pour la même réservation
    │   → transactions (type=paiement_session_touchpay, notes='session:{reservationId}')
    │   → buildTouchpaySendPaymentArgs($tx)
    ▼
Page touchpay_session.php — SendPaymentInfos(...)
    ▼
POST /intouch/callback
    │ completeFromWebhook()
    │   → type='paiement_session_touchpay' ∈ DEPOT_TYPES
    │   → creditWallet($userId, $amount)  ← le portefeuille est crédité
    │   → notifyUser() : redirect vers /client/payer/{reservationId}
    ▼
GET /intouch/succes/{paymentId}       # succes.php détecte notes='session:{id}'
    │ Affiche bouton "Finaliser le paiement de la mission →"
    ▼
GET /client/payer/{reservationId}     # Solde maintenant suffisant
    │ Confirmation paiement → escrow normal
    ▼
POST /client/payer/{reservationId}
    │ ClientController::payer() → PaymentService::payReservation()
    │   → Débite le portefeuille → escrow plateforme
    │   → Réservation statut = 'en_cours'
```

**Pourquoi ce flux en deux étapes ?**

La transaction TouchPay crédite d'abord le portefeuille, puis le client confirme l'escrow normalement. Cela préserve :
- La logique escrow existante (fonds bloqués jusqu'à fin de mission)
- La traçabilité (deux transactions séparées dans `transactions`)
- La sécurité (le webhook ne touche pas directement à la réservation)

**Données stockées dans `transactions.notes` :**

| Scénario | Valeur du champ `notes` |
|---|---|
| Widget dépôt / widget abonnement | `touchpay_widget_pending` |
| Widget paiement session | `session:{reservationId}` (ex. `session:42`) |
| API Pay-In (Push USSD) | `ITP-XXXXXXXX` (code placeholder puis code réel) |

---

## 8. Table `transactions` — types et états

### Types de transaction (`type`)

| Type | Déclencheur | Effet webhook/admin |
|---|---|---|
| `abonnement` | Paiement abonnement (API ou widget) | Active `abonnements` |
| `depot_portefeuille` | Dépôt portefeuille (API ou widget) | Crédite `wallets` |
| `paiement_session_touchpay` | Widget TouchPay session | Crédite `wallets` |

> Les types `depot_portefeuille` et `paiement_session_touchpay` sont définis dans la constante `DEPOT_TYPES` du service, ce qui déclenche `creditWallet()` à la confirmation.

### États (`status`)

| État | Signification |
|---|---|
| `pending` | Transaction créée, paiement en attente |
| `success` | Paiement confirmé (webhook ou admin) |
| `failed` | Paiement refusé ou erreur technique |

### Champs clés

| Champ | Rôle |
|---|---|
| `payment_id` | Identifiant unique GLOBALO (UUID v4) = `idFromClient` envoyé à InTouch |
| `total_amount` | Montant total débité côté InTouch (amount + platform_fee) |
| `amount` | Montant net (crédité au portefeuille ou à l'abonnement) |
| `platform_fee` | Commission plateforme (0 pour les dépôts) |
| `notes` | Métadonnées : code transaction, `session:{id}`, `touchpay_widget_pending` |
| `transaction_code` | Code USSD renseigné par le client (API Pay-In) |

---

## 9. Webhook InTouch (`/intouch/callback`)

Le webhook est l'unique point d'entrée pour la confirmation automatique des paiements.

### Endpoint

```
POST /intouch/callback
Content-Type: application/json
```

### Traitement (`IntouchController::callback()`)

```php
// 1. Vérification méthode HTTP
// 2. Vérification signature HMAC (si INTOUCH_CALLBACK_SECRET défini)
//    Header : X-InTouch-Signature ou X-Signature
// 3. Délégation à IntouchPaymentService::completeFromWebhook($payload)
```

### Logique de `completeFromWebhook()` (dans `IntouchPaymentService`)

```php
// 1. Extraire payment_id depuis payload
//    Clés acceptées : idFromClient, id_from_client, partner_transaction_id, reference
// 2. Vérifier que le status est un succès
//    Valeurs acceptées : SUCCESS, SUCCES, OK, COMPLETED, success=true, code=0
// 3. Charger la transaction (doit être pending + provider=intouch)
// 4. Dans une transaction DB :
//    → finalizeIntouchSuccess($paymentId)   [status=success, updated_at=NOW()]
//    → Si type ∈ ABONNEMENT_TYPES → activateAbonnement()
//    → Si type ∈ DEPOT_TYPES     → creditWallet()
// 5. notifyUser() → notification in-app + éventuellement redirect
```

### Sécurisation du webhook

```php
// Ajouter dans config.php ou .env :
define('INTOUCH_CALLBACK_SECRET', 'votre_secret_hmac');
```

Si la constante est définie, le webhook rejette toute requête dont le header `X-InTouch-Signature` ne correspond pas exactement. Sans cette constante, toutes les requêtes POST sont acceptées (déconseillé en production).

---

## 10. Sécurité

### Protection CSRF

Les formulaires POST (API Pay-In) sont protégés par un token CSRF :

```php
// Dans la vue
<?= \App\Core\Security::getCsrfField() ?>

// Dans le contrôleur
if (!Security::validateCsrf()) {
    $_SESSION['flash_error'] = 'Session expirée.';
    $this->redirect($retourUrl);
}
```

> **Widget TouchPay :** Le widget ne soumet pas de formulaire PHP. Aucun token CSRF n'est nécessaire côté GLOBALO pour le déclenchement du widget.

### Échappement des sorties

Toutes les données affichées en vue passent par `Security::escape()` :

```php
$e = fn($s) => \App\Core\Security::escape($s ?? '');
echo $e($transaction['payment_id']);
```

### Validation des accès

Chaque action InTouch vérifie le rôle et l'identité avant traitement :

```php
Auth::requireRole('client');                              // payer une session
Auth::requireRole('client', 'expert', 'etudiant', 'professeur'); // dépôt, abonnement
// + vérification que la réservation appartient bien au client authentifié
```

### Idempotence du webhook

`completeFromWebhook()` est idempotent : si la transaction est déjà `success` ou introuvable, le webhook répond `200 OK` sans ré-exécuter les effets de bord.

### Déduplication des transactions pendantes

Pour les paiements de session via widget, une transaction pendante de moins de 2h pour la même réservation est réutilisée plutôt que recréée :

```php
// Dans prepareTouchpayWidgetSession()
SELECT * FROM transactions
WHERE user_id = ? AND type = 'paiement_session_touchpay'
  AND notes = 'session:{reservationId}' AND status = 'pending'
  AND created_at > DATE_SUB(NOW(), INTERVAL 2 HOUR)
```

---

## 11. Validation manuelle (admin)

Si le webhook ne se déclenche pas (réseau, configuration), un administrateur peut valider manuellement via le panneau admin.

```php
// IntouchPaymentService::validateByAdmin($paymentId, $adminId, $notes)
// → transactionModel->validate() : passe status=success
// → activateAbonnement() ou creditWallet() selon le type
// → notifyUser()

// IntouchPaymentService::refuseByAdmin($paymentId, $adminId, $notes)
// → transactionModel->refuse() : passe status=failed
// → notifyUser()
```

Le client peut aussi soumettre manuellement le code reçu sur son téléphone :

```
POST /intouch/soumettre
payment_id={id}&transaction_code={code}
```

---

## 12. Schéma de décision : quel mode utiliser ?

```
Besoin de paiement
        │
        ▼
INTOUCH_ID + TOUCHPAY_SECURE_CODE définis ?
        │
    OUI │                          NON │
        ▼                              ▼
API digest définie ?           API digest définie ?
        │                              │
    OUI │       NON │              OUI │       NON │
        ▼           ▼                  ▼           ▼
   Formulaire  Widget seul        Formulaire    ❌ Aucun
   API + bouton                   API seul     paiement
   TouchPay en                    (pas de      disponible
   alternative                    widget)
```

### Résumé par cas

| Configuration | Abonnement | Dépôt portefeuille | Paiement session |
|---|---|---|---|
| Aucune | ❌ | ❌ | ❌ |
| `INTOUCH_ID` + `TOUCHPAY_SECURE_CODE` uniquement | ✅ Widget | ✅ Widget | ✅ Widget |
| API digest uniquement | ✅ Formulaire | ✅ Formulaire | ❌ (widget requis) |
| API digest + Widget | ✅ Les deux | ✅ Les deux | ✅ Widget |

---

## 13. Variables d'environnement / Constantes

| Variable / Constante | Obligatoire | Description |
|---|---|---|
| `INTOUCH_ID` | Oui (widget) | Code agence InTouch (ex. `SECOG8069`) |
| `TOUCHPAY_SECURE_CODE` | Oui (widget) | Code sécurité TouchPay |
| `INTOUCH_API_USERNAME` | Oui (API) | Login HTTP Digest Auth |
| `INTOUCH_API_PASSWORD` | Oui (API) | Mot de passe HTTP Digest Auth |
| `INTOUCH_LOGIN_AGENT` | Oui (API) | Login agent marchand |
| `INTOUCH_PASSWORD_AGENT` | Oui (API) | Mot de passe agent marchand |
| `INTOUCH_SERVICE_ORANGE` | Non | Code service Orange Money Mali (défaut : `ML_PAIEMENTMARCHAND_OM_TP`) |
| `INTOUCH_SERVICE_MOOV` | Non | Code service Moov Africa (défaut : `ML_PAIEMENTMARCHAND_MOOV_TP`) |
| `INTOUCH_SERVICE_WAVE` | Non | Code service Wave (défaut : `ML_PAIEMENTWAVE_TP`) |
| `INTOUCH_MERCHANT_URL` | Non | URL API marchand (remplace le modèle par défaut) |
| `INTOUCH_CALLBACK_SECRET` | Non | Secret HMAC pour vérifier la signature du webhook |
| `TOUCHPAY_SCRIPT_URL` | Non | URL du script JS TouchPay (défaut : `https://touchpay.gutouch.com/touchpay/script`) |
| `TOUCHPAY_ABONNEMENT_MODE` | Non | `auto` (défaut), `widget`, ou `api` — force le mode pour les abonnements |
| `TOUCHPAY_SENDPAYMENT_ARGS_JSON` | Non | Tableau JSON de surcharge des arguments `SendPaymentInfos()` |

### Placeholders pour `TOUCHPAY_SENDPAYMENT_ARGS_JSON`

Si cette variable est définie, elle remplace le tableau d'arguments par défaut envoyé à `SendPaymentInfos()`. Les placeholders suivants sont interpolés :

| Placeholder | Valeur |
|---|---|
| `{{payment_id}}` | Identifiant unique de la transaction |
| `{{agency_code}}` | `INTOUCH_ID` |
| `{{secure_code}}` | `TOUCHPAY_SECURE_CODE` |
| `{{domain_name}}` | Domaine du site (extrait de `BASE_URL`) |
| `{{amount}}` | Montant total en XOF (entier) |
| `{{callback}}` | URL du webhook (`/intouch/callback`) |
| `{{return_url}}` | URL de retour (`/intouch/verification/{paymentId}`) |

---

## 14. Personnalisation avancée

### Changer les codes de service opérateur

Par défaut, les codes Mali sont utilisés. Pour d'autres pays InTouch :

```php
// config.php
define('INTOUCH_SERVICE_ORANGE', 'CI_PAIEMENTMARCHAND_OM_TP'); // Côte d'Ivoire
define('INTOUCH_SERVICE_MOOV',   'CI_PAIEMENTMARCHAND_MOOV_TP');
define('INTOUCH_SERVICE_WAVE',   'CI_PAIEMENTWAVE_TP');
```

### Forcer l'API Pay-In pour les abonnements

```php
define('TOUCHPAY_ABONNEMENT_MODE', 'api');
// Le formulaire classique sera affiché même si le widget est configuré
```

### Personnaliser les arguments SendPaymentInfos

```php
// config.php — si votre contrat InTouch nécessite des paramètres supplémentaires
define('TOUCHPAY_SENDPAYMENT_ARGS_JSON', json_encode([
    '{{payment_id}}',
    '{{agency_code}}',
    '{{secure_code}}',
    '{{domain_name}}',
    '{{amount}}',
    '{{callback}}',
    '{{return_url}}',  // paramètre optionnel selon votre contrat
]));
```

### Ajouter un opérateur

Dans `IntouchController`, la méthode `operatorIntouch()` valide l'opérateur :

```php
private const INTOUCH_OPERATORS = ['ORANGE', 'MOOV', 'WAVE'];
```

Pour ajouter un opérateur (ex. MTN), ajouter la valeur dans le tableau et définir la constante de service correspondante dans `serviceCodeForOperator()`.

---

*Document généré le 2026-04-12 — GLOBALO v1.x*
