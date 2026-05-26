<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$bp = $base_path ?? '/professeur';
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$exercice = $exercice ?? [];
$propExistante = $proposition_existante ?? null;
$propData = $prop_data ?? [];
$errors = $errors ?? [];
$csrf = \App\Core\Security::getCsrfField();
$exerciceId = (int) ($exercice['id'] ?? 0);
?>
<div class="etd-page">
    <div class="etd-page__header">
        <a href="<?= $baseUrl . $bp ?>/exercices-disponibles" class="cl-back" style="display:inline-flex;align-items:center;gap:.35rem;margin-bottom:.75rem;font-size:.85rem">← Exercices disponibles</a>
        <h1 class="etd-page__title">Proposer une correction</h1>
        <p class="etd-page__sub"><?= $e($exercice['titre'] ?? '') ?></p>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="etd-alert etd-alert--error"><ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="etd-form-block" style="margin-bottom:1rem">
        <p style="margin:0;font-size:.88rem;color:#475569;line-height:1.55"><?= nl2br($e(substr($exercice['description'] ?? '', 0, 600))) ?></p>
    </div>

    <?php if ($propExistante): ?>
    <p>Proposition déjà envoyée.</p>
    <a href="<?= $baseUrl . $bp ?>/exercices-disponibles" class="etd-btn etd-btn--ghost">Retour</a>
    <?php else: ?>
    <form method="post" action="<?= $baseUrl . $bp ?>/proposer-exercice/<?= $exerciceId ?>" class="etd-form-block prop-form">
        <?= $csrf ?>
        <?php require APP_PATH . '/Views/partials/proposition_form_fields.php'; ?>
        <div class="prop-form__actions" style="margin-top:1.25rem;display:flex;gap:.65rem">
            <button type="submit" class="etd-btn etd-btn--primary">Envoyer ma proposition</button>
            <a href="<?= $baseUrl . $bp ?>/exercices-disponibles" class="etd-btn etd-btn--ghost">Annuler</a>
        </div>
    </form>
    <?php endif; ?>
</div>
