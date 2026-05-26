<?php
$baseUrl     = rtrim(BASE_URL ?? '', '/');
$e           = fn($s) => \App\Core\Security::escape($s ?? '');
$demande     = $demande ?? [];
$reservation = $reservation ?? null;

$statut_lb = ['ouverte'=>'Ouverte','en_cours'=>'En cours','terminee'=>'Terminée','annulee'=>'Annulée'];
$statut_cl = ['ouverte'=>'#16a34a','en_cours'=>'#2563eb','terminee'=>'#6b7280','annulee'=>'#dc2626'];
$statut_bg = ['ouverte'=>'#dcfce7','en_cours'=>'#dbeafe','terminee'=>'#f1f5f9','annulee'=>'#fee2e2'];
$urgence_lb = ['normale'=>'Normale','urgent'=>'Urgent','tres_urgent'=>'Très urgent'];
$res_statut_lb = ['en_attente'=>'En attente','confirme'=>'Confirmée','annule'=>'Annulée','termine'=>'Terminée','paye'=>'Payée'];
$res_statut_cl = ['en_attente'=>'#f59e0b','confirme'=>'#2563eb','annule'=>'#dc2626','termine'=>'#6b7280','paye'=>'#16a34a'];

$statut = $demande['statut'] ?? 'ouverte';
$urgence = $demande['urgence'] ?? 'normale';
$bp = $client_base_path ?? (isset($demandesListUrl) && strpos((string) $demandesListUrl, '/app/') !== false ? '/app' : '/client');
?>

<!-- En-tête -->
<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem">
    <?php $backUrl = $demandesListUrl ?? $baseUrl . '/app/demandes'; ?>
    <a href="<?= \App\Core\Security::escape($backUrl) ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none;flex-shrink:0" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <h1 style="margin:0;font-size:1.1rem;font-weight:800;color:var(--primary);flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">Détail demande</h1>
    <span style="flex-shrink:0;font-size:0.72rem;font-weight:700;padding:0.25rem 0.7rem;border-radius:999px;background:<?= $statut_bg[$statut] ?? '#f1f5f9' ?>;color:<?= $statut_cl[$statut] ?? '#6b7280' ?>">
        <?= $statut_lb[$statut] ?? $e($statut) ?>
    </span>
</div>

<!-- Fiche principale -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1.1rem;margin-bottom:1rem">
    <h2 style="margin:0 0 0.5rem;font-size:1rem;font-weight:700;color:var(--primary)"><?= $e($demande['titre'] ?? '') ?></h2>

    <!-- Méta -->
    <div style="display:flex;flex-wrap:wrap;gap:0.4rem;margin-bottom:0.85rem">
        <?php if (!empty($demande['competence_nom'])): ?>
        <span style="font-size:0.75rem;font-weight:600;padding:0.2rem 0.65rem;border-radius:999px;background:var(--accent-soft);color:var(--accent)">
            <?= $e($demande['competence_nom']) ?>
        </span>
        <?php endif; ?>
        <?php if (!empty($demande['duree_estimee_heures'])): ?>
        <span style="font-size:0.75rem;font-weight:500;padding:0.2rem 0.65rem;border-radius:999px;background:#f1f5f9;color:#475569">
            ⏱ <?= $demande['duree_estimee_heures'] ?>h estimée<?= $demande['duree_estimee_heures'] > 1 ? 's' : '' ?>
        </span>
        <?php endif; ?>
        <?php if ($urgence !== 'normale'): ?>
        <span style="font-size:0.75rem;font-weight:700;padding:0.2rem 0.65rem;border-radius:999px;background:<?= $urgence === 'tres_urgent' ? '#fee2e2' : '#fef9c3' ?>;color:<?= $urgence === 'tres_urgent' ? '#dc2626' : '#a16207' ?>">
            <?= $urgence === 'tres_urgent' ? '🔴 Très urgent' : '🟡 Urgent' ?>
        </span>
        <?php endif; ?>
    </div>

    <!-- Description -->
    <?php if (!empty($demande['description'])): ?>
    <p style="margin:0 0 0.75rem;font-size:0.88rem;color:var(--text);line-height:1.55">
        <?= nl2br($e($demande['description'])) ?>
    </p>
    <?php endif; ?>

    <!-- Lien vidéo -->
    <?php if (!empty($demande['lien_video'])): ?>
    <a href="<?= $e($demande['lien_video']) ?>" target="_blank" rel="noopener"
       style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.82rem;color:var(--accent);text-decoration:none;font-weight:600">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
        Voir la vidéo jointe
    </a>
    <?php endif; ?>

    <!-- Pièce jointe -->
    <?php if (!empty($demande['fichier_joint'])): ?>
    <a href="<?= $baseUrl ?>/uploads/<?= $e($demande['fichier_joint']) ?>" target="_blank" rel="noopener"
       style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.82rem;color:var(--accent);text-decoration:none;font-weight:600;margin-top:0.4rem">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
        Pièce jointe
    </a>
    <?php endif; ?>

    <p style="margin:0.85rem 0 0;font-size:0.72rem;color:var(--text-muted)">
        Créée le <?= !empty($demande['created_at']) ? date('d/m/Y à H:i', strtotime($demande['created_at'])) : '—' ?>
    </p>
