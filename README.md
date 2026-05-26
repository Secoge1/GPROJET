# GLOBALO

Plateforme communautaire d’assistance professionnelle à la demande.

## Technologies

- PHP natif (sans framework)
- MySQL
- HTML5 / CSS3 / JavaScript (Vanilla)
- AJAX
- Architecture MVC

## Installation

1. **Cloner ou placer le projet** dans un répertoire web (ex. `www/globalo`).

2. **Créer la base MySQL** et importer le schéma :
   ```bash
   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS globalo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p globalo < database/schema.sql
   ```

3. **Configurer la base** dans `config/config.php` ou via variables d’environnement (voir `config/config.sample.env`).

4. **Accès à l’application**
   - **Version desktop** : `http://localhost/globalo/public/`
   - **Version mobile (app)** : `http://localhost/globalo/public/app`
   - **Admin** : `http://localhost/globalo/public/admin` (nécessite un compte avec rôle `admin`)

5. **Réécriture d’URL**  
   Le fichier `public/.htaccess` utilise `RewriteBase /globalo/public/`. Adapter selon votre chemin (ex. `/globalo/` si la racine du site pointe sur `public`).

## Structure

```
globalo/
├── app/
│   ├── Controllers/     # Contrôleurs (Client, Expert, Admin, Api, App)
│   ├── Core/           # Database, Router, Auth, Security, Controller, Model
│   ├── Models/         # Modèles métier
│   ├── Views/
│   │   ├── desktop/    # Vues version Web Desktop
│   │   ├── mobile/     # Vues version Web App Mobile
│   │   └── errors/
│   └── bootstrap.php
├── config/
│   └── config.php
├── database/
│   └── schema.sql
├── public/
│   ├── assets/css/     # desktop.css, mobile.css
│   ├── assets/js/      # app.js
│   ├── .htaccess
│   └── index.php       # Point d’entrée
└── uploads/            # Fichiers uploadés (à créer, droits en écriture)
```

## Comptes par défaut

Aucun compte admin n’est créé par le schéma. Pour en créer un, insérer un utilisateur avec `role = 'admin'` dans la table `utilisateurs`, ou modifier temporairement un compte existant en base.

## Sécurité

- CSRF sur les formulaires
- Mots de passe hachés (PHP `password_hash`)
- Requêtes préparées (PDO)
- Échappement des sorties (XSS)
- Sessions sécurisées

## Langue

Interface en français par défaut.
