<?php
$csrfField       = \App\Core\Security::getCsrfField();
$baseUrl         = rtrim(BASE_URL ?? '', '/');
$flashError      = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);
$googleEnabled     = defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== '';
$emailNonVerifie   = \App\Core\Security::escape($email_non_verifie ?? '');
?>
<div class="mob-auth">

    <div class="mob-auth__hero">
        <a href="<?= $baseUrl ?>/" class="mob-auth__logo">
            <img src="<?= logo_url() ?>" alt="Globalo" style="height:44px;width:auto;max-width:180px;display:block">
        </a>
        <h1 class="mob-auth__title"><?= __("auth.login.title") ?></h1>
        <p class="mob-auth__lead"><?= __("auth.login.lead") ?></p>
    </div>

    <?php if ($flashError): ?>
    <div class="mob-auth__alert">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= \App\Core\Security::escape($flashError) ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
    <div class="mob-auth__alert">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= \App\Core\Security::escape($error) ?>
    </div>
    <?php if ($emailNonVerifie !== ''): ?>
    <form method="POST" action="<?= $baseUrl ?>/auth/renvoyer-verification" style="margin-bottom:1rem;">
        <?= \App\Core\Security::getCsrfField() ?>
        <input type="hidden" name="email" value="<?= $emailNonVerifie ?>">
        <button type="submit" style="display:flex;align-items:center;justify-content:center;gap:.5rem;width:100%;padding:.8rem 1rem;background:#f0fdf4;color:#15803d;border:1.5px solid #86efac;border-radius:12px;font-size:.875rem;font-weight:600;font-family:var(--font);cursor:pointer;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.51"/></svg>
            Renvoyer l'email de vérification
        </button>
    </form>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($googleEnabled): ?>
    <a href="<?= $baseUrl ?>/auth/google" class="btn-google-mob" id="btn-google-mob-login" aria-label="Continuer avec Google">
        <span class="btn-google-mob__icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 48 48">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
        </span>
        <span class="btn-google-mob__text">Continuer avec Google</span>
        <span class="btn-google-mob__shine" aria-hidden="true"></span>
    </a>
    <div class="google-or-divider"><span class="google-or-divider__line"></span><span class="google-or-divider__text">ou avec email</span><span class="google-or-divider__line"></span></div>
    <script>
    (function(){
        var b=document.getElementById('btn-google-mob-login');
        if(!b) return;
        b.addEventListener('click',function(){b.classList.add('loading');b.querySelector('.btn-google-mob__text').textContent='Redirection…';});
    })();
    </script>
    <?php endif; ?>

    <form method="post" action="<?= $baseUrl ?>/auth/connexion" class="mob-auth__form">
        <?= $csrfField ?>

        <div class="form-mobile">
            <label class="form-mobile__label"><?= __("auth.login.email") ?></label>
            <input type="email" name="email" class="form-mobile__input"
                   required autocomplete="email"
                   value="<?= \App\Core\Security::escape($_POST['email'] ?? '') ?>"
                   placeholder="vous@exemple.fr">
        </div>

        <div class="form-mobile">
            <label class="form-mobile__label"><?= __("auth.login.password") ?></label>
            <input type="password" name="password" class="form-mobile__input"
                   required autocomplete="current-password"
                   placeholder="••••••••">
        </div>

        <button type="submit" class="btn-publish mob-auth__submit">
            <?= __("auth.login.submit") ?>
        </button>

        <div class="mob-auth__links">
            <a href="<?= $baseUrl ?>/auth/mot-de-passe-oublie" class="mob-auth__link"><?= __("auth.login.forgot") ?></a>
            <a href="<?= $baseUrl ?>/auth/inscription" class="mob-auth__link mob-auth__link--accent">
                <?= __("auth.login.no_account") ?> <strong><?= __("nav.signup") ?></strong>
            </a>
        </div>
    </form>

</div>

<style>
.mob-auth { padding: 0 0 2rem; }
.mob-auth__hero { text-align: center; padding: 1.75rem 0 1.5rem; }
.mob-auth__logo { display: inline-block; margin-bottom: 1.25rem; }
.mob-auth__title { font-size: 1.375rem; font-weight: 800; color: var(--text); margin: 0 0 .4rem; }
.mob-auth__lead  { font-size: .875rem; color: var(--text-muted); margin: 0; }
.mob-auth__alert {
    display: flex; align-items: center; gap: .5rem;
    background: #fef2f2; border: 1px solid #fca5a5;
    color: #b91c1c; border-radius: 10px;
    padding: .75rem 1rem; font-size: .875rem; margin-bottom: 1rem;
}
.mob-auth__form { display: flex; flex-direction: column; gap: 1rem; }
.mob-auth__submit {
    margin-top: .25rem;
    background: var(--accent);
    color: #fff;
    border: none;
    width: 100%;
    padding: .9rem;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    font-family: var(--font);
    cursor: pointer;
}
.mob-auth__links { display: flex; flex-direction: column; align-items: center; gap: .75rem; margin-top: .5rem; }
.mob-auth__link { font-size: .875rem; color: var(--text-muted); text-decoration: none; text-align: center; }
.mob-auth__link--accent { color: var(--accent); }
/* bouton google – styles partagés avec inscription mobile */
.btn-google-mob{position:relative;display:flex;align-items:center;justify-content:center;gap:12px;width:100%;padding:15px 18px;background:#fff;color:#3c4043;border:1.5px solid #e0e0e0;border-radius:14px;font-size:.9375rem;font-weight:600;font-family:inherit;text-decoration:none;cursor:pointer;overflow:hidden;transition:box-shadow .28s cubic-bezier(.22,1,.36,1),border-color .2s,transform .2s;box-shadow:0 2px 8px rgba(0,0,0,.08),0 1px 3px rgba(0,0,0,.05);letter-spacing:.01em;box-sizing:border-box;margin-bottom:.9rem}
.btn-google-mob:active{transform:scale(.98);box-shadow:0 1px 4px rgba(0,0,0,.08)}
.btn-google-mob:focus-visible{outline:3px solid #4285F4;outline-offset:2px}
.btn-google-mob.loading{pointer-events:none;opacity:.8}
.btn-google-mob__icon{display:flex;align-items:center;justify-content:center;width:22px;height:22px;flex-shrink:0}
.btn-google-mob__text{flex:1;text-align:center}
.btn-google-mob__shine{position:absolute;top:0;left:-80%;width:50%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.6),transparent);transform:skewX(-15deg);pointer-events:none;animation:google-shine 3s 1s ease-in-out forwards}
@keyframes google-shine{0%{left:-80%}100%{left:130%}}
.google-or-divider{display:flex;align-items:center;gap:10px;margin:.25rem 0 1rem;color:#9ca3af;font-size:.8rem;font-weight:500}
.google-or-divider__line{flex:1;height:1px;background:linear-gradient(90deg,transparent,#e5e7eb 30%,#e5e7eb 70%,transparent)}
.google-or-divider__text{white-space:nowrap;padding:0 4px}
.form-mobile__label { display: block; font-size: .875rem; font-weight: 600; color: var(--text); margin-bottom: .4rem; }
.form-mobile__input {
    display: block; width: 100%; box-sizing: border-box;
    padding: .75rem 1rem; font-size: 1rem; font-family: var(--font);
    border: 1.5px solid var(--border); border-radius: 10px;
    background: var(--surface); color: var(--text);
    transition: border-color .15s;
}
.form-mobile__input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(22,163,74,.15); }
</style>
