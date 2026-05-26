<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$contactEmail = $contactEmail ?? 'contact@secogesarl.com';
$e = function ($s) { return \App\Core\Security::escape($s ?? ''); };
?>
<div class="page-contact">
    <header class="contact-hero">
        <div class="contact-hero-bg" aria-hidden="true"></div>
        <div class="contact-hero-content">
            <div class="contact-hero-icon" aria-hidden="true">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
            </div>
            <h1 class="contact-hero-title"><?= __("contact.title") ?></h1>
            <p class="contact-hero-lead"><?= __("contact.lead") ?></p>
        </div>
    </header>

    <section class="contact-content">
        <div class="contact-card">
            <div class="contact-card-inner">
                <h2 class="contact-card-title"><?= __("contact.write_title") ?></h2>
                <p class="contact-card-text"><?= __("contact.write_lead") ?></p>
                <div class="contact-email-block">
                    <a href="mailto:<?= $e($contactEmail) ?>" class="contact-email-link" id="contact-email-link">
                        <span class="contact-email-icon" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </span>
                        <span class="contact-email-value"><?= $e($contactEmail) ?></span>
                    </a>
                    <button type="button" class="contact-email-copy btn btn-outline btn-sm" id="contact-copy-btn" title="Copier l'adresse" aria-label="Copier l'adresse email">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        Copier
                    </button>
                </div>
                <p class="contact-card-note"><?= __("contact.reply_note") ?></p>
            </div>
        </div>

        <div class="contact-cta">
            <p class="contact-cta-text"><?= __("contact.has_account") ?></p>
            <a href="<?= $baseUrl ?>/auth/connexion" class="btn btn-primary"><?= __("contact.login") ?></a>
        </div>
    </section>
</div>

<script>
(function() {
    var btn = document.getElementById('contact-copy-btn');
    var link = document.getElementById('contact-email-link');
    if (!btn || !link) return;
    var email = link.getAttribute('href').replace(/^mailto:/i, '').trim();
    btn.addEventListener('click', function() {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(email).then(function() {
                var t = btn.textContent;
                btn.textContent = 'Copié !';
                btn.classList.add('contact-email-copy--ok');
                setTimeout(function() { btn.textContent = t; btn.classList.remove('contact-email-copy--ok'); }, 2000);
            });
        }
    });
})();
</script>
