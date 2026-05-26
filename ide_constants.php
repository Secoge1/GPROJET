<?php

/**
 * Constantes GLOBALO pour l’IDE et les analyseurs statiques uniquement.
 * Ce fichier n’est pas inclus au runtime par l’application ; Intelephense / PhpStorm
 * indexent les define() ci‑dessous lorsque le dossier est dans le workspace.
 *
 * À l’exécution, ROOT_PATH / APP_PATH / BASE_URL viennent de public/index.php + config/config.php.
 */
namespace {
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', '');
    }
    if (!defined('APP_PATH')) {
        define('APP_PATH', '');
    }
    if (!defined('PUBLIC_PATH')) {
        define('PUBLIC_PATH', '');
    }
    if (!defined('BASE_URL')) {
        define('BASE_URL', 'http://localhost');
    }
}
