<?php
/**
 * Script d'exécution de la migration RH
 * Usage : php tools/run_migration_rh.php
 */
chdir(dirname(__DIR__));
require_once 'app/bootstrap.php';

try {
    $db  = App\Core\Database::getInstance();
    $sql = file_get_contents('database/migration_rh.sql');

    // Diviser les instructions SQL
    $stmts = preg_split('/;\s*\n/', $sql);
    $ok = 0;
    $err = 0;

    foreach ($stmts as $stmt) {
        $stmt = trim($stmt);
        if (empty($stmt) || strpos($stmt, '--') === 0) {
            continue;
        }
        try {
            $db->exec($stmt);
            echo "[OK] Table créée/vérifiée\n";
            $ok++;
        } catch (PDOException $e) {
            echo '[ERR] ' . $e->getMessage() . "\n";
            $err++;
        }
    }

    echo "\n=== Migration RH terminée : {$ok} ok, {$err} erreur(s) ===\n";

} catch (Throwable $e) {
    echo 'FATAL: ' . $e->getMessage() . "\n";
    exit(1);
}
