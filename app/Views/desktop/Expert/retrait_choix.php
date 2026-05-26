<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e       = fn($s) => \App\Core\Security::escape($s ?? '');
$prefix  = $expert_path_prefix ?? '/expert';
$solde   = (float) ($solde ?? 0);
$devise  = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');

$ops = [
    'ORANGE' => ['label' => 'Orange Money', 'file' => 'orange-money.png', 'hint' => 'Recevez sur votre compte Orange Money'],
    'MOOV'   => ['label' => 'Moov Africa', 'file' => 'moov-africa.png',  'hint' => 'Recevez sur votre compte Moov Money'],
    'WAVE'   => ['label' => 'Wave',        'file' => 'wave.png',         'hint' => 'Recevez sur votre compte Wave'],
];
?>
<section class="section-desktop page-expert page-expert-retrait-choix">
    <div class="page-expert__header">
        <a href="<?= $e($baseUrl . $prefix . '/revenus') ?>" class="page-expert__back" aria-label="Retour aux revenus">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Mes revenus
        </a>
        <h1 class="page-expert__title">Choisir un opérateur</h1>
        <p class="page-expert__subtitle">Sélectionnez le réseau Mobile Money sur lequel vous recevrez le virement.</p>
    </div>

    <div class="retrait-hero" style="margin-bottom:1.5rem">
        <div class="retrait-hero__content">
            <span class="retrait-hero__label">Solde disponible</span>
            <span class="retrait-hero__amount"><?= number_format($solde, 0, ',', ' ') ?> <span class="retrait-hero__devise"><?= $e($devise) ?></span></span>
        </div>
    </div>

    <div class="retrait-choix-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem;margin-bottom:1.5rem">
        <?php foreach ($ops as $code => $info): ?>
        <a href="<?= $e($baseUrl . $prefix . '/retrait?operateur=' . rawurlencode($code)) ?>"
           class="page-expert__card"
           style="display:flex;flex-direction:column;align-items:center;text-align:center;padding:1.5rem 1.25rem;text-decoration:none;color:inherit;border:2px solid var(--border, #e2e8f0);border-radius:12px;transition:box-shadow .2s, border-color .2s"
           onmouseover="this.style.borderColor='#16a34a';this.style.boxShadow='0 4px 14px rgba(22,163,74,.12)'"
           onmouseout="this.style.borderColor='';this.style.boxShadow=''">
            <img src="<?= $e($baseUrl) ?>/assets/images/operators/<?= $e($info['file']) ?>" alt="" width="140" height="56" style="object-fit:contain;margin-bottom:0.75rem">
            <strong style="font-size:1.05rem;color:var(--primary, #0f172a)"><?= $e($info['label']) ?></strong>
            <span style="font-size:0.85rem;color:#64748b;margin-top:0.35rem;line-height:1.35"><?= $e($info['hint']) ?></span>
            <span style="margin-top:1rem;font-size:0.88rem;font-weight:600;color:#16a34a">Continuer →</span>
        </a>
        <?php endforeach; ?>
    </div>

    <p style="font-size:0.85rem;color:#64748b;text-align:center">Traitement sous 24–48 h ouvrées après validation par l’administration.</p>
</section>
