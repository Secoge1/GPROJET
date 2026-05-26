<?php
/**
 * GLOBALO - Génère un hash bcrypt et l'INSERT SQL pour créer un compte admin.
 * À utiliser UNE SEULE FOIS puis SUPPRIMER ce fichier (sécurité).
 */

declare(strict_types=1);

$password = $_GET['p'] ?? 'MotDePasseAdmin';
$email = $_GET['email'] ?? 'admin@globalo.local';

$hash = password_hash($password, PASSWORD_DEFAULT);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Générer hash admin</title>
    <style>
        body { font-family: system-ui, sans-serif; padding: 2rem; max-width: 700px; margin: 0 auto; }
        pre { background: #f1f5f9; padding: 1rem; border-radius: 8px; overflow-x: auto; font-size: 0.875rem; }
        .warning { background: #fef3c7; border: 1px solid #f59e0b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; }
        h1 { font-size: 1.25rem; }
    </style>
</head>
<body>
    <h1>Hash pour compte admin</h1>
    <div class="warning">
        <strong>Important :</strong> supprime ce fichier (generate_admin_hash.php) après usage pour des raisons de sécurité.
    </div>
    <p><strong>Mot de passe utilisé :</strong> <code><?= htmlspecialchars($password) ?></code></p>
    <p><strong>Email :</strong> <code><?= htmlspecialchars($email) ?></code></p>
    <p>Exécute cet SQL dans phpMyAdmin (remplace le hash si tu as changé le mot de passe) :</p>
    <pre><?php
$hashEscaped = addslashes($hash);
echo "INSERT INTO utilisateurs (email, mot_de_passe, role, nom, prenom, email_verifie, actif)\n";
echo "VALUES (\n";
echo "  '" . addslashes($email) . "',\n";
echo "  '" . $hashEscaped . "',\n";
echo "  'admin',\n";
echo "  'Admin',\n";
echo "  'Globalo',\n";
echo "  1,\n";
echo "  1\n";
echo ");";
    ?></pre>
    <p><small>Tu peux passer d'autres valeurs en GET : <code>?p=TonMotDePasse&email=admin@mondomaine.com</code></small></p>
</body>
</html>
