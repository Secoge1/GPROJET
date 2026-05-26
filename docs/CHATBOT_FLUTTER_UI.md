# GLOBALO – Structure de l’UI Chatbot (Flutter)

## Vue d’ensemble

L’écran chat Flutter communique avec l’API PHP `POST /api/chatbot/send` et affiche bulles de message, indicateur « en train d’écrire » et boutons d’actions rapides.

## Structure des widgets

```
ChatScreen
├── AppBar(title: "Assistant GLOBALO")
├── ListView.builder (messages)
│   ├── ChatBubble (user) / ChatBubble (assistant)
│   ├── ExpertCard (si experts dans le payload)
│   └── TypingIndicator (quand loading)
├── QuickActionsBar (Find expert, Post request, My sessions, Support)
└── ChatInput
    ├── TextField
    └── SendButton
```

## Modèles de données

```dart
// message_from_api.dart
class ChatMessage {
  final String role;      // 'user' | 'assistant'
  final String content;
  final String? intent;
  final List<ExpertSummary>? experts;
  final List<String>? quickActions;
}

class ExpertSummary {
  final int id;
  final String name;
  final String titre;
  final double? noteMoyenne;
  final int nombreAvis;
  final double tarifHoraire;
  final bool disponible;
}

// API response
class ChatResponse {
  final String reply;
  final String intent;
  final List<ExpertSummary> experts;
  final List<QuickAction> quickActions;
  final String conversationUid;
  final int? createdDemandeId;
}
```

## Appels API

- **Envoyer un message**  
  `POST /api/chatbot/send`  
  Body: `{"message": "...", "conversation_uid": "optional-uuid"}`  
  Headers: `Content-Type: application/json`, session cookie ou `Authorization: Bearer <token>` si utilisé.

- **Historique**  
  `GET /api/chatbot/history?conversation_uid=...`

- **Actions rapides**  
  `GET /api/chatbot/quick-actions`  
  Réponse: `{"quick_actions": [{"id": "find_expert", "label": "Trouver un expert"}, ...]}`

## Exemple de code Flutter (résumé)

```dart
// chat_screen.dart
class ChatScreen extends StatefulWidget {
  @override
  _ChatScreenState createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  final List<ChatMessage> _messages = [];
  String? _conversationUid;
  bool _loading = false;
  final _textController = TextEditingController();
  final _scrollController = ScrollController();

  Future<void> _sendMessage(String text) async {
    if (text.trim().isEmpty) return;
    setState(() {
      _messages.add(ChatMessage(role: 'user', content: text));
      _loading = true;
    });
    _scrollToBottom();

    final body = {
      'message': text,
      if (_conversationUid != null) 'conversation_uid': _conversationUid,
    };
    final response = await http.post(
      Uri.parse('$baseUrl/api/chatbot/send'),
      headers: {'Content-Type': 'application/json', ...authHeaders},
      body: jsonEncode(body),
    );

    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      setState(() {
        _conversationUid = data['conversation_uid'];
        _messages.add(ChatMessage(
          role: 'assistant',
          content: data['reply'],
          experts: (data['experts'] as List?)?.map((e) => ExpertSummary.fromJson(e)).toList(),
        ));
        _loading = false;
      });
    } else {
      setState(() => _loading = false);
      // show error
    }
    _scrollToBottom();
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Assistant GLOBALO')),
      body: Column(
        children: [
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              itemCount: _messages.length + (_loading ? 1 : 0),
              itemBuilder: (context, i) {
                if (i == _messages.length) {
                  return TypingIndicator();
                }
                final msg = _messages[i];
                return Column(
                  crossAxisAlignment: msg.role == 'user' ? Alignment.centerRight : Alignment.centerLeft,
                  children: [
                    ChatBubble(message: msg),
                    if (msg.experts != null && msg.experts!.isNotEmpty)
                      ...msg.experts!.map((e) => ExpertCard(expert: e)),
                  ],
                );
              },
            ),
          ),
          QuickActionsBar(
            actions: ['find_expert', 'post_request', 'my_sessions', 'support'],
            onTap: (id) => _onQuickAction(id),
          ),
          ChatInput(
            controller: _textController,
            onSend: () => _sendMessage(_textController.text),
          ),
        ],
      ),
    );
  }
}
```

## ChatBubble

- **User** : alignée à droite, fond bleu (ou couleur primaire), texte blanc.
- **Assistant** : alignée à gauche, fond gris clair, texte noir.
- Padding 12–16, border radius 16–18, marge verticale 4–8.

## ExpertCard

Pour chaque élément de `response.experts` :

- Nom + titre
- Note (étoiles ou chiffre) + nombre d’avis
- Tarif horaire (€/h)
- Badge « Disponible » si `disponible == true`
- Bouton **Démarrer une session** → navigation vers écran de réservation avec `expert_id`

## QuickActionsBar

Boutons horizontaux (ou chips) :

- **Trouver un expert** → pré-remplit le champ avec « Je cherche un expert » ou ouvre une liste d’experts.
- **Publier une demande** → « Je veux créer une demande d’assistance » ou écran « Nouvelle demande ».
- **Mes sessions** → navigation vers « Mes réservations ».
- **Contacter le support** → lien ou écran contact.

Au tap, soit envoyer un message prédéfini au chatbot, soit naviguer directement (mes sessions, support).

## TypingIndicator

Petites bulles animées (3 points) ou « L’assistant écrit… » pendant `_loading == true`.

## Gestion de l’authentification

- Si l’app utilise des sessions web : envoyer les cookies avec `http.Client` (ou équivalent).
- Si l’app utilise un token : header `Authorization: Bearer <token>`.
- L’API peut être utilisée sans être connecté : `conversation_uid` permet de reprendre la conversation.

## Dépendances suggérées

- `http` ou `dio` pour les appels API
- État : `Provider`, `Riverpod` ou `setState` selon le projet

## Résumé des endpoints

| Méthode | URL | Description |
|--------|-----|-------------|
| POST | `/api/chatbot/send` | Envoyer un message, recevoir la réponse (+ experts si find_expert) |
| GET | `/api/chatbot/history?conversation_uid=` | Historique de la conversation |
| GET | `/api/chatbot/quick-actions` | Liste des actions rapides (id + label) |
