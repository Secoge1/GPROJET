<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$demande = $demande ?? [];
$propExistante = $proposition_existante ?? null;
$propData = $prop_data ?? [];
$errors = $errors ?? [];
$competencesNoms = $competences_noms ?? [];
$csrf = \App\Core\Security::getCsrfField();
$demandeId = (int) ($demande['id'] ?? 0);
$demandesListUrl = $demandes_list_url ?? ($baseUrl . '/expert/demandes');
$formAction = $proposer_form_action ?? ($baseUrl . '/expert/proposer-demande/' . $demandeId);
?>
<section class="section-desktop page-expert page-expert-proposer">
    <div class="missions-header">
        <div class="missions-header__left">
            <a href="<?= $e($demandesListUrl) ?>" class="page-expert__back">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Demandes clients
            </a>
            <h1 class="missions-header__title">Proposer mes services</h1>
            <p class="missions-header__sub"><?= $e($demande['titre'] ?? '') ?></p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="cl-alert cl-alert--error" style="margin-bottom:1rem">
        <ul style="margin:0;padding-left:1.2rem"><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <div class="cl-card" style="margin-bottom:1rem">
        <h2 class="cl-card__title" style="margin:0 0 .75rem">Besoin du client</h2>
        <?php if (!empty($demande['competence_nom'])): ?>
        <p style="margin:0 0 .5rem;font-size:.85rem"><strong>Compétence :</strong> <?= $e($demande['competence_nom']) ?></p>
        <?php endif; ?>
        <?php if (!empty($demande['description'])): ?>
        <p style="margin:0;font-size:.88rem;color:#475569;line-height:1.55"><?= nl2br($e($demande['description'])) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($propExistante): ?>
    <div class="prop-list prop-list--sent">
        <p class="prop-list__sent-msg">Vous avez déjà envoyé une proposition (<?= $e($propExistante['statut'] ?? '') ?>).</p>
        <a href="<?= $e($demandesListUrl) ?>" class="cl-btn cl-btn--outline">Retour aux demandes</a>
    </div>
    <?php else: ?>
    <form method="post" action="<?= $e($formAction) ?>" class="cl-card prop-form">
        <?= $csrf ?>
        <?php if (!empty($competencesNoms)): ?>
        <p class="prop-form__prefill">Vos compétences : <?= $e(implode(', ', $competencesNoms)) ?></p>
        <?php endif; ?>
        <?php require APP_PATH . '/Views/partials/proposition_form_fields.php'; ?>
        <div class="prop-form__actions">
            <button type="submit" class="cl-btn cl-btn--amber">Envoyer ma proposition</button>
            <a href="<?= $e($demandesListUrl) ?>" class="cl-btn cl-btn--outline">Annuler</a>
        </div>
    </form>
    <?php endif; ?>
</section>
