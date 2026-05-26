<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e       = fn($s) => \App\Core\Security::escape($s ?? '');
$ex      = $exercice ?? [];

$statutLabel = ['ouvert' => 'Ouvert', 'en_cours' => 'En cours', 'correction_livree' => 'À valider', 'resolu' => 'Résolu', 'annule' => 'Annulé'];
$statutColor = ['ouvert' => '#16a34a', 'en_cours' => '#2563eb', 'correction_livree' => '#d97706', 'resolu' => '#6b7280', 'annule' => '#dc2626'];
$typeLabel   = ['devoir' => 'Devoir', 'examen' => 'Examen', 'tp' => 'TP/TD', 'projet' => 'Projet',
                'dissertation' => 'Dissertation', 'qcm' => 'QCM', 'oral' => 'Oral', 'autre' => 'Autre'];
$diffLabel   = ['facile' => 'Facile', 'moyen' => 'Moyen', 'difficile' => 'Difficile', 'tres_difficile' => 'Très difficile'];
$urgenceLabel= ['normale' => 'Normale', 'urgent' => 'Urgent', 'tres_urgent' => 'Très urgent'];

$statut     = $ex['statut'] ?? 'ouvert';
$statColor  = $statutColor[$statut] ?? '#6b7280';
?>

<!-- En-tête -->
<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem">
    <a href="<?= $baseUrl ?>/etudiant/exercices<?= !empty($ex['matiere_id']) ? '?matiere=' . (int)$ex['matiere_id'] : '' ?>"
       style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none;flex-shrink:0" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <div style="flex:1;min-width:0">
        <h1 style="margin:0;font-size:1.05rem;font-weight:700;color:var(--primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= $e($ex['titre'] ?? '') ?></h1>
        <div style="display:flex;gap:0.4rem;flex-wrap:wrap;margin-top:0.35rem">
            <?php if (!empty($ex['matiere_nom'])): ?>
            <span style="font-size:0.72rem;font-weight:600;padding:0.15rem 0.55rem;border-radius:999px;background:var(--accent-soft);color:var(--accent)"><?= $e($ex['matiere_nom']) ?></span>
            <?php endif; ?>
            <span style="font-size:0.72rem;font-weight:600;padding:0.15rem 0.55rem;border-radius:999px;background:<?= $statColor ?>22;color:<?= $statColor ?>">
                <?= $statutLabel[$statut] ?? ucfirst($statut) ?>
            </span>
        </div>
    </div>
</div>

<!-- Infos rapides -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;margin-bottom:1.25rem">
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:0.75rem">
        <p style="margin:0 0 0.15rem;font-size:0.72rem;color:var(--text-muted);font-weight:500">Type</p>
        <p style="margin:0;font-size:0.85rem;font-weight:600;color:var(--primary)"><?= $e($typeLabel[$ex['type_exercice'] ?? ''] ?? '') ?></p>
    </div>
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:0.75rem">
        <p style="margin:0 0 0.15rem;font-size:0.72rem;color:var(--text-muted);font-weight:500">Difficulté</p>
        <p style="margin:0;font-size:0.85rem;font-weight:600;color:var(--primary)"><?= $e($diffLabel[$ex['niveau_difficulte'] ?? 'moyen'] ?? '') ?></p>
    </div>
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:0.75rem">
        <p style="margin:0 0 0.15rem;font-size:0.72rem;color:var(--text-muted);font-weight:500">Urgence</p>
        <p style="margin:0;font-size:0.85rem;font-weight:600;color:var(--primary)"><?= $e($urgenceLabel[$ex['urgence'] ?? 'normale'] ?? '') ?></p>
    </div>
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:0.75rem">
        <p style="margin:0 0 0.15rem;font-size:0.72rem;color:var(--text-muted);font-weight:500">Soumis le</p>
        <p style="margin:0;font-size:0.85rem;font-weight:600;color:var(--primary)"><?= date('d/m/Y', strtotime($ex['created_at'] ?? 'now')) ?></p>
    </div>
    <?php if (!empty($ex['date_limite'])): ?>
    <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:var(--radius);padding:0.75rem;grid-column:span 2">
        <p style="margin:0 0 0.15rem;font-size:0.72rem;color:#92400e;font-weight:500">⏰ Date limite</p>
        <p style="margin:0;font-size:0.9rem;font-weight:700;color:#92400e"><?= date('d/m/Y à H:i', strtotime($ex['date_limite'])) ?></p>
    </div>
    <?php endif; ?>
</div>

<!-- Énoncé -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
    <h2 style="margin:0 0 0.75rem;font-size:0.9rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.04em">Énoncé</h2>
    <div style="font-size:0.9rem;color:var(--text);line-height:1.6;white-space:pre-wrap"><?= $e($ex['description'] ?? '') ?></div>

    <?php if (!empty($ex['fichier'])): ?>
    <a href="<?= $baseUrl ?>/uploads/<?= $e($ex['fichier']) ?>" target="_blank" rel="noopener"
       class="btn-mobile btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:0.4rem;margin-top:1rem">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
        Pièce jointe
    </a>
    <?php endif; ?>

    <?php if (!empty($ex['lien_ressource'])): ?>
    <a href="<?= $e($ex['lien_ressource']) ?>" target="_blank" rel="noopener noreferrer"
       class="btn-mobile btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:0.4rem;margin-top:0.5rem">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        Ressource externe
    </a>
    <?php endif; ?>
</div>

<?php if (($ex['statut'] ?? '') === 'ouvert' && empty($ex['expert_id'])):
    $propositions = $propositions ?? [];
    $can_choose = !empty($can_choose_proposition);
    $base_path = $base_path ?? '/etudiant';
    require APP_PATH . '/Views/partials/exercice_propositions_list.php';
