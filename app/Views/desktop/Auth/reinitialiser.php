<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField = \App\Core\Security::getCsrfField();
$token = $token ?? '';
$error = $error ?? '';
$success = $success ?? false;
?>
<div class="page-reinitialiser page-auth-password">
    <div class="auth-page-backdrop" aria-hidden="true"></div>

    <div class="auth-page-content">
        <header class="auth-intro">
            <a href="<?= $baseUrl ?>/" class="auth-logo-link" aria-label="Globalo - Accueil">
                <span class="auth-logo-circle">
                    <img src="<?= logo_url() ?>" alt="Globalo" class="auth-logo" width="160" height="160">
                </span>
            </a>
            <span class="auth-badge">Nouveau mot de passe</span>
            <div class="auth-intro-icon auth-intro-icon--lock" aria-hidden="true">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <h1><?= __("auth.reset.title") ?></h1>
            <p class="auth-intro-lead"><?= __("auth.reset.lead") ?></p>
        </header>

        <div class="auth-form-wrapper auth-form-wrapper--password">
            <?php if ($success): ?>
                <div class="auth-result auth-result--success">
                    <span class="auth-result-icon" aria-hidden="true">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </span>
                    <p class="auth-result-title"><?= __("auth.reset.success") ?></p>
                    <a href="<?= $baseUrl ?>/auth/connexion" class="btn btn-primary btn-lg">Se connecter</a>
                </div>
            <?php elseif (empty($token)): ?>
                <div class="auth-result auth-result--error">
                    <span class="auth-result-icon auth-result-icon--error" aria-hidden="true">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </span>
                    <p class="auth-result-title">Lien invalide ou expiré.</p>
                    <p class="auth-result-desc">Demandez un nouveau lien depuis la page « Mot de passe oublié ».</p>
                    <a href="<?= $baseUrl ?>/auth/mot-de-passe-oublie" class="btn btn-outline">Demander un nouveau lien</a>
                    <a href="<?= $baseUrl ?>/" class="btn btn-ghost btn-sm" style="margin-top:0.75rem">← Retour à l'accueil</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-error" role="alert">
                        <span class="alert-icon" aria-hidden="true">!</span>
                        <span><?= $e($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= $baseUrl ?>/auth/reinitialiser?token=<?= $e($token) ?>" class="auth-form">
                    <?= $csrfField ?>

                    <section class="auth-form-block" aria-labelledby="block-password">
                        <h2 id="block-password" class="auth-form-block-title">
                            <span class="block-title-num">1</span> Mot de passe
                        </h2>
                        <div class="form-group">
                            <label for="password">Nouveau mot de passe</label>
                            <input type="password" id="password" name="password" required minlength="<?= (int) (defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8) ?>" placeholder="8 caractères minimum" autocomplete="new-password">
                        </div>
                        <div class="form-group">
                            <label for="password_confirm">Confirmer le mot de passe</label>
                            <input type="password" id="password_confirm" name="password_confirm" required placeholder="••••••••" autocomplete="new-password">
                        </div>
                    </section>

                    <div class="auth-form-actions">
                        <button type="submit" class="btn btn-primary btn-lg btn-block btn-auth-submit">
                            <?= __("auth.reset.submit") ?>
                        </button>
                        <p class="auth-form-link">
                            <a href="<?= $baseUrl ?>/auth/connexion" class="auth-form-link-back">← Retour à la connexion</a>
                        </p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.page-reinitialiser .auth-intro-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 72px;
    height: 72px;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(22, 163, 74, 0.12) 0%, rgba(22, 163, 74, 0.06) 100%);
    color: var(--accent, #16a34a);
    margin-bottom: 1rem;
}
.page-reinitialiser .auth-form-wrapper--password { max-width: 420px; margin: 0 auto; }
.page-reinitialiser .auth-result {
    text-align: center;
    padding: 1.5rem 0;
}
.page-reinitialiser .auth-result-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #f0fdf4;
    color: #16a34a;
    margin-bottom: 1rem;
}
.page-reinitialiser .auth-result-icon--error {
    background: #fef2f2;
    color: #dc2626;
}
.page-reinitialiser .auth-result-title { font-size: 1.0625rem; font-weight: 600; color: var(--primary); margin: 0 0 0.35rem; }
.page-reinitialiser .auth-result-desc { font-size: 0.9375rem; color: var(--text-muted); margin: 0 0 1.25rem; }
.page-reinitialiser .auth-form-link-back {
    display: inline-block;
    font-size: 0.9375rem;
    color: var(--text-muted);
    text-decoration: none;
    margin-top: 1rem;
    transition: color 0.15s;
}
.page-reinitialiser .auth-form-link-back:hover { color: var(--accent); }
.page-reinitialiser .btn-ghost { background: transparent; color: var(--text-muted); border: none; }
.page-reinitialiser .btn-ghost:hover { color: var(--accent); background: transparent; }
</style>
