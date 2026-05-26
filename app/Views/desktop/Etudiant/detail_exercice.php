<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$e        = fn($s) => \App\Core\Security::escape($s ?? '');
$ex       = $exercice ?? [];

$statuts = ['ouvert'=>'Ouvert','en_cours'=>'En cours','correction_livree'=>'À valider','resolu'=>'Résolu','annule'=>'Annulé'];
$types   = ['devoir'=>'Devoir','examen'=>'Examen','tp'=>'TP/TD','projet'=>'Projet','dissertation'=>'Dissertation','qcm'=>'QCM','oral'=>'Oral','autre'=>'Autre'];
$diffs   = ['facile'=>'Facile','moyen'=>'Moyen','difficile'=>'Difficile','tres_difficile'=>'Très difficile'];
$urgences= ['normale'=>'Normale','urgent'=>'Urgent','tres_urgent'=>'Très urgent'];
?>
<div class="etd-page">
    <div class="etd-page__header">
        <div>
            <a href="<?= $baseUrl ?>/etudiant/exercices<?= !empty($ex['matiere_id']) ? '?matiere=' . (int)$ex['matiere_id'] : '' ?>" class="etd-back">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                Retour aux exercices
            </a>
            <h1 class="etd-page__title"><?= $e($ex['titre'] ?? '') ?></h1>
        </div>
        <div class="etd-detail-badges">
            <?php if (!empty($ex['matiere_nom'])): ?>
            <span class="etd-matiere-tag etd-matiere-tag--lg"><?= $e($ex['matiere_nom']) ?></span>
            <?php endif; ?>
            <?php
            $statut = $ex['statut'] ?? 'ouvert';
            $statClass = ['ouvert'=>'etd-badge--green','en_cours'=>'etd-badge--blue','correction_livree'=>'etd-badge--amber','resolu'=>'etd-badge--gray','annule'=>'etd-badge--red'][$statut] ?? 'etd-badge--gray';
            ?>
            <span class="etd-badge <?= $statClass ?> etd-badge--lg"><?= $e($statuts[$statut] ?? ucfirst($statut)) ?></span>
        </div>
    </div>

    <div class="etd-detail-grid">

        <!-- Contenu principal -->
        <div class="etd-detail-main">
            <div class="etd-card">
                <h2 class="etd-card__section-title">Énoncé</h2>
                <div class="etd-description">
                    <?= nl2br($e($ex['description'] ?? '')) ?>
                </div>

                <?php if (!empty($ex['fichier'])): ?>
                <div class="etd-fichier">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    <a href="<?= $baseUrl ?>/uploads/<?= $e($ex['fichier']) ?>" target="_blank" rel="noopener">
                        Télécharger la pièce jointe
                    </a>
                </div>
                <?php endif; ?>

                <?php if (!empty($ex['lien_ressource'])): ?>
                <div class="etd-fichier">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    <a href="<?= $e($ex['lien_ressource']) ?>" target="_blank" rel="noopener noreferrer">
                        Ouvrir la ressource externe
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php
            $propositions = $propositions ?? [];
            $can_choose = !empty($can_choose_proposition);
            $base_path = $base_path ?? '/etudiant';
            require APP_PATH . '/Views/partials/exercice_propositions_list.php';
            ?>

            <?php
            $paiementStatut = $ex['paiement_statut'] ?? 'non_requis';
            $prixCorrection = (float) ($ex['prix_correction'] ?? 0);
            $soldeWallet    = (float) ($solde_wallet ?? 0);
            $csrfField      = \App\Core\Security::getCsrfField();
            ?>

            <?php
            $correctionPhase = in_array(($ex['statut'] ?? ''), ['resolu', 'correction_livree'], true);
            ?>
            <?php if ($correctionPhase): ?>

                <?php if ($paiementStatut === 'paye' || $paiementStatut === 'non_requis'): ?>
                <!-- Correction accessible -->
                <div class="etd-card etd-card--solution">
                    <h2 class="etd-card__section-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        Solution / Correction
                    </h2>
                    <?php if (!empty($ex['solution'])): ?>
                    <div class="etd-solution"><?= nl2br($e($ex['solution'])) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($ex['commentaire_expert'])): ?>
                    <div class="etd-commentaire-expert">
                        <strong>Commentaire :</strong> <?= nl2br($e($ex['commentaire_expert'])) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($ex['note_finale'])): ?>
                    <div class="etd-note-finale">
                        Note finale : <strong class="etd-note-value"><?= number_format((float)$ex['note_finale'], 1) ?>/20</strong>
                    </div>
                    <?php endif; ?>
                </div>
                    <?php
                    $can_confirm_exercice = $can_confirm_exercice ?? false;
                    $base_path = $base_path ?? '/etudiant';
                    require APP_PATH . '/Views/partials/exercice_cloture_etudiant.php';
                    ?>

                <?php elseif ($paiementStatut === 'en_attente'): ?>
                <!-- Correction verrouillée -->
                <div class="etd-card" style="border:2px dashed #fbbf24;background:#fffbeb;">
                    <div style="text-align:center;padding:1.5rem 1rem;">
                        <div style="font-size:2.5rem;margin-bottom:.5rem">🔒</div>
                        <h2 style="color:#92400e;font-size:1.1rem;margin:0 0 .5rem">Correction disponible</h2>
                        <p style="color:#78350f;font-size:.9rem;margin:0 0 1.25rem;line-height:1.5">
                            Votre exercice a été corrigé par un professeur.<br>
                            Payez <strong><?= number_format($prixCorrection, 0, ',', ' ') ?> XOF</strong> pour accéder à la correction complète.
                        </p>
                        <?php if ($soldeWallet >= $prixCorrection): ?>
                        <p style="font-size:.82rem;color:#6b7280;margin:0 0 1rem">
                            Solde portefeuille : <strong style="color:#16a34a"><?= number_format($soldeWallet, 0, ',', ' ') ?> XOF</strong>
                        </p>
                        <form method="post" action="<?= $baseUrl ?>/etudiant/payer-correction/<?= (int)$ex['id'] ?>" style="display:inline">
                            <?= $csrfField ?>
                            <input type="hidden" name="exercice_id" value="<?= (int)$ex['id'] ?>">
                            <button type="submit" class="etd-btn etd-btn--primary" style="background:#16a34a;padding:.75rem 2rem;font-size:.95rem">
                                💳 Débloquer (<?= number_format($prixCorrection, 0, ',', ' ') ?> XOF)
                            </button>
                        </form>
                        <?php else: ?>
                        <p style="font-size:.82rem;color:#dc2626;margin:0 0 1rem">
                            Solde insuffisant : <strong><?= number_format($soldeWallet, 0, ',', ' ') ?> XOF</strong>
                            — il vous manque <?= number_format($prixCorrection - $soldeWallet, 0, ',', ' ') ?> XOF
                        </p>
                        <a href="<?= $baseUrl ?>/etudiant/portefeuille" class="etd-btn etd-btn--primary" style="background:#0284c7">
                            Recharger mon portefeuille
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            <?php elseif (($ex['statut'] ?? '') === 'en_cours'): ?>
            <div class="etd-card" style="background:#eff6ff;border:1px solid #bfdbfe;text-align:center;padding:1.5rem">
                <div style="font-size:2rem;margin-bottom:.4rem">⏳</div>
                <p style="margin:0;font-size:.95rem;font-weight:600;color:#1d4ed8">Correction en cours de rédaction…</p>
                <p style="margin:.25rem 0 0;font-size:.83rem;color:#6b7280">Un professeur traite votre exercice.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar détail -->
        <aside class="etd-detail-side">
            <div class="etd-card etd-card--info">
                <h3 class="etd-card__section-title">Informations</h3>
                <ul class="etd-info-list">
                    <li>
                        <span class="etd-info-label">Type</span>
                        <span class="etd-info-val"><?= $e($types[$ex['type_exercice'] ?? ''] ?? ucfirst($ex['type_exercice'] ?? '')) ?></span>
                    </li>
                    <li>
                        <span class="etd-info-label">Difficulté</span>
                        <span class="etd-info-val"><?= $e($diffs[$ex['niveau_difficulte'] ?? ''] ?? ucfirst($ex['niveau_difficulte'] ?? '')) ?></span>
                    </li>
                    <li>
                        <span class="etd-info-label">Urgence</span>
                        <span class="etd-info-val"><?= $e($urgences[$ex['urgence'] ?? ''] ?? ucfirst($ex['urgence'] ?? '')) ?></span>
                    </li>
                    <?php if (!empty($ex['date_limite'])): ?>
                    <li>
                        <span class="etd-info-label">Deadline</span>
                        <span class="etd-info-val etd-deadline"><?= date('d/m/Y H:i', strtotime($ex['date_limite'])) ?></span>
                    </li>
                    <?php endif; ?>
                    <li>
                        <span class="etd-info-label">Soumis le</span>
                        <span class="etd-info-val"><?= date('d/m/Y', strtotime($ex['created_at'] ?? 'now')) ?></span>
                    </li>
                    <?php if (!empty($ex['matiere_filiere'])): ?>
                    <li>
                        <span class="etd-info-label">Filière</span>
                        <span class="etd-info-val"><?= $e($ex['matiere_filiere']) ?></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>

            <a href="<?= $baseUrl ?>/etudiant/exercices/nouveau" class="etd-btn etd-btn--primary etd-btn--block">
                Soumettre un autre exercice
            </a>
        </aside>
    </div>
</div>
