<?php
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$role      = $user['role'] ?? 'etudiant';
$basePath  = $base_path ?? ($role === 'professeur' ? '/professeur' : '/etudiant');
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$profil    = $profil ?? [];
$stats     = $stats ?? [];
$recents   = $recents ?? [];
$matieres  = $matieres ?? [];
$nbDispo   = (int)($nb_exercices_disponibles ?? 0);
$prenom    = $e($profil['prenom'] ?? $profil['nom'] ?? ($role === 'professeur' ? 'Professeur' : 'Étudiant'));

$niveauLabel = [
    'facile'        => 'Facile',
    'moyen'         => 'Moyen',
    'difficile'     => 'Difficile',
    'tres_difficile'=> 'Très difficile',
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
$urgenceBadge = function(string $u): string {
    if ($u === 'tres_urgent') return '<span class="etd-urgence etd-urgence--red">Tres urgent</span>';
    if ($u === 'urgent')      return '<span class="etd-urgence etd-urgence--orange">Urgent</span>';
    return '';
};
?>
<div class="etd-dashboard">

    <!-- Hero -->
    <div class="etd-hero">
        <div class="etd-hero__left">
            <p class="etd-hero__label">Bienvenue,</p>
            <h1 class="etd-hero__title"><?= $prenom ?> <span>🎓</span></h1>
            <p class="etd-hero__sub">
                <?php if (!empty($profil['universite'])): ?>
                    <?= $e($profil['universite']) ?><?= !empty($profil['pays']) ? ' · ' . $e($profil['pays']) : '' ?>
                <?php else: ?>
                    Gérez vos exercices par matière universitaire.
                <?php endif; ?>
            </p>
        </div>
        <div class="etd-hero__actions">
            <?php if ($role === 'professeur'): ?>
            <a href="<?= $baseUrl . $basePath ?>/exercices-disponibles" class="etd-btn etd-btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                Exercices à corriger
                <?php if ($nbDispo > 0): ?>
                <span style="background:#fff;color:#7c3aed;border-radius:20px;padding:1px 7px;font-size:.72rem;font-weight:800;margin-left:.2rem"><?= $nbDispo ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= $baseUrl . $basePath ?>/retrait-choix" class="etd-btn etd-btn--outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Retrait Mobile Money
            </a>
            <?php else: ?>
            <a href="<?= $baseUrl . $basePath ?>/exercices/nouveau" class="etd-btn etd-btn--primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Soumettre un exercice
            </a>
            <a href="<?= $baseUrl . $basePath ?>/matieres" class="etd-btn etd-btn--outline">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Mes matières
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats (étudiants uniquement) -->
    <?php if ($role === 'professeur' && $nbDispo > 0): ?>
    <a href="<?= $baseUrl . $basePath ?>/exercices-disponibles" style="display:flex;align-items:center;gap:.75rem;background:linear-gradient(135deg,#ede9fe,#ddd6fe);border:1px solid #c4b5fd;border-radius:12px;padding:.85rem 1.1rem;margin-bottom:1rem;text-decoration:none;color:#5b21b6;font-weight:600;font-size:.9rem;transition:filter .15s;" onmouseover="this.style.filter='brightness(.97)'" onmouseout="this.style.filter=''">
        <span style="background:#7c3aed;color:#fff;border-radius:10px;width:36px;height:36px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;font-weight:800"><?= $nbDispo ?></span>
        <span>exercice<?= $nbDispo > 1 ? 's' : '' ?> en attente de correction — <strong>Cliquez pour les voir</strong></span>
        <svg style="margin-left:auto;flex-shrink:0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
    <?php endif; ?>

    <!-- Stats (étudiants uniquement) -->
    <?php if ($role === 'etudiant'): ?>
    <div class="etd-stats">
        <div class="etd-stat-card">
            <div class="etd-stat-card__icon etd-stat-card__icon--amber">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            </div>
            <div class="etd-stat-card__body">
                <span class="etd-stat-card__num"><?= (int)($stats['total'] ?? 0) ?></span>
                <span class="etd-stat-card__lbl">Total exercices</span>
            </div>
        </div>
        <div class="etd-stat-card">
            <div class="etd-stat-card__icon etd-stat-card__icon--green">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="etd-stat-card__body">
                <span class="etd-stat-card__num"><?= (int)($stats['resolus'] ?? 0) ?></span>
                <span class="etd-stat-card__lbl">Résolus</span>
            </div>
        </div>
        <div class="etd-stat-card">
            <div class="etd-stat-card__icon etd-stat-card__icon--blue">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="etd-stat-card__body">
                <span class="etd-stat-card__num"><?= (int)($stats['en_cours'] ?? 0) ?></span>
                <span class="etd-stat-card__lbl">En cours</span>
            </div>
        </div>
        <div class="etd-stat-card">
            <div class="etd-stat-card__icon etd-stat-card__icon--purple">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            </div>
            <div class="etd-stat-card__body">
                <span class="etd-stat-card__num"><?= !empty($stats['moyenne_notes']) ? number_format((float)$stats['moyenne_notes'], 1) . '/20' : '—' ?></span>
                <span class="etd-stat-card__lbl">Moyenne</span>
            </div>
        </div>
        <a href="<?= $baseUrl . $basePath ?>/profil" class="etd-stat-card etd-stat-card--link">
            <div class="etd-stat-card__icon etd-stat-card__icon--teal">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            </div>
            <div class="etd-stat-card__body">
                <span class="etd-stat-card__num"><?= count($matieres) ?></span>
                <span class="etd-stat-card__lbl">Matières maîtrisées</span>
            </div>
            <svg class="etd-stat-card__arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
        </a>
    </div>
    <?php endif; ?>

    <div class="etd-grid">

        <!-- Exercices récents / En cours de correction -->
        <div class="etd-card etd-card--wide">
            <div class="etd-card__head">
                <h2 class="etd-card__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <?= $role === 'professeur' ? 'Exercices en cours de correction' : 'Exercices récents' ?>
                </h2>
                <a href="<?= $role === 'professeur' ? $baseUrl . $basePath . '/exercices-disponibles' : $baseUrl . $basePath . '/exercices' ?>" class="etd-card__link"><?= $role === 'professeur' ? 'Voir les exercices →' : 'Tout voir →' ?></a>
            </div>
            <?php if (empty($recents)): ?>
            <div class="etd-empty">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <?php if ($role === 'professeur'): ?>
                <p>Aucun exercice en cours de correction</p>
                <a href="<?= $baseUrl . $basePath ?>/exercices-disponibles" class="etd-btn-sm etd-btn-sm--primary">Voir les exercices à corriger</a>
                <?php else: ?>
                <p>Aucun exercice soumis pour le moment</p>
                <a href="<?= $baseUrl . $basePath ?>/exercices/nouveau" class="etd-btn-sm etd-btn-sm--primary">Soumettre un exercice</a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <ul class="etd-list">
                <?php foreach ($recents as $ex): ?>
                <li class="etd-list__item">
                    <div class="etd-list__left">
                        <span class="etd-list__title"><?= $e($ex['titre']) ?></span>
                        <span class="etd-list__meta">
                            <?php if (!empty($ex['matiere_nom'])): ?><strong><?= $e($ex['matiere_nom']) ?></strong> · <?php endif; ?>
                            <?= $e(ucfirst(str_replace('_', ' ', $ex['type_exercice'] ?? ''))) ?>
                            <?php if (!empty($ex['date_limite'])): ?>
                            · <span class="etd-deadline">Limite : <?= date('d/m/Y', strtotime($ex['date_limite'])) ?></span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="etd-list__right">
                        <?= $urgenceBadge($ex['urgence'] ?? 'normale') ?>
                        <?= $statutBadge($ex['statut'] ?? 'ouvert') ?>
                        <a href="<?= $role === 'professeur' ? $baseUrl . $basePath . '/corriger/' . (int)$ex['id'] : $baseUrl . $basePath . '/exercices/' . (int)$ex['id'] ?>" class="etd-btn-sm etd-btn-sm--outline">Voir</a>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <!-- Mes matières maîtrisées -->
        <div class="etd-card">
            <div class="etd-card__head">
                <h2 class="etd-card__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    Mes matières maîtrisées
                </h2>
                <a href="<?= $baseUrl . $basePath ?>/profil" class="etd-card__link">Gérer →</a>
            </div>
            <?php if (empty($matieres)): ?>
            <div class="etd-empty">
                <p>Ajoutez les matières que vous maîtrisez pour un suivi personnalisé.</p>
                <a href="<?= $baseUrl . $basePath ?>/profil" class="etd-btn-sm etd-btn-sm--primary">Configurer mon profil</a>
            </div>
            <?php else: ?>
            <ul class="etd-matieres-list">
                <?php foreach (array_slice($matieres, 0, 8) as $m): ?>
                <li class="etd-matieres-list__item">
                    <span class="etd-matieres-list__nom"><?= $e($m['matiere_nom']) ?></span>
                    <span class="etd-niveau etd-niveau--<?= $e($m['niveau_maitrise'] ?? 'intermediaire') ?>">
                        <?= ['debutant'=>'Débutant','intermediaire'=>'Intermédiaire','avance'=>'Avancé','expert'=>'Expert'][$m['niveau_maitrise'] ?? 'intermediaire'] ?? '' ?>
                    </span>
                    <?php if (!empty($m['note_obtenue'])): ?>
                    <span class="etd-note"><?= number_format((float)$m['note_obtenue'], 1) ?>/20</span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if (count($matieres) > 8): ?>
            <p class="etd-card__more"><a href="<?= $baseUrl . $basePath ?>/profil">+<?= count($matieres) - 8 ?> autres matières</a></p>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Profil rapide -->
        <div class="etd-card etd-card--profil">
            <div class="etd-card__head">
                <h2 class="etd-card__title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    Mon profil
                </h2>
                <a href="<?= $baseUrl . $basePath ?>/profil" class="etd-card__link">Modifier →</a>
            </div>
            <ul class="etd-profil-info">
                <?php if (!empty($profil['universite'])): ?>
                <li><span class="etd-pi-label">Université</span><span class="etd-pi-val"><?= $e($profil['universite']) ?></span></li>
                <?php endif; ?>
                <?php if (!empty($profil['pays'])): ?>
                <li><span class="etd-pi-label">Pays</span><span class="etd-pi-val"><?= $e($profil['pays']) ?></span></li>
                <?php endif; ?>
                <?php if (!empty($profil['filiere'])): ?>
                <li><span class="etd-pi-label">Filière</span><span class="etd-pi-val"><?= $e($profil['filiere']) ?></span></li>
                <?php endif; ?>
                <?php if (!empty($profil['niveau_etude'])): ?>
                <li><span class="etd-pi-label">Niveau</span><span class="etd-pi-val"><?= $e($profil['niveau_etude']) ?></span></li>
                <?php endif; ?>
                <?php if (empty($profil['universite']) && empty($profil['niveau_etude'])): ?>
                <li class="etd-profil-info__empty">
                    <a href="<?= $baseUrl . $basePath ?>/profil" class="etd-btn-sm etd-btn-sm--primary">Compléter mon profil</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

    </div>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="etd-flash etd-flash--success" id="etd-flash">
    <?= \App\Core\Security::escape($_SESSION['flash_success']) ?>
    <button onclick="document.getElementById('etd-flash').remove()">×</button>
</div>
<?php unset($_SESSION['flash_success']); endif; ?>
