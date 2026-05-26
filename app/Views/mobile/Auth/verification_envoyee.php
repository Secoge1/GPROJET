<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$email        = \App\Core\Security::escape($verify_email ?? '');
$devLink      = $verification_link ?? '';
$showDevLink  = !empty($devLink) && defined('DEBUG') && DEBUG;
$emailSent    = $email_sent ?? true;

// Décodage du message de renvoi (format "type:texte")
$resendRaw  = $resend_message ?? '';
$resendType = '';
$resendText = '';
if ($resendRaw !== '') {
    $colonPos   = strpos($resendRaw, ':');
    $resendType = $colonPos !== false ? substr($resendRaw, 0, $colonPos) : 'success';
    $resendText = $colonPos !== false ? substr($resendRaw, $colonPos + 1) : $resendRaw;
}
?>
<style>
.vfy-wrap{display:flex;flex-direction:column;align-items:center;padding:2rem 0 1.5rem;text-align:center}
.vfy-icon-ring{width:88px;height:88px;border-radius:50%;background:linear-gradient(135deg,#dcfce7 0%,#bbf7d0 100%);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;box-shadow:0 0 0 12px rgba(22,163,74,.08)}
.vfy-title{font-size:1.35rem;font-weight:800;color:#0f172a;margin:0 0 .6rem;line-height:1.25}
.vfy-lead{font-size:.9rem;color:#64748b;line-height:1.55;margin:0 0 .5rem;padding:0 .25rem}
.vfy-email{display:inline-block;font-size:.88rem;font-weight:700;color:#16a34a;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:.25rem .75rem;margin-bottom:1.5rem;word-break:break-all}
.vfy-card{width:100%;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.1rem 1.25rem;margin-bottom:1.25rem;text-align:left}
.vfy-card-row{display:flex;align-items:flex-start;gap:.75rem}
.vfy-card-icon{width:36px;height:36px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:.05rem}
.vfy-card-icon svg{stroke:#16a34a}
.vfy-card-text{font-size:.85rem;color:#374151;line-height:1.5}
.vfy-card-text strong{color:#0f172a}
.vfy-btn-primary{display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;padding:.9rem 1.5rem;background:#16a34a;color:#fff;font-size:1rem;font-weight:700;font-family:var(--font,'Plus Jakarta Sans',sans-serif);border-radius:12px;text-decoration:none;box-shadow:0 4px 12px rgba(22,163,74,.25);letter-spacing:.01em;transition:background .15s,transform .1s;margin-bottom:.75rem}
.vfy-btn-primary:active{background:#15803d;transform:scale(.975)}
.vfy-btn-ghost{display:flex;align-items:center;justify-content:center;gap:.4rem;width:100%;padding:.8rem 1.5rem;border:1.5px solid #e2e8f0;border-radius:12px;background:transparent;color:#64748b;font-size:.9rem;font-weight:600;font-family:var(--font,'Plus Jakarta Sans',sans-serif);text-decoration:none;transition:background .15s,border-color .15s}
.vfy-btn-ghost:active{background:#f8fafc;border-color:#cbd5e1}
.vfy-divider{display:flex;align-items:center;gap:.75rem;margin:.75rem 0;color:#94a3b8;font-size:.8rem}
.vfy-divider::before,.vfy-divider::after{content:'';flex:1;height:1px;background:#e2e8f0}
</style>

<div class="vfy-wrap">

    <!-- Icône animée -->
    <div class="vfy-icon-ring">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
        </svg>
    </div>

    <h1 class="vfy-title"><?= __('auth.verify.sent_title') ?></h1>
    <p class="vfy-lead"><?= __('auth.verify.sent_lead') ?></p>

    <?php if ($email): ?>
    <span class="vfy-email">📬 <?= $email ?></span>
    <?php endif; ?>

</div>

<!-- Instruction -->
<div class="vfy-card">
    <div class="vfy-card-row">
        <div class="vfy-card-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <p class="vfy-card-text">
            <strong><?= __('auth.verify.check_inbox') ?></strong>
        </p>
    </div>
</div>

<?php if (!$emailSent): ?>
<div style="margin-bottom:1rem;padding:.9rem 1rem;background:#fefce8;border:1px solid #fde047;border-radius:12px;font-size:.85rem;color:#713f12;line-height:1.5;">
    <strong>L'email n'a pas pu être envoyé.</strong><br>
    Vérifiez la configuration SMTP dans l'administration du site, ou cliquez sur le bouton ci-dessous pour réessayer.
</div>
<?php endif; ?>

<?php if ($resendText !== ''): ?>
<?php if ($resendType === 'error'): ?>
<div style="display:flex;align-items:flex-start;gap:.6rem;margin-bottom:1rem;padding:.85rem 1rem;background:#fef2f2;border:1px solid #fca5a5;border-radius:12px;font-size:.85rem;color:#991b1b;line-height:1.5;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:1px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <?= \App\Core\Security::escape($resendText) ?>
</div>
<?php else: ?>
<div style="display:flex;align-items:flex-start;gap:.6rem;margin-bottom:1rem;padding:.85rem 1rem;background:#f0fdf4;border:1px solid #86efac;border-radius:12px;font-size:.85rem;color:#166534;line-height:1.5;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" style="flex-shrink:0;margin-top:1px;"><polyline points="20 6 9 17 4 12"/></svg>
    <span><strong>Email envoyé !</strong><br><?= \App\Core\Security::escape($resendText) ?></span>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- CTA principal -->
<a href="<?= $baseUrl ?>/auth/connexion" class="vfy-btn-primary">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
    <?= __('auth.login.submit') ?>
</a>

<div class="vfy-divider">ou</div>

<!-- Renvoyer l'email de vérification -->
<form method="POST" action="<?= $baseUrl ?>/auth/renvoyer-verification">
    <?= \App\Core\Security::getCsrfField() ?>
    <input type="hidden" name="email" value="<?= $email ?>">
    <button type="submit" class="vfy-btn-ghost" style="cursor:pointer;width:100%;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/></svg>
        <?= __('auth.verify.resend') ?>
    </button>
</form>

<?php if ($showDevLink): ?>
<div style="margin-top:1.5rem;padding:1rem;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;text-align:center">
    <p style="margin:0 0 .6rem;font-size:.78rem;font-weight:600;color:#92400e">Mode développement</p>
    <a href="<?= \App\Core\Security::escape($devLink) ?>"
       style="display:inline-flex;align-items:center;gap:.4rem;font-size:.85rem;font-weight:700;color:#d97706;text-decoration:none;padding:.5rem 1rem;border:1.5px solid #fbbf24;border-radius:8px;background:#fff">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        Cliquer pour vérifier
    </a>
</div>
<?php endif; ?>
