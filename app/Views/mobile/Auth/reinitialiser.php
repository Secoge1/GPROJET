<?php
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField = \App\Core\Security::getCsrfField();
$token     = $token ?? '';
$error     = $error ?? '';
$success   = $success ?? false;
?>
<div class="mob-auth mob-auth--reset">
    <div class="mob-auth__hero">
        <a href="<?= $baseUrl ?>/" class="mob-auth__logo">
            <img src="<?= logo_url() ?>" alt="Globalo" class="mob-auth__logo-img">
        </a>
        <div class="mob-auth__icon mob-auth__icon--lock" aria-hidden="true">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h1 class="mob-auth__title"><?= __("auth.reset.title") ?></h1>
        <p class="mob-auth__lead"><?= __("auth.reset.lead") ?></p>
    </div>

    <?php if ($success): ?>
    <div class="mob-auth__card mob-auth__card--success">
        <span class="mob-auth__success-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </span>
        <p class="mob-auth__success-text"><?= __("auth.reset.success") ?></p>
        <a href="<?= $baseUrl ?>/auth/connexion" class="mob-auth__btn mob-auth__btn--primary">Se connecter</a>
    </div>

    <?php elseif (empty($token)): ?>
    <div class="mob-auth__card mob-auth__card--error">
        <span class="mob-auth__error-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </span>
        <p class="mob-auth__error-title">Lien invalide ou expiré</p>
        <p class="mob-auth__error-desc">Demandez un nouveau lien depuis la page « Mot de passe oublié ».</p>
        <a href="<?= $baseUrl ?>/auth/mot-de-passe-oublie" class="mob-auth__btn mob-auth__btn--outline">Demander un nouveau lien</a>
    </div>

    <?php else: ?>
    <?php if ($error): ?>
    <div class="mob-auth__alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span><?= $e($error) ?></span>
    </div>
    <?php endif; ?>

    <div class="mob-auth__card">
        <form method="post" action="<?= $baseUrl ?>/auth/reinitialiser?token=<?= $e($token) ?>" class="mob-auth__form">
            <?= $csrfField ?>

            <div class="form-mobile">
                <label class="form-mobile__label" for="password">Nouveau mot de passe</label>
                <input class="form-mobile__input" type="password" id="password" name="password"
                       required minlength="<?= (int) (defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 8) ?>"
                       placeholder="8 caractères minimum" autocomplete="new-password">
            </div>

            <div class="form-mobile">
                <label class="form-mobile__label" for="password_confirm">Confirmer le mot de passe</label>
                <input class="form-mobile__input" type="password" id="password_confirm" name="password_confirm"
                       required placeholder="••••••••" autocomplete="new-password">
            </div>

            <button type="submit" class="mob-auth__btn mob-auth__btn--submit">
                <?= __("auth.reset.submit") ?>
            </button>
        </form>
    </div>
    <?php endif; ?>

    <p class="mob-auth__footer-link">
        <a href="<?= $baseUrl ?>/auth/connexion">← Retour à la connexion</a>
    </p>
</div>

<style>
.mob-auth--reset { padding: 0 1.25rem 2.5rem; }
.mob-auth--reset .mob-auth__hero { padding: 1.5rem 0 1.25rem; }
.mob-auth--reset .mob-auth__logo-img { height: 44px; width: auto; max-width: 180px; display: block; margin: 0 auto 1rem; }
.mob-auth--reset .mob-auth__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    border-radius: 16px;
    background: linear-gradient(135deg, rgba(22, 163, 74, 0.15) 0%, rgba(22, 163, 74, 0.06) 100%);
    color: var(--accent, #16a34a);
    margin-bottom: 0.85rem;
}
.mob-auth--reset .mob-auth__title { font-size: 1.35rem; font-weight: 800; color: var(--text, #111827); margin: 0 0 0.35rem; }
.mob-auth--reset .mob-auth__lead { font-size: 0.9rem; color: var(--text-muted, #6b7280); margin: 0; line-height: 1.5; }
.mob-auth--reset .mob-auth__card {
    background: var(--surface, #fff);
    border-radius: 16px;
    padding: 1.5rem 1.25rem;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    border: 1px solid var(--border, #e5e7eb);
    margin-bottom: 1rem;
}
.mob-auth--reset .mob-auth__card--success,
.mob-auth--reset .mob-auth__card--error { text-align: center; padding: 1.75rem 1.25rem; }
.mob-auth--reset .mob-auth__success-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #f0fdf4;
    color: #16a34a;
    margin-bottom: 0.75rem;
}
.mob-auth--reset .mob-auth__error-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #fef2f2;
    color: #dc2626;
    margin-bottom: 0.75rem;
}
.mob-auth--reset .mob-auth__success-text,
.mob-auth--reset .mob-auth__error-title { font-size: 0.9375rem; font-weight: 600; color: var(--text); margin: 0 0 0.35rem; }
.mob-auth--reset .mob-auth__error-desc { font-size: 0.875rem; color: var(--text-muted); margin: 0 0 1rem; line-height: 1.5; }
.mob-auth--reset .mob-auth__btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 0.9rem 1rem;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    font-family: inherit;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: opacity 0.15s, transform 0.1s;
}
.mob-auth--reset .mob-auth__btn:active { transform: scale(0.98); }
.mob-auth--reset .mob-auth__btn--primary,
.mob-auth--reset .mob-auth__btn--submit { background: var(--accent, #16a34a); color: #fff; }
.mob-auth--reset .mob-auth__btn--outline {
    background: transparent;
    color: var(--accent);
    border: 2px solid var(--accent);
}
.mob-auth--reset .mob-auth__form { display: flex; flex-direction: column; gap: 1rem; }
.mob-auth--reset .form-mobile__label { display: block; font-size: 0.875rem; font-weight: 600; color: var(--text); margin-bottom: 0.4rem; }
.mob-auth--reset .form-mobile__input {
    display: block; width: 100%; box-sizing: border-box;
    padding: 0.75rem 1rem; font-size: 1rem; font-family: inherit;
    border: 1.5px solid var(--border, #e5e7eb); border-radius: 10px;
    background: var(--surface); color: var(--text);
}
.mob-auth--reset .form-mobile__input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(22,163,74,.15); }
.mob-auth--reset .mob-auth__footer-link { text-align: center; margin: 0; font-size: 0.9rem; }
.mob-auth--reset .mob-auth__footer-link a { color: var(--text-muted); text-decoration: none; font-weight: 500; }
.mob-auth--reset .mob-auth__footer-link a:hover { color: var(--accent); }
.mob-auth--reset .mob-auth__alert {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #fef2f2;
    border: 1px solid #fca5a5;
    color: #b91c1c;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    margin-bottom: 1rem;
}
</style>
