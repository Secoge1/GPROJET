# Guide — Changer de Nom de Domaine

Ce document liste **tous les fichiers à modifier** lorsque vous déployez GLOBALO sur un nouveau nom de domaine (migration ou nouvelle installation).

---

## Résumé rapide

| Priorité | Fichier | Ce qu'il faut changer |
|----------|---------|----------------------|
| 🔴 Obligatoire | `.htaccess` (racine) | `BASE_URL` + `GLOBALO_ENV` |
| 🔴 Obligatoire | `public/.htaccess` | Redirection HTTPS |
| 🟡 Si nouveau compte InTouch | `.htaccess` (racine) | Credentials InTouch/TouchPay |
| 🟡 Si nouveau compte InTouch | `config/config.php` | Fallbacks credentials |
| 🟢 Optionnel | `public/.htaccess` | CSP (si domaine tiers supplémentaire) |

---

## 1. `.htaccess` — Fichier principal (racine du projet)

**Chemin :** `GloboProject/.htaccess`

C'est le fichier **le plus important**. Il contrôle l'environnement entier.

### Ce qu'il faut modifier

```apache
# 1. Changer l'environnement en production
SetEnv GLOBALO_ENV production          # ← était "development" en local

# 2. Décommenter et mettre votre nouveau domaine
SetEnv BASE_URL    https://VOTRE-NOUVEAU-DOMAINE.com   # ← décommenter + remplacer

# 3. Base de données — mettre les credentials du nouvel hébergeur
SetEnv DB_HOST     localhost
SetEnv DB_NAME     NOM_DE_VOTRE_BASE
SetEnv DB_USER     VOTRE_UTILISATEUR_DB
SetEnv DB_PASS     VOTRE_MOT_DE_PASSE_DB
```

### Exemple concret (migration de `batimemo.crmapp.online` vers `globalo.monsite.com`)

**Avant :**
```apache
SetEnv GLOBALO_ENV development
# SetEnv BASE_URL    https://batimemo.crmapp.online
SetEnv DB_NAME     cp2640311p29_globalo
SetEnv DB_USER     root
SetEnv DB_PASS
```

**Après :**
```apache
SetEnv GLOBALO_ENV production
SetEnv BASE_URL    https://globalo.monsite.com
SetEnv DB_NAME     globalo_monsite_db
SetEnv DB_USER     globalo_user
SetEnv DB_PASS     monMotDePasse
```

> **Important :** Si vous êtes en LOCAL (WAMP/XAMPP), commentez `BASE_URL` avec `#` et mettez `GLOBALO_ENV development`. L'URL sera détectée automatiquement.

---

## 2. `public/.htaccess` — Redirection HTTPS

**Chemin :** `GloboProject/public/.htaccess`

Ce fichier contient la règle de redirection HTTP → HTTPS. Elle est liée au nom de domaine.

### Ce qu'il faut modifier (ligne ~12)

```apache
# Trouver cette ligne :
RewriteCond %{HTTP_HOST} batimemo.crmapp.online [NC]

# Remplacer par votre nouveau domaine :
RewriteCond %{HTTP_HOST} VOTRE-NOUVEAU-DOMAINE.com [NC]
```

### Exemple

```apache
# Avant
RewriteCond %{HTTP_HOST} batimemo.crmapp.online [NC]

# Après
RewriteCond %{HTTP_HOST} globalo.monsite.com [NC]
```

> **Note :** Si votre hébergeur force déjà HTTPS (cPanel, Cloudflare, etc.), vous pouvez commenter cette règle entièrement avec `#` pour éviter les boucles de redirection.

---

## 3. InTouch / TouchPay — Si vous changez de compte InTouch

Si vous installez le projet pour un **autre client** ou une **autre entreprise**, vous devrez changer les credentials InTouch dans `.htaccess` (racine) **ET** dans `config/config.php`.

### 3a. `.htaccess` (racine) — Variables d'environnement

```apache
SetEnv INTOUCH_API_USERNAME   NOUVEAU_USERNAME_HASH
SetEnv INTOUCH_API_PASSWORD   NOUVEAU_PASSWORD_HASH
SetEnv INTOUCH_LOGIN_AGENT    NOUVEAU_LOGIN_AGENT
SetEnv INTOUCH_PASSWORD_AGENT NOUVEAU_PASSWORD_AGENT
SetEnv INTOUCH_ID             NOUVEAU_ID_MARCHAND
SetEnv TOUCHPAY_SECURE_CODE   NOUVEAU_SECURE_CODE
```

