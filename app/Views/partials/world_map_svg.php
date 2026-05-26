<?php
/**
 * Carte du monde SVG — 100% inline, aucune dépendance externe.
 * Variables attendues :
 *   $svgCountryData : array [CODE_ISO2 => nb_visites]
 *   $svgCountryNames : array [CODE_ISO2 => nom_pays]  (optionnel)
 *   $svgMapId : string  ID HTML unique (défaut 'svg-world-map')
 *   $svgHeight : int    hauteur px (défaut 340)
 */
$svgCountryData  = $svgCountryData  ?? [];
$svgCountryNames = $svgCountryNames ?? [];
$svgMapId        = $svgMapId        ?? 'svg-world-map';
$svgHeight       = $svgHeight       ?? 340;

$defaultNames = [
    'SN'=>'Sénégal','CI'=>'Côte d\'Ivoire','ML'=>'Mali','BJ'=>'Bénin',
    'TG'=>'Togo','GN'=>'Guinée','CM'=>'Cameroun','CD'=>'RD Congo','MG'=>'Madagascar',
    'NE'=>'Niger','TD'=>'Tchad','GH'=>'Ghana','NG'=>'Nigeria','KE'=>'Kenya','TZ'=>'Tanzanie',
    'ZA'=>'Afrique du Sud','RW'=>'Rwanda','UG'=>'Ouganda','ET'=>'Éthiopie','MZ'=>'Mozambique',
    'AO'=>'Angola','ZM'=>'Zambie','ZW'=>'Zimbabwe','MU'=>'Maurice','RE'=>'La Réunion',
    'MQ'=>'Martinique','GP'=>'Guadeloupe','GF'=>'Guyane','SL'=>'Sierra Leone',
    'FR'=>'France','BE'=>'Belgique','CH'=>'Suisse','DE'=>'Allemagne','ES'=>'Espagne',
    'IT'=>'Italie','GB'=>'Royaume-Uni','NL'=>'Pays-Bas','PT'=>'Portugal','PL'=>'Pologne',
    'RO'=>'Roumanie','SE'=>'Suède','NO'=>'Norvège','DK'=>'Danemark','FI'=>'Finlande',
    'CA'=>'Canada','US'=>'États-Unis','MX'=>'Mexique','BR'=>'Brésil','AR'=>'Argentine',
    'CO'=>'Colombie','PE'=>'Pérou','CL'=>'Chili','VE'=>'Venezuela',
    'MA'=>'Maroc','DZ'=>'Algérie','TN'=>'Tunisie','EG'=>'Égypte','LY'=>'Libye',
    'CN'=>'Chine','JP'=>'Japon','IN'=>'Inde','KR'=>'Corée du Sud','SG'=>'Singapour',
    'AU'=>'Australie','NZ'=>'Nouvelle-Zélande','ID'=>'Indonésie','PH'=>'Philippines',
    'RU'=>'Russie','TR'=>'Turquie','SA'=>'Arabie Saoudite','AE'=>'Émirats Arabes',
    'IL'=>'Israël','IR'=>'Iran','IQ'=>'Irak',
];
$names = array_merge($defaultNames, $svgCountryNames);

