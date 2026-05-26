<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e       = fn($s) => \App\Core\Security::escape($s ?? '');
$bp      = $prof_base_path ?? '/professeur';
$solde   = (float) ($solde ?? 0);
$devise  = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');

$ops = [
    'ORANGE' => ['label' => 'Orange Money', 'file' => 'orange-money.png', 'hint' => 'Recevez sur Orange Money'],
    'MOOV'   => ['label' => 'Moov Africa', 'file' => 'moov-africa.png',  'hint' => 'Recevez sur Moov Money'],
    'WAVE'   => ['label' => 'Wave',        'file' => 'wave.png',         'hint' => 'Recevez sur Wave'],
];
?>
<section class="section-desktop page-etudiant page-retrait-choix">
    <div class="page-expert__header">
        <a href="<?= $e($baseUrl . $bp . '/portefeuille') ?>" class="page-expert__back" aria-label="Retour">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Mon portefeuille
        </a>
        <h1 class="page-expert__title">Choisir un opérateur</h1>
        <p class="page-expert__subtitle">Sélectionnez le réseau Mobile Money pour votre retrait.</p>
    </div>

    <div class="retrait-hero" style="margin-bottom:1.5rem">
        <div class="retrait-hero__content">
            <span class="retrait-hero__label">Solde disponible</span>
            <span class="retrait-hero__amount"><?= number_format($solde, 0, ',', ' ') ?> <span class="retrait-hero__devise"><?= $e($devise) ?></span></span>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem;margin-bottom:1.5rem">
        <?php foreach ($ops as $code => $info): ?>
        <a href="<?= $e($baseUrl . $bp . '/retrait?operateur=' . rawurlencode($code)) ?>"
           class="page-expert__card"
           style="display:flex;flex-direction:column;align-items:center;text-align:center;padding:1.5rem 1.25rem;text-decoration:none;color:inherit;border:2px solid #e2e8f0;border-radius:12px">
            <img src="<?= $e($baseUrl) ?>/assets/images/operators/<?= $e($info['file']) ?>" alt="" width="140" height="56" style="object-fit:contain;margin-bottom:0.75rem">
            <strong style="font-size:1.05rem;color:#0f172a"><?= $e($info['label']) ?></strong>
            <span style="font-size:0.85rem;color:#64748b;margin-top:0.35rem"><?= $e($info['hint']) ?></span>
            <span style="margin-top:1rem;font-size:0.88rem;font-weight:600;color:#2563eb">Continuer →</span>
        </a>
        <?php endforeach; ?>
    </div>

    <p style="font-size:0.85rem;color:#64748b;text-align:center">Traitement sous 24–48 h ouvrées après validation.</p>
</section>
