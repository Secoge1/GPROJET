<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$user = $user ?? null;
$redirectUrl = $redirectUrl ?? $baseUrl . '/client/demandes/nouvelle';
$demandes = $demandes ?? [];
$urgenceLb = ['normale' => 'Normale', 'urgent' => 'Urgent', 'tres_urgent' => 'Très urgent'];
?>
<div class="mob-demandes-public">

    <header class="mob-demandes-public__hero">
        <div class="mob-demandes-public__hero-icon" aria-hidden="true">
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M24 4C12.95 4 4 12.95 4 24s8.95 20 20 20 20-8.95 20-20S35.05 4 24 4zm0 36c-8.82 0-16-7.18-16-16S15.18 8 24 8s16 7.18 16 16-7.18 16-16 16z" fill="currentColor" opacity="0.2"/><path d="M24 14v10l6 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
        </div>
        <span class="mob-demandes-public__badge">Demandes d'assistance</span>
        <?php if (!empty($demandes)): ?>
        <span class="mob-dem-pub-hero-count">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <?= count($demandes) ?> demande<?= count($demandes) > 1 ? 's' : '' ?> ouverte<?= count($demandes) > 1 ? 's' : '' ?> en ce moment
        </span>
        <?php endif; ?>
        <h1 class="mob-demandes-public__title">Créer une demande</h1>
        <p class="mob-demandes-public__lead">Décrivez votre besoin. Un expert vous accompagnera en 1 à 3 heures. Inscription gratuite, paiement en XOF.</p>
    </header>

    <div class="mob-demandes-public__card">
        <?php if ($user && ($user['role'] ?? '') === 'client'): ?>
            <p class="mob-demandes-public__card-text">Vous êtes connecté. Créez une nouvelle demande.</p>
            <div class="mob-demandes-public__actions">
                <a href="<?= $e($redirectUrl) ?>" class="btn-mobile btn-primary">Créer une demande</a>
                <a href="<?= $baseUrl ?>/app/demandes" class="btn-mobile btn-outline">Mes demandes</a>
            </div>
        <?php elseif ($user): ?>
            <p class="mob-demandes-public__card-text">Les demandes sont pour les clients. Inscrivez-vous en tant que client pour en créer.</p>
            <a href="<?= $baseUrl ?>/auth/inscription?role=client" class="btn-mobile btn-primary">S'inscrire en tant que client</a>
        <?php else: ?>
            <p class="mob-demandes-public__card-text">Connectez-vous ou créez un compte pour déposer votre demande.</p>
            <div class="mob-demandes-public__actions">
                <a href="<?= $baseUrl ?>/auth/connexion?redirect=<?= urlencode($redirectUrl) ?>" class="btn-mobile btn-primary">Se connecter</a>
                <a href="<?= $baseUrl ?>/auth/inscription?role=client" class="btn-mobile btn-outline">Créer un compte</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($demandes)): ?>
    <section class="mob-demandes-public__list">
        <h2 class="mob-demandes-public__list-title">
            Demandes ouvertes
            <?php $_nbMob = count($demandes); ?>
            <span class="mob-dem-pub-count-badge" aria-label="<?= $_nbMob ?> demandes ouvertes"><?= $_nbMob > 99 ? '99+' : $_nbMob ?></span>
        </h2>
        <p class="mob-demandes-public__list-desc"><?= count($demandes) ?> demande<?= count($demandes) > 1 ? 's' : '' ?> ouverte<?= count($demandes) > 1 ? 's' : '' ?> sur la plateforme. Connectez-vous pour en créer une.</p>
        <ul class="mob-demandes-public__list-ul">
            <?php foreach ($demandes as $d):
                $cp = trim((string) ($d['client_prenom'] ?? ''));
                $cn = trim((string) ($d['client_nom'] ?? ''));
                $initialsList = strtoupper(mb_substr($cp, 0, 1) . mb_substr($cn, 0, 1));
                if ($initialsList === '') {
                    $initialsList = '?';
                }
                $colorsList   = ['#2563eb', '#16a34a', '#7c3aed', '#0d9488', '#d97706'];
                $avatarBgList = $colorsList[abs(crc32($cp . $cn)) % count($colorsList)];
                $clientLabelRaw = $cp !== '' ? ($cn !== '' ? $cp . ' ' . mb_substr($cn, 0, 1) . '.' : $cp) : '';
            ?>
            <?php
            $jobUrl = !empty($d['slug'])
                ? $baseUrl . '/jobs/' . $e($d['slug'])
                : null;
            ?>
            <li class="mob-demandes-public__list-item">
                <?php if ($jobUrl): ?>
                <a href="<?= $jobUrl ?>" class="mob-demandes-public__list-item-link">
                <?php endif; ?>
                <div class="mob-demandes-public__list-item-row">
                    <?php
                    $initials     = $initialsList;
                    $avatarBg     = $avatarBgList;
                    $avatarColumn = $d['client_avatar'] ?? null;
                    $pays         = $d['client_pays'] ?? null;
                    $alt          = $clientLabelRaw !== '' ? 'Client ' . $clientLabelRaw : '';
                    $size         = 'sm';
                    require APP_PATH . '/Views/partials/public_user_thumb.php';
                    ?>
                    <div class="mob-demandes-public__list-item-content">
                        <strong class="mob-demandes-public__list-item-title"><?= $e($d['titre'] ?? '') ?></strong>
                        <?php if ($clientLabelRaw !== ''): ?>
                        <span class="mob-demandes-public__list-item-client">Demandeur : <?= $e($clientLabelRaw) ?></span>
                        <?php endif; ?>
                        <span class="mob-demandes-public__list-item-meta">
                            <?php if (!empty($d['competence_nom'])): ?>
                                <span><?= $e($d['competence_nom']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($d['urgence']) && ($d['urgence'] ?? '') !== 'normale'): ?>
                                <span class="mob-demandes-public__urgence"><?= $e($urgenceLb[$d['urgence']] ?? $d['urgence']) ?></span>
                            <?php endif; ?>
                            <span><?= date('d/m/Y', strtotime($d['created_at'] ?? 'now')) ?></span>
                        </span>
                    </div>
                </div>
                <?php if ($jobUrl): ?>
                </a>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php if (!$user): ?>
        <div class="mob-demandes-public__list-cta">
            <a href="<?= $baseUrl ?>/auth/connexion?redirect=<?= urlencode($redirectUrl) ?>" class="btn-mobile btn-primary">Se connecter pour créer une demande</a>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <section class="mob-demandes-public__steps">
        <h2 class="mob-demandes-public__steps-title">Comment ça marche</h2>
        <div class="mob-demandes-public__steps-list">
            <div class="mob-demandes-public__step">
                <span class="mob-demandes-public__step-num">1</span>
                <div>
                    <strong>Créez une demande</strong>
                    <span> — Titre, description, compétence.</span>
                </div>
            </div>
            <div class="mob-demandes-public__step">
                <span class="mob-demandes-public__step-num">2</span>
                <div>
                    <strong>Choisissez un expert</strong>
                    <span> — Réservez un créneau.</span>
                </div>
            </div>
            <div class="mob-demandes-public__step">
                <span class="mob-demandes-public__step-num">3</span>
                <div>
                    <strong>Session en direct</strong>
                    <span> — Travaillez ensemble.</span>
                </div>
            </div>
            <div class="mob-demandes-public__step">
                <span class="mob-demandes-public__step-num">4</span>
                <div>
                    <strong>Paiement Mobile Money</strong>
                    <span> — Réglez en XOF depuis votre téléphone, en toute sécurité.</span>
                </div>
            </div>
        </div>

        <!-- Bloc paiement visuel -->
        <div style="margin-top:1.25rem;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:16px;overflow:hidden">
            <div style="display:flex;align-items:center;gap:.6rem;padding:.75rem 1rem;border-bottom:1px solid #e2e8f0;background:#fff">
                <span style="display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;background:#dcfce7;flex-shrink:0">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.2" stroke-linecap="round"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                </span>
                <div>
                    <p style="margin:0;font-size:.82rem;font-weight:700;color:#0f172a">Paiement Mobile Money</p>
                    <p style="margin:0;font-size:.7rem;color:#64748b">Sécurisé · Devise XOF · Confirmation instantanée</p>
                </div>
                <span style="margin-left:auto;font-size:.68rem;font-weight:700;background:#dcfce7;color:#16a34a;padding:.15rem .45rem;border-radius:20px;flex-shrink:0">Sécurisé</span>
            </div>
            <div style="padding:.85rem 1rem">
                <p style="margin:0 0 .7rem;font-size:.75rem;color:#64748b;font-weight:500">Opérateurs acceptés</p>
                <div style="display:flex;align-items:center;gap:.5rem">
                    <div style="flex:1;display:flex;align-items:center;justify-content:center;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:.4rem .4rem;height:44px">
                        <img src="<?= $baseUrl ?>/assets/images/operators/wave.png" alt="Wave" style="max-height:32px;max-width:100%;width:auto;object-fit:contain">
                    </div>
                    <div style="flex:1;display:flex;align-items:center;justify-content:center;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:.4rem .4rem;height:44px">
                        <img src="<?= $baseUrl ?>/assets/images/operators/orange-money.png" alt="Orange Money" style="max-height:32px;max-width:100%;width:auto;object-fit:contain">
                    </div>
                    <div style="flex:1;display:flex;align-items:center;justify-content:center;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;padding:.4rem .4rem;height:44px">
                        <img src="<?= $baseUrl ?>/assets/images/operators/moov-africa.png" alt="Moov Africa" style="max-height:32px;max-width:100%;width:auto;object-fit:contain">
                    </div>
                </div>
                <p style="margin:.7rem 0 0;font-size:.72rem;color:#94a3b8;text-align:center">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="vertical-align:middle;margin-right:.2rem"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Paiement chiffré · Aucune commission cachée
                </p>
            </div>
        </div>
    </section>

</div>
<style>
.mob-demandes-public__list-title {
    display: flex;
    align-items: center;
    gap: .5rem;
    flex-wrap: wrap;
}
.mob-dem-pub-count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 22px;
    height: 22px;
    padding: 0 7px;
    border-radius: 999px;
    background: #f97316;
    color: #fff;
    font-size: .75rem;
    font-weight: 800;
    line-height: 1;
    flex-shrink: 0;
}
.mob-dem-pub-hero-count {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    margin-top: .4rem;
    background: rgba(249,115,22,.12);
    color: #ea580c;
    border: 1px solid rgba(249,115,22,.3);
    border-radius: 999px;
    padding: .25rem .65rem;
    font-size: .75rem;
    font-weight: 600;
    line-height: 1.3;
}
</style>
