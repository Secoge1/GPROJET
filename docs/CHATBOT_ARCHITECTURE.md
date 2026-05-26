# GLOBALO – Architecture du chatbot IA

## 1. Vue d’ensemble

Le chatbot est un assistant conversationnel intégré à la plateforme GLOBALO. Il permet de :
- Répondre aux questions sur le fonctionnement de la plateforme (paiements, retraits, réservations).
- Détecter l’intention (find_expert, create_task, help_*, general_question).
- Rechercher et proposer des experts selon la demande (catégorie, disponibilité, tarif, note).
- Créer une demande d’assistance (task) à partir de la conversation (durée, budget, catégorie).

```
┌─────────────────┐     ┌──────────────────┐     ┌─────────────────────┐
│  Flutter / Web  │────▶│  API /chatbot    │────▶│  ChatbotService     │
│  Chat UI        │◀────│  (PHP)           │◀────│  (intent + OpenAI)  │
└─────────────────┘     └──────────────────┘     └──────────┬──────────┘
                                                              │
                    ┌──────────────────────────────────────────┼────────────────────────────────┐
                    │                                          │                                  │
                    ▼                                          ▼                                  ▼
           ┌────────────────┐                        ┌────────────────────┐            ┌────────────────────┐
           │  OpenAI API    │                        │  ExpertMatching    │            │  DemandeModel      │
           │  (intent + NL) │                        │  (search experts)   │            │  (create task)     │
           └────────────────┘                        └────────────────────┘            └────────────────────┘
                                                              │                                  │
                                                              ▼                                  ▼
                                                    ┌────────────────────┐            ┌────────────────────┐
                                                    │  MySQL: profils_    │            │  MySQL: demandes_   │
                                                    │  experts,          │            │  assistance         │
                                                    │  competences       │            └────────────────────┘
                                                    └────────────────────┘
```

## 2. Intents (détection d’intention)

| Intent            | Description                          | Actions backend                          |
|-------------------|--------------------------------------|------------------------------------------|
| `find_expert`     | Recherche d’un expert                | Recherche par compétence/dispo/tarif     |
| `create_task`     | Création d’une demande d’assistance  | Collecte durée/budget → création demande|
| `help_payment`    | Questions sur les paiements          | Réponse depuis aide + OpenAI             |
| `help_withdrawal` | Questions sur les retraits           | Idem                                    |
| `help_booking`    | Comment réserver un expert           | Idem                                    |
| `help_commission` | Commission plateforme                | Idem                                    |
| `my_sessions`     | Voir mes sessions / réservations     | Renvoi lien ou données si API            |
| `general_question`| Autre question                      | Réponse OpenAI + contexte plateforme    |

## 3. API Endpoints

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| `POST` | `/api/chatbot/send` | Envoyer un message, recevoir la réponse (texte + éventuels experts / quick_actions). |
| `GET`  | `/api/chatbot/history` | Historique de la conversation (optionnel, pour reprendre une session). |
| `GET`  | `/api/chatbot/quick-actions` | Liste des actions rapides (Find expert, Post request, My sessions, Support). |
| `POST` | `/api/chatbot/quick-action` | Déclencher une action rapide (payload `action_id` ou `action`). |

### Payload `POST /api/chatbot/send`

**Request:**
```json
{
  "message": "I need help with Flutter",
  "conversation_id": "uuid-optional"
}
```

**Response (exemple):**
```json
{
  "reply": "I can help you find a Flutter expert. Here are some available now.",
  "intent": "find_expert",
  "quick_actions": ["find_expert", "post_request", "my_sessions", "support"],
  "experts": [
    {
      "id": 1,
      "name": "Jean Dupont",
      "titre": "Développeur Flutter",
      "note_moyenne": 4.8,
      "nombre_avis": 12,
      "tarif_horaire": 65.00,
      "disponible": true
    }
  ],
  "conversation_id": "uuid"
}
```

## 4. Base de données (additions)

- **chatbot_conversations** : session de chat (utilisateur, conversation_id, métadonnées).
- **chatbot_messages** : messages (role: user/assistant, content, intent, payload JSON).
- **chatbot_config** : personnalité, réponses par défaut, catégories supportées (admin).
- Paramètres **parametres** : `chatbot_openai_api_key`, `chatbot_system_prompt`, etc. (ou table dédiée).

Voir `database/chatbot_schema.sql`.

## 5. Flux principal

1. **Client** envoie un message → **API** `POST /api/chatbot/send`.
2. **ChatbotService** :
   - Optionnel : récupère les derniers messages (conversation_id) pour contexte.
   - Appel **OpenAI** (classification d’intent + génération de réponse) avec prompt système.
3. Selon l’intent :
   - **find_expert** : **ExpertMatchingService** → recherche par compétence (mapping NL→competence_id), disponibilité, note, tarif → liste d’experts.
   - **create_task** : si paramètres manquants (durée, budget), réponse pour les demander ; sinon **DemandeModel::create**.
   - **help_*** : réponse à partir de la doc aide (paramétrable) + OpenAI.
4. Réponse JSON : `reply`, `intent`, `experts` (si pertinent), `quick_actions`, `conversation_id`.

## 6. Sécurité et scalabilité

- Authentification : session ou token (Bearer) pour les utilisateurs connectés ; anonyme possible avec `conversation_id` éphémère.
- Clé OpenAI stockée côté serveur (env ou `parametres`), jamais exposée au client.
- Limite de débit (rate limit) par utilisateur/conversation pour éviter abus et coûts API.
- Cache optionnel des réponses “help” et des listes d’experts (TTL court) pour réduire appels OpenAI et charge BDD.

## 7. Admin

- **Personnalité** : prompt système du chatbot (texte libre).
- **Réponses par défaut** : pour chaque intent, réponse par défaut si OpenAI échoue.
- **Catégories supportées** : mapping nom → `competences.id` pour le matching.
- **Documentation d’aide** : blocs texte pour paiements, retraits, réservations, commissions (injectés dans le contexte OpenAI).

Documentation détaillée des prompts : voir `docs/CHATBOT_PROMPTS.md`.

## 8. Installation rapide

1. **Base de données**  
   Exécuter le script d’ajout des tables et paramètres du chatbot :  
   `mysql -u ... -p globalo < database/chatbot_schema.sql`

2. **Clé OpenAI**  
   - Soit définir la variable d’environnement `OPENAI_API_KEY`.  
   - Soit renseigner la clé dans **Admin > Chatbot IA** (stockée dans la table `parametres`).

3. **Admin**  
   Aller sur **/admin/chatbot** pour modifier le prompt système, les réponses par défaut et les textes d’aide (paiements, retraits, réservation, commission).

4. **API**  
   - Envoyer un message : `POST /api/chatbot/send` avec body `{"message": "..."}`.  
   - Historique : `GET /api/chatbot/history?conversation_uid=...`.  
   - Actions rapides : `GET /api/chatbot/quick-actions`.
