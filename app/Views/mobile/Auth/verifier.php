<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e       = fn($s) => \App\Core\Security::escape($s ?? '');
$success   = $success ?? false;
$error     = $error ?? '';
$startDate = $startDate ?? null;
?>

<div class="mobile-auth-card">
    <div style="text-align:center;margin-bottom:1.75rem">
        <div style="width:52px;height:52px;border-radius:14px;background:var(--accent-soft);display:inline-flex;align-items:center;justify-content:center;margin-bottom:0.85rem">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.65 3.4 2 2 0 0 1 3.62 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.54a16 16 0 0 0 6.55 6.55l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
        </div>
        <h1 style="font-size:1.35rem;font-weight:700;margin:0 0 0.3rem;color:var(--primary)">Vérification de l'email</h1>
    </div>

    <?php if ($success): ?>
        <div class="mobile-alert mobile-alert--success" style="margin-bottom:1.5rem">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            Votre adresse email a été vérifiée avec succès.
            <?php if ($startDate): ?>
                <br><strong>Votre abonnement démarrera le <?= $e($startDate) ?></strong>.
            <?php endif; ?>
        </div>
        <a href="<?= $baseUrl ?>/auth/connexion" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
            Se connecter
        </a>
    <?php elseif ($error): ?>
        <div class="mobile-alert mobile-alert--error" style="margin-bottom:1.5rem">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= $e($error) ?>
        </div>
        <a href="<?= $baseUrl ?>/app" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center">
            Retour à l'accueil
        </a>
    <?php endif; ?>
</div>
