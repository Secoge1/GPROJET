<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = fn($s) => \App\Core\Security::escape($s ?? '');
$solde        = (float)($solde ?? 0);
$transactions = $transactions ?? [];
$waveDepots    = $wave_depots  ?? [];
$waveNumeroRaw   = (string) ($wave_numero ?? '+223 94 03 54 56');
$waveNumero      = $e($waveNumeroRaw);
$wavePhoneJs     = (isset($wave_phone_e164) && (string) $wave_phone_e164 !== '')
    ? (string) $wave_phone_e164
    : preg_replace('/\s+/', '', $waveNumeroRaw);
$devise          = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');

/* ---- Helpers badges ---- */
function clTxTypeBadge(string $t): string {
    $map = [
        'depot'         => ['cl-badge--green',  'Dépôt'],
        'debit'         => ['cl-badge--red',     'Débit'],
        'credit'        => ['cl-badge--green',   'Crédit'],
        'retrait'       => ['cl-badge--orange',  'Retrait'],
        'remboursement' => ['cl-badge--blue',    'Remboursement'],
        'paiement'      => ['cl-badge--amber',   'Paiement'],
        'escrow'        => ['cl-badge--gray',    'Escrow'],
    ];
    [$cls, $lbl] = $map[strtolower($t)] ?? ['cl-badge--gray', $t];
    return "<span class=\"cl-badge {$cls}\">{$lbl}</span>";
}

function clTxStatutBadge(string $s): string {
    $sl = strtolower($s);
    if (in_array($sl, ['effectue', 'complete'], true)) {
        return '<span class="cl-badge cl-badge--green">Effectué</span>';
    } elseif ($sl === 'en_attente') {
        return '<span class="cl-badge cl-badge--orange">En attente</span>';
    } elseif (in_array($sl, ['annule', 'annulee'], true)) {
        return '<span class="cl-badge cl-badge--red">Annulé</span>';
    }
    return '<span class="cl-badge cl-badge--gray">' . htmlspecialchars($s, ENT_QUOTES) . '</span>';
}

