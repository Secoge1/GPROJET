<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$contactEmail = $contactEmail ?? 'contact@secogesarl.com';
$e = fn($s) => \App\Core\Security::escape($s ?? '');
?>
<div class="mob-contact">

    <div class="mob-contact__hero">
        <div class="mob-contact__icon">✉️</div>
        <h1 class="mob-contact__title"><?= __("contact.title") ?></h1>
        <p class="mob-contact__lead"><?= __("contact.lead") ?></p>
    </div>

    <div class="mob-contact__card">
        <h2 class="mob-contact__card-title"><?= __("contact.write_title") ?></h2>
        <p style="font-size:.875rem;color:var(--text-muted);margin:0 0 1rem"><?= __("contact.write_lead") ?></p>

        <a href="mailto:<?= $e($contactEmail) ?>" class="mob-contact__email-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
            <?= $e($contactEmail) ?>
        </a>

        <button type="button" id="mob-copy-btn" class="mob-contact__copy-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
            Copier l'adresse
        </button>

        <p style="font-size:.8rem;color:var(--text-muted);margin:.75rem 0 0"><?= __("contact.reply_note") ?></p>
    </div>

    <?php if (!\App\Core\Auth::check()): ?>
    <div class="mob-contact__cta">
        <p style="font-size:.875rem;color:var(--text-muted);margin:0 0 .75rem"><?= __("contact.has_account") ?></p>
        <a href="<?= $baseUrl ?>/auth/connexion" class="btn-publish" style="display:block;text-align:center;text-decoration:none"><?= __("contact.login") ?></a>
    </div>
    <?php endif; ?>

</div>

<style>
.mob-contact { padding: 0 1.25rem 2rem; }
.mob-contact__hero { text-align: center; padding: 2rem 0 1.5rem; }
.mob-contact__icon { font-size: 3rem; margin-bottom: .75rem; }
.mob-contact__title { font-size: 1.375rem; font-weight: 800; color: var(--text); margin: 0 0 .4rem; }
.mob-contact__lead  { font-size: .875rem; color: var(--text-muted); margin: 0; }
.mob-contact__card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 1.25rem;
    margin-bottom: 1rem;
}
.mob-contact__card-title { font-size: 1rem; font-weight: 700; margin: 0 0 .5rem; color: var(--text); }
.mob-contact__email-btn {
    display: flex; align-items: center; gap: .6rem;
    background: var(--accent-soft); color: var(--accent);
    border-radius: 10px; padding: .875rem 1rem;
    text-decoration: none; font-weight: 700; font-size: .9375rem;
    margin-bottom: .75rem; word-break: break-all;
}
.mob-contact__copy-btn {
    display: flex; align-items: center; gap: .5rem; justify-content: center;
    width: 100%; padding: .75rem; border-radius: 10px;
    border: 1.5px solid var(--border); background: var(--surface);
    color: var(--text-muted); font-size: .875rem; font-family: var(--font);
    cursor: pointer; transition: background .15s, color .15s;
}
.mob-contact__copy-btn:active { background: var(--accent-soft); color: var(--accent); }
.mob-contact__cta { background: var(--accent-soft); border-radius: 12px; padding: 1.25rem; }
</style>

<script>
(function () {
    var btn   = document.getElementById('mob-copy-btn');
    var email = '<?= $e($contactEmail) ?>';
    if (!btn) return;
    btn.addEventListener('click', function () {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(email).then(function () {
                btn.textContent = '✓ Copié !';
                btn.style.color = '#16a34a';
                setTimeout(function () {
                    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg> Copier l\'adresse';
                    btn.style.color = '';
                }, 2000);
            });
        }
    });
})();
</script>
