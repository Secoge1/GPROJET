<?php
declare(strict_types=1);
/**
 * Champs téléphone Mobile Money pour checkout / dépôt PayTech.
 * Variables attendues : paytech_phone_dial_options, paytech_phone_dial_default, paytech_phone_local_value,
 *                      paytech_phone_variant ('mobile'|'desktop'), paytech_phone_id_prefix (optionnel).
 */
$e = static fn (?string $s): string => \App\Core\Security::escape((string) ($s ?? ''));
$variant   = ($paytech_phone_variant ?? 'desktop') === 'mobile' ? 'mobile' : 'desktop';
$dialOpts  = isset($paytech_phone_dial_options) && is_array($paytech_phone_dial_options)
    ? $paytech_phone_dial_options
    : (new \App\Services\PayTechCheckoutAssistant())->dialOptionsForSelect();
$dialDef   = (string) ($paytech_phone_dial_default ?? '223');
$localVal  = (string) ($paytech_phone_local_value ?? '');
$idPx      = preg_replace('/[^a-z0-9_-]/i', '', (string) ($paytech_phone_id_prefix ?? 'pt'));
if ($idPx === '') {
    $idPx = 'pt';
}
$showSave   = ($paytech_phone_show_save ?? true) !== false;
$saveChk    = ($paytech_phone_save_checked ?? true) !== false;
$wrapMod    = $variant === 'mobile' ? 'mob' : 'desk';
?>
<div class="paytech-phone-block paytech-phone-block--<?= $e($wrapMod) ?>" data-paytech-phone>
    <p class="paytech-phone-block__title">Numéro Mobile Money</p>
    <p class="paytech-phone-block__hint"><?= $variant === 'mobile'
        ? 'Saisissez le numéro lié à votre compte Mobile Money (Orange, Moov ou Wave). Il doit correspondre à la ligne débitée lors de la confirmation du paiement.'
        : 'Obligatoire pour préremplir le formulaire de paiement, lier le paiement à votre ligne et limiter les litiges.' ?></p>
    <div class="paytech-phone-block__grid">
        <div class="paytech-phone-block__field">
            <label class="paytech-phone-block__label" for="<?= $e($idPx) ?>-dial">Indicatif pays</label>
            <select id="<?= $e($idPx) ?>-dial" name="paytech_dial" class="paytech-phone-block__select" required>
                <?php foreach ($dialOpts as $digits => $label): ?>
                <option value="<?= $e((string) $digits) ?>"<?= ((string) $digits === $dialDef) ? ' selected' : '' ?>><?= $e((string) $label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="paytech-phone-block__field paytech-phone-block__field--grow">
            <label class="paytech-phone-block__label" for="<?= $e($idPx) ?>-local">Numéro (sans indicatif)</label>
            <input type="tel"
                   id="<?= $e($idPx) ?>-local"
                   name="paytech_local"
                   class="paytech-phone-block__input"
                   inputmode="numeric"
                   autocomplete="tel-national"
                   required
                   minlength="6"
                   maxlength="12"
                   pattern="[0-9]{6,12}"
                   placeholder="Ex. 70000000"
                   value="<?= $e($localVal) ?>">
        </div>
    </div>
    <?php if ($showSave): ?>
    <label class="paytech-phone-block__save">
        <input type="checkbox" name="save_paytech_phone" value="1"<?= $saveChk ? ' checked' : '' ?>>
        <span>Enregistrer pour mes prochains paiements</span>
    </label>
    <?php endif; ?>
</div>