// Coordonnées [lat, lng] par pays
$coords = [
    'SN'=>[14.69,-14.45],'CI'=>[7.53,-5.55],'ML'=>[17.57,-3.99],'BJ'=>[9.31,2.32],
    'TG'=>[8.62,0.82],'GN'=>[11.0,-10.94],'CM'=>[3.85,11.5],
    'CD'=>[-4.04,21.76],'MG'=>[-18.77,46.87],'NE'=>[17.61,8.08],'TD'=>[15.45,18.73],
    'GH'=>[7.95,-1.02],'NG'=>[9.08,8.67],'KE'=>[-1.28,36.82],'TZ'=>[-6.37,34.89],
    'ZA'=>[-30.56,22.94],'RW'=>[-1.94,29.87],'UG'=>[1.37,32.29],'ET'=>[9.15,40.49],
    'MZ'=>[-18.67,35.53],'AO'=>[-11.2,17.87],'ZM'=>[-13.13,27.85],'ZW'=>[-19.01,29.15],
    'MU'=>[-20.35,57.55],'RE'=>[-21.12,55.54],'MQ'=>[14.64,-60.98],'GP'=>[16.27,-61.55],
    'GF'=>[3.93,-53.13],'SL'=>[8.46,-11.78],
    'FR'=>[46.23,2.21],'BE'=>[50.5,4.47],'CH'=>[46.82,8.23],'DE'=>[51.17,10.45],
    'ES'=>[40.46,-3.75],'IT'=>[41.87,12.57],'GB'=>[55.38,-3.44],'NL'=>[52.13,5.29],
    'PT'=>[39.4,-8.22],'PL'=>[51.92,19.15],'RO'=>[45.94,24.97],'SE'=>[60.13,18.64],
    'NO'=>[60.47,8.47],'DK'=>[56.26,9.50],'FI'=>[61.92,25.74],
    'CA'=>[56.13,-106.35],'US'=>[37.09,-95.71],'MX'=>[23.63,-102.55],
    'BR'=>[-14.24,-51.93],'AR'=>[-38.42,-63.62],'CO'=>[4.57,-74.3],
    'PE'=>[-9.19,-75.02],'CL'=>[-35.68,-71.54],'VE'=>[6.42,-66.59],
    'MA'=>[31.79,-7.09],'DZ'=>[28.03,1.66],'TN'=>[33.89,9.54],'EG'=>[26.82,30.8],
    'LY'=>[26.34,17.23],'SD'=>[12.86,30.22],
    'CN'=>[35.86,104.2],'JP'=>[36.2,138.25],'IN'=>[20.59,78.96],'KR'=>[35.91,127.77],
    'SG'=>[1.35,103.82],'AU'=>[-25.27,133.78],'NZ'=>[-40.9,174.89],
    'ID'=>[-0.79,113.92],'PH'=>[12.88,121.77],'TH'=>[15.87,100.99],
    'VN'=>[14.06,108.28],'MY'=>[4.21,101.97],
    'RU'=>[61.52,105.32],'TR'=>[38.96,35.24],'SA'=>[23.89,45.08],'AE'=>[23.42,53.85],
    'IL'=>[31.05,34.85],'IR'=>[32.43,53.69],'IQ'=>[33.22,43.68],
    'PK'=>[30.38,69.35],'BD'=>[23.68,90.36],
];

// Projection équirectangulaire -> SVG viewBox 1000x500
function svgXY(float $lat, float $lng): array {
    return [round(($lng + 180) / 360 * 1000, 1), round((90 - $lat) / 180 * 500, 1)];
}

$maxCount = max(1, max(array_values($svgCountryData) ?: [1]));

// Préparer les points pour le JS tooltip
$dotData = [];
foreach ($svgCountryData as $code => $count) {
    $code = strtoupper($code);
    if (!isset($coords[$code])) continue;
    [$lat, $lng] = $coords[$code];
    [$cx, $cy] = svgXY($lat, $lng);
    $r = max(5, min(22, 5 + ($count / $maxCount) * 17));
    $dotData[] = compact('code', 'count', 'cx', 'cy', 'r');
}
?>
<div class="svgmap-container" id="<?= htmlspecialchars($svgMapId) ?>" style="position:relative;">
    <svg viewBox="0 0 1000 500" xmlns="http://www.w3.org/2000/svg"
         style="width:100%;height:<?= $svgHeight ?>px;display:block;border-radius:0 0 0 12px;"
         preserveAspectRatio="xMidYMid meet">

        <!-- Océan -->
        <rect fill="#d6eaf8" width="1000" height="500"/>

        <!-- ── Groenland ── -->
        <path fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.6"
              d="M395,18 L460,20 L468,36 L455,55 L430,65 L380,62 L370,45 Z"/>

        <!-- ── Amérique du Nord ── -->
        <path fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.6"
              d="M33,55 L55,80 L80,95 L155,108 L158,145 L210,192 L255,205 L265,222 L275,222
                 L278,180 L292,155 L318,128 L350,118 L352,92 L300,70 L265,55 L230,50
                 L200,48 L165,47 L130,55 L90,55 Z"/>

        <!-- ── Amérique Centrale & Caraïbes (simplifié) ── -->
        <path fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.6"
              d="M255,205 L265,222 L270,228 L260,230 L250,225 L245,215 Z"/>

        <!-- ── Amérique du Sud ── -->
        <path fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.6"
              d="M290,222 L335,215 L405,255 L408,280 L395,310 L358,342 L322,398 L295,395
                 L290,365 L278,255 Z"/>

        <!-- ── Europe ── -->
        <path fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.6"
              d="M475,148 L500,145 L540,130 L545,145 L562,144 L600,148 L575,125
                 L588,118 L583,82 L568,65 L545,52 L520,58 L490,88 L486,110 L475,128 Z"/>

        <!-- ── Grande-Bretagne (île) ── -->
        <path fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.6"
              d="M482,108 L490,88 L495,82 L500,100 L494,112 Z"/>

        <!-- ── Islande ── -->
        <ellipse fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.6" cx="408" cy="75" rx="18" ry="10"/>

        <!-- ── Afrique ── -->
        <path fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.6"
              d="M483,148 L502,145 L538,155 L570,162 L596,165 L608,175 L642,218
                 L618,250 L612,278 L602,318 L592,340 L552,344 L528,295 L522,242
                 L508,230 L488,233 L462,230 L452,210 L453,188 Z"/>

        <!-- ── Madagascar ── -->
        <path fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.6"
              d="M625,285 L634,300 L636,320 L625,325 L618,308 Z"/>

        <!-- ── Asie (masse principale) ── -->
        <path fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.6"
              d="M600,148 L620,140 L650,158 L660,162 L695,170 L720,183 L725,228
                 L790,245 L820,230 L850,210 L840,185 L882,152 L898,110 L970,68
                 L900,55 L860,62 L780,58 L700,58 L645,62 L583,60 L578,82 L585,118
                 L572,128 Z"/>

        <!-- ── Péninsule indochinoise & Malaisie ── -->
        <path fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.6"
              d="M790,245 L812,258 L820,268 L808,272 L795,258 Z"/>

        <!-- ── Sri Lanka ── -->
        <ellipse fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.5" cx="728" cy="234" rx="5" ry="8"/>

        <!-- ── Japon ── -->
        <path fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.6"
              d="M868,128 L878,138 L885,155 L875,158 L865,148 Z"/>

        <!-- ── Australie ── -->
        <path fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.6"
              d="M653,305 L755,295 L910,320 L918,378 L885,418 L835,430
                 L782,412 L752,372 L742,340 L700,318 Z"/>

        <!-- ── Nouvelle-Zélande ── -->
        <path fill="#dde8d4" stroke="#c5d8b5" stroke-width="0.6"
              d="M965,362 L975,345 L982,355 L978,372 Z"/>

        <!-- ══ Cercles pays visiteurs (PHP) ══ -->
        <?php foreach ($dotData as $d):
            $pulse = $d['count'] >= $maxCount * 0.25;
            $opacity = round(0.55 + ($d['count'] / $maxCount) * 0.35, 2);
        ?>
        <?php if ($pulse): ?>
        <circle cx="<?= $d['cx'] ?>" cy="<?= $d['cy'] ?>" r="<?= $d['r'] + 7 ?>"
                fill="none" stroke="#16a34a" stroke-width="1.2" opacity="0.4"
                class="svgmap-pulse"/>
        <?php endif; ?>
        <circle cx="<?= $d['cx'] ?>" cy="<?= $d['cy'] ?>" r="<?= $d['r'] ?>"
                fill="#22c55e" stroke="#15803d" stroke-width="0.8"
                opacity="<?= $opacity ?>"
                class="svgmap-dot"
                data-code="<?= htmlspecialchars($d['code']) ?>"
                data-count="<?= $d['count'] ?>"
                data-name="<?= htmlspecialchars($names[$d['code']] ?? $d['code']) ?>"
                style="cursor:pointer;"/>
        <?php endforeach; ?>

    </svg>

    <!-- Tooltip -->
    <div id="<?= htmlspecialchars($svgMapId) ?>-tip" class="svgmap-tip" style="display:none;"></div>
