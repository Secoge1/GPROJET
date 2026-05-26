<?php
/**
 * Bouton client : confirmer que la demande est résolue (après prestation expert).
 * @var bool $can_confirm_demande
 * @var array|null $reservation
 * @var string $client_base_path
 */
$canConfirm = !empty($can_confirm_demande);
$reservation = $reservation ?? null;
if (!$canConfirm || !$reservation) {
    return;
}
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$baseUrl = rtrim(BASE_URL ?? '', '/');
$bp = $client_base_path ?? '/client';
$csrf = \App\Core\Security::getCsrfField();
$reservationId = (int) ($reservation['id'] ?? 0);
?>
<section class="demande-cloture" aria-labelledby="demande-cloture-title">
    <div class="demande-cloture__box">
        <h3 id="demande-cloture-title" class="demande-cloture__title">Confirmer la résolution</h3>
        <p class="demande-cloture__text">
            L'expert a terminé sa prestation. Vérifiez le travail reçu, puis confirmez que votre demande est
            <strong>résolue</strong>. Une proposition ou une session terminée ne clôt pas automatiquement votre demande.
        </p>
        <form method="post" action="<?= $baseUrl . $bp ?>/confirmer-demande-resolue/<?= $reservationId ?>" class="demande-cloture__form">
            <?= $csrf ?>
            <button type="submit" class="cl-btn cl-btn--green cl-btn--sm"
                    onclick="return confirm('Confirmer que votre demande est bien résolue et que la prestation vous convient ?');">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><polyline points="20 6 9 17 4 12"/></svg>
                Ma demande est résolue
            </button>
        </form>
    </div>
</section>
