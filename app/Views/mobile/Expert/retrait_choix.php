<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e       = fn($s) => \App\Core\Security::escape($s ?? '');
$prefix  = $expert_path_prefix ?? '/expert';
$solde   = (float) ($solde ?? 0);
$devise  = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');

$ops = [
    'ORANGE' => ['label' => 'Orange Money', 'file' => 'orange-money.png', 'hint' => 'Compte Orange Money'],
    'MOOV'   => ['label' => 'Moov Africa', 'file' => 'moov-africa.png',  'hint' => 'Compte Moov Money'],
    'WAVE'   => ['label' => 'Wave',        'file' => 'wave.png',         'hint' => 'Compte Wave'],
];
?>

<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem">
    <a href="<?= $e($baseUrl . $prefix . '/revenus') ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none;flex-shrink:0" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <h1 style="margin:0;font-size:1.15rem;font-weight:700;color:var(--primary)">Retrait — choisir l’opérateur</h1>
</div>

<p style="margin:0 0 1rem;font-size:0.85rem;color:var(--text-muted);line-height:1.45">
    Sélectionnez le <strong>compte Mobile Money</strong> sur lequel vous souhaitez recevoir vos gains (même numéro que sur votre téléphone).
</p>

<div style="background:linear-gradient(135deg,#16a34a,#15803d);border-radius:var(--radius);padding:1rem 1.25rem;margin-bottom:1.25rem;color:#fff">
    <p style="margin:0 0 0.1rem;font-size:0.78rem;opacity:0.85">Solde disponible</p>
    <p style="margin:0;font-size:1.35rem;font-weight:800"><?= number_format($solde, 0, ',', ' ') ?> <span style="font-size:0.8rem"><?= $e($devise) ?></span></p>
</div>

<div style="display:flex;flex-direction:column;gap:0.75rem;margin-bottom:1.25rem">
    <?php foreach ($ops as $code => $info): ?>
    <a href="<?= $e($baseUrl . $prefix . '/retrait?operateur=' . rawurlencode($code)) ?>"
       class="retrait-op-card"
       style="display:flex;align-items:center;gap:1rem;padding:1rem 1.1rem;background:var(--card-bg);border:2px solid var(--border);border-radius:var(--radius);text-decoration:none;color:var(--text);box-sizing:border-box">
        <span style="flex-shrink:0;width:56px;height:56px;border-radius:12px;background:#fff;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;padding:6px">
            <img src="<?= $e($baseUrl) ?>/assets/images/operators/<?= $e($info['file']) ?>" alt="" width="120" height="48" style="max-width:100%;max-height:44px;width:auto;height:auto;object-fit:contain">
        </span>
        <span style="flex:1;min-width:0">
            <span style="display:block;font-weight:700;font-size:0.95rem;color:var(--primary)"><?= $e($info['label']) ?></span>
            <span style="display:block;font-size:0.76rem;color:var(--text-muted);margin-top:0.15rem"><?= $e($info['hint']) ?></span>
        </span>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" style="flex-shrink:0"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
    </a>
    <?php endforeach; ?>
</div>

<p style="margin:0;font-size:0.72rem;color:var(--text-muted);text-align:center;line-height:1.4">
    Virement traité manuellement sous 24–48 h après validation par l’administration.
</p>