function waveDepotStatusBadge(string $s, ?string $transactionCode = null): string {
    if ($s === 'pending') {
        $codeOk = $transactionCode !== null && trim($transactionCode) !== '';
        if (!$codeOk) {
            return '<span class="cl-badge cl-badge--amber">À compléter — saisir le code</span>';
        }
        return '<span class="cl-badge cl-badge--orange">En attente validation admin</span>';
    } elseif ($s === 'success') {
        return '<span class="cl-badge cl-badge--green">Validé</span>';
    } elseif ($s === 'failed') {
        return '<span class="cl-badge cl-badge--red">Refusé</span>';
    }
    return '<span class="cl-badge cl-badge--gray">' . htmlspecialchars($s, ENT_QUOTES) . '</span>';
}
?>
<div class="cl-page">

    <!-- En-tête -->
    <div class="cl-page__hero">
        <div class="cl-page__hero-left">
            <a href="<?= $baseUrl ?>/client" class="cl-back">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Tableau de bord
            </a>
            <h1 class="cl-page__title">Mon portefeuille</h1>
            <p class="cl-page__sub">Gérez votre solde et déposez des fonds via Mobile Money (Orange Money ou Moov Africa).</p>
        </div>
    </div>

    <!-- Flash messages -->
    <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="cl-alert cl-alert--error" style="margin-bottom:1.25rem;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= $e($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="cl-alert cl-alert--success" style="margin-bottom:1.25rem;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <?= $e($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Grille principale : solde + formulaire Wave -->
    <div class="cl-wallet-grid">

        <!-- Carte solde -->
        <div class="cl-wallet-balance">
            <div class="cl-wallet-balance__icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            </div>
            <div class="cl-wallet-balance__body">
                <span class="cl-wallet-balance__label">Solde disponible</span>
                <span class="cl-wallet-balance__amount"><?= number_format($solde, 0, ',', ' ') ?></span>
                <span class="cl-wallet-balance__currency"><?= $e($devise) ?></span>
            </div>
            <div class="cl-wallet-balance__wave">
                <svg viewBox="0 0 200 60" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0 40 Q50 10 100 40 Q150 70 200 40 L200 60 L0 60 Z" fill="rgba(255,255,255,0.08)"/>
                </svg>
            </div>
        </div>

        <!-- Recharge portefeuille (PayTech) -->
        <div class="cl-card cl-card--deposit">
            <div class="cl-card__head" style="display:flex;align-items:center;gap:.75rem;">
                <h2 class="cl-card__title" style="margin:0;">Recharger le portefeuille</h2>
            </div>

            <?php if (!empty($paytech_configured)): ?>
            <div style="background:#f0fdfa;border:1px solid #99f6e4;border-radius:10px;padding:1.1rem;margin-bottom:1.25rem;">
                <p style="margin:0 0 .65rem;font-size:.9rem;font-weight:600;color:#0f766e;">Paiement mobile</p>
                <p style="margin:0 0 1rem;font-size:.85rem;color:#334155;line-height:1.5;">
                    Paiement Mobile Money sur la page sécurisée Service de paiement. Notifications serveur (IPN) : <code style="font-size:.75rem;">POST <?= $e($baseUrl) ?>/paytech/callback</code>
                </p>
                <a href="<?= $baseUrl ?>/paytech/depot" class="cl-btn cl-btn--amber" style="display:inline-flex;text-decoration:none;align-items:center;gap:.45rem;">
                    Recharger via Mobile Money
                </a>
                <p style="margin:.75rem 0 0;font-size:.78rem;color:#64748b;">
                    <a href="<?= $baseUrl ?>/paytech/historique" style="color:#0d9488;font-weight:600;">Voir l’historique Service de paiement</a>
                </p>
            </div>
            <?php elseif (!empty($touchpay_configured) || !empty($intouch_api_configured)): ?>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1.1rem;margin-bottom:1.25rem;">
                <p style="margin:0 0 1rem;font-size:.85rem;color:#334155;line-height:1.5;">
                    Recharge Mobile Money (repli lorsque Service de paiement n’est pas encore activé sur le serveur).
                </p>
                <a href="<?= $baseUrl ?>/intouch/touchpay-depot" class="cl-btn cl-btn--amber" style="display:inline-flex;text-decoration:none;align-items:center;gap:.45rem;background:#475569;border-color:#475569;">
                    Recharger en Mobile Money
                </a>
                <p style="margin:.65rem 0 0;font-size:.78rem;color:#64748b;">Préférez <strong>Service de paiement</strong> lorsque <code>PAYTECH_*</code> est configuré — même parcours que sur l’accueil plateforme.</p>
            </div>
            <?php else: ?>
            <div class="cl-alert cl-alert--warn" style="margin:0;font-size:.88rem;color:#92400e;background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:1rem;">
                Aucune passerelle de dépôt configurée. Contactez l'administrateur pour configurer le service de paiement.
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Dépôts Mobile Money en cours -->
    <?php if (!empty($waveDepots)): ?>
    <div class="cl-card" style="margin-top:1.5rem;">
        <div class="cl-card__head">
            <h2 class="cl-card__title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6l4 12L11 6l4 12 4-12"/></svg>
                Dépôts Mobile Money récents
            </h2>
        </div>
        <div class="cl-tx-table-wrap">
            <table class="cl-tx-table">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($waveDepots as $wd): ?>
                    <tr>
                        <td style="font-family:monospace;font-size:.8rem;"><?= $e($wd['payment_id']) ?></td>
                        <td class="cl-tx-amount cl-tx-amount--credit">
                            <?= number_format((float)$wd['amount'], 0, ',', ' ') ?> <?= $e($devise) ?>
                        </td>
                        <td><?= waveDepotStatusBadge($wd['status'] ?? '', $wd['transaction_code'] ?? null) ?></td>
                        <td class="cl-tx-date"><?= !empty($wd['created_at']) ? date('d/m/Y H:i', strtotime($wd['created_at'])) : '—' ?></td>
                        <td>
                            <?php if (($wd['status'] ?? '') === 'pending'):
                                $wdProv = strtolower((string) ($wd['provider'] ?? ''));
                                $suivreHref = $wdProv === 'paytech'
                                    ? ($baseUrl . '/paytech/historique')
                                    : ($baseUrl . '/intouch/verification/' . rawurlencode((string) ($wd['payment_id'] ?? '')));
                            ?>
                            <a href="<?= $e($suivreHref) ?>" class="cl-btn-sm cl-btn-sm--amber">
                                Suivre →
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Historique des transactions portefeuille -->
    <div class="cl-card" style="margin-top:1.5rem;">
        <div class="cl-card__head">
            <h2 class="cl-card__title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                Historique des transactions
            </h2>
            <?php if (!empty($transactions)): ?>
            <span class="cl-card__count"><?= count($transactions) ?> transaction<?= count($transactions) > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($transactions)): ?>
        <div class="cl-empty" style="padding:2.5rem 1.5rem;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
            <p>Aucune transaction pour le moment</p>
        </div>
        <?php else: ?>
        <div class="cl-tx-table-wrap">
            <table class="cl-tx-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td><?= clTxTypeBadge($t['type'] ?? '') ?></td>
                        <td class="cl-tx-amount <?= str_starts_with($t['type'] ?? '', 'debit') || ($t['type'] ?? '') === 'paiement' ? 'cl-tx-amount--debit' : 'cl-tx-amount--credit' ?>">
                            <?= number_format((float)($t['montant'] ?? 0), 0, ',', ' ') ?> <?= $e($devise) ?>
                        </td>
                        <td><?= clTxStatutBadge($t['statut'] ?? '') ?></td>
                        <td class="cl-tx-date">
                            <?= !empty($t['created_at']) ? date('d/m/Y', strtotime($t['created_at'])) : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>
