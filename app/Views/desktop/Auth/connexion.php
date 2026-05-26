<?php
$csrfField       = \App\Core\Security::getCsrfField();
$baseUrl         = rtrim(BASE_URL ?? '', '/');
$flashError      = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);
$googleEnabled     = defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== '';
$emailNonVerifie   = \App\Core\Security::escape($email_non_verifie ?? '');
?>
<div class="page-connexion">
    <div class="auth-page-backdrop" aria-hidden="true"></div>

    <div class="auth-page-content">
        <header class="auth-intro">
            <a href="<?= $baseUrl ?>/" class="auth-logo-link" aria-label="Globalo - Accueil">
                <span class="auth-logo-circle">
                    <img src="<?= logo_url() ?>" alt="Globalo" class="auth-logo" width="160" height="160">
                </span>
            </a>
            <span class="auth-badge"><?= __("auth.login.badge") ?></span>
            <h1><?= __("auth.login.title") ?></h1>
            <p class="auth-intro-lead"><?= __("auth.login.lead") ?></p>
        </header>

        <div class="auth-form-wrapper">
            <?php if ($flashError): ?>
                <div class="alert alert-error" role="alert">
                    <span class="alert-icon" aria-hidden="true">!</span>
                    <span><?= \App\Core\Security::escape($flashError) ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-error" role="alert">
                    <span class="alert-icon" aria-hidden="true">!</span>
                    <span><?= \App\Core\Security::escape($error) ?></span>
                </div>
                <?php if ($emailNonVerifie !== ''): ?>
                <form method="POST" action="<?= $baseUrl ?>/auth/renvoyer-verification" style="margin-top:.5rem;">
                    <?= \App\Core\Security::getCsrfField() ?>
                    <input type="hidden" name="email" value="<?= $emailNonVerifie ?>">
                    <button type="submit" class="btn btn-outline btn-block" style="font-size:.875rem;">
                        Renvoyer l'email de vérification à <?= $emailNonVerifie ?>
                    </button>
                </form>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($googleEnabled): ?>
            <div class="auth-social">
                <a href="<?= $baseUrl ?>/auth/google" class="btn-google-unified" id="btn-google-login" aria-label="Se connecter avec Google">
                    <span class="btn-google-icon-wrap" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        </svg>
                    </span>
                    <span class="btn-google-text">Continuer avec Google</span>
                    <span class="btn-google-shine" aria-hidden="true"></span>
                </a>
                <div class="auth-divider-google"><span class="auth-divider-google__line"></span><span class="auth-divider-google__label">ou avec email</span><span class="auth-divider-google__line"></span></div>
            </div>
            <style>
            .btn-google-unified{position:relative;display:flex;align-items:center;justify-content:center;gap:12px;width:100%;padding:14px 20px;background:#fff;color:#3c4043;border:1.5px solid #e0e0e0;border-radius:12px;font-size:.9375rem;font-weight:600;font-family:inherit;text-decoration:none;cursor:pointer;overflow:hidden;transition:box-shadow .28s cubic-bezier(.22,1,.36,1),border-color .2s,transform .22s cubic-bezier(.22,1,.36,1);box-shadow:0 1px 4px rgba(0,0,0,.08),0 1px 2px rgba(0,0,0,.05);letter-spacing:.01em;user-select:none;-webkit-user-select:none}
            .btn-google-unified:hover{box-shadow:0 6px 20px rgba(66,133,244,.18),0 2px 8px rgba(0,0,0,.1);border-color:#c5c5c5;transform:translateY(-2px)}
            .btn-google-unified:active{transform:translateY(0);box-shadow:0 1px 4px rgba(0,0,0,.08)}
            .btn-google-unified:focus-visible{outline:3px solid #4285F4;outline-offset:2px}
            .btn-google-unified.loading{pointer-events:none;opacity:.8}
            .btn-google-icon-wrap{display:flex;align-items:center;justify-content:center;width:22px;height:22px;flex-shrink:0;transition:transform .2s}
            .btn-google-unified:hover .btn-google-icon-wrap{transform:scale(1.08)}
            .btn-google-text{flex:1;text-align:center;transition:letter-spacing .2s}
            .btn-google-shine{position:absolute;top:0;left:-80%;width:50%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.55),transparent);transform:skewX(-15deg);pointer-events:none;transition:left .55s ease}
            .btn-google-unified:hover .btn-google-shine{left:130%}
            .auth-divider-google{display:flex;align-items:center;gap:10px;margin:18px 0 4px;color:#9ca3af;font-size:.8125rem;font-weight:500}
            .auth-divider-google__line{flex:1;height:1px;background:linear-gradient(90deg,transparent,#e5e7eb,transparent)}
            .auth-divider-google__label{white-space:nowrap;padding:0 4px}
            </style>
            <script>
            (function(){
                var btn = document.getElementById('btn-google-login');
                if (!btn) return;
                btn.addEventListener('click', function() {
                    btn.classList.add('loading');
                    btn.querySelector('.btn-google-text').textContent = 'Redirection…';
                });
            })();
            </script>
            <?php endif; ?>

            <form method="post" action="<?= $baseUrl ?>/auth/connexion" class="auth-form">
                <?= $csrfField ?>

                <section class="auth-form-block" aria-labelledby="block-connexion">
                    <h2 id="block-connexion" class="auth-form-block-title">
                        <span class="block-title-num">1</span> <?= __("auth.login.credentials") ?>
                    </h2>
                    <div class="form-group">
                        <label for="email"><?= __("auth.login.email") ?></label>
                        <input type="email" id="email" name="email" required autocomplete="email" value="<?= \App\Core\Security::escape($_POST['email'] ?? '') ?>" placeholder="vous@exemple.fr">
                    </div>
                    <div class="form-group">
                        <label for="password"><?= __("auth.login.password") ?></label>
                        <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                    </div>
                </section>

                <div class="auth-form-actions">
                    <button type="submit" class="btn btn-primary btn-lg btn-block btn-auth-submit">
                        <?= __("auth.login.submit") ?>
                    </button>
                    <p class="auth-form-link auth-form-link-secondary">
                        <a href="<?= $baseUrl ?>/auth/mot-de-passe-oublie"><?= __("auth.login.forgot") ?></a>
                    </p>
                    <p class="auth-form-link"><?= __("auth.login.no_account") ?> <a href="<?= $baseUrl ?>/auth/inscription"><?= __("nav.signup") ?></a></p>
                </div>
            </form>
        </div>
    </div>
</div>
<style>
.auth-social { margin-bottom: 1.25rem; }
.btn-google-auth {
    display: flex; align-items: center; justify-content: center; gap: .65rem;
    width: 100%; padding: .75rem 1rem;
    background: #fff; color: #3c4043;
    border: 1.5px solid #dadce0; border-radius: 8px;
    font-size: .95rem; font-weight: 600; text-decoration: none;
    transition: box-shadow .15s, border-color .15s;
}
.btn-google-auth:hover { box-shadow: 0 1px 6px rgba(0,0,0,.15); border-color: #bbb; }
.auth-divider { display: flex; align-items: center; gap: .75rem; margin: 1.1rem 0; color: #9ca3af; font-size: .82rem; }
.auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }
</style>
