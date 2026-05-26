<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
?>
<div id="cookie-consent-banner" class="cookie-banner" role="dialog" aria-label="Préférences cookies" aria-hidden="true" style="display:none;">
    <div class="cookie-banner-inner">
        <p class="cookie-banner-text">
            Nous utilisons des cookies pour le fonctionnement du site, l'analyse d'audience et la publicité (réseaux sociaux). 
            Vous pouvez accepter tout, refuser le non essentiel ou personnaliser.
            <a href="<?= $baseUrl ?>/home/confidentialite">En savoir plus</a>
        </p>
        <div class="cookie-banner-actions">
            <button type="button" id="cookie-accept-all" class="btn btn-primary btn-sm">Tout accepter</button>
            <button type="button" id="cookie-reject-all" class="btn btn-outline btn-sm">Refuser le non essentiel</button>
            <button type="button" id="cookie-customize" class="btn btn-outline btn-sm">Personnaliser</button>
        </div>
        <div id="cookie-customize-panel" class="cookie-customize-panel" style="display:none;">
            <label><input type="checkbox" id="cookie-pref-analytics" checked> Cookies d'analyse (Google Analytics)</label>
            <label><input type="checkbox" id="cookie-pref-marketing" checked> Cookies marketing (Facebook, LinkedIn, publicité)</label>
            <button type="button" id="cookie-save-prefs" class="btn btn-primary btn-sm">Enregistrer</button>
        </div>
    </div>
</div>
