<?php
$baseUrl     = rtrim(BASE_URL ?? '', '/');
$bp          = $base_path ?? '/professeur';
$profHref    = static function (string $path) use ($baseUrl, $bp): string {
    $path = ltrim($path, '/');
    if ($bp === '/app') {
        $flat  = ['exercices-disponibles', 'proposer-exercice', 'prendre-exercice', 'corriger', 'compte'];
        $first = explode('/', $path)[0] ?? '';
        if (in_array($first, $flat, true)) {
            return $baseUrl . '/app/' . $path;
        }
        return $baseUrl . '/professeur/' . $path;
    }
    return $baseUrl . '/professeur/' . $path;
};
$e           = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField   = \App\Core\Security::getCsrfField();
$disponibles = $disponibles ?? [];
$enCharge    = $en_charge   ?? [];
$profValide  = $prof_valide ?? false;

$urgenceColor = ['normale' => '#6b7280', 'urgent' => '#d97706', 'tres_urgent' => '#dc2626'];
$urgenceLabel = ['normale' => 'Normale', 'urgent' => 'Urgent', 'tres_urgent' => 'Très urgent'];
$typeLabel    = ['devoir' => 'Devoir', 'examen' => 'Examen', 'tp' => 'TP/TD', 'projet' => 'Projet',
                 'dissertation' => 'Dissertation', 'qcm' => 'QCM', 'oral' => 'Oral', 'autre' => 'Autre'];
$statutColor  = ['en_cours' => '#2563eb', 'correction_livree' => '#d97706', 'resolu' => '#16a34a'];
$statutLabel  = ['en_cours' => 'En cours', 'correction_livree' => 'Attente étudiant', 'resolu' => 'Résolu'];

$flashError   = $_SESSION['flash_error']   ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>

