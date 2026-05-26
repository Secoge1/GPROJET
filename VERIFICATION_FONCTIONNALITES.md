# GLOBALO — Vérification des fonctionnalités (post-implémentation)

Toutes les fonctionnalités listées ci-dessous ont été implémentées avec vérification à l’appui.

---

## ✅ Implémenté et vérifiable

### Comptes et sécurité
| Fonctionnalité | Fichiers / vérification |
|----------------|-------------------------|
| Vérification email | Inscription : `AuthController::inscription()` génère token, redirige vers `verification-envoyee`. Envoi email via `MailerService::sendVerificationEmail()`. SMTP si configuré (SMTP_HOST, SMTP_USER, SMTP_PASS, etc.), sinon `mail()`. Lien : `auth/verifier?token=`. |
| Création portefeuille à l’inscription | `UtilisateurModel::createPortefeuille()` appelé après `create()` dans `AuthController::inscription()`. |
| Profils vérifiés / badges | Champ `valide_par_admin` en BDD. Affichage "Expert vérifié" dans `Experts/show.php`. Validation côté admin : `AdminController::validerExpert()`, `admin/experts`. |

### Clients
| Fonctionnalité | Fichiers / vérification |
|----------------|-------------------------|
| Liste des experts disponibles | `ExpertsController::index()`, `ProfilExpertModel::getListDisponibles()` (disponible=1, valide_par_admin=1). Vue `Experts/index.php`. |
| Filtres par compétence et recherche | Paramètres GET `competence`, `q` dans `ExpertsController::index()`, formulaire dans la vue. |
| Fiche expert publique (profil + avis) | `ExpertsController::show($id)`, `ProfilExpertModel::getByIdPublic()`, `AvisModel::getByExpert()`. Vue `Experts/show.php`. |
| Réservation d’une session | `ClientController::reserver()` : formulaire choix expert + date/heure. Création en BDD via `ReservationModel::create()`. Lien depuis "Mes demandes" → "Réserver un expert". |
| Paiement (portefeuille) | `ClientController::payer()` : débit client, crédit expert (montant - commission), enregistrement dans `paiements`, statut réservation → `en_cours`. Vue `Client/payer.php`, `Client/portefeuille.php`. |
| Portefeuille et dépôt (simulation) | `ClientController::portefeuille()`, `PortefeuilleModel::crediter()`, type `depot` dans `paiements`. |
| Notation après mission | `ClientController::noter()`, `AvisModel::createForReservation()`, `updateExpertStats()`. Vue `Client/noter.php`. Lien depuis détail réservation si statut `terminee` et pas encore noté. |
| Mes commandes | `ClientController::commandes()` : liste des réservations côté client. Vues `Client/commandes.php` (desktop + mobile). URL : `/client/commandes`. |

### Experts
| Fonctionnalité | Fichiers / vérification |
|----------------|-------------------------|
| Réception des demandes (ouvertes) | `ExpertController::demandes()`, `DemandeModel::getOuvertesPourExpert()`. Vue `Expert/demandes.php`. |
| Accepter / refuser une réservation | `ExpertController::accepter()`, `refuser()` : mise à jour statut réservation, notification client. Liens dans `Expert/reservations.php`. |
| Terminer une session | `ExpertController::terminer()` : statut → `terminee`, notification client. Lien "Terminer la session" si statut `en_cours`. |
| Revenus et historique | `ExpertController::revenus()`, `PaiementModel::getTotalGainsExpert()`, `getByExpert()`. Vue `Expert/revenus.php`. |
| Demande de retrait | `ExpertController::retrait()`, `DemandeRetraitModel::create()`, débit du portefeuille. Vue `Expert/retrait.php`. |
| Mes prestations | `ExpertController::prestations()` : réservations terminées (sessions réalisées). Vues `Expert/prestations.php` (desktop + mobile). URL : `/expert/prestations`. |

### Statuts des missions
| Statut | Où c’est appliqué |
|--------|-------------------|
| En attente | À la création de la réservation (`ReservationModel::create()`). |
| Acceptée | `ExpertController::accepter()`. |
| En cours | Après paiement réussi dans `ClientController::payer()`. |
| Terminée | `ExpertController::terminer()`. |
| Annulée | `ExpertController::refuser()`. |

### Communication
| Fonctionnalité | Fichiers / vérification |
|----------------|-------------------------|
| Messagerie par réservation | `MessagesController::index()` (liste), `conversation($id)` (détail). `MessageModel::getByReservation()`, `create()`, `marquerLus()`. |
| Envoi / réception en AJAX | `Api\MessagesController::list()` (GET, paramètres `reservation_id`, `after_id`), `send()` (POST). Polling dans `Messages/conversation.php` (intervalle 3 s). |
| Pièces jointes (messagerie) | Table `pieces_jointes`. `PieceJointeModel`, `UploadService`. Formulaire conversation : `pieces[]` (multipart). API `send()` accepte fichiers. Téléchargement : `FichierController::piece()` → `/fichier/piece/{id}`. |