</div>

<style>
.svgmap-container { position:relative; overflow:hidden; }
.svgmap-dot { transition: r .15s, opacity .15s; }
.svgmap-dot:hover { filter: brightness(1.3); }
.svgmap-tip {
    position:absolute;
    background:#0f172a;
    color:#f1f5f9;
    font-size:12px;
    padding:6px 10px;
    border-radius:7px;
    pointer-events:none;
    white-space:nowrap;
    z-index:10;
    box-shadow:0 4px 14px rgba(0,0,0,.25);
    font-family:'Plus Jakarta Sans',sans-serif;
}
@keyframes svgPulse {
    0%   { opacity:.45; transform:scale(1); }
    70%  { opacity:0;   transform:scale(1.8); }
    100% { opacity:0; }
}
.svgmap-pulse {
    animation: svgPulse 2.4s ease-out infinite;
    transform-box: fill-box;
    transform-origin: center;
}
</style>
<script>
(function () {
    var mapEl = document.getElementById(<?= json_encode($svgMapId) ?>);
    if (!mapEl) return;
    var tip = document.getElementById(<?= json_encode($svgMapId . '-tip') ?>);
    var dots = mapEl.querySelectorAll('.svgmap-dot');
    dots.forEach(function (dot) {
        dot.addEventListener('mouseenter', function (e) {
            var name  = dot.getAttribute('data-name') || dot.getAttribute('data-code');
            var count = dot.getAttribute('data-count');
            tip.innerHTML = '<strong>' + name + '</strong> — ' + count + ' visite(s)';
            tip.style.display = 'block';
        });
        dot.addEventListener('mousemove', function (e) {
            var rect = mapEl.getBoundingClientRect();
            var x = e.clientX - rect.left + 12;
            var y = e.clientY - rect.top - 30;
            tip.style.left = x + 'px';
            tip.style.top  = y + 'px';
        });
        dot.addEventListener('mouseleave', function () {
            tip.style.display = 'none';
        });
    });
})();
</script>
