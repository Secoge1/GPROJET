<?php
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$bp         = $base_path ?? '/professeur';
$profHref   = static function (string $path) use ($baseUrl, $bp): string {
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
$e          = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField  = \App\Core\Security::getCsrfField();
$disponibles = $disponibles ?? [];
$enCharge    = $en_charge   ?? [];

$urgenceColor = ['normale' => '#6b7280', 'urgent' => '#d97706', 'tres_urgent' => '#dc2626'];
$urgenceLabel = ['normale' => 'Normale', 'urgent' => 'Urgent', 'tres_urgent' => 'Très urgent'];
$typeLabel    = ['devoir' => 'Devoir', 'examen' => 'Examen', 'tp' => 'TP/TD', 'projet' => 'Projet',
                 'dissertation' => 'Dissertation', 'qcm' => 'QCM', 'oral' => 'Oral', 'autre' => 'Autre'];

$flashError   = $_SESSION['flash_error']   ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>

<h1 style="margin:0 0 1.25rem;font-size:1.1rem;font-weight:700;color:var(--primary)">Exercices disponibles</h1>

<?php if ($flashError): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:.85rem 1rem;margin-bottom:1rem">
    <p style="margin:0;font-size:.85rem;color:#dc2626">⚠️ <?= $e($flashError) ?></p>
</div>
<?php endif; ?>
<?php if ($flashSuccess): ?>
<div style="background:#f0fdf4;border:1px solid #86efac;border-radius:var(--radius);padding:.85rem 1rem;margin-bottom:1rem">
    <p style="margin:0;font-size:.85rem;color:#16a34a">✅ <?= $e($flashSuccess) ?></p>
</div>
<?php endif; ?>

<!-- Mes corrections en cours -->
<?php if (!empty($enCharge)): ?>
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:1.25rem">
    <div style="padding:.7rem 1rem;border-bottom:1px solid var(--border);background:#eff6ff">
        <h2 style="margin:0;font-size:.88rem;font-weight:700;color:#1d4ed8">Mes corrections (<?= count($enCharge) ?>)</h2>
    </div>
    <?php foreach ($enCharge as $ex): ?>
    <?php
    $st = (string) ($ex['statut'] ?? 'en_cours');
    if ($st === 'resolu') {
        $sColor = '#16a34a';
        $sLabel = 'Résolu';
    } elseif ($st === 'correction_livree') {
        $sColor = '#d97706';
        $sLabel = 'Attente étudiant';
    } else {
        $sColor = '#2563eb';
        $sLabel = 'En cours';
    }
    ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem 1rem;border-bottom:1px solid var(--border)">
        <div style="flex:1;min-width:0">
            <p style="margin:0 0 .15rem;font-size:.85rem;font-weight:600;color:var(--primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= $e($ex['titre'] ?? '') ?>
            </p>
            <p style="margin:0;font-size:.72rem;color:var(--text-muted)">
                <?= $e($ex['prenom'] ?? '') ?> <?= $e($ex['etudiant_nom'] ?? '') ?>
                <?php if (!empty($ex['matiere_nom'])): ?> · <?= $e($ex['matiere_nom']) ?><?php endif; ?>
            </p>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.3rem;flex-shrink:0;margin-left:.75rem">
            <span style="font-size:.7rem;font-weight:700;color:<?= $sColor ?>;background:<?= $sColor ?>18;padding:.15rem .5rem;border-radius:20px"><?= $sLabel ?></span>
            <?php if (($ex['statut'] ?? '') === 'en_cours'): ?>
            <a href="<?= $profHref('corriger/' . (int)$ex['id']) ?>"
               class="btn-mobile btn-primary btn-sm" style="padding:.35rem .7rem;font-size:.72rem;text-decoration:none">Corriger</a>
            <?php elseif (($ex['statut'] ?? '') === 'correction_livree'): ?>
            <span style="font-size:.68rem;color:#b45309;font-weight:600;text-align:right">Validation étudiant</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Exercices ouverts à prendre en charge -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
    <div style="padding:.7rem 1rem;border-bottom:1px solid var(--border)">
        <h2 style="margin:0;font-size:.88rem;font-weight:700;color:var(--primary)">
            À corriger (<?= count($disponibles) ?>)
        </h2>
    </div>
    <?php if (empty($disponibles)): ?>
    <div style="padding:2rem 1rem;text-align:center">
        <p style="margin:0;font-size:.88rem;color:var(--text-muted)">Aucun exercice ouvert pour vos matières.</p>
    </div>
    <?php else: ?>
    <?php foreach ($disponibles as $ex): ?>
    <?php
    $uc = $urgenceColor[$ex['urgence'] ?? 'normale'] ?? '#6b7280';
    $ul = $urgenceLabel[$ex['urgence'] ?? 'normale'] ?? '';
    $tl = $typeLabel[$ex['type_exercice'] ?? 'autre'] ?? ucfirst($ex['type_exercice'] ?? '');
    ?>
    <div style="padding:.85rem 1rem;border-bottom:1px solid var(--border)">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem">
            <div style="flex:1;min-width:0;margin-right:.75rem">
                <p style="margin:0 0 .2rem;font-size:.88rem;font-weight:600;color:var(--primary)"><?= $e($ex['titre'] ?? '') ?></p>
                <p style="margin:0;font-size:.72rem;color:var(--text-muted)">
                    <?= $e($ex['prenom'] ?? '') ?> <?= $e($ex['etudiant_nom'] ?? '') ?>
                    <?php if (!empty($ex['matiere_nom'])): ?> · <span style="color:var(--accent)"><?= $e($ex['matiere_nom']) ?></span><?php endif; ?>
                </p>
            </div>
            <span style="flex-shrink:0;font-size:.7rem;font-weight:700;color:<?= $uc ?>;background:<?= $uc ?>18;padding:.2rem .55rem;border-radius:20px"><?= $ul ?></span>
        </div>
        <p style="margin:0 0 .65rem;font-size:.8rem;color:var(--text-muted);overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical">
            <?= $e(substr($ex['description'] ?? '', 0, 120)) ?>…
        </p>
        <div style="display:flex;align-items:center;justify-content:flex-end;gap:.45rem;flex-wrap:wrap">
            <span style="font-size:.72rem;color:var(--text-muted);margin-right:auto"><?= $tl ?></span>
            <?php if (!empty($ex['ma_proposition_id'])): ?>
            <span style="font-size:.75rem;font-weight:600;color:#7c3aed">Proposition envoyée</span>
            <?php else: ?>
            <a href="<?= $profHref('proposer-exercice/' . (int)$ex['id']) ?>"
               class="btn-mobile btn-outline btn-sm" style="padding:.4rem .75rem;font-size:.75rem">Proposer</a>
            <?php endif; ?>
            <form method="post" action="<?= $profHref('prendre-exercice') ?>" style="display:inline;margin:0">
                <?= $csrfField ?>
                <input type="hidden" name="exercice_id" value="<?= (int)$ex['id'] ?>">
                <button type="submit"
                        style="padding:.4rem .9rem;font-size:.78rem;font-weight:700;background:#0284c7;color:#fff;border:none;border-radius:var(--radius);cursor:pointer">
                    Prendre directement
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
