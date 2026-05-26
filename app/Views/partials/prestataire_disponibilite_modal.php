<?php
/**
 * Modale rappel disponibilité (expert / professeur validés).
 * @var array<string, mixed> $dispoPrompt
 */
$dispoPrompt = $dispoPrompt ?? null;
if (!$dispoPrompt) {
    return;
}
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$disponible = !empty($dispoPrompt['disponible']);
$roleLabel = ($dispoPrompt['role'] ?? '') === 'professeur' ? 'professeur' : 'expert';
$csrf = \App\Core\Security::generateCsrfToken();
?>
<div id="prestataire-dispo-modal"
     class="prestataire-dispo-modal"
     role="dialog"
     aria-modal="true"
     aria-labelledby="prestataire-dispo-title"
     aria-hidden="false"
     data-toggle-url="<?= $e($dispoPrompt['toggle_url'] ?? '') ?>"
     data-dismiss-url="<?= $e($dispoPrompt['dismiss_url'] ?? '') ?>"
     data-csrf="<?= $e($csrf) ?>"
     data-initial="<?= $disponible ? '1' : '0' ?>">
    <div class="prestataire-dispo-modal__backdrop" data-dispo-close aria-hidden="true"></div>
    <div class="prestataire-dispo-modal__panel">
        <button type="button" class="prestataire-dispo-modal__close" data-dispo-dismiss aria-label="Fermer">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>

        <div class="prestataire-dispo-modal__badge" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <h2 id="prestataire-dispo-title" class="prestataire-dispo-modal__title">Votre profil est validé !</h2>
        <p class="prestataire-dispo-modal__lead">
            Pour apparaître dans les recherches et recevoir des missions en tant que <?= $e($roleLabel) ?>,
            activez votre <strong>disponibilité</strong>. Vous pourrez repasser en hors ligne à tout moment.
        </p>

        <div class="prestataire-dispo-modal__toggle-card" data-dispo-state="<?= $disponible ? 'on' : 'off' ?>">
            <div class="prestataire-dispo-modal__status-row">
                <span class="prestataire-dispo-modal__status-dot" aria-hidden="true"></span>
                <div>
                    <p class="prestataire-dispo-modal__status-label" data-dispo-status-label>
                        <?= $disponible ? 'Disponible maintenant' : 'Hors ligne' ?>
                    </p>
                    <p class="prestataire-dispo-modal__status-hint" data-dispo-status-hint>
                        <?= $disponible
                            ? 'Les clients peuvent vous trouver et vous contacter.'
                            : 'Vous n’apparaissez pas dans les listes publiques.' ?>
                    </p>
                </div>
            </div>

            <label class="prestataire-dispo-modal__switch" aria-label="Activer ma disponibilité">
                <input type="checkbox"
                       class="prestataire-dispo-modal__switch-input"
                       id="prestataire-dispo-checkbox"
                       <?= $disponible ? 'checked' : '' ?>>
                <span class="prestataire-dispo-modal__switch-track">
                    <span class="prestataire-dispo-modal__switch-thumb"></span>
                </span>
                <span class="prestataire-dispo-modal__switch-text">
                    <span class="prestataire-dispo-modal__switch-on">En ligne</span>
                    <span class="prestataire-dispo-modal__switch-off">Hors ligne</span>
                </span>
            </label>
        </div>

        <p class="prestataire-dispo-modal__feedback" data-dispo-feedback role="status" aria-live="polite"></p>

        <div class="prestataire-dispo-modal__actions">
            <button type="button" class="prestataire-dispo-modal__btn prestataire-dispo-modal__btn--primary" data-dispo-save>
                <?= $disponible ? 'Confirmer' : 'Activer ma disponibilité' ?>
            </button>
            <button type="button" class="prestataire-dispo-modal__btn prestataire-dispo-modal__btn--ghost" data-dispo-dismiss>
                Plus tard
            </button>
            <a href="<?= $e($dispoPrompt['profil_url'] ?? '#') ?>" class="prestataire-dispo-modal__link">
                Gérer dans mon profil
            </a>
        </div>
    </div>
</div>
