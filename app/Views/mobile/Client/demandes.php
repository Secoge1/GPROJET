<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$e        = fn($s) => \App\Core\Security::escape($s ?? '');
$demandes = $demandes ?? [];

$statut_lb = ['ouverte'=>'Ouverte','en_cours'=>'En cours','terminee'=>'Terminée','annulee'=>'Annulée'];
$statut_cl = ['ouverte'=>'#16a34a','en_cours'=>'#2563eb','terminee'=>'#6b7280','annulee'=>'#dc2626'];
$user       = $user ?? null;
$prenom     = trim((string)($user['prenom'] ?? ''));
$salutation = $prenom !== '' ? $e($prenom) : 'cher membre';

$bp = $client_base_path ?? '/client';
$isApp = ($bp === '/app');
$nouvelleUrl = $baseUrl . ($isApp ? '/app/nouvelle' : $bp . '/demandes/nouvelle');
$demandeDetailUrl = $baseUrl . $bp . '/demandes';
$reserverUrl = $baseUrl . $bp . '/reserver';
?>
<div class="mob-cl-demandes">

    <header class="mob-cl-demandes__header">
        <p class="mob-cl-demandes__greeting">Bonjour, <?= $salutation ?> 👋</p>
        <div class="mob-cl-demandes__title-row">
            <div class="mob-cl-demandes__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <h1 class="mob-cl-demandes__title">Mes demandes</h1>
            <a href="<?= $nouvelleUrl ?>" class="btn-mobile btn-primary btn-sm mob-cl-demandes__cta">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Nouvelle
            </a>
        </div>
    </header>

    <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="mobile-flash-success"><?= $e($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); endif; ?>

    <?php if (empty($demandes)): ?>
    <div class="mob-cl-demandes__empty">
        <div class="mob-cl-demandes__empty-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <p class="mob-cl-demandes__empty-title">Aucune demande pour le moment</p>
        <p class="mob-cl-demandes__empty-desc">Créez votre première demande et trouvez l'expert idéal.</p>
        <a href="<?= $nouvelleUrl ?>" class="btn-mobile btn-primary mob-cl-demandes__empty-btn">Créer une demande</a>
    </div>
    <?php else: ?>
    <div class="mob-cl-demandes__summary">
        <strong><?= count($demandes) ?></strong> demande<?= count($demandes) > 1 ? 's' : '' ?>
        <?php $ouvertes = array_filter($demandes, fn($d) => ($d['statut'] ?? '') === 'ouverte'); ?>
        <?php if (count($ouvertes) > 0): ?>
        <span class="mob-cl-demandes__summary-dot">·</span>
        <span class="mob-cl-demandes__summary-open"><?= count($ouvertes) ?> ouverte<?= count($ouvertes) > 1 ? 's' : '' ?></span>
        <?php endif; ?>
    </div>
    <ul class="mob-cl-demandes__list">
        <?php foreach ($demandes as $d):
            $statutDemande = isset($d['statut']) ? strtolower(trim((string)$d['statut'])) : '';
            $isOuverte = ($statutDemande === 'ouverte');
            $showDetail = in_array($statutDemande, ['en_cours', 'ouverte'], true);
            $statutColor = $statut_cl[$d['statut']] ?? '#6b7280';
        ?>
        <li class="mob-cl-demandes__card">
            <div class="mob-cl-demandes__card-head">
                <div class="mob-cl-demandes__card-title-wrap">
                    <h2 class="mob-cl-demandes__card-title"><?= $e($d['titre']) ?></h2>
                    <p class="mob-cl-demandes__card-meta">
                        <?= !empty($d['competence_nom']) ? $e($d['competence_nom']) . ' · ' : '' ?><?= !empty($d['duree_estimee_heures']) ? (int)$d['duree_estimee_heures'] . 'h' : '' ?>
                    </p>
                </div>
                <div class="mob-cl-demandes__card-badges">
                    <span class="mob-cl-demandes__badge" style="background:<?= $statutColor ?>18;color:<?= $statutColor ?>"><?= $statut_lb[$d['statut']] ?? $e($d['statut']) ?></span>
                    <?php if (($d['urgence'] ?? 'normale') !== 'normale'): ?>
                    <span class="mob-cl-demandes__urgence <?= ($d['urgence'] ?? '') === 'tres_urgent' ? 'mob-cl-demandes__urgence--high' : '' ?>">
                        <?= ($d['urgence'] ?? '') === 'tres_urgent' ? 'Très urgent' : 'Urgent' ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($d['description'])): ?>
            <p class="mob-cl-demandes__card-desc"><?= $e(mb_substr($d['description'], 0, 100)) ?><?= mb_strlen($d['description']) > 100 ? '…' : '' ?></p>
            <?php endif; ?>
            <div class="mob-cl-demandes__card-actions">
                <?php if ($isOuverte): ?>
                <a href="<?= $reserverUrl ?>/<?= (int)($d['id'] ?? 0) ?>" class="btn-mobile btn-primary btn-sm">Réserver un expert</a>
                <?php endif; ?>
                <?php if ($showDetail): ?>
                <a href="<?= $demandeDetailUrl ?>/<?= (int)($d['id'] ?? 0) ?>" class="btn-mobile btn-outline btn-sm">Détail</a>
                <?php endif; ?>
            </div>
            <?php if (!empty($d['created_at'])): ?>
            <p class="mob-cl-demandes__card-date">Créée le <?= date('d/m/Y', strtotime($d['created_at'])) ?></p>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>

</div>
