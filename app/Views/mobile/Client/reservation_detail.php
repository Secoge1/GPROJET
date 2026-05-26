<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e       = fn($s) => \App\Core\Security::escape($s ?? '');
$r       = $reservation ?? [];
$statut  = $r['statut'] ?? '';

$statut_lb = ['en_attente'=>'En attente','acceptee'=>'Acceptée','en_cours'=>'En cours','terminee'=>'Terminée','annulee'=>'Annulée','payee'=>'Payée'];
$statut_cl = ['en_attente'=>'#f59e0b','acceptee'=>'#2563eb','en_cours'=>'#16a34a','terminee'=>'#6b7280','annulee'=>'#dc2626','payee'=>'#7c3aed'];
$sc        = $statut_cl[$statut] ?? '#6b7280';
$bp        = $client_base_path ?? '/client';
?>

<!-- En-tête -->
<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem">
    <a href="<?= $baseUrl . $bp ?>/reservations" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none;flex-shrink:0" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <div>
        <h1 style="margin:0;font-size:1.05rem;font-weight:700;color:var(--primary)">Réservation #<?= (int)($r['id'] ?? 0) ?></h1>
        <span style="font-size:0.75rem;font-weight:600;padding:0.15rem 0.55rem;border-radius:999px;background:<?= $sc ?>18;color:<?= $sc ?>">
            <?= $statut_lb[$statut] ?? $e($statut) ?>
        </span>
    </div>
</div>

<?php if (!empty($_SESSION['flash_error'])): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem;font-size:0.85rem;color:#dc2626">
    <?= $e($_SESSION['flash_error']) ?>
</div>
<?php unset($_SESSION['flash_error']); endif; ?>

<?php if ($statut === 'acceptee'): ?>
<div class="client-payment-banner client-payment-banner--pulse client-payment-banner--sticky" role="alert">
    <div class="client-payment-banner__title">Paiement requis pour démarrer</div>
    <p class="client-payment-banner__text">Sans ce règlement depuis votre portefeuille, l’expert ne peut pas commencer la mission.</p>
    <a href="<?= $baseUrl . $bp ?>/payer/<?= (int)($r['id'] ?? 0) ?>" class="btn-mobile btn-primary client-payment-banner__btn">Payer maintenant</a>
</div>
<?php endif; ?>

<!-- Infos clés -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
    <div style="display:flex;flex-direction:column;gap:0.6rem">
        <?php if (!empty($r['demande_titre'])): ?>
        <div style="display:flex;justify-content:space-between;font-size:0.87rem">
            <span style="color:var(--text-muted)">Demande</span>
            <span style="font-weight:600;color:var(--primary);text-align:right;max-width:60%"><?= $e($r['demande_titre']) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($r['expert_prenom'])): ?>
        <div style="display:flex;justify-content:space-between;font-size:0.87rem">
            <span style="color:var(--text-muted)">Expert</span>
            <span style="font-weight:600;color:var(--primary)"><?= $e($r['expert_prenom'] . ' ' . ($r['expert_nom'] ?? '')) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($r['date_debut_prevue'])): ?>
        <div style="display:flex;justify-content:space-between;font-size:0.87rem">
            <span style="color:var(--text-muted)">Date prévue</span>
            <span style="font-weight:600;color:var(--primary)"><?= date('d/m/Y H:i', strtotime($r['date_debut_prevue'])) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($r['duree_heures'])): ?>
        <div style="display:flex;justify-content:space-between;font-size:0.87rem">
            <span style="color:var(--text-muted)">Durée</span>
            <span style="font-weight:600;color:var(--primary)"><?= $r['duree_heures'] ?> h</span>
        </div>
        <?php endif; ?>
        <?php if (!empty($r['montant_total'])): ?>
        <div style="display:flex;justify-content:space-between;font-size:0.87rem;padding-top:0.5rem;border-top:1px solid var(--border)">
            <span style="color:var(--text-muted)">Montant</span>
            <span style="font-weight:800;font-size:1rem;color:var(--primary)"><?= number_format((float)$r['montant_total'], 0, ',', ' ') ?> <?= $e(devise()) ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$can_confirm_demande = $can_confirm_demande ?? false;
$demande = $demande ?? null;
$client_base_path = $client_base_path ?? $bp;
$reservation = $r;
if (!empty($can_confirm_demande)) {
    require APP_PATH . '/Views/partials/demande_cloture_client.php';
}
?>

<!-- Actions contextuelles -->
<div style="display:flex;flex-direction:column;gap:0.65rem;margin-bottom:1rem">
    <?php if ($statut === 'acceptee'): ?>
    <a href="<?= $baseUrl . $bp ?>/payer/<?= (int)$r['id'] ?>" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;background:#7c3aed;border-color:#7c3aed;font-weight:800">
        💳 Payer la réservation
    </a>
    <?php endif; ?>

    <?php if (in_array($statut, ['en_cours', 'terminee', 'payee'])): ?>
    <a href="<?= $baseUrl ?>/messages/conversation/<?= (int)$r['id'] ?>" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center;gap:0.5rem">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Messagerie
    </a>
    <?php endif; ?>

    <?php if ($statut === 'terminee' || $statut === 'payee'): ?>
    <?php
    $avisModel = new \App\Models\AvisModel();
    $dejaNote  = $avisModel->existsForReservation((int)$r['id']);
    ?>
    <?php if (!$dejaNote): ?>
    <a href="<?= $baseUrl . $bp ?>/noter/<?= (int)$r['id'] ?>" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center;gap:0.5rem">
        ⭐ Noter l'expert
    </a>
    <?php else: ?>
    <div style="text-align:center;font-size:0.82rem;color:var(--accent);font-weight:600">✅ Vous avez déjà noté cet expert</div>
    <?php endif; ?>
    <?php endif; ?>

    <a href="<?= $baseUrl . $bp ?>/reservations" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center">← Retour à la liste</a>
</div>
