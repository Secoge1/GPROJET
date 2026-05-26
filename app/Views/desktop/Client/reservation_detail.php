<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e       = fn($s) => \App\Core\Security::escape($s ?? '');
$r       = $reservation ?? [];
$statut  = $r['statut'] ?? '';

$statutMap = [
    'en_attente' => ['cl-badge--orange', 'En attente'],
    'acceptee'   => ['cl-badge--amber',  'Acceptée'],
    'payee'      => ['cl-badge--blue',   'Payée'],
    'en_cours'   => ['cl-badge--blue',   'En cours'],
    'terminee'   => ['cl-badge--green',  'Terminée'],
    'annulee'    => ['cl-badge--red',    'Annulée'],
    'refusee'    => ['cl-badge--red',    'Refusée'],
];
[$badgeCls, $badgeLbl] = $statutMap[$statut] ?? ['cl-badge--gray', ucfirst(str_replace('_', ' ', $statut))];

// Timeline steps
$steps = [
    ['key' => 'en_attente', 'label' => 'En attente'],
    ['key' => 'acceptee',   'label' => 'Acceptée'],
    ['key' => 'payee',      'label' => 'Payée'],
    ['key' => 'en_cours',   'label' => 'En cours'],
    ['key' => 'terminee',   'label' => 'Terminée'],
];
$stepOrder  = ['en_attente' => 0, 'acceptee' => 1, 'payee' => 2, 'en_cours' => 3, 'terminee' => 4];
$currentIdx = $stepOrder[$statut] ?? -1;
$isCancelled = in_array($statut, ['annulee', 'refusee']);
?>
<div class="cl-page">

    <!-- En-tête -->
    <div class="cl-page__hero cl-page__hero--narrow">
        <div class="cl-page__hero-left">
            <a href="<?= $baseUrl ?>/client/reservations" class="cl-back">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Mes réservations
            </a>
            <h1 class="cl-page__title">Réservation #<?= (int)$r['id'] ?></h1>
            <div style="display:flex;align-items:center;gap:.75rem;margin-top:.25rem">
                <span class="cl-badge <?= $badgeCls ?>"><?= $badgeLbl ?></span>
                <?php if (!empty($r['created_at'])): ?>
                <span class="cl-page__sub" style="margin:0">Créée le <?= date('d/m/Y', strtotime($r['created_at'])) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Flash -->
    <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="cl-alert cl-alert--error">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= $e($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="cl-alert cl-alert--success">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <?= $e($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Timeline (uniquement si pas annulée) -->
    <?php if (!$isCancelled): ?>
    <div class="cl-timeline-wrap">
        <div class="cl-timeline">
            <?php foreach ($steps as $i => $step): ?>
            <?php
            $isDone    = $i < $currentIdx;
            $isCurrent = $i === $currentIdx;
            $cls = $isDone ? 'cl-tl__step--done' : ($isCurrent ? 'cl-tl__step--current' : '');
            ?>
            <div class="cl-tl__step <?= $cls ?>">
                <div class="cl-tl__dot">
                    <?php if ($isDone): ?>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php elseif ($isCurrent): ?>
                    <div class="cl-tl__dot-inner"></div>
                    <?php endif; ?>
                </div>
                <span class="cl-tl__label"><?= $step['label'] ?></span>
            </div>
            <?php if ($i < count($steps) - 1): ?>
            <div class="cl-tl__line <?= $isDone ? 'cl-tl__line--done' : '' ?>"></div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="cl-detail-grid">

        <!-- Infos principales -->
        <div class="cl-card">
            <h2 class="cl-card__title" style="margin-bottom:1.25rem">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Détails
            </h2>
            <dl class="cl-detail-dl">
                <div class="cl-detail-dl__row">
                    <dt>Demande</dt>
                    <dd><?= $e($r['demande_titre'] ?? '—') ?></dd>
                </div>
                <div class="cl-detail-dl__row">
                    <dt>Expert</dt>
                    <dd><?= $e(trim(($r['expert_prenom'] ?? '') . ' ' . ($r['expert_nom'] ?? ''))) ?: '—' ?></dd>
                </div>
                <?php if (!empty($r['date_debut_prevue'])): ?>
                <div class="cl-detail-dl__row">
                    <dt>Date prévue</dt>
                    <dd><?= date('d/m/Y à H:i', strtotime($r['date_debut_prevue'])) ?></dd>
                </div>
                <?php endif; ?>
                <?php if (!empty($r['duree_heures'])): ?>
                <div class="cl-detail-dl__row">
                    <dt>Durée</dt>
                    <dd><?= (int)$r['duree_heures'] ?>h</dd>
                </div>
                <?php endif; ?>
                <div class="cl-detail-dl__row">
                    <dt>Montant</dt>
                    <dd class="cl-detail-dl__amount"><?= number_format((float)($r['montant_total'] ?? 0), 0, ',', ' ') ?> <?= $e(devise()) ?></dd>
                </div>
            </dl>
        </div>

        <!-- Actions -->
        <div class="cl-card">
            <h2 class="cl-card__title" style="margin-bottom:1.25rem">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                Actions
            </h2>
            <div class="cl-action-list">
                <?php if ($statut === 'acceptee'): ?>
                <a href="<?= $baseUrl ?>/client/payer/<?= (int)$r['id'] ?>" class="cl-action-btn cl-action-btn--amber">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    <div>
                        <strong>Payer la réservation</strong>
                        <span>Procéder au règlement via Mobile Money</span>
                    </div>
                </a>
                <?php endif; ?>

                <?php if (in_array($statut, ['en_cours', 'payee'])): ?>
                <a href="<?= $baseUrl ?>/messages/conversation/<?= (int)$r['id'] ?>" class="cl-action-btn cl-action-btn--blue">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    <div>
                        <strong>Messagerie</strong>
                        <span>Contacter l'expert</span>
                    </div>
                </a>
                <?php endif; ?>

                <?php
                $can_confirm_demande = $can_confirm_demande ?? false;
                $demande = $demande ?? null;
                $client_base_path = $client_base_path ?? '/client';
                if (!empty($can_confirm_demande)) {
                    require APP_PATH . '/Views/partials/demande_cloture_client.php';
                }
                ?>

                <?php if ($statut === 'terminee'): ?>
                <?php
                $avisModel      = new \App\Models\AvisModel();
                $avisClientModel = new \App\Models\AvisClientModel();
                $dejaNote       = $avisModel->existsForReservation((int)$r['id']);
                $avisExpertSurMoi = $avisClientModel->getByReservation((int)$r['id']);
                ?>
                <?php if ($avisExpertSurMoi): ?>
                <div class="cl-avis-received">
                    <div class="cl-avis-received__head">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        Avis de l'expert sur vous
                    </div>
                    <div class="cl-avis-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="cl-star <?= $i <= (int)$avisExpertSurMoi['note'] ? 'cl-star--on' : '' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <?php if (!empty(trim($avisExpertSurMoi['commentaire'] ?? ''))): ?>
                    <p class="cl-avis-received__comment">«&nbsp;<?= $e($avisExpertSurMoi['commentaire']) ?>&nbsp;»</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if (!$dejaNote): ?>
                <a href="<?= $baseUrl ?>/client/noter/<?= (int)$r['id'] ?>" class="cl-action-btn cl-action-btn--green">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <div>
                        <strong>Noter l'expert</strong>
                        <span>Partagez votre expérience</span>
                    </div>
                </a>
                <?php else: ?>
                <div class="cl-action-btn cl-action-btn--done">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <div>
                        <strong>Avis envoyé</strong>
                        <span>Merci pour votre retour</span>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (in_array($statut, ['en_attente', 'acceptee', 'payee', 'en_cours', 'terminee'])): ?>
                <a href="<?= $baseUrl ?>/client/reservations" class="cl-action-btn cl-action-btn--ghost">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                    <div>
                        <strong>Retour à la liste</strong>
                        <span>Voir toutes mes réservations</span>
                    </div>
                </a>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- ── Livraisons de l'expert ── -->
    <?php
    $livraisons = $livraisons ?? [];
    function cl_ext_icon(string $ext): string {
        if (in_array($ext, ['doc','docx','odt','rtf'], true))  return '📄';
        if (in_array($ext, ['xls','xlsx','csv','ods'], true))  return '📊';
        if (in_array($ext, ['ppt','pptx','odp'], true))        return '📑';
        if (in_array($ext, ['mdb','accdb'], true))             return '🗄️';
        if ($ext === 'pdf')                                    return '📕';
        if (in_array($ext, ['zip','rar'], true))               return '🗜️';
        return '📎';
    }
    ?>
    <div class="cl-card cl-livraisons" style="margin-top:1.25rem;grid-column:1/-1;">
        <h2 class="cl-card__title" style="margin-bottom:1.25rem">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Fichiers livrés par l'expert
            <?php if (!empty($livraisons)): ?>
            <span style="background:#dcfce7;color:#16a34a;border-radius:20px;font-size:.72rem;font-weight:700;padding:.1rem .55rem;margin-left:.35rem;"><?= count($livraisons) ?></span>
            <?php endif; ?>
        </h2>

        <?php if (empty($livraisons)): ?>
        <div style="text-align:center;padding:1.5rem;color:#94a3b8;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#e2e8f0" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <p style="margin:.5rem 0 0;font-size:.875rem;">Aucun fichier livré pour cette mission.</p>
            <p style="font-size:.78rem;margin:.25rem 0 0;">L'expert déposera ici les documents une fois la mission réalisée.</p>
        </div>
        <?php else: ?>
        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.875rem;">
            <?php foreach ($livraisons as $lv): ?>
            <?php
                $ext  = strtolower(pathinfo($lv['nom_fichier'] ?? '', PATHINFO_EXTENSION));
                $icon = $lv['type'] === 'video' ? '🎬' : cl_ext_icon($ext);
                $date = !empty($lv['created_at']) ? date('d/m/Y à H:i', strtotime($lv['created_at'])) : '';
                $expertNom = trim(($lv['expert_prenom'] ?? '') . ' ' . ($lv['expert_nom'] ?? '')) ?: 'L\'expert';
            ?>
            <li style="display:flex;align-items:flex-start;gap:.875rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.875rem 1rem;">
                <div style="font-size:1.5rem;flex-shrink:0;"><?= $icon ?></div>
                <div style="flex:1;min-width:0;">
                    <?php if ($lv['type'] === 'video'): ?>
                    <a href="<?= $e($lv['lien_externe'] ?? '#') ?>" target="_blank" rel="noopener"
                       style="display:inline-flex;align-items:center;gap:.35rem;font-weight:700;color:#1d4ed8;font-size:.9rem;text-decoration:none;word-break:break-all;">
                        Vidéo / Lien externe
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    </a>
                    <p style="font-size:.75rem;color:#94a3b8;margin:.2rem 0 0;">Ouvre dans un nouvel onglet — lien externe <?= $e($expertNom) ?></p>
                    <?php else: ?>
                    <a href="<?= $baseUrl ?>/fichier/livraison/<?= (int)$lv['id'] ?>"
                       style="display:inline-flex;align-items:center;gap:.35rem;font-weight:700;color:#1d4ed8;font-size:.9rem;text-decoration:none;word-break:break-all;"
                       download>
                        <?= $e($lv['nom_fichier'] ?? 'Fichier') ?>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </a>
                    <?php if (!empty($lv['taille'])): ?>
                    <span style="font-size:.72rem;color:#94a3b8;margin-left:.4rem;"><?= number_format((float)$lv['taille'] / 1024, 0, ',', ' ') ?> Ko</span>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if (!empty($lv['commentaire'])): ?>
                    <p style="font-size:.8rem;color:#475569;margin:.3rem 0 0;font-style:italic;">«&nbsp;<?= $e(mb_substr($lv['commentaire'], 0, 200)) ?>&nbsp;»</p>
                    <?php endif; ?>
                    <?php if ($date): ?>
                    <span style="font-size:.72rem;color:#94a3b8;display:block;margin-top:.2rem;">Livré le <?= $e($date) ?></span>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

</div>

