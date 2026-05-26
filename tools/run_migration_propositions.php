<?php
require dirname(__DIR__) . '/config/config.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$files = [
    dirname(__DIR__) . '/database/migration_propositions_prestataires.sql',
    dirname(__DIR__) . '/database/migration_exercice_propositions_no_fk.sql',
];

foreach ($files as $file) {
    if (!is_file($file)) {
        echo "Missing: $file\n";
        continue;
    }
    $sql = file_get_contents($file);
    $sql = preg_replace('/--.*$/m', '', $sql);
    foreach (preg_split('/;\s*\n/', $sql) as $q) {
        $q = trim($q);
        if ($q === '') {
            continue;
        }
        try {
            $pdo->exec($q);
            echo "OK: " . substr(str_replace("\n", ' ', $q), 0, 60) . "...\n";
        } catch (Throwable $e) {
            echo "SKIP (" . basename($file) . "): " . $e->getMessage() . "\n";
        }
    }
}

echo "Done on database: " . DB_NAME . "\n";
