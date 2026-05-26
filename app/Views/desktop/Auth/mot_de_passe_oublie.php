<?php
$csrfField = \App\Core\Security::getCsrfField();
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
?>
<div class="page-mot-de-passe-oublie page-auth-password">
    <div class="auth-page-backdrop" aria-hidden="true"></div>

    <div class="auth-page-content">
        <header class="auth-intro">
            <a href="<?= $baseUrl ?>/" class="auth-logo-link" aria-label="Globalo - Accueil">
                <span class="auth-logo-circle">
                    <img src="<?= logo_url() ?>" alt="Globalo" class="auth-logo" width="160" height="160">
                </span>
            </a>
            <span class="auth-badge">Réinitialisation</span>
            <div class="auth-intro-icon auth-intro-icon--key" aria-hidden="true">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                </svg>
            </div>
            <h1><?= __("auth.forgot.title") ?></h1>
            <p class="auth-intro-lead"><?= __("auth.forgot.lead") ?></p>
        </header>

        <div class="auth-form-wrapper auth-form-wrapper--password">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error" role="alert">
                    <span class="alert-icon" aria-hidden="true">!</span>
                    <span><?= $e($error) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success" role="status">
                    <span class="alert-icon alert-icon-success" aria-hidden="true">✓</span>
                    <span><?= $e($message) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= $baseUrl ?>/auth/mot-de-passe-oublie" class="auth-form">
                <?= $csrfField ?>

                <section class="auth-form-block" aria-labelledby="block-email">
                    <h2 id="block-email" class="auth-form-block-title">
                        <span class="block-title-num">1</span> Email du compte
                    </h2>
                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <input type="email" id="email" name="email" required autocomplete="email" value="<?= $e($_POST['email'] ?? '') ?>" placeholder="vous@exemple.fr" class="auth-input auth-input--email">
                    </div>
                </section>

                <div class="auth-form-actions">
                    <button type="submit" class="btn btn-primary btn-lg btn-block btn-auth-submit">
                        <span class="btn-auth-submit-text"><?= __("auth.forgot.submit") ?></span>
                    </button>
                    <p class="auth-form-link">
                        <a href="<?= $baseUrl ?>/auth/connexion" class="auth-form-link-back">← Retour à la connexion</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.page-auth-password .auth-intro-icon {
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
.page-auth-password .auth-form-wrapper--password { max-width: 420px; margin: 0 auto; }
.page-auth-password .auth-form-actions { margin-top: 0.5rem; }
.page-auth-password .auth-form-link-back {
    display: inline-block;
    font-size: 0.9375rem;
    color: var(--text-muted);
    text-decoration: none;
    margin-top: 1rem;
    transition: color 0.15s;
}
.page-auth-password .auth-form-link-back:hover { color: var(--accent); }
</style>
