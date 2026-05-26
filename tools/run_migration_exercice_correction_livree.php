<?php
/**
 * Ajoute le statut exercice `correction_livree` (une fois par base).
 * Usage : php tools/run_migration_exercice_correction_livree.php
 */
require dirname(__DIR__) . '/config/config.php';

$file = dirname(__DIR__) . '/database/migration_exercice_statut_correction_livree.sql';
if (!is_file($file)) {
    fwrite(STDERR, "Missing: $file\n");
    exit(1);
}

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$sql = file_get_contents($file);
$sql = preg_replace('/--.*$/m', '', $sql);
foreach (preg_split('/;\s*\n/', $sql) as $q) {
    $q = trim($q);
    if ($q === '') {
        continue;
    }
    try {
        $pdo->exec($q);
        echo "OK\n";
    } catch (Throwable $e) {
        echo 'SKIP: ' . $e->getMessage() . "\n";
    }
}
echo 'Done on ' . DB_NAME . "\n";
