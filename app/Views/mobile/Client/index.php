<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = fn($s) => \App\Core\Security::escape($s ?? '');
$demandes     = $demandes ?? [];
$reservations = $reservations ?? [];
$referralLink = $referral_link ?? '';
$user         = $user ?? null;
$prenom       = trim((string)($user['prenom'] ?? ''));
$salutation   = $prenom !== '' ? $e($prenom) : 'cher membre';
$avatarLetter = $prenom !== '' ? mb_strtoupper(mb_substr($prenom, 0, 1)) : 'M';
$bp = $client_base_path ?? '/client';
$nouvelleDemandeUrl = $baseUrl . ($bp === '/app' ? '/app/nouvelle' : $bp . '/demandes/nouvelle');

$statut_cl = ['ouverte'=>'#16a34a','en_cours'=>'#2563eb','terminee'=>'#6b7280','annulee'=>'#dc2626'];
$statut_lb = ['ouverte'=>'Ouverte','en_cours'=>'En cours','terminee'=>'Terminée','annulee'=>'Annulée',
              'acceptee'=>'Acceptée','en_attente'=>'En attente','payee'=>'Payée'];
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="mobile-flash-success"><?= $e($_SESSION['flash_success']) ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>

<?php
$pending_payment_reservations = $pending_payment_reservations ?? [];
if (!empty($pending_payment_reservations)):
    $nbPay = count($pending_payment_reservations);
    $firstPay = $pending_payment_reservations[0];
    $payBannerHref = $nbPay === 1
        ? $baseUrl . $bp . '/payer/' . (int) ($firstPay['id'] ?? 0)
        : $baseUrl . $bp . '/reservations';
    $payBannerCta = $nbPay === 1 ? 'Payer maintenant' : 'Voir mes réservations et payer';
?>
<div class="client-payment-banner" role="alert">
    <div class="client-payment-banner__title">Paiement requis</div>
    <p class="client-payment-banner__text">
        <?= $nbPay === 1
            ? 'L’expert a accepté cette mission. Réglez le montant depuis votre portefeuille pour que la session démarre.'
            : 'Vous avez <strong>' . (int) $nbPay . ' réservation(s)</strong> acceptée(s) par un expert — le paiement est indispensable pour lancer la mission.' ?>
    </p>
    <a href="<?= $payBannerHref ?>" class="btn-mobile btn-primary client-payment-banner__btn"><?= $e($payBannerCta) ?></a>
</div>
<?php endif; ?>

<!-- Salutation -->
<div class="mobile-greeting" style="margin-bottom:1.25rem">
    <div>
        <h2 style="font-size:1.25rem;margin:0 0 0.2rem">Bonjour, <?= $salutation ?> 👋</h2>
        <p style="margin:0;font-size:0.82rem;color:var(--text-muted)">Que puis-je faire pour vous ?</p>
    </div>
    <a href="<?= $baseUrl . $bp ?>/compte" class="icon-avatar" aria-label="Mon compte"><?= $avatarLetter ?></a>
</div>

<!-- Actions primaires -->
<a href="<?= $baseUrl . $bp ?>/urgence" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:0.65rem;background:#dc2626;border-color:#dc2626">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    Besoin d'aide maintenant
</a>
<a href="<?= $nouvelleDemandeUrl ?>" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:1.5rem">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Nouvelle demande
</a>

<!-- Résumé chiffres -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.6rem;margin-bottom:1.25rem">
    <div class="mobile-stat-card">
        <span class="mobile-stat-card__num" style="color:#2563eb"><?= count($demandes) ?></span>
        <span class="mobile-stat-card__lbl">Demandes</span>
    </div>
    <div class="mobile-stat-card">
        <span class="mobile-stat-card__num" style="color:#16a34a"><?= count(array_filter($reservations, fn($r) => ($r['statut'] ?? '') === 'en_cours')) ?></span>
        <span class="mobile-stat-card__lbl">En cours</span>
    </div>
    <div class="mobile-stat-card">
        <span class="mobile-stat-card__num" style="color:#6b7280"><?= count(array_filter($reservations, fn($r) => ($r['statut'] ?? '') === 'terminee')) ?></span>
        <span class="mobile-stat-card__lbl">Terminées</span>
    </div>
</div>

