<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$solde        = (float)($solde ?? 0);
$totalGains   = (float)($totalGains ?? 0);
$transactions = $transactions ?? [];
$devise       = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$prefix       = $expert_path_prefix ?? '/expert';

$typeConfig = [
    'paiement_session' => ['label' => 'Paiement mission', 'icon' => '#22c55e', 'bg' => '#dcfce7', 'sign' => '+'],
    'paiement'    => ['label' => 'Paiement reçu',    'icon' => '#22c55e', 'bg' => '#dcfce7', 'sign' => '+'],
    'commission'  => ['label' => 'Commission',        'icon' => '#ef4444', 'bg' => '#fef2f2', 'sign' => '−'],
    'retrait'     => ['label' => 'Retrait',           'icon' => '#3b82f6', 'bg' => '#eff6ff', 'sign' => '−'],
    'remboursement'=> ['label' => 'Remboursement',   'icon' => '#f59e0b', 'bg' => '#fffbeb', 'sign' => '−'],
    'depot'       => ['label' => 'Dépôt',             'icon' => '#8b5cf6', 'bg' => '#f5f3ff', 'sign' => '+'],
];
$statutConfig = [
    'effectue'   => ['label' => 'Effectué',   'color' => '#16a34a', 'bg' => '#dcfce7'],
    'en_attente' => ['label' => 'En attente', 'color' => '#b45309', 'bg' => '#fffbeb'],
    'bloque'     => ['label' => 'Bloqué',     'color' => '#6b7280', 'bg' => '#f1f5f9'],
    'echoue'     => ['label' => 'Échoué',     'color' => '#ef4444', 'bg' => '#fef2f2'],
    'rembourse'  => ['label' => 'Remboursé',  'color' => '#f59e0b', 'bg' => '#fffbeb'],
];
?>
<section class="section-desktop page-expert page-expert-revenus">

    <!-- En-tête -->
    <div class="missions-header">
        <div class="missions-header__left">
            <a href="<?= $baseUrl ?>/expert" class="page-expert__back" aria-label="Retour au tableau de bord">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Tableau de bord
            </a>
            <div class="missions-header__title-wrap">
                <div class="missions-header__icon" aria-hidden="true">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                </div>
                <div>
                    <h1 class="missions-header__title">Mes revenus</h1>
                    <p class="missions-header__sub">Solde disponible et historique de vos transactions.</p>
                </div>
            </div>
        </div>
        <a href="<?= $e($baseUrl . $prefix . '/retrait-choix') ?>" class="revenus-retrait-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 11 21 7 17 3"/><line x1="21" y1="7" x2="9" y2="7"/><path d="M3 21v-4a4 4 0 0 1 4-4h14"/></svg>
            Demander un retrait
        </a>
    </div>

    <!-- KPI Cards -->
    <div class="revenus-kpi-grid">
        <!-- Solde disponible -->
        <div class="revenus-kpi-card revenus-kpi-card--primary">
            <div class="revenus-kpi-card__icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            </div>
            <div class="revenus-kpi-card__content">
                <span class="revenus-kpi-card__label">Solde disponible</span>
                <span class="revenus-kpi-card__value"><?= number_format($solde, 0, ',', ' ') ?></span>
                <span class="revenus-kpi-card__devise"><?= $e($devise) ?></span>
            </div>
            <?php if ($solde >= 10): ?>
            <div class="revenus-kpi-card__tag">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Retrait possible
            </div>
            <?php endif; ?>
        </div>

        <!-- Total gains -->
        <div class="revenus-kpi-card revenus-kpi-card--gains">
            <div class="revenus-kpi-card__icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
            </div>
            <div class="revenus-kpi-card__content">
                <span class="revenus-kpi-card__label">Total des gains</span>
                <span class="revenus-kpi-card__value"><?= number_format($totalGains, 0, ',', ' ') ?></span>
                <span class="revenus-kpi-card__devise"><?= $e($devise) ?></span>
            </div>
            <?php if (count($transactions) > 0): ?>
            <div class="revenus-kpi-card__tag">
                <?= count($transactions) ?> transaction<?= count($transactions) > 1 ? 's' : '' ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php
    $waveDepotsRv = $wave_depots ?? [];
    $paytechOkRv = !empty($paytech_configured);
    $mmFallbackRv = isset($mm_fallback_deposit_url) && (string) $mm_fallback_deposit_url !== '' ? (string) $mm_fallback_deposit_url : null;
    ?>
    <!-- Mobile Money — crédit portefeuille expert -->
    <div style="margin:0 0 1.75rem;padding:1.15rem 1.25rem;border-radius:14px;border:1px solid #99f6e4;background:#f0fdfa;box-sizing:border-box;">
        <div style="display:flex;align-items:flex-start;gap:.65rem;margin-bottom:.65rem;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            <div>
                <h2 style="margin:0 0 .2rem;font-size:1.02rem;font-weight:700;color:#0f766e;">Créditer mon portefeuille</h2>
                <p style="margin:0;font-size:.82rem;color:#475569;line-height:1.45;">Recharge en Mobile Money (XOF)&nbsp;: Service de paiement en priorité, ou repli direct si configuré.</p>
            </div>
        </div>
        <?php if ($paytechOkRv): ?>
        <p style="margin:0 0 .75rem;font-size:.8rem;color:#334155;line-height:1.5;">
            Paiement Mobile Money sécurisé — confirmation automatique après transaction.
        </p>
        <div style="display:flex;flex-wrap:wrap;gap:.6rem .9rem;align-items:center;">
            <a href="<?= $baseUrl ?>/paytech/depot" style="display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.1rem;background:#0d9488;color:#fff;border-radius:9px;font-weight:600;font-size:.86rem;text-decoration:none;">
                Recharger via Mobile Money
            </a>
            <a href="<?= $baseUrl ?>/paytech/historique" style="font-size:.84rem;color:#0d9488;font-weight:600;text-decoration:none;">Historique des paiements →</a>
        </div>
        <?php elseif ($mmFallbackRv !== null): ?>
        <div style="display:flex;flex-wrap:wrap;gap:.6rem .9rem;align-items:center;">
            <a href="<?= $e($mmFallbackRv) ?>" style="display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.1rem;background:#475569;color:#fff;border-radius:9px;font-weight:600;font-size:.86rem;text-decoration:none;">
                Recharger en Mobile Money
            </a>
        </div>
        <p style="margin:.65rem 0 0;font-size:.78rem;color:#64748b;">Pour activer la recharge Mobile Money directe, configurez les paramètres de paiement sur le serveur.</p>
        <?php else: ?>
        <div style="font-size:.84rem;color:#92400e;background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:.8rem .95rem;">
            Aucune passerelle de dépôt configurée. Contactez l'administrateur pour activer le service de paiement Mobile Money.
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($waveDepotsRv)): ?>
    <div class="revenus-history" style="margin-bottom:1.75rem;">
        <div class="revenus-history__header">
            <h2 class="revenus-history__title">Dépôts Mobile Money récents</h2>
            <span class="revenus-history__count"><?= count($waveDepotsRv) ?></span>
        </div>
        <div class="revenus-table-wrap">
            <table class="revenus-table">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th class="revenus-col-right">Montant</th>
                        <th class="revenus-col-center">Statut</th>
                        <th class="revenus-col-right">Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($waveDepotsRv as $wd):
                        $st = strtolower((string) ($wd['status'] ?? ''));
                        if ($st === 'success' || $st === 'successful') {
                            $stLabel = 'Payé';
                        } elseif ($st === 'failed' || $st === 'failure') {
                            $stLabel = 'Échoué';
                        } elseif ($st === 'pending') {
                            $stLabel = 'En attente';
                        } elseif ($st !== '') {
                            $stLabel = $st;
                        } else {
                            $stLabel = '—';
                        }
                    ?>
                    <tr class="revenus-table__row">
                        <td style="font-family:monospace;font-size:.8rem;"><?= $e((string) ($wd['payment_id'] ?? '')) ?></td>
                        <td class="revenus-col-right revenus-montant--credit"><?= number_format((float) ($wd['amount'] ?? 0), 0, ',', ' ') ?> <?= $e($devise) ?></td>
                        <td class="revenus-col-center"><span class="revenus-badge"><?= $e($stLabel) ?></span></td>
                        <td class="revenus-col-right revenus-table__date"><?= !empty($wd['created_at']) ? date('d/m/Y H:i', strtotime($wd['created_at'])) : '—' ?></td>
                        <td class="revenus-col-center"><?php if ($st === 'pending'): $pr = strtolower((string) ($wd['provider'] ?? '')); $hrefFu = ($pr === 'paytech') ? ($baseUrl . '/paytech/historique') : ($baseUrl . '/intouch/verification/' . rawurlencode((string) ($wd['payment_id'] ?? ''))); ?>
                            <a href="<?= $e($hrefFu) ?>" class="revenus-retrait-btn" style="padding:.35rem .7rem;font-size:.76rem;text-decoration:none;">Suivre</a>
                        <?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Historique -->
    <div class="revenus-history">
        <div class="revenus-history__header">
            <h2 class="revenus-history__title">Historique des transactions</h2>
            <?php if (!empty($transactions)): ?>
            <span class="revenus-history__count"><?= count($transactions) ?> au total</span>
            <?php endif; ?>
        </div>

        <?php if (empty($transactions)): ?>
        <div class="missions-empty" style="border-style:dashed;">
            <div class="missions-empty__icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
            <h3 class="missions-empty__title">Aucune transaction</h3>
            <p class="missions-empty__text">Vos paiements et retraits apparaîtront ici au fil de vos missions.</p>
        </div>
        <?php else: ?>
        <div class="revenus-table-wrap">
            <table class="revenus-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="revenus-col-right">Montant</th>
                        <th class="revenus-col-center">Statut</th>
                        <th class="revenus-col-right">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t):
                        $type     = $t['type'] ?? 'paiement';
                        $tConf    = $typeConfig[$type] ?? ['label' => ucfirst($type), 'icon' => '#64748b', 'bg' => '#f8fafc', 'sign' => ''];
                        $statut   = $t['statut'] ?? 'effectue';
                        $sConf    = $statutConfig[$statut] ?? ['label' => ucfirst($statut), 'color' => '#64748b', 'bg' => '#f8fafc'];
                        $montant  = (float)($t['montant_net_expert'] ?? $t['montant'] ?? 0);
                        $date     = !empty($t['created_at']) ? date('d/m/Y', strtotime($t['created_at'])) : '—';
                        $isCredit = $tConf['sign'] === '+';
                    ?>
                    <tr class="revenus-table__row">
                        <td>
                            <div class="revenus-type-cell">
                                <span class="revenus-type-icon" style="background:<?= $tConf['bg'] ?>;color:<?= $tConf['icon'] ?>;">
                                    <?php if ($type === 'paiement' || $type === 'paiement_session' || $type === 'depot'): ?>
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <?php elseif ($type === 'retrait'): ?>
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 11 21 7 17 3"/><line x1="21" y1="7" x2="9" y2="7"/><path d="M3 21v-4a4 4 0 0 1 4-4h14"/></svg>
                                    <?php else: ?>
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                    <?php endif; ?>
                                </span>
                                <span class="revenus-type-label"><?= $e($tConf['label']) ?></span>
                            </div>
                        </td>
                        <td class="revenus-table__desc">
                            <?php if (!empty($t['reservation_id'])): ?>
                            Mission #<?= (int)$t['reservation_id'] ?>
                            <?php else: ?>
                            <span style="color:#94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="revenus-col-right revenus-table__montant <?= $isCredit ? 'revenus-montant--credit' : 'revenus-montant--debit' ?>">
                            <?= $tConf['sign'] ?><?= number_format($montant, 0, ',', ' ') ?>
                            <span class="revenus-table__devise"><?= $e($devise) ?></span>
                        </td>
                        <td class="revenus-col-center">
                            <span class="revenus-badge" style="color:<?= $sConf['color'] ?>;background:<?= $sConf['bg'] ?>;">
                                <?= $e($sConf['label']) ?>
                            </span>
                        </td>
                        <td class="revenus-col-right revenus-table__date"><?= $e($date) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</section>
