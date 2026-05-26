<?php
declare(strict_types=1);
/**
 * Vignette ronde : photo (uploads ou image par défaut), initiales si photo personnalisée en échec, drapeau pays.
 *
 * Variables attendues : $baseUrl, $initials, $avatarBg, $avatarColumn (nullable), $pays (nullable), $alt (optionnel), $size sm|md|lg
 */
use App\Helpers\PublicUserPresentation;

$baseUrl      = rtrim((string) ($baseUrl ?? BASE_URL ?? ''), '/');
$initials     = (string) ($initials ?? '?');
$avatarBg     = (string) ($avatarBg ?? '#64748b');
$avatarColumn = $avatarColumn ?? null;
$pays         = $pays ?? null;
$alt          = (string) ($alt ?? '');
$size         = in_array(($size ?? 'md'), ['sm', 'md', 'lg'], true) ? ($size ?? 'md') : 'md';

$hasUpload = PublicUserPresentation::hasUploadedAvatar($avatarColumn === null ? null : (string) $avatarColumn);
$photoUrl  = PublicUserPresentation::publicAvatarUrl($avatarColumn === null ? null : (string) $avatarColumn, $baseUrl);
$flag      = PublicUserPresentation::countryFlagEmoji($pays === null ? null : (string) $pays);
$flagTitle = PublicUserPresentation::countryLabel($pays === null ? null : (string) $pays);
$e         = static fn (?string $s): string => \App\Core\Security::escape((string) ($s ?? ''));

$dim = $size === 'lg' ? 72 : ($size === 'sm' ? 44 : 56);
?>
<div class="public-user-thumb public-user-thumb--<?= $e($size) ?>" data-public-user-thumb>
    <div class="public-user-thumb__circle">
        <?php if ($hasUpload): ?>
        <span class="public-user-thumb__initials" style="background:<?= $e($avatarBg) ?>;color:#fff;" aria-hidden="true"><?= $e($initials) ?></span>
        <?php endif; ?>
        <img src="<?= $e($photoUrl) ?>"
             alt="<?= $e($alt) ?>"
             class="public-user-thumb__img<?= $hasUpload ? ' public-user-thumb__img--overlay' : '' ?>"
             width="<?= (int) $dim ?>"
             height="<?= (int) $dim ?>"
             loading="lazy"
             decoding="async"
             <?php if ($hasUpload): ?>onerror="this.classList.add('public-user-thumb__img--hidden');"<?php endif; ?>>
        <?php if ($flag !== ''): ?>
        <span class="public-user-thumb__flag" title="<?= $e($flagTitle) ?>"><?= $flag ?></span>
        <?php endif; ?>
    </div>
</div>
