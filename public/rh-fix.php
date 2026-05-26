<?php
/**
 * GLOBALO — Fix sensibilité casse dossier RH
 * Renomme Views/desktop/RH → Views/desktop/Rh sur le serveur Linux
 * SUPPRIMER après utilisation !
 */
$rootPath = dirname(__DIR__);
$oldPath  = $rootPath . '/app/Views/desktop/RH';
$tmpPath  = $rootPath . '/app/Views/desktop/RH_tmp';
$newPath  = $rootPath . '/app/Views/desktop/Rh';

echo '<style>body{font:15px monospace;background:#0f172a;color:#e2e8f0;padding:24px}
.ok{color:#34d399}.err{color:#f87171}</style>';
echo '<h2 style="color:#60a5fa">🔧 Fix dossier RH → Rh</h2>';

if (is_dir($newPath)) {
    echo '<span class="ok">✓ Le dossier Rh/ existe déjà — rien à faire.</span><br>';
    echo '<br><a href="/rh" style="color:#34d399">→ Tester /rh maintenant</a>';
} elseif (!is_dir($oldPath)) {
    echo '<span class="err">✗ Dossier RH/ introuvable à : ' . htmlspecialchars($oldPath) . '</span><br>';
} else {
    // Linux ne permet pas renommage case-only en une étape : passer par un nom temporaire
    $step1 = rename($oldPath, $tmpPath);
    if (!$step1) {
        echo '<span class="err">✗ Étape 1 échouée (RH → RH_tmp). Vérifiez les permissions.</span><br>';
    } else {
        $step2 = rename($tmpPath, $newPath);
        if (!$step2) {
            // Annuler
            rename($tmpPath, $oldPath);
            echo '<span class="err">✗ Étape 2 échouée (RH_tmp → Rh). Rollback effectué.</span><br>';
        } else {
            echo '<span class="ok">✓ Succès ! Dossier renommé : RH/ → Rh/</span><br><br>';
            // Vérifier les vues
            $views = ['dashboard.php','inscriptions.php','profils.php','marketing.php','manager.php','_chat_widget.php'];
            foreach ($views as $v) {
                $ok = is_file($newPath . '/' . $v);
                echo '<span class="' . ($ok ? 'ok' : 'err') . '">' . ($ok ? '✓' : '✗') . " $v</span><br>";
            }
            echo '<br><strong style="color:#34d399">✅ Prêt ! Testez /rh maintenant.</strong>';
            echo '<br><br><a href="/rh" style="background:#10b981;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;display:inline-block">→ Ouvrir l\'Espace RH</a>';
        }
    }
}
echo '<br><br><p style="color:#64748b;font-size:12px">⚠️ Supprimez ce fichier : public/rh-fix.php</p>';
