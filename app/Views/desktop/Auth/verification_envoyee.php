<?php
$baseUrl           = $baseUrl ?? rtrim(BASE_URL ?? '', '/');
$verification_link = $verification_link ?? '';
$emailSent         = $email_sent ?? true;
$verifyEmail       = \App\Core\Security::escape($verify_email ?? '');
$showDevLink       = !empty($verification_link) && defined('DEBUG') && DEBUG;

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
<div class="page-verification-envoyee">
    <div class="auth-page-backdrop" aria-hidden="true"></div>

    <div class="auth-page-content">
        <header class="auth-intro">
            <a href="<?= $baseUrl ?>/" class="auth-logo-link" aria-label="Globalo - Accueil">
                <span class="auth-logo-circle">
                    <img src="<?= logo_url() ?>" alt="Globalo" class="auth-logo" width="160" height="160">
                </span>
            </a>
            <span class="auth-badge">Vérification</span>
            <h1>Vérification envoyée</h1>
            <p class="auth-intro-lead">Un lien d'activation a été envoyé à votre adresse email. Cliquez sur le lien reçu pour activer votre compte.</p>
        </header>

        <div class="auth-form-wrapper auth-form-wrapper--message">
            <div class="verification-envoyee-icon" aria-hidden="true">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>

            <?php if (!$emailSent): ?>
                <div class="alert alert-error" style="margin-bottom:1rem;">
                    <strong>L'email n'a pas pu être envoyé.</strong>
                    Vérifiez que la configuration SMTP est correcte dans l'administration,
                    ou utilisez le lien ci-dessous (mode développement) pour activer votre compte.
                </div>
            <?php endif; ?>

            <?php if ($resendText !== ''): ?>
                <?php if ($resendType === 'error'): ?>
                    <div class="alert alert-error" style="margin-bottom:1rem;">
                        <?= \App\Core\Security::escape($resendText) ?>
                    </div>
                <?php else: ?>
                    <div style="display:flex;align-items:flex-start;gap:.75rem;background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:.9rem 1.1rem;margin-bottom:1rem;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;margin-top:1px;"><polyline points="20 6 9 17 4 12"/></svg>
                        <span style="font-size:.9rem;color:#166534;line-height:1.5;"><?= \App\Core\Security::escape($resendText) ?></span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <p class="verification-envoyee-text">Si vous ne voyez pas l'email, vérifiez vos spams ou réessayez dans quelques minutes.</p>

            <?php if ($showDevLink): ?>
                <div class="verification-envoyee-dev">
                    <p class="verification-envoyee-dev-label">En mode développement</p>
                    <a href="<?= \App\Core\Security::escape($verification_link) ?>" class="btn btn-outline btn-block verification-envoyee-dev-link">
                        Cliquer ici pour vérifier
                    </a>
                </div>
            <?php endif; ?>

            <!-- Renvoyer l'email de vérification -->
            <form method="POST" action="<?= $baseUrl ?>/auth/renvoyer-verification" style="margin-bottom:0.75rem;">
                <?= \App\Core\Security::getCsrfField() ?>
                <input type="hidden" name="email" value="<?= $verifyEmail ?>">
                <button type="submit" class="btn btn-outline btn-block">
                    Renvoyer l'email de vérification
                </button>
            </form>

            <p class="verification-envoyee-actions">
                <a href="<?= $baseUrl ?>/" class="btn btn-primary btn-block">Retour à l'accueil</a>
            </p>
        </div>
    </div>
</div>
