<?php
/**
 * Bouton étudiant : confirmer que l'exercice est résolu (après correction reçue / débloquée).
 * @var bool $can_confirm_exercice
 * @var array $exercice
 * @var string $base_path Préfixe URL (/etudiant)
 */
$canConfirm = !empty($can_confirm_exercice);
$ex = $exercice ?? [];
if (!$canConfirm || empty($ex['id'])) {
    return;
}
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$baseUrl = rtrim(BASE_URL ?? '', '/');
$bp = $base_path ?? '/etudiant';
$csrf = \App\Core\Security::getCsrfField();
$exId = (int) $ex['id'];
?>
<section class="exercice-cloture-etd" style="margin:1rem 0;padding:1rem;border-radius:var(--radius,10px);border:1px solid #bbf7d0;background:#f0fdf4">
    <h3 style="margin:0 0 .5rem;font-size:.9rem;font-weight:800;color:#15803d">Confirmer la résolution</h3>
    <p style="margin:0 0 1rem;font-size:.82rem;color:#365314;line-height:1.55">
        Vous avez consulté la correction. Si elle répond à votre demande, confirmez que votre exercice est
        <strong>résolu</strong>. Une proposition ou une correction envoyée ne clôt pas automatiquement votre demande.
    </p>
    <form method="post" action="<?= $e($baseUrl . $bp . '/confirmer-exercice-resolu/' . $exId) ?>">
        <?= $csrf ?>
        <button type="submit" class="etd-btn etd-btn--primary"
                style="display:inline-flex;align-items:center;gap:.4rem;background:#16a34a;border-color:#16a34a"
                onclick="return confirm('Confirmer que votre exercice est bien résolu et que la correction vous convient ?');">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
            Mon exercice est résolu
        </button>
    </form>
</section>