### 3b. `config/config.php` — Fallbacks (valeurs de secours)

**Chemin :** `GloboProject/config/config.php`

Ces valeurs sont utilisées si Apache ne lit pas le `.htaccess` (ex : CLI PHP, cron jobs).

Chercher et remplacer les lignes `define('INTOUCH_...')` :

```php
define('INTOUCH_API_USERNAME',   _intouch_env('INTOUCH_API_USERNAME',   'NOUVEAU_USERNAME_HASH'));
define('INTOUCH_API_PASSWORD',   _intouch_env('INTOUCH_API_PASSWORD',   'NOUVEAU_PASSWORD_HASH'));
define('INTOUCH_LOGIN_AGENT',    _intouch_env('INTOUCH_LOGIN_AGENT',    'NOUVEAU_LOGIN_AGENT'));
define('INTOUCH_PASSWORD_AGENT', _intouch_env('INTOUCH_PASSWORD_AGENT', 'NOUVEAU_PASSWORD_AGENT'));
define('INTOUCH_ID',             _intouch_env('INTOUCH_ID',             'NOUVEAU_ID_MARCHAND'));
define('TOUCHPAY_SECURE_CODE',   _intouch_env('TOUCHPAY_SECURE_CODE',   'NOUVEAU_SECURE_CODE'));
```

### 3c. Enregistrer le domaine chez InTouch

**Le Checkout Page (script2) valide le domaine côté serveur InTouch.**

Après avoir changé de domaine, vous devez contacter InTouch pour enregistrer le nouveau domaine :
- Back-office InTouch : `https://bo.gutouch.com`
- Déclarez le nouveau domaine dans les paramètres de votre compte marchand

> Sans cette étape, le bouton de paiement retournera une erreur HTTP 403.

---

## 4. `public/.htaccess` — CSP (Content Security Policy)

**Chemin :** `GloboProject/public/.htaccess`

Normalement vous n'avez **rien à changer** ici lors d'un simple changement de domaine — la CSP liste des domaines tiers (InTouch, Google, etc.), pas votre propre domaine.

**Exception :** Si vous ajoutez un nouveau service tiers (analytics, chat, etc.), ajoutez son domaine aux directives concernées.

```apache
script-src  'self' ... https://NOUVEAU-SERVICE-TIERS.com;
connect-src 'self' ... https://NOUVEAU-SERVICE-TIERS.com;
```

---

## 5. Base de données — Imports SQL

Si vous déployez sur un **nouvel hébergeur** avec une base vide, pensez à :

1. Exporter la base locale : `mysqldump -u root cp2640311p29_globalo > backup.sql`
2. Créer la nouvelle base sur l'hébergeur
3. Importer : `mysql -u USER -p NOUVELLE_BASE < backup.sql`
4. Mettre à jour `.htaccess` avec les nouveaux credentials DB

---

## Checklist de déploiement

```
[ ] 1. .htaccess (racine) — BASE_URL décommenté + nouveau domaine
[ ] 2. .htaccess (racine) — GLOBALO_ENV = production
[ ] 3. .htaccess (racine) — credentials DB mis à jour
[ ] 4. public/.htaccess   — RewriteCond HTTPS mis à jour avec nouveau domaine
[ ] 5. config/config.php  — fallbacks InTouch mis à jour (si nouveau compte)
[ ] 6. Base de données     — importée sur le nouvel hébergeur
[ ] 7. InTouch back-office — nouveau domaine enregistré sur bo.gutouch.com
[ ] 8. Test paiement       — vérifier que le Checkout Page se charge sans 403
```

---

## Fichiers qui se mettent à jour AUTOMATIQUEMENT

Ces fichiers n'ont **pas besoin d'être modifiés** lors d'un changement de domaine — ils lisent `BASE_URL` dynamiquement :

| Fichier | Pourquoi automatique |
|---------|----------------------|
| `config/config.php` | Lit `BASE_URL` depuis `getenv()` |
| `app/Services/IntouchPaymentService.php` | Utilise `BASE_URL` pour construire `url_success` et `url_failed` |
| `app/Core/Router.php` | Utilise les segments d'URL relatifs |
| Toutes les vues PHP | Utilisent la constante `BASE_URL` définie dans `config.php` |

---

*Dernière mise à jour : 2026-04-13*
