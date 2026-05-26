<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn(string $s): string => \App\Core\Security::escape($s);
$prixClient = (int) ($prix_client_xof ?? 1000);
$prixExpert = (int) ($prix_expert_xof ?? 1500);
$prixEtudiant = (int) ($prix_etudiant_xof ?? 500);
$prixProfesseur = (int) ($prix_professeur_xof ?? 1000);
$formatFcfa = fn(int $n) => $n > 0 ? number_format($n, 0, ',', ' ') . ' Fcfa/mois' : '';
$pricingNote = __("about.pricing.subscriptions_note", [
    'client'     => $formatFcfa($prixClient),
    'expert'     => $formatFcfa($prixExpert),
    'etudiant'   => $formatFcfa($prixEtudiant),
    'professeur' => $formatFcfa($prixProfesseur),
]);
?>
<div class="mob-apropos">

    <div class="mob-apropos-hero">
        <span class="mob-apropos-badge"><?= $e(__('nav.about')) ?></span>
        <h1 class="mob-apropos-title"><?= $e(__('about.title')) ?></h1>
        <p class="mob-apropos-lead"><?= $e(__('about.lead')) ?></p>
    </div>

    <div class="mob-apropos-stats">
        <div class="mob-apropos-stat">
            <strong>5</strong>
            <span><?= $e(__('about.stats.countries')) ?></span>
        </div>
        <div class="mob-apropos-stat">
            <strong>50+</strong>
            <span><?= $e(__('about.stats.subjects')) ?></span>
        </div>
        <div class="mob-apropos-stat">
            <strong>1–3h</strong>
            <span><?= $e(__('about.stats.session')) ?></span>
        </div>
        <div class="mob-apropos-stat mob-apropos-stat--accent">
            <strong>PT</strong>
            <span><?= $e(__('about.stats.paytech')) ?></span>
        </div>
    </div>

    <section class="mob-apropos-section">
        <h2><?= $e(__('about.mission.title')) ?></h2>
        <p><?= __("about.mission.text") ?></p>
        <p><?= __("about.mission.paytech") ?></p>
    </section>

    <section class="mob-apropos-paytech" aria-labelledby="mob-paytech-title">
        <div class="mob-apropos-paytech__inner">
            <span class="mob-apropos-paytech__badge"><?= $e(__('about.paytech.badge')) ?></span>
            <h2 id="mob-paytech-title" class="mob-apropos-paytech__title"><?= $e(__('about.paytech.title')) ?></h2>
            <p class="mob-apropos-paytech__lead"><?= __("about.paytech.lead") ?></p>
            <ul class="mob-apropos-paytech__list">
                <li><?= $e(__('about.paytech.f1')) ?></li>
                <li><?= $e(__('about.paytech.f2')) ?></li>
                <li><?= $e(__('about.paytech.f3')) ?></li>
                <li><?= $e(__('about.paytech.f4')) ?></li>
            </ul>
            <p class="mob-apropos-paytech__note"><?= $e(__('about.paytech.note')) ?></p>
            <span class="mob-apropos-paytech__link"><?= $e(__('about.paytech.link')) ?></span>
        </div>
    </section>

    <section class="mob-apropos-section">
        <h2><?= $e(__('about.who.title')) ?></h2>
        <p class="mob-apropos-section-note"><?= $pricingNote ?></p>

        <div class="mob-apropos-card mob-apropos-card--client">
            <div class="mob-apropos-card-icon">💼</div>
            <h3><?= $e(__('about.who.client.title')) ?></h3>
            <span class="mob-apropos-card-tarif mob-apropos-card-tarif--payant"><?= $e($formatFcfa($prixClient)) ?></span>
            <p><?= $e(__('about.who.client.text')) ?></p>
            <p class="mob-apropos-card-hint"><?= $e(__('about.who.client.feature_paytech')) ?></p>
            <a href="<?= $e($baseUrl . '/auth/inscription') ?>" class="mob-apropos-link"><?= $e(__('about.who.client.button')) ?> →</a>
        </div>

        <div class="mob-apropos-card mob-apropos-card--expert">
            <div class="mob-apropos-card-icon">🎯</div>
            <h3><?= $e(__('about.who.expert.title')) ?></h3>
            <span class="mob-apropos-card-tarif mob-apropos-card-tarif--payant"><?= $e($formatFcfa($prixExpert)) ?></span>
            <p><?= $e(__('about.who.expert.text')) ?></p>
            <a href="<?= $e($baseUrl . '/auth/inscription') ?>" class="mob-apropos-link"><?= $e(__('about.who.expert.button')) ?> →</a>
        </div>

        <div class="mob-apropos-card mob-apropos-card--etudiant">
            <div class="mob-apropos-card-icon">🎓</div>
            <h3><?= $e(__('about.who.etudiant.title')) ?></h3>
            <span class="mob-apropos-card-tarif mob-apropos-card-tarif--payant"><?= $e($formatFcfa($prixEtudiant)) ?></span>
            <p><?= $e(__('about.who.etudiant.text')) ?></p>
            <a href="<?= $e($baseUrl . '/auth/inscription') ?>" class="mob-apropos-link"><?= $e(__('about.who.etudiant.button')) ?> →</a>
        </div>

        <div class="mob-apropos-card mob-apropos-card--professeur">
            <div class="mob-apropos-card-icon">👨‍🏫</div>
            <h3><?= $e(__('about.who.professeur.title')) ?></h3>
            <span class="mob-apropos-card-tarif mob-apropos-card-tarif--payant"><?= $e($formatFcfa($prixProfesseur)) ?></span>
            <p><?= $e(__('about.who.professeur.text')) ?></p>
            <a href="<?= $e($baseUrl . '/auth/inscription') ?>" class="mob-apropos-link mob-apropos-link--violet"><?= $e(__('about.who.professeur.button')) ?> →</a>
        </div>
    </section>

    <section class="mob-apropos-section mob-apropos-section--pays">
        <h2><?= $e(__('about.countries.title')) ?></h2>
        <p class="mob-apropos-pays-lead"><?= $e(__('about.countries.lead')) ?></p>
        <div class="mob-apropos-pays">
            <span>🇲🇱 Mali</span>
            <span>🇨🇮 Côte d'Ivoire</span>
            <span>🇸🇳 Sénégal</span>
            <span>🇧🇯 Bénin</span>
            <span>🇳🇪 Niger</span>
        </div>
    </section>

    <section class="mob-apropos-cta" aria-label="<?= $e(__('about.cta.title')) ?>">
        <p class="mob-apropos-cta__title"><?= $e(__('about.cta.title')) ?></p>
        <p class="mob-apropos-cta__lead"><?= $e(__('about.cta.lead')) ?></p>
        <div class="mob-apropos-cta__buttons">
            <a href="<?= $e($baseUrl . '/auth/inscription') ?>" class="mob-apropos-cta__btn mob-apropos-cta__btn--primary">
                <span class="mob-apropos-cta__btn-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                </span>
                <?= $e(__('about.cta.signup')) ?>
            </a>
            <a href="<?= $e($baseUrl . '/experts') ?>" class="mob-apropos-cta__btn mob-apropos-cta__btn--outline">
                <span class="mob-apropos-cta__btn-icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <?= $e(__('about.cta.experts')) ?>
            </a>
            <a href="<?= $e($baseUrl . '/home/contact') ?>" class="mob-apropos-cta__contact"><?= $e(__('about.cta.contact')) ?></a>
        </div>
    </section>

</div>

<style>
.mob-apropos-paytech__lead strong { color: #f0fdfa; font-weight: 800; }
.mob-apropos-cta__lead {
    font-size: 0.82rem;
    color: #64748b;
    margin: -0.35rem 0 1rem;
    line-height: 1.5;
    text-align: center;
}
.mob-apropos-section p { margin: 0 0 0.75rem; }
.mob-apropos-section p:last-child { margin-bottom: 0; }
.mob-apropos-card-hint {
    font-size: .75rem;
    color: #0f766e;
    font-weight: 600;
    margin: 0 0 .6rem !important;
    line-height: 1.45;
}
.mob-apropos-cta__contact {
    display: block;
    text-align: center;
    font-size: 0.88rem;
    font-weight: 700;
    color: #0f766e;
    text-decoration: none;
    padding: 0.35rem;
}
.mob-apropos-pays-lead { font-size: .78rem; color: #64748b; margin: 0 0 .75rem; line-height: 1.45; }
</style>