endif; ?>

<?php
$paiementStatut = $ex['paiement_statut'] ?? 'non_requis';
$prixCorrection = (float) ($ex['prix_correction'] ?? 0);
$soldeWallet    = (float) ($solde_wallet ?? 0);
$csrfField      = \App\Core\Security::getCsrfField();
?>

<!-- Section correction / paiement -->
<?php
$correctionPhase = in_array(($ex['statut'] ?? ''), ['resolu', 'correction_livree'], true);
?>
<?php if ($correctionPhase): ?>

    <?php if ($paiementStatut === 'paye' || $paiementStatut === 'non_requis'): ?>
    <!-- Correction accessible -->
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
        <h2 style="margin:0 0 0.75rem;font-size:0.9rem;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:0.04em">
            ✅ Solution / Correction
        </h2>
        <?php if (!empty($ex['solution'])): ?>
        <div style="font-size:0.9rem;color:var(--text);line-height:1.6;white-space:pre-wrap;margin-bottom:0.75rem"><?= $e($ex['solution']) ?></div>
        <?php endif; ?>
        <?php if (!empty($ex['commentaire_expert'])): ?>
        <div style="border-top:1px solid #bbf7d0;padding-top:0.75rem;font-size:0.85rem;color:var(--text);line-height:1.5">
            <strong>Commentaire :</strong> <?= nl2br($e($ex['commentaire_expert'])) ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($ex['note_finale'])): ?>
        <div style="margin-top:0.75rem;padding:0.75rem;background:#fff;border-radius:calc(var(--radius) - 2px);text-align:center">
            <span style="font-size:0.8rem;color:var(--text-muted)">Note finale</span>
            <p style="margin:0.25rem 0 0;font-size:1.5rem;font-weight:800;color:#16a34a"><?= number_format((float)$ex['note_finale'], 1) ?><span style="font-size:0.9rem;color:var(--text-muted)">/20</span></p>
        </div>
        <?php endif; ?>
    </div>
        <?php
        $can_confirm_exercice = $can_confirm_exercice ?? false;
        $base_path = $base_path ?? '/etudiant';
        require APP_PATH . '/Views/partials/exercice_cloture_etudiant.php';
        ?>

    <?php elseif ($paiementStatut === 'en_attente'): ?>
    <!-- Correction verrouillée : bouton paiement par portefeuille -->
    <div style="background:#fffbeb;border:1.5px solid #fde68a;border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
        <div style="text-align:center;margin-bottom:0.85rem">
            <div style="font-size:2rem">🔒</div>
            <h2 style="margin:0.4rem 0 0.25rem;font-size:0.95rem;font-weight:700;color:#92400e">Correction disponible</h2>
            <p style="margin:0;font-size:0.82rem;color:#92400e;line-height:1.5">
                Votre exercice a été corrigé par un professeur.<br>
                Payez <strong><?= number_format($prixCorrection, 0, ',', ' ') ?> XOF</strong> pour accéder à la correction.
            </p>
        </div>

        <?php if ($soldeWallet >= $prixCorrection): ?>
        <!-- Solde suffisant -->
        <p style="font-size:0.78rem;color:#6b7280;text-align:center;margin:0 0 0.75rem">
            Solde portefeuille : <strong style="color:#16a34a"><?= number_format($soldeWallet, 0, ',', ' ') ?> XOF</strong>
        </p>
        <form method="post" action="<?= $baseUrl ?>/etudiant/payer-correction/<?= (int)$ex['id'] ?>">
            <?= $csrfField ?>
            <input type="hidden" name="exercice_id" value="<?= (int)$ex['id'] ?>">
            <button type="submit"
                    style="display:flex;align-items:center;justify-content:center;gap:0.5rem;width:100%;padding:0.85rem;background:#16a34a;color:#fff;font-size:0.95rem;font-weight:700;border:none;border-radius:var(--radius);cursor:pointer">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Débloquer (<?= number_format($prixCorrection, 0, ',', ' ') ?> XOF)
            </button>
        </form>
        <?php else: ?>
        <!-- Solde insuffisant -->
        <p style="font-size:0.78rem;color:#dc2626;text-align:center;margin:0 0 0.75rem">
            Solde insuffisant : <strong><?= number_format($soldeWallet, 0, ',', ' ') ?> XOF</strong>
            (manque <?= number_format($prixCorrection - $soldeWallet, 0, ',', ' ') ?> XOF)
        </p>
        <a href="<?= $baseUrl ?>/etudiant/portefeuille"
           style="display:flex;align-items:center;justify-content:center;gap:0.5rem;width:100%;padding:0.85rem;background:#0284c7;color:#fff;font-size:0.95rem;font-weight:700;border-radius:var(--radius);text-decoration:none;box-sizing:border-box">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 6l4 12L11 6l4 12 4-12"/></svg>
            Recharger via Mobile Money
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

<?php elseif (($ex['statut'] ?? '') === 'en_cours'): ?>
<!-- Correction en cours de rédaction -->
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:var(--radius);padding:1rem;margin-bottom:1rem;text-align:center">
    <div style="font-size:1.8rem;margin-bottom:0.35rem">⏳</div>
    <p style="margin:0;font-size:0.84rem;font-weight:600;color:#1d4ed8">Correction en cours de rédaction…</p>
    <p style="margin:0.3rem 0 0;font-size:0.78rem;color:#6b7280">Un professeur traite votre exercice.</p>
</div>
<?php endif; ?>

<!-- Actions -->
<a href="<?= $baseUrl ?>/etudiant/exercices/nouveau" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    Soumettre un autre exercice
</a>