### Admin
| Fonctionnalité | Fichiers / vérification |
|----------------|-------------------------|
| Gestion des utilisateurs | `AdminController::users()`, `UtilisateurModel::getAllWithRole()`. Vue `Admin/users.php`. |
| Activer / désactiver un compte | `AdminController::toggleActif()`, URL `admin/toggle-actif/{id}`. |
| Validation des profils experts | `AdminController::experts()`, `validerExpert()`. `ProfilExpertModel::getAllForAdmin()`. Vue `Admin/experts.php`. |
| Paramètres (commission, nom, email) | `AdminController::parametres()`, `ParametreModel::get()`, `set()`, `getCommissionPercent()`. Vue `Admin/parametres.php`. |
| Signalements (liste) | `AdminController::signalements()`, requête SQL sur table `signalements`. Vue `Admin/signalements.php`. |
| Statistiques tableau de bord | `AdminController::index()` : total utilisateurs, experts, réservations. |

### API REST
| Endpoint | Rôle |
|----------|------|
| `GET /api/messages/list?reservation_id=&after_id=` | Liste des messages d’une réservation (pour polling). |
| `POST /api/messages/send` | Envoi d’un message (reservation_id, contenu, optionnel : pieces[]). Réponse inclut id, contenu. |
| `GET /fichier/piece/{id}` | Téléchargement pièce jointe (accès réservation vérifié). |
| `GET /api/auth/me` | Utilisateur connecté (existant). |

### Sécurité
- CSRF : champs dans les formulaires, validation dans `public/index.php` pour POST non-API.
- Mots de passe : `Security::hashPassword()`, `verifyPassword()`.
- Requêtes SQL : PDO préparées partout.
- Sorties : `Security::escape()` dans les vues.
- Rôles : `Auth::requireRole()`, `requireAuth()` sur les actions protégées.

### Routeur Admin
- `/admin` → `AdminController::index()`.
- `/admin/users` → `AdminController::users()`.
- `/admin/toggle-actif/{id}` → `AdminController::toggleActif()`.
- `/admin/experts` → `AdminController::experts()`.
- `/admin/valider-expert/{id}` → `AdminController::validerExpert()`.
- `/admin/parametres` → `AdminController::parametres()`.
- `/admin/signalements` → `AdminController::signalements()`.

### Commande et prestation
| Fonctionnalité | Fichiers / vérification |
|----------------|-------------------------|
| Commande (client) | Alias des réservations. `ClientController::commandes()`, vues `Client/commandes.php`. URL : `/client/commandes`. |
| Prestation (expert) | Réservations terminées. `ExpertController::prestations()`, `ReservationModel::getByExpertWithStatut(..., 'terminee')`. Vues `Expert/prestations.php`. URL : `/expert/prestations`. |

### Session / visio (structure de base)
| Fonctionnalité | Fichiers / vérification |
|----------------|-------------------------|
| Page session | `SessionController::room()`, URL `/session/room/{reservation_id}`. Vérification accès client/expert. Vue placeholder « Visioconférence bientôt disponible » + lien messagerie. Lien depuis conversation : « Session / visio ». |

### Vues mobiles dédiées
- **Client** : `mobile/Client/index.php`, `commandes.php`, `demandes.php`, `nouvelle_demande.php`.
- **Expert** : `mobile/Expert/index.php`, `demandes.php`, `missions.php`, `reservations.php`, `profil.php`, `prestations.php`.
- **Messages** : `mobile/Messages/index.php`, `conversation.php`.
- **Session** : `mobile/Session/room.php`.
- Détection mobile : `Controller::isMobileView` (User-Agent ou préfixe `/app`). Layout `layout_mobile.php` avec bottom nav.

---

## Non implémenté (hors périmètre actuel)

- **WebRTC** (appel vidéo, partage d’écran) : structure de la page session en place (`/session/room/{id}`) ; l’intégration technique (Jitsi, PeerJS, etc.) reste à faire.

---

## Comment vérifier manuellement

1. **Inscription** : créer un client et un expert → vérifier redirection "Vérification envoyée" et lien (en dev) vers `auth/verifier?token=...`.
2. **Experts** : en tant qu’admin, valider un expert (`admin/experts` → Valider). En tant que client, aller sur `/experts`, filtrer, ouvrir une fiche.
3. **Réservation** : client crée une demande → "Réserver un expert" → choisir expert + date → créer réservation.
4. **Expert** : dans "Réservations", accepter une réservation.
5. **Paiement** : client dépose des fonds (`client/portefeuille`) puis paie la réservation (`client/payer/{id}`).
6. **Session** : expert clique "Terminer la session" → client peut noter (`client/noter/{id}`).
7. **Messagerie** : depuis le détail d’une réservation (en cours ou terminée), ouvrir "Messagerie" et envoyer un message ; rafraîchir ou attendre le polling.
8. **Admin** : connexion admin → Utilisateurs, Validation experts, Paramètres, Signalements.
9. **Pièces jointes** : dans une conversation, joindre un fichier (PDF, images, etc.) puis envoyer ; affichage et lien de téléchargement sous chaque message.
10. **Email** : configurer SMTP (voir `config.sample.env`) pour l’envoi réel du lien de vérification en production.
11. **Commandes / Prestations** : client → `/client/commandes` ; expert → `/expert/prestations`.
12. **Session** : depuis une conversation, cliquer « Session / visio » → page placeholder.

Toutes les fonctionnalités ci-dessus sont implémentées dans le code et vérifiables via ces scénarios.