<!-- Dernières demandes -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:1rem">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:0.85rem 1rem;border-bottom:1px solid var(--border)">
        <h3 style="margin:0;font-size:0.9rem;font-weight:700;color:var(--primary)">Mes demandes</h3>
        <a href="<?= $baseUrl . $bp ?>/demandes" style="font-size:0.8rem;color:var(--accent);text-decoration:none;font-weight:500">Tout voir →</a>
    </div>
    <?php if (empty($demandes)): ?>
    <div style="padding:1.25rem 1rem;text-align:center">
        <p class="mobile-empty-hint" style="margin:0 0 0.65rem">Aucune demande</p>
        <a href="<?= $nouvelleDemandeUrl ?>" class="btn-mobile btn-outline btn-sm" style="display:inline-flex">Créer une demande</a>
    </div>
    <?php else: ?>
    <?php foreach (array_slice($demandes, 0, 4) as $d): ?>
    <div style="padding:0.75rem 1rem;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem">
            <div style="flex:1;min-width:0">
                <p style="margin:0 0 0.2rem;font-weight:600;font-size:0.88rem;color:var(--primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= $e($d['titre']) ?></p>
                <p style="margin:0;font-size:0.75rem;color:var(--text-muted)"><?= $e($d['competence_nom'] ?? '') ?><?= !empty($d['duree_estimee_heures']) ? ' · ' . $d['duree_estimee_heures'] . 'h' : '' ?></p>
            </div>
            <span style="flex-shrink:0;font-size:0.7rem;font-weight:600;padding:0.15rem 0.55rem;border-radius:999px;background:<?= ($statut_cl[$d['statut']] ?? '#6b7280') ?>18;color:<?= $statut_cl[$d['statut']] ?? '#6b7280' ?>">
                <?= $statut_lb[$d['statut']] ?? $e($d['statut']) ?>
            </span>
        </div>
        <?php if (($d['statut'] ?? '') === 'ouverte'): ?>
        <a href="<?= $baseUrl . $bp ?>/reserver/<?= (int)$d['id'] ?>" style="display:inline-flex;align-items:center;gap:0.3rem;margin-top:0.4rem;font-size:0.75rem;font-weight:600;color:var(--accent);text-decoration:none">
            Réserver un expert →
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Dernières réservations -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:1rem">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:0.85rem 1rem;border-bottom:1px solid var(--border)">
        <h3 style="margin:0;font-size:0.9rem;font-weight:700;color:var(--primary)">Réservations récentes</h3>
        <a href="<?= $baseUrl . $bp ?>/reservations" style="font-size:0.8rem;color:var(--accent);text-decoration:none;font-weight:500">Tout voir →</a>
    </div>
    <?php if (empty($reservations)): ?>
    <div style="padding:1.25rem 1rem;text-align:center">
        <p class="mobile-empty-hint" style="margin:0">Aucune réservation</p>
    </div>
    <?php else: ?>
    <?php foreach (array_slice($reservations, 0, 3) as $r): ?>
    <a href="<?= $baseUrl . $bp ?>/reservations/<?= (int)$r['id'] ?>" style="display:block;padding:0.75rem 1rem;border-bottom:1px solid var(--border);text-decoration:none">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem">
            <div style="flex:1;min-width:0">
                <p style="margin:0 0 0.2rem;font-weight:600;font-size:0.88rem;color:var(--primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= $e($r['expert_titre'] ?? $r['demande_titre'] ?? 'Réservation') ?></p>
                <p style="margin:0;font-size:0.75rem;color:var(--text-muted)"><?= !empty($r['expert_prenom']) ? $e($r['expert_prenom'] . ' ' . ($r['expert_nom'] ?? '')) : '' ?></p>
            </div>
            <span style="flex-shrink:0;font-size:0.7rem;font-weight:600;padding:0.15rem 0.55rem;border-radius:999px;background:<?= ($statut_cl[$r['statut']] ?? '#6b7280') ?>18;color:<?= $statut_cl[$r['statut']] ?? '#6b7280' ?>">
                <?= $statut_lb[$r['statut']] ?? $e($r['statut']) ?>
            </span>
        </div>
        <?php if (($r['statut'] ?? '') === 'acceptee'): ?>
        <span class="client-payment-inline client-payment-inline--compact" style="margin-top:0.45rem;display:inline-flex;width:100%;box-sizing:border-box" role="status">Paiement obligatoire pour démarrer</span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Parrainage -->
<?php if ($referralLink): ?>
<div style="background:var(--accent-soft);border:1px solid #bbf7d0;border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
    <h3 style="margin:0 0 0.65rem;font-size:0.88rem;font-weight:700;color:var(--accent)">🎁 Mon lien de parrainage</h3>
    <div style="display:flex;gap:0.5rem;align-items:center">
        <input type="text" readonly value="<?= $e($referralLink) ?>" id="referral-link-input"
               style="flex:1;padding:0.6rem 0.75rem;font-size:0.8rem;border:1px solid #bbf7d0;border-radius:8px;background:#fff;color:var(--text);min-width:0">
        <button type="button" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('referral-link-input').value);this.textContent='✓ Copié!';"
                class="btn-mobile btn-outline btn-sm" style="flex-shrink:0;border-color:var(--accent);color:var(--accent)">Copier</button>
    </div>
</div>
<?php endif; ?>