</div>

<?php
$demande_recommendations = $demande_recommendations ?? null;
$demande_welcome_hint    = $demande_welcome_hint ?? false;
require APP_PATH . '/Views/partials/demande_recommendations.php';
?>

<?php
$propositions = $propositions ?? [];
$can_choose = !empty($can_choose_proposition);
$client_base_path = $client_base_path ?? '/client';
require APP_PATH . '/Views/partials/demande_propositions_list.php';
require APP_PATH . '/Views/partials/demande_cloture_client.php';
?>

<!-- Réservation liée -->
<?php if ($reservation): ?>
<?php
    $rStatut = $reservation['statut'] ?? 'en_attente';
    $rColor  = $res_statut_cl[$rStatut] ?? '#6b7280';
?>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:1rem">
    <div style="padding:0.75rem 1rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <p style="margin:0;font-size:0.82rem;font-weight:700;color:var(--primary)">Réservation associée</p>
        <span style="font-size:0.72rem;font-weight:700;padding:0.2rem 0.6rem;border-radius:999px;background:<?= $rColor ?>18;color:<?= $rColor ?>">
            <?= $res_statut_lb[$rStatut] ?? $e($rStatut) ?>
        </span>
    </div>
    <div style="padding:0.85rem 1rem">
        <?php if (!empty($reservation['date_session'])): ?>
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/></svg>
            <span style="font-size:0.85rem;color:var(--text)">
                <?= date('d/m/Y à H:i', strtotime($reservation['date_session'])) ?>
            </span>
        </div>
        <?php endif; ?>
        <?php if (!empty($reservation['montant'])): ?>
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            <span style="font-size:0.85rem;font-weight:700;color:var(--primary)">
                <?= number_format((float)$reservation['montant'], 0, ',', ' ') ?> <?= $e(devise()) ?>
            </span>
        </div>
        <?php endif; ?>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.75rem">
            <a href="<?= $baseUrl ?><?= \App\Core\Security::escape($bp) ?>/reservations/<?= (int)$reservation['id'] ?>" class="btn-mobile btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:0.3rem">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                Voir la réservation
            </a>
            <?php if ($rStatut === 'confirme' && !empty($reservation['id'])): ?>
            <a href="<?= $baseUrl ?>/session/room/<?= (int)$reservation['id'] ?>" class="btn-mobile btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:0.3rem">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"/><rect x="1" y="5" width="15" height="14" rx="2"/></svg>
                Rejoindre la session
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Actions selon statut -->
<?php if ($statut === 'ouverte' && !$reservation): ?>
<div style="display:flex;flex-direction:column;gap:0.6rem">
    <a href="<?= $baseUrl ?><?= \App\Core\Security::escape($bp) ?>/reserver/<?= (int)$demande['id'] ?>" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Réserver un expert
    </a>
    <a href="<?= $baseUrl ?>/experts?demande_id=<?= (int)$demande['id'] ?>" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center;gap:0.5rem">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Parcourir les experts
    </a>
</div>
<?php elseif ($statut === 'en_cours' && !$reservation): ?>
<a href="<?= $baseUrl ?><?= \App\Core\Security::escape($bp) ?>/reservations" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center;gap:0.5rem">
    Voir mes réservations
</a>
<?php endif; ?>
