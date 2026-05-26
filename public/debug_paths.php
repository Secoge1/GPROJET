<?php
/**
 * Diagnostic des chemins - à supprimer en production
 * Ouvrir : http://localhost/globalo/public/debug_paths.php
 */
header('Content-Type: text/plain; charset=utf-8');
$rootPath = dirname(__DIR__);
define('ROOT_PATH', $rootPath);
define('APP_PATH', $rootPath . DIRECTORY_SEPARATOR . 'app');
$viewFile = APP_PATH . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'desktop' . DIRECTORY_SEPARATOR . 'Home' . DIRECTORY_SEPARATOR . 'index.php';
echo "ROOT_PATH = " . ROOT_PATH . "\n";
echo "APP_PATH = " . APP_PATH . "\n";
echo "Vue Home/index = " . $viewFile . "\n";
echo "Fichier vue existe : " . (is_file($viewFile) ? 'OUI' : 'NON') . "\n";
echo "Dossier app existe : " . (is_dir(APP_PATH) ? 'OUI' : 'NON') . "\n";
echo "Dossier Views existe : " . (is_dir(APP_PATH . DIRECTORY_SEPARATOR . 'Views') ? 'OUI' : 'NON') . "\n";
