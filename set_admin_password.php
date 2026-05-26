<?php
/**
 * GLOBALO - Définir le mot de passe de l'admin (à exécuter UNE FOIS en CLI puis supprimer).
 *
 * Usage : php set_admin_password.php
 *        php set_admin_password.php "MonMotDePasse"
 */

declare(strict_types=1);

$rootPath = __DIR__;
define('ROOT_PATH', $rootPath);
define('APP_PATH', $rootPath . DIRECTORY_SEPARATOR . 'app');

require_once APP_PATH . '/bootstrap.php';

use App\Core\Security;
use App\Core\Database;

$password = $argv[1] ?? 'MotDePasseAdmin';
$hash = Security::hashPassword($password);
$db = Database::getInstance();
$stmt = $db->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE email = 'admin@globalo.local'");
$stmt->execute([$hash]);

if ($stmt->rowCount() > 0) {
    echo "OK : Mot de passe admin mis à jour.\n";
    echo "Connectez-vous avec : admin@globalo.local / " . (isset($argv[1]) ? '(le mot de passe que vous avez passé)' : 'MotDePasseAdmin') . "\n";
} else {
    echo "Aucun utilisateur admin@globalo.local trouvé en base. Créez d'abord le compte (ex. via phpMyAdmin ou generate_admin_hash.php).\n";
    exit(1);
}
