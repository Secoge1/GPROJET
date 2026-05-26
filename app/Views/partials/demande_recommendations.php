<?php
declare(strict_types=1);
/**
 * Bloc suggestions (experts, profs, missions proches, liens).
 * Variables : $demande, $demande_recommendations, $demande_welcome_hint, $baseUrl, $client_base_path (/app ou /client)
 */
$e = static fn (?string $s): string => \App\Core\Security::escape((string) ($s ?? ''));
$reco = $demande_recommendations ?? null;
$welcome = !empty($demande_welcome_hint);
$bp = (string) ($client_base_path ?? '/client');
$baseUrl = rtrim((string) ($baseUrl ?? BASE_URL ?? ''), '/');
$demande = $demande ?? [];
if (($demande['statut'] ?? '') !== 'ouverte' || !\is_array($reco)) {
    return;
}
$isApp = ($bp === '/app');
$experts = $reco['experts'] ?? [];
$profs = $reco['professeurs'] ?? [];
$similar = $reco['similar_demandes'] ?? [];
$links = $reco['service_links'] ?? [];
$inferred = !empty($reco['competence_inferred']);
$demandeId = (int) ($demande['id'] ?? 0);
?>
<section class="demande-reco" aria-labelledby="demande-reco-title">
    <?php if ($welcome): ?>
    <div class="demande-reco__welcome" role="status">
        <strong>Demande enregistrée.</strong>
        Voici des suggestions adaptées à votre besoin<?= $inferred ? ' (catégorie déduite de votre texte)' : '' ?>.
    </div>
    <?php endif; ?>
    <h2 id="demande-reco-title" class="demande-reco__title">Suggestions pour vous</h2>

    <?php if (!empty($experts)): ?>
    <div class="demande-reco__block">
        <h3 class="demande-reco__subtitle">Experts à considérer</h3>
        <ul class="demande-reco__list">
            <?php foreach ($experts as $ex):
                $slugRaw = (string) ($ex['slug'] ?? ('expert-' . (int) ($ex['id'] ?? 0)));
                $exId = (int) ($ex['id'] ?? 0);
                $href = $isApp
                    ? $baseUrl . '/app/experts/' . $exId
                    : $baseUrl . '/expert/' . rawurlencode($slugRaw);
                $name = trim(($ex['prenom'] ?? '') . ' ' . ($ex['nom'] ?? ''));
                $top = !empty($ex['recommendation_is_top']);
                $score = (string) ($ex['recommendation_score'] ?? '');
                $reason = trim((string) ($ex['recommendation_reason'] ?? ''));
            ?>
            <li class="demande-reco__item">
                <a href="<?= $href ?>" class="demande-reco__link">
                    <span class="demande-reco__name"><?= $e($name !== '' ? $name : ($ex['titre'] ?? 'Expert')) ?></span>
                    <?php if ($top): ?><span class="demande-reco__pill">Top</span><?php endif; ?>
                    <?php if ($score !== ''): ?><span class="demande-reco__score"><?= $e($score) ?>/100</span><?php endif; ?>
                </a>
                <?php if ($reason !== ''): ?><p class="demande-reco__reason"><?= $e($reason) ?></p><?php endif; ?>
                <a href="<?= $e($baseUrl . $bp . '/reserver/' . $demandeId) ?>" class="demande-reco__cta">Réserver cet expert</a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($profs)): ?>
    <div class="demande-reco__block">
        <h3 class="demande-reco__subtitle">Professeurs proches de votre sujet</h3>
        <ul class="demande-reco__list demande-reco__list--compact">
            <?php foreach ($profs as $pr):
                $pid = (int) ($pr['id'] ?? 0);
                $href = $isApp ? $baseUrl . '/app/professeurs/' . $pid : $baseUrl . '/professeurs/show/' . $pid;
                $name = trim(($pr['prenom'] ?? '') . ' ' . ($pr['nom'] ?? ''));
            ?>
            <li class="demande-reco__item">
                <a href="<?= $e($href) ?>" class="demande-reco__link"><?= $e($name !== '' ? $name : ($pr['titre'] ?? 'Professeur')) ?></a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($similar)): ?>
    <div class="demande-reco__block">
        <h3 class="demande-reco__subtitle">Autres besoins récents sur la plateforme</h3>
        <p class="demande-reco__hint">Exemples de demandes ouvertes (domaine proche). Accédez aux experts du même secteur.</p>
        <ul class="demande-reco__list demande-reco__list--compact">
            <?php foreach ($similar as $s):
                $compId = (int) ($s['competence_id'] ?? 0);
                $explore = $baseUrl . '/experts' . ($compId > 0 ? '?competence=' . $compId : '');
            ?>
            <li class="demande-reco__item">
                <span class="demande-reco__sim-title"><?= $e($s['titre'] ?? '') ?></span>
                <?php if (!empty($s['competence_nom'])): ?>
                <span class="demande-reco__sim-meta"><?= $e((string) $s['competence_nom']) ?></span>
                <?php endif; ?>
                <a href="<?= $e($explore) ?>" class="demande-reco__cta demande-reco__cta--inline">Voir des experts</a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (\is_array($links) && $links !== []): ?>
    <div class="demande-reco__block demande-reco__block--links">
        <h3 class="demande-reco__subtitle">Pour aller plus loin</h3>
        <div class="demande-reco__chips">
            <?php foreach ($links as $lnk):
                $path = (string) ($lnk['path'] ?? '/');
                if ($path === '' || $path[0] !== '/') {
                    $path = '/' . ltrim($path, '/');
                }
            ?>
            <a href="<?= $e($baseUrl . $path) ?>" class="demande-reco__chip"><?= $e((string) ($lnk['label'] ?? 'Lien')) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</section>
