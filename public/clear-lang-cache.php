<?php
// Script usage unique — supprimer après utilisation
if (function_exists('opcache_invalidate')) {
    $files = [
        __DIR__ . '/../app/Lang/fr.php',
        __DIR__ . '/../app/Lang/en.php',
    ];
    foreach ($files as $f) {
        opcache_invalidate($f, true);
    }
    echo 'OPcache vidé pour les fichiers de langue. ';
} else {
    echo 'OPcache non activé ou déjà vide. ';
}
echo 'Rafraîchissez maintenant la page /home/apropos.';
