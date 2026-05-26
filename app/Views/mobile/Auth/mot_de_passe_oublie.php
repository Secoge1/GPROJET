<?php
$csrfField = \App\Core\Security::getCsrfField();
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$message   = $message ?? '';
$success   = !empty($message);
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
?>
<div class="mob-auth mob-auth--password">
    <div class="mob-auth__hero">
        <a href="<?= $baseUrl ?>/" class="mob-auth__logo">
            <img src="<?= logo_url() ?>" alt="Globalo" class="mob-auth__logo-img">
        </a>
        <div class="mob-auth__icon mob-auth__icon--key" aria-hidden="true">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
        </div>
        <h1 class="mob-auth__title"><?= __("auth.forgot.title") ?></h1>
        <p class="mob-auth__lead"><?= __("auth.forgot.lead") ?></p>
    </div>

    <?php if ($success): ?>
    <div class="mob-auth__card mob-auth__card--success">
        <span class="mob-auth__success-icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </span>
        <p class="mob-auth__success-text"><?= !empty($message) ? $e($message) : __("auth.forgot.success") ?></p>
        <a href="<?= $baseUrl ?>/auth/connexion" class="mob-auth__btn mob-auth__btn--primary"><?= __("auth.login.submit") ?></a>
    </div>
    <?php else: ?>

    <?php if (!empty($error)): ?>
    <div class="mob-auth__alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span><?= $e($error) ?></span>
    </div>
    <?php endif; ?>

    <div class="mob-auth__card">
        <form method="post" action="<?= $baseUrl ?>/auth/mot-de-passe-oublie" class="mob-auth__form">
            <?= $csrfField ?>
            <div class="form-mobile">
                <label class="form-mobile__label"><?= __("auth.login.email") ?></label>
                <input type="email" name="email" class="form-mobile__input" required autocomplete="email"
                       value="<?= $e($_POST['email'] ?? '') ?>"
                       placeholder="vous@exemple.fr">
            </div>
            <button type="submit" class="mob-auth__btn mob-auth__btn--submit"><?= __("auth.forgot.submit") ?></button>
        </form>
    </div>

    <p class="mob-auth__footer-link">
        <a href="<?= $baseUrl ?>/auth/connexion">← Retour à la connexion</a>
    </p>
    <?php endif; ?>
</div>

<style>
.mob-auth--password { padding: 0 1.25rem 2.5rem; }
.mob-auth--password .mob-auth__hero { padding: 1.5rem 0 1.25rem; }
.mob-auth--password .mob-auth__logo-img { height: 44px; width: auto; max-width: 180px; display: block; margin: 0 auto 1rem; }
.mob-auth__icon {
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
.mob-auth__icon--key { }
.mob-auth--password .mob-auth__title { font-size: 1.35rem; font-weight: 800; color: var(--text, #111827); margin: 0 0 0.35rem; }
.mob-auth--password .mob-auth__lead { font-size: 0.9rem; color: var(--text-muted, #6b7280); margin: 0; line-height: 1.5; }
.mob-auth__card {
    background: var(--surface, #fff);
    border-radius: 16px;
    padding: 1.5rem 1.25rem;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    border: 1px solid var(--border, #e5e7eb);
    margin-bottom: 1rem;
}
.mob-auth__card--success {
    text-align: center;
    padding: 1.75rem 1.25rem;
}
.mob-auth__success-icon {
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
.mob-auth__success-text { font-size: 0.9375rem; color: var(--text); font-weight: 500; margin: 0 0 1.25rem; line-height: 1.5; }
.mob-auth__btn {
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
.mob-auth__btn:active { transform: scale(0.98); }
.mob-auth__btn--primary,
.mob-auth__btn--submit {
    background: var(--accent, #16a34a);
    color: #fff;
}
.mob-auth__form { display: flex; flex-direction: column; gap: 1rem; }
.mob-auth__footer-link { text-align: center; margin: 0; font-size: 0.9rem; }
.mob-auth__footer-link a { color: var(--text-muted); text-decoration: none; font-weight: 500; }
.mob-auth__footer-link a:hover { color: var(--accent); }
</style>
