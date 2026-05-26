<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$csrfField = \App\Core\Security::getCsrfField();
$e        = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$devise   = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$solde    = (float)($solde ?? 0);
$demandes = $demandes ?? [];
$errors   = $errors   ?? [];
$prefix   = $expert_path_prefix ?? '/expert';
$operateur = strtoupper(trim((string)($operateur ?? '')));
$opLabels  = ['ORANGE' => 'Orange Money', 'MOOV' => 'Moov Africa', 'WAVE' => 'Wave'];
$opLibelle = $opLabels[$operateur] ?? $operateur;

$statutLabels = [
    'en_attente' => ['label' => 'En attente',  'color' => '#f59e0b', 'bg' => '#fffbeb'],
    'traitee'    => ['label' => 'Traité',      'color' => '#16a34a', 'bg' => '#dcfce7'],
    'refusee'    => ['label' => 'Refusé',      'color' => '#ef4444', 'bg' => '#fef2f2'],
];
?>
<section class="section-desktop page-expert page-expert-retrait">

    <!-- En-tête -->
    <div class="page-expert__header">
        <a href="<?= $e($baseUrl . $prefix . '/retrait-choix') ?>" class="page-expert__back" aria-label="Retour au choix d’opérateur">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Opérateurs
        </a>
        <h1 class="page-expert__title">Demande de retrait</h1>
        <p class="page-expert__subtitle">Transférez vos gains vers votre compte <strong><?= $e($opLibelle) ?></strong>.</p>
    </div>

    <?php if ($operateur !== ''): ?>
    <div class="retrait-alert" role="status" style="background:#ecfdf7;border-color:#86efac;color:#166534;margin-bottom:1.25rem;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <span>Opérateur sélectionné : <strong><?= $e($opLibelle) ?></strong> — <a href="<?= $e($baseUrl . $prefix . '/retrait-choix') ?>" style="color:inherit;font-weight:600">Changer</a></span>
    </div>
    <?php endif; ?>

    <!-- Carte solde hero -->
    <div class="retrait-hero">
        <div class="retrait-hero__icon" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="5" width="20" height="14" rx="2"/>
                <line x1="2" y1="10" x2="22" y2="10"/>
            </svg>
        </div>
        <div class="retrait-hero__content">
            <span class="retrait-hero__label">Solde disponible</span>
            <span class="retrait-hero__amount"><?= number_format($solde, 0, ',', ' ') ?> <span class="retrait-hero__devise"><?= $e($devise) ?></span></span>
            <span class="retrait-hero__hint">Minimum de retrait : 500 <?= $e($devise) ?></span>
        </div>
        <?php if ($solde >= 500): ?>
        <div class="retrait-hero__badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
            Retrait possible
        </div>
        <?php else: ?>
        <div class="retrait-hero__badge retrait-hero__badge--warn">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            Solde insuffisant
        </div>
        <?php endif; ?>
    </div>

    <!-- Erreurs -->
    <?php if (!empty($errors)): ?>
    <div class="retrait-alert" role="alert">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <ul><?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <!-- Formulaire de retrait -->
    <div class="page-expert__card retrait-form-card">
        <div class="retrait-form-card__header">
            <div class="retrait-form-card__icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 5 5 12"/></svg>
            </div>
            <div>
                <h2 class="retrait-form-card__title">Nouveau retrait</h2>
                <p class="retrait-form-card__sub">Virement instantané via Mobile Money</p>
            </div>
        </div>

        <form method="post" action="<?= $e($baseUrl . $prefix . '/retrait' . ($operateur !== '' ? '?operateur=' . rawurlencode($operateur) : '')) ?>" class="retrait-form" <?= $solde < 500 ? 'aria-disabled="true"' : '' ?>>
            <?= $csrfField ?>
            <input type="hidden" name="operateur" value="<?= $e($operateur) ?>">

            <div class="retrait-form__grid">
                <div class="form-group retrait-form__field">
                    <label for="montant" class="retrait-form__label">
                        Montant à retirer
                        <span class="retrait-form__badge-devise"><?= $e($devise) ?></span>
                    </label>
                    <div class="retrait-form__input-wrap">
                        <span class="retrait-form__input-prefix">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        </span>
                        <input
                            type="number"
                            name="montant"
                            id="montant"
                            min="500"
                            max="<?= max(500, (int)$solde) ?>"
                            step="1"
                            required
                            placeholder="Ex. 5 000"
                            value="<?= $e($_POST['montant'] ?? '') ?>"
                            class="retrait-form__input"
                            <?= $solde < 500 ? 'disabled' : '' ?>
                        >
                    </div>
                    <span class="retrait-form__hint">Entre 500 et <?= number_format($solde, 0, ',', ' ') ?> <?= $e($devise) ?></span>
                </div>

                <div class="form-group retrait-form__field">
                    <label for="iban" class="retrait-form__label">
                        Numéro <?= $e($opLibelle) ?>
                        <span class="retrait-form__required" style="color:#ef4444;font-size:.75rem;">*&nbsp;Requis</span>
                    </label>
                    <div class="retrait-form__input-wrap">
                        <span class="retrait-form__input-prefix">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 11.23 19.79 19.79 0 0 1 1.61 2.6 2 2 0 0 1 3.6.42h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.05a16 16 0 0 0 6.09 6.09l1.71-1.71a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 14.92z"/></svg>
                        </span>
                        <input
                            type="text"
                            name="iban"
                            id="iban"
                            maxlength="34"
                            required
                            placeholder="Ex. +221 77 000 00 00"
                            value="<?= $e($_POST['iban'] ?? '') ?>"
                            class="retrait-form__input"
                            <?= $solde < 500 ? 'disabled' : '' ?>
                        >
                    </div>
                    <span class="retrait-form__hint">Numéro du compte <?= $e($opLibelle) ?> (indicatif pays inclus)</span>
                </div>
            </div>

            <div class="retrait-form__footer">
                <?php if ($solde >= 500): ?>
                <button type="submit" class="btn btn-primary retrait-form__submit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    Envoyer la demande de retrait
                </button>
                <?php else: ?>
                <button type="button" class="btn btn-primary retrait-form__submit" disabled style="opacity:.5;cursor:not-allowed;" aria-disabled="true">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                    Solde insuffisant
                </button>
                <?php endif; ?>
                <p class="retrait-form__security">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    Virement sécurisé — traitement sous 24–48 h ouvrées
                </p>
            </div>
        </form>
    </div>

    <!-- Historique des retraits -->
    <div class="page-expert__card">
        <div class="retrait-history__header">
            <h2 class="page-expert__card-title" style="margin:0;">Historique des retraits</h2>
            <?php if (!empty($demandes)): ?>
            <span class="retrait-history__count"><?= count($demandes) ?> demande<?= count($demandes) > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($demandes)): ?>
        <div class="retrait-history__empty">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            <p>Aucune demande de retrait pour l'instant.</p>
            <span>Vos demandes apparaîtront ici après votre premier retrait.</span>
        </div>
        <?php else: ?>
        <div class="retrait-history__table-wrap">
            <table class="retrait-history__table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Numéro Mobile Money</th>
                        <th class="retrait-col-right">Montant</th>
                        <th class="retrait-col-center">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($demandes as $d):
                        $st = $d['statut'] ?? 'en_attente';
                        $stInfo = $statutLabels[$st] ?? ['label' => ucfirst($st), 'color' => '#64748b', 'bg' => '#f1f5f9'];
                        $date = $d['created_at'] ?? '';
                        $dateFormatted = $date ? date('d/m/Y H:i', strtotime($date)) : '—';
                    ?>
                    <tr>
                        <td class="retrait-history__date">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px;color:#94a3b8"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <?= $e($dateFormatted) ?>
                        </td>
                        <td class="retrait-history__iban"><?= $e($d['iban'] ?? '—') ?></td>
                        <td class="retrait-col-right retrait-history__amount">
                            <?= number_format((float)($d['montant'] ?? 0), 0, ',', ' ') ?> <span style="color:#94a3b8;font-weight:400;font-size:.8rem"><?= $e($devise) ?></span>
                        </td>
                        <td class="retrait-col-center">
                            <span class="retrait-badge" style="color:<?= $stInfo['color'] ?>;background:<?= $stInfo['bg'] ?>;">
                                <?= $e($stInfo['label']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</section>
