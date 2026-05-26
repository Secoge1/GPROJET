<?php
$baseUrl        = rtrim(BASE_URL ?? '', '/');
$e              = fn($s) => \App\Core\Security::escape($s ?? '');
$exercices      = $exercices ?? [];
$matieres       = $matieres ?? [];
$matiere_id     = (int)($matiere_id ?? 0);
$matiereCourante = $matiere_courante ?? null;

$typeLabel = [
    'devoir'=>'Devoir','examen'=>'Examen','tp'=>'TP','projet'=>'Projet',
    'dissertation'=>'Dissertation','qcm'=>'QCM','oral'=>'Oral','autre'=>'Autre',
];
$statutBadge = function(string $s): string {
    $map = [
        'ouvert'  => ['etd-badge--green',  'Ouvert'],
        'en_cours'=> ['etd-badge--blue',   'En cours'],
        'correction_livree' => ['etd-badge--amber', 'À valider'],
        'resolu'  => ['etd-badge--gray',   'Résolu'],
        'annule'  => ['etd-badge--red',    'Annulé'],
    ];
    [$cls, $lbl] = $map[$s] ?? ['etd-badge--gray', ucfirst($s)];
    return "<span class=\"etd-badge {$cls}\">{$lbl}</span>";
};
$difficulteBadge = function(string $d): string {
    $map = [
        'facile'        => ['etd-diff--green',  'Facile'],
        'moyen'         => ['etd-diff--amber',  'Moyen'],
        'difficile'     => ['etd-diff--orange', 'Difficile'],
        'tres_difficile'=> ['etd-diff--red',    'Très difficile'],
    ];
    [$cls, $lbl] = $map[$d] ?? ['etd-diff--gray', $d];
    return "<span class=\"etd-diff {$cls}\">{$lbl}</span>";
};
?>
<div class="etd-page">

    <div class="etd-page__header">
        <div>
            <h1 class="etd-page__title">
                <?= $matiereCourante ? 'Exercices · ' . $e($matiereCourante['nom']) : 'Mes exercices' ?>
            </h1>
            <p class="etd-page__sub">Tous vos exercices soumis<?= $matiereCourante ? ' en ' . $e($matiereCourante['nom']) : '' ?></p>
        </div>
        <a href="<?= $baseUrl ?>/etudiant/exercices/nouveau" class="etd-btn etd-btn--primary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nouvel exercice
        </a>
    </div>

    <!-- Filtre par matière -->
    <div class="etd-filters">
        <a href="<?= $baseUrl ?>/etudiant/exercices" class="etd-filter-pill <?= !$matiere_id ? 'active' : '' ?>">Toutes</a>
        <?php foreach ($matieres as $mat): ?>
        <a href="<?= $baseUrl ?>/etudiant/exercices?matiere=<?= (int)$mat['id'] ?>"
           class="etd-filter-pill <?= $matiere_id === (int)$mat['id'] ? 'active' : '' ?>">
            <?= $e($mat['nom']) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="etd-flash etd-flash--success">
        <?= $e($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); endif; ?>

    <?php if (empty($exercices)): ?>
    <div class="etd-empty etd-empty--page">
        <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        <p>Aucun exercice<?= $matiereCourante ? ' pour ' . $e($matiereCourante['nom']) : '' ?> pour le moment.</p>
        <a href="<?= $baseUrl ?>/etudiant/exercices/nouveau" class="etd-btn etd-btn--primary">Soumettre un exercice</a>
    </div>
    <?php else: ?>
    <div class="etd-exercices-grid">
        <?php foreach ($exercices as $ex): ?>
        <div class="etd-exercice-card">
            <div class="etd-exercice-card__head">
                <div class="etd-exercice-card__meta">
                    <?php if (!empty($ex['matiere_nom'])): ?>
                    <span class="etd-matiere-tag"><?= $e($ex['matiere_nom']) ?></span>
                    <?php endif; ?>
                    <span class="etd-type-tag"><?= $e($typeLabel[$ex['type_exercice'] ?? ''] ?? ucfirst($ex['type_exercice'] ?? '')) ?></span>
                </div>
                <div class="etd-exercice-card__badges">
                    <?php if (($ex['urgence'] ?? '') === 'tres_urgent'): ?>
                    <span class="etd-urgence etd-urgence--red">Urgent</span>
                    <?php elseif (($ex['urgence'] ?? '') === 'urgent'): ?>
                    <span class="etd-urgence etd-urgence--orange">Urgent</span>
                    <?php endif; ?>
                    <?= $statutBadge($ex['statut'] ?? 'ouvert') ?>
                </div>
            </div>
            <h3 class="etd-exercice-card__title">
                <a href="<?= $baseUrl ?>/etudiant/exercices/<?= (int)$ex['id'] ?>"><?= $e($ex['titre']) ?></a>
            </h3>
            <p class="etd-exercice-card__desc"><?= $e(mb_substr($ex['description'] ?? '', 0, 120)) ?><?= mb_strlen($ex['description'] ?? '') > 120 ? '…' : '' ?></p>
            <div class="etd-exercice-card__footer">
                <div class="etd-exercice-card__info">
                    <?= $difficulteBadge($ex['niveau_difficulte'] ?? 'moyen') ?>
                    <?php if (!empty($ex['date_limite'])): ?>
                    <span class="etd-deadline">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?= date('d/m/Y', strtotime($ex['date_limite'])) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <a href="<?= $baseUrl ?>/etudiant/exercices/<?= (int)$ex['id'] ?>" class="etd-btn-sm etd-btn-sm--outline">Détail →</a>
            </div>
            <?php if (!empty($ex['note_finale'])): ?>
            <div class="etd-exercice-card__note">
                Note finale : <strong><?= number_format((float)$ex['note_finale'], 1) ?>/20</strong>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
