<?php
/** Champs formulaire proposition (expert / professeur). */
$propData = $prop_data ?? [];
$e = fn($s) => \App\Core\Security::escape($s ?? '');
?>
<div class="prop-form__grid">
    <div class="prop-form__field prop-form__field--full">
        <label for="prop-presentation">Présentation courte <span class="prop-form__req">*</span></label>
        <input type="text" id="prop-presentation" name="presentation" maxlength="500" required
               value="<?= $e($propData['presentation'] ?? '') ?>"
               placeholder="Ex. : Expert Excel certifié, 8 ans d'expérience">
    </div>
    <div class="prop-form__field">
        <label for="prop-tarif">Tarif proposé (FCFA) <span class="prop-form__req">*</span></label>
        <input type="number" id="prop-tarif" name="tarif_propose" min="500" max="9999999" step="100" required
               value="<?= $e((string) ($propData['tarif_propose'] ?? '')) ?>"
               placeholder="Ex. 15000">
        <span class="prop-form__hint">Forfait total pour la mission (FCFA)</span>
    </div>
    <div class="prop-form__field">
        <label for="prop-delai">Délai (jours) <span class="prop-form__req">*</span></label>
        <input type="number" id="prop-delai" name="delai_jours" min="1" max="90" required
               value="<?= $e((string) ($propData['delai_jours'] ?? '3')) ?>">
        <span class="prop-form__hint">Délai estimé pour démarrer / livrer</span>
    </div>
    <div class="prop-form__field prop-form__field--full">
        <label for="prop-competences">Compétences clés</label>
        <input type="text" id="prop-competences" name="competences_cles" maxlength="500"
               value="<?= $e($propData['competences_cles'] ?? '') ?>"
               placeholder="Ex. : Comptabilité, Excel, fiscalité OHADA">
    </div>
    <div class="prop-form__field prop-form__field--full">
        <label for="prop-message">Message détaillé <span class="prop-form__req">*</span></label>
        <textarea id="prop-message" name="message" rows="5" maxlength="5000" required
                  placeholder="Décrivez votre approche, ce que vous proposez, vos garanties…"><?= $e($propData['message'] ?? '') ?></textarea>
    </div>
</div>
