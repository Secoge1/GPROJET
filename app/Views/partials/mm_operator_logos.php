<?php
/**
 * Bandeau logos opérateurs Mobile Money (Orange Money, Moov, Wave).
 * Variables optionnelles avant include :
 *   $baseUrl            — URL de base (sinon BASE_URL)
 *   $mm_logo_size       — 'xs' | 'sm' | 'md' | 'lg' (hauteur des logos)
 *   $mm_logo_wrap_class — classes CSS du conteneur (défaut : mm-operator-logos)
 *   $mm_logo_wrap_style — styles inline complets du conteneur (écrase le défaut si fourni non vide)
 *   $mm_logo_height     — hauteur CSS explicite (ex. '34px'), prioritaire sur $mm_logo_size
 */
$b = rtrim((string) ($baseUrl ?? (defined('BASE_URL') ? BASE_URL : '')), '/');
$esc = static fn(string $v): string => \App\Core\Security::escape($v);
$pubOp = (defined('PUBLIC_PATH') && is_string(PUBLIC_PATH)) ? rtrim(PUBLIC_PATH, '/\\') . '/assets/images/operators' : '';
$sufPng = ($pubOp !== '' && is_file($pubOp . '/orange-money.png') && is_file($pubOp . '/moov-africa.png') && is_file($pubOp . '/wave.png')) ? '.png' : '.svg';
$heights = ['xs' => '28px', 'sm' => '30px', 'md' => '42px', 'lg' => '52px'];
$mmSize = $mm_logo_size ?? 'md';
$h = isset($mm_logo_height) && $mm_logo_height !== '' ? (string) $mm_logo_height : ($heights[$mmSize] ?? $heights['md']);
$wrapClass = $mm_logo_wrap_class ?? 'mm-operator-logos';
$defaultStyle = 'display:flex;flex-wrap:wrap;align-items:center;gap:.75rem;';
$customWrap = isset($mm_logo_wrap_style) ? trim((string) $mm_logo_wrap_style) : '';
if ($customWrap !== '') {
    $wrapStyle = $customWrap;
} elseif (!empty($mm_logo_no_default_flex)) {
    $wrapStyle = '';
} else {
    $wrapStyle = $defaultStyle;
}
?>
<div class="<?= $esc(trim($wrapClass . ' mm-operator-logos--intouch')) ?>" role="group" aria-label="Opérateurs Mobile Money acceptés"<?= $wrapStyle !== '' ? ' style="' . $esc($wrapStyle) . '"' : '' ?>>
    <img src="<?= $esc($b) ?>/assets/images/operators/orange-money<?= $esc($sufPng) ?>" alt="Orange Money" width="140" height="56" style="height:<?= $esc($h) ?>;width:auto;max-width:min(180px,42vw);border:1px solid #e2e8f0;background:#fff;padding:5px 12px;object-fit:contain;object-position:center;box-sizing:content-box;border-radius:12px;">
    <img src="<?= $esc($b) ?>/assets/images/operators/moov-africa<?= $esc($sufPng) ?>" alt="Moov Africa" width="140" height="56" style="height:<?= $esc($h) ?>;width:auto;max-width:min(180px,42vw);border:1px solid #e2e8f0;background:#fff;padding:5px 12px;object-fit:contain;object-position:center;box-sizing:content-box;border-radius:12px;">
    <img src="<?= $esc($b) ?>/assets/images/operators/wave<?= $esc($sufPng) ?>" alt="Wave" width="140" height="56" style="height:<?= $esc($h) ?>;width:auto;max-width:min(180px,42vw);border:1px solid #e2e8f0;background:#fff;padding:5px 12px;object-fit:contain;object-position:center;box-sizing:content-box;border-radius:14px;">
</div>
