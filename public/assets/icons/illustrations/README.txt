Illustrations des icônes système
================================

Les icônes sont définies dans le fichier CSV : data/icons_illustrations.csv

Colonnes du CSV :
- id : clé unique (home, demandes, messages, profil, client, expert, contact, etc.)
- label_fr : libellé en français
- illustration_path : chemin relatif vers l'image (ex: assets/icons/illustrations/home.svg)
- emoji_fallback : caractère ou entité HTML affiché si l'illustration est absente
- context : usage (nav_mobile, about, contact, auth, experts, chatbot)

Pour ajouter une illustration : placez un fichier (SVG ou PNG) dans ce dossier
avec le nom indiqué dans le CSV (ex: home.svg, contact.svg). L'application
affichera l'image à la place de l'emoji de secours.