<div class="etd-page">
    <div class="etd-page__header">
        <div>
            <h1 class="etd-page__title">Exercices à corriger</h1>
            <p style="margin:.25rem 0 0;font-size:.85rem;color:#6b7280">Prenez en charge des exercices d'étudiants et soumettez vos corrections.</p>
        </div>
    </div>

    <?php if (!$profValide): ?>
    <div style="padding:.85rem 1.1rem;border-radius:10px;background:#fffbeb;border:1px solid #fde68a;border-left:4px solid #f59e0b;color:#92400e;margin-bottom:1.25rem;font-size:.875rem;display:flex;gap:.65rem;align-items:flex-start">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" style="flex-shrink:0;margin-top:2px"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span><strong>Profil en attente de validation.</strong> Vous pouvez consulter les exercices disponibles, mais vous ne pourrez les prendre en charge qu'une fois votre profil validé par un administrateur.</span>
    </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
    <div class="alert-error" style="padding:.75rem 1rem;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;margin-bottom:1rem">⚠️ <?= $e($flashError) ?></div>
    <?php endif; ?>
    <?php if ($flashSuccess): ?>
    <div style="padding:.75rem 1rem;border-radius:8px;background:#f0fdf4;border:1px solid #86efac;color:#16a34a;margin-bottom:1rem">✅ <?= $e($flashSuccess) ?></div>
    <?php endif; ?>

    <div class="etd-detail-grid">
        <!-- Colonne principale : disponibles -->
        <div class="etd-detail-main">
            <div class="etd-card">
                <h2 class="etd-card__section-title" style="margin-bottom:1rem">
                    Exercices ouverts (<?= count($disponibles) ?>)
                </h2>
                <?php if (empty($disponibles)): ?>
                <p style="color:#6b7280;font-size:.9rem;text-align:center;padding:2rem 0">Aucun exercice ouvert pour l'instant. Les nouveaux exercices soumis par les étudiants apparaîtront ici.</p>
                <?php else: ?>
                <div style="display:flex;flex-direction:column;gap:.75rem">
                    <?php foreach ($disponibles as $ex): ?>
                    <?php
                    $uc = $urgenceColor[$ex['urgence'] ?? 'normale'] ?? '#6b7280';
                    $ul = $urgenceLabel[$ex['urgence'] ?? 'normale'] ?? '';
                    $tl = $typeLabel[$ex['type_exercice'] ?? 'autre'] ?? '';
                    ?>
                    <div style="border:1px solid #e2e8f0;border-radius:10px;padding:1rem;background:#fff">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;margin-bottom:.6rem">
                            <div>
                                <p style="margin:0 0 .2rem;font-size:.95rem;font-weight:600;color:#0f172a"><?= $e($ex['titre'] ?? '') ?></p>
                                <p style="margin:0;font-size:.8rem;color:#6b7280">
                                    Étudiant : <?= $e($ex['prenom'] ?? '') ?> <?= $e($ex['etudiant_nom'] ?? '') ?>
                                    <?php if (!empty($ex['matiere_nom'])): ?> · <strong><?= $e($ex['matiere_nom']) ?></strong><?php endif; ?>
                                </p>
                            </div>
                            <div style="display:flex;gap:.4rem;flex-shrink:0">
                                <span style="font-size:.75rem;font-weight:600;padding:.2rem .6rem;border-radius:20px;background:<?= $uc ?>18;color:<?= $uc ?>"><?= $ul ?></span>
                                <span style="font-size:.75rem;font-weight:500;padding:.2rem .6rem;border-radius:20px;background:#f1f5f9;color:#475569"><?= $tl ?></span>
                            </div>
                        </div>
                        <p style="margin:0 0 .75rem;font-size:.85rem;color:#374151;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">
                            <?= $e(substr($ex['description'] ?? '', 0, 200)) ?>
                        </p>
                        <?php if (!empty($ex['date_limite'])): ?>
                        <p style="margin:0 0 .65rem;font-size:.78rem;color:#d97706">
                            ⏰ Deadline : <?= date('d/m/Y à H:i', strtotime($ex['date_limite'])) ?>
                        </p>
                        <?php endif; ?>
                        <div style="display:flex;flex-wrap:wrap;gap:.5rem;align-items:center">
                            <?php if (!empty($ex['ma_proposition_id'])): ?>
                            <span style="font-size:.82rem;font-weight:600;color:#7c3aed">Proposition envoyée</span>
                            <?php else: ?>
                            <a href="<?= $profHref('proposer-exercice/' . (int)$ex['id']) ?>" class="etd-btn etd-btn--primary" style="padding:.5rem 1.25rem;font-size:.85rem">
                                Proposer une correction
                            </a>
                            <?php endif; ?>
                            <?php if ($profValide): ?>
                            <form method="post" action="<?= $profHref('prendre-exercice') ?>" style="display:inline">
                                <?= $csrfField ?>
                                <input type="hidden" name="exercice_id" value="<?= (int)$ex['id'] ?>">
                                <button type="submit" class="etd-btn etd-btn--ghost" style="padding:.5rem 1rem;font-size:.85rem">
                                    Prendre directement
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar : mes corrections en cours -->
        <aside class="etd-detail-side">
            <div class="etd-card etd-card--info">
                <h3 class="etd-card__section-title">Mes corrections (<?= count($enCharge) ?>)</h3>
                <?php if (empty($enCharge)): ?>
                <p style="font-size:.83rem;color:#6b7280">Aucune correction en cours.</p>
                <?php else: ?>
                <ul class="etd-info-list" style="margin:0;padding:0;list-style:none">
                    <?php foreach ($enCharge as $ex): ?>
                    <?php $sc = $statutColor[$ex['statut'] ?? 'en_cours'] ?? '#6b7280'; $sl = $statutLabel[$ex['statut'] ?? 'en_cours'] ?? ''; ?>
                    <li style="padding:.5rem 0;border-bottom:1px solid #f1f5f9">
                        <p style="margin:0 0 .2rem;font-size:.85rem;font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            <?= $e($ex['titre'] ?? '') ?>
                        </p>
                        <div style="display:flex;align-items:center;justify-content:space-between">
                            <span style="font-size:.72rem;color:<?= $sc ?>;background:<?= $sc ?>18;padding:.1rem .45rem;border-radius:20px;font-weight:600"><?= $sl ?></span>
                            <?php if (($ex['statut'] ?? '') === 'en_cours'): ?>
                            <a href="<?= $profHref('corriger/' . (int)$ex['id']) ?>" class="etd-btn-sm etd-btn-sm--primary" style="padding:.35rem .75rem;font-size:.75rem;text-decoration:none">Corriger</a>
                            <?php elseif (($ex['statut'] ?? '') === 'correction_livree'): ?>
                            <span style="font-size:.72rem;color:#b45309;font-weight:600">En attente de validation étudiant</span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
