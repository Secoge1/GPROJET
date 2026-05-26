<?php
/**
 * Script d'implémentation Checkout Page InTouch (script2)
 * SendPaymentInfos(order_number, agency_code, secure_code, domain_name,
 *   url_success, url_failed, amount, city, email, clientFirstName, clientLastName, clientPhone)
 *
 * Supprimer ce fichier après exécution.
 */

// ── 1. Mettre à jour buildTouchpaySendPaymentArgs dans IntouchPaymentService ──────
$svcFile = 'app/Services/IntouchPaymentService.php';
$svc     = file_get_contents($svcFile);

// Remplacer la méthode buildTouchpaySendPaymentArgs et getTouchpayScriptUrl
$oldMethod = 'public function buildTouchpaySendPaymentArgs(array $txRow): array
    {
        $base      = rtrim(BASE_URL ?? \'\', \'/\');
        $paymentId = (string) ($txRow[\'payment_id\'] ?? \'\');
        $total     = (int) round((float) ($txRow[\'total_amount\'] ?? 0));
        $agency    = $this->getAgencyCode();
        $secure    = $this->getTouchpaySecureCode();
        $domain    = $this->getTouchpayDomainName();
        $callback  = $base . \'/intouch/callback\';

        $override = getenv(\'TOUCHPAY_SENDPAYMENT_ARGS_JSON\');
        if ($override !== false && trim((string) $override) !== \'\') {
            $decoded = json_decode((string) $override, true);
            if (is_array($decoded)) {
                return $this->interpolateTouchpayTemplate($decoded, $paymentId, $agency, $secure, $domain, $total, $callback, $base);
            }
        }

        return [
            $paymentId,
            $agency,
            $secure,
            $domain,
            $total,
            $callback,
        ];
    }';

$newMethod = 'public function buildTouchpaySendPaymentArgs(array $txRow, array $userInfo = []): array
    {
        $base      = rtrim(BASE_URL ?? \'\', \'/\');
        $paymentId = (string) ($txRow[\'payment_id\'] ?? \'\');
        $total     = (int) round((float) ($txRow[\'total_amount\'] ?? 0));
        $agency    = $this->getAgencyCode();
        $secure    = $this->getTouchpaySecureCode();
        $domain    = $this->getTouchpayDomainName();

        // URLs de succès et d\'échec (Checkout Page script2)
        $urlSuccess = $base . \'/intouch/succes/\' . rawurlencode($paymentId);
        $urlFailed  = $base . \'/intouch/echec/\' . rawurlencode($paymentId);

        // Infos client (depuis la transaction jointure utilisateurs ou userInfo passé en param)
        $email     = (string) ($userInfo[\'email\']  ?? $txRow[\'email\']  ?? \'\');
        $prenom    = (string) ($userInfo[\'prenom\'] ?? $txRow[\'prenom\'] ?? \'\');
        $nom       = (string) ($userInfo[\'nom\']    ?? $txRow[\'nom\']    ?? \'\');
        $phone     = (string) ($userInfo[\'phone\']  ?? $txRow[\'phone\']  ?? \'\');
        $ville     = (string) ($userInfo[\'ville\']  ?? \'\');

        // Nettoyer le téléphone (format local sans +223)
        $phoneLocal = ltrim(preg_replace(\'/^\\+?223/\', \'\', preg_replace(\'/\\s+/\', \'\', $phone)) ?? \'\', \'0\');
        if ($phoneLocal === \'\') $phoneLocal = \'00000000\';

        return [
            $paymentId,   // order_number
            $agency,      // agency_code
            $secure,      // secure_code
            $domain,      // domain_name
            $urlSuccess,  // url_redirection_success
            $urlFailed,   // url_redirection_failed
            $total,       // amount
            $ville ?: \'Bamako\',  // city
            $email,       // email
            $prenom ?: \'Client\', // clientFirstName
            $nom    ?: \'GLOBALO\',// clientLastName
            $phoneLocal,  // clientPhone
        ];
    }';

if (strpos($svc, 'public function buildTouchpaySendPaymentArgs(array $txRow): array') !== false) {
    $svc = str_replace($oldMethod, $newMethod, $svc);
    echo "[1a] buildTouchpaySendPaymentArgs mis a jour\n";
} else {
    // Tentative par pattern plus souple
    $pattern = '/public function buildTouchpaySendPaymentArgs\(array \$txRow\): array\s*\{.*?^\s*\}/ms';
    $svc = preg_replace($pattern, $newMethod, $svc, 1);
    echo "[1a] buildTouchpaySendPaymentArgs mis a jour (pattern)\n";
}

// Mettre à jour getTouchpayScriptUrl pour utiliser script2
$svc = str_replace(
    "return defined('TOUCHPAY_SCRIPT_URL') ? (string) TOUCHPAY_SCRIPT_URL : 'https://touchpay.gutouch.com/touchpay/script';",
    "return defined('TOUCHPAY_SCRIPT_URL') ? (string) TOUCHPAY_SCRIPT_URL : 'https://touchpay-tz.gutouch.com/touchpay/script2/prod_touchpay-0.0.2.js';",
    $svc
);
echo "[1b] getTouchpayScriptUrl mis a jour vers script2\n";

file_put_contents($svcFile, $svc);
$lint = shell_exec('php -l ' . escapeshellarg($svcFile) . ' 2>&1');
echo "Lint Service : " . trim($lint) . "\n\n";


// ── 2. Mettre à jour config.php : nouveau script URL + mode api ──────────────
$cfgFile = 'config/config.php';
$cfg     = file_get_contents($cfgFile);
$cfg = str_replace(
    "'https://touchpay.gutouch.com/touchpay/script'",
    "'https://touchpay-tz.gutouch.com/touchpay/script2/prod_touchpay-0.0.2.js'",
    $cfg
);
$cfg = str_replace(
    "define('TOUCHPAY_ABONNEMENT_MODE',  _intouch_env('TOUCHPAY_ABONNEMENT_MODE', 'api'));",
    "define('TOUCHPAY_ABONNEMENT_MODE',  _intouch_env('TOUCHPAY_ABONNEMENT_MODE', 'widget')); // widget = Checkout Page script2",
    $cfg
);
file_put_contents($cfgFile, $cfg);
$lint = shell_exec('php -l ' . escapeshellarg($cfgFile) . ' 2>&1');
echo "[2] config.php mis a jour\nLint Config : " . trim($lint) . "\n\n";


// ── 3. Mettre à jour .htaccess : mode widget + nouveau script URL ────────────
$htFile = '.htaccess';
$ht     = file_get_contents($htFile);
$ht = str_replace(
    'SetEnv TOUCHPAY_ABONNEMENT_MODE api',
    'SetEnv TOUCHPAY_ABONNEMENT_MODE widget',
    $ht
);
if (strpos($ht, 'TOUCHPAY_ABONNEMENT_MODE') === false) {
    $ht = str_replace(
        'SetEnv INTOUCH_SERVICE_WAVE   ML_PAIEMENTWAVE_TP',
        "SetEnv INTOUCH_SERVICE_WAVE   ML_PAIEMENTWAVE_TP\nSetEnv TOUCHPAY_ABONNEMENT_MODE widget",
        $ht
    );
}
// Mettre à jour le script URL dans .htaccess si défini
$ht = str_replace(
    'https://touchpay.gutouch.com/touchpay/script',
    'https://touchpay-tz.gutouch.com/touchpay/script2/prod_touchpay-0.0.2.js',
    $ht
);
file_put_contents($htFile, $ht);
echo "[3] .htaccess mis a jour\n\n";


// ── 4. Ajouter la route /intouch/echec dans IntouchController ────────────────
$ctrlFile = 'app/Controllers/IntouchController.php';
$ctrl     = file_get_contents($ctrlFile);

// Ajouter la méthode echec() après la méthode succes()
$succesEnd = 'public function historique(';
$echecMethod = 'public function echec(string $paymentId = \'\'): void
    {
        Auth::requireRole(\'client\', \'expert\', \'etudiant\', \'professeur\');
        $userId = (int) Auth::id();
        $base   = rtrim(BASE_URL ?? \'\', \'/\');

        $tx = $this->transactionModel->findByPaymentId($paymentId);
        if ($tx === null || (int) $tx[\'user_id\'] !== $userId) {
            $_SESSION[\'flash_error\'] = \'Transaction introuvable.\';
            $this->redirect($this->portefeuilleHomeUrl());
            return;
        }

        // Marquer comme failed si encore pending
        if ($tx[\'status\'] === \'pending\') {
            $db = \App\Core\Database::getInstance();
            $db->prepare("UPDATE transactions SET status=\'failed\', notes=\'Abandon paiement CheckOut\', updated_at=NOW() WHERE payment_id=? AND status=\'pending\' LIMIT 1")
               ->execute([$paymentId]);
        }

        $isDepot = ($tx[\'type\'] ?? \'\') === \'depot_portefeuille\'
                || ($tx[\'type\'] ?? \'\') === \'paiement_session_touchpay\';

        $this->render(\'echec\', [
            \'pageTitle\'   => \'Paiement annulé — GLOBALO\',
            \'user\'        => [\'id\' => $userId, \'role\' => Auth::role()],
            \'transaction\' => $tx,
            \'is_depot\'    => $isDepot,
            \'retry_url\'   => $isDepot
                ? $this->portefeuilleHomeUrl()
                : ($base . \'/abonnement\'),
        ]);
    }

    ';

if (strpos($ctrl, 'public function echec(') === false) {
    $ctrl = str_replace($succesEnd, $echecMethod . $succesEnd, $ctrl);
    echo "[4] Methode echec() ajoutee dans IntouchController\n";
} else {
    echo "[4] Methode echec() deja presente\n";
}

// Mettre à jour le docblock routes
$ctrl = str_replace(
    ' * GET  /intouch/succes/{paymentId}        — Page de succès',
    " * GET  /intouch/succes/{paymentId}        — Page de succès\n * GET  /intouch/echec/{paymentId}         — Page d'échec / abandon",
    $ctrl
);
file_put_contents($ctrlFile, $ctrl);
$lint = shell_exec('php -l ' . escapeshellarg($ctrlFile) . ' 2>&1');
echo "Lint Controller : " . trim($lint) . "\n\n";


// ── 5. Mettre à jour IntouchController::touchpay() pour passer les infos user ─
$ctrl = file_get_contents($ctrlFile);

// Dans touchpay(), passer userInfo à buildTouchpaySendPaymentArgs
$ctrl = str_replace(
    '$args = $this->intouchService->buildTouchpaySendPaymentArgs($tx);

        $this->render(\'touchpay\',',
    '$args = $this->intouchService->buildTouchpaySendPaymentArgs($tx, [
            \'email\'  => $tx[\'email\']  ?? \'\',
            \'prenom\' => $tx[\'prenom\'] ?? \'\',
            \'nom\'    => $tx[\'nom\']    ?? \'\',
        ]);

        $this->render(\'touchpay\',',
    $ctrl
);

// Dans touchpayDepot(), pareil
$ctrl = str_replace(
    '$args = $this->intouchService->buildTouchpaySendPaymentArgs($tx);

        $this->render(\'touchpay_depot\',',
    '$args = $this->intouchService->buildTouchpaySendPaymentArgs($tx, [
            \'email\'  => $tx[\'email\']  ?? \'\',
            \'prenom\' => $tx[\'prenom\'] ?? \'\',
            \'nom\'    => $tx[\'nom\']    ?? \'\',
        ]);

        $this->render(\'touchpay_depot\',',
    $ctrl
);

// Dans touchpaySession(), pareil
$ctrl = str_replace(
    '$args = $this->intouchService->buildTouchpaySendPaymentArgs($tx);

        $this->render(\'touchpay_session\',',
    '$args = $this->intouchService->buildTouchpaySendPaymentArgs($tx, [
            \'email\'  => $tx[\'email\']  ?? \'\',
            \'prenom\' => $tx[\'prenom\'] ?? \'\',
            \'nom\'    => $tx[\'nom\']    ?? \'\',
        ]);

        $this->render(\'touchpay_session\',',
    $ctrl
);

file_put_contents($ctrlFile, $ctrl);
$lint = shell_exec('php -l ' . escapeshellarg($ctrlFile) . ' 2>&1');
echo "[5] userInfo passe a buildTouchpaySendPaymentArgs\nLint Controller : " . trim($lint) . "\n\n";


// ── 6. Créer la vue echec.php ─────────────────────────────────────────────────
$echecView = <<<'HTML'
<?php
/**
 * GLOBALO — Page d'échec / abandon paiement Checkout InTouch
 */
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$tx        = $transaction ?? [];
$retryUrl  = $retry_url ?? ($baseUrl . '/abonnement');
$isDepot   = $is_depot ?? false;
$paymentId = $tx['payment_id'] ?? '';
?>
<div style="max-width:480px;margin:3rem auto;padding:0 1rem;text-align:center;">

    <div style="background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;">

        <div style="background:linear-gradient(135deg,#dc2626,#b91c1c);padding:2rem;color:#fff;">
            <div style="font-size:3rem;margin-bottom:.5rem;">✕</div>
            <h1 style="font-size:1.4rem;font-weight:700;margin:0;">Paiement annulé</h1>
            <p style="opacity:.85;font-size:.9rem;margin:.25rem 0 0;">La transaction n'a pas abouti</p>
        </div>

        <div style="padding:1.75rem;">
            <?php if ($paymentId): ?>
            <p style="font-size:.82rem;color:#6b7280;margin:0 0 1.25rem;">
                Référence : <code style="background:#f1f5f9;padding:.15rem .4rem;border-radius:4px;"><?= $e($paymentId) ?></code>
            </p>
            <?php endif; ?>

            <p style="color:#374151;font-size:.95rem;margin:0 0 1.5rem;line-height:1.6;">
                Le paiement a été interrompu ou annulé. Aucun montant n'a été débité.<br>
                Vous pouvez réessayer à tout moment.
            </p>

            <a href="<?= $e($retryUrl) ?>" style="display:block;width:100%;background:#2563eb;color:#fff;border:none;border-radius:10px;padding:.9rem;font-size:1rem;font-weight:600;text-decoration:none;box-sizing:border-box;margin-bottom:.75rem;">
                ↩ Réessayer
            </a>

            <?php if ($paymentId): ?>
            <a href="<?= $baseUrl ?>/intouch/verification/<?= rawurlencode($paymentId) ?>" style="display:block;font-size:.85rem;color:#6b7280;text-decoration:none;margin-top:.5rem;">
                Vérifier le statut de la transaction
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
HTML;

file_put_contents('app/Views/desktop/Intouch/echec.php', $echecView);
$lint = shell_exec('php -l app/Views/desktop/Intouch/echec.php 2>&1');
echo "[6] Vue echec.php creee\nLint : " . trim($lint) . "\n\n";


// ── 7. Réécrire les vues touchpay*.php avec le nouveau script et la fonction checkout ─
// Vue touchpay.php (abonnement)
$tpView = <<<'PHPVIEW'
<?php
/**
 * GLOBALO — Checkout Page InTouch (script2) — Abonnement
 * SendPaymentInfos(order_number, agency_code, secure_code, domain_name,
 *   url_success, url_failed, amount, city, email, firstName, lastName, phone)
 */
$baseUrl        = rtrim(BASE_URL ?? '', '/');
$e              = fn($s) => \App\Core\Security::escape($s ?? '');
$abonnementType = $abonnement_type ?? 'client';
$montant        = (float) ($montant ?? 0);
$commission     = (float) ($commission ?? 0);
$total          = (float) ($total ?? 0);
$paymentId      = $payment_id ?? '';
$scriptUrl      = $touchpay_script_url ?? 'https://touchpay-tz.gutouch.com/touchpay/script2/prod_touchpay-0.0.2.js';
$sendArgs       = $touchpay_send_args ?? [];
$altUrl         = $paiement_classique_url ?? ($baseUrl . '/intouch/paiement/' . rawurlencode($abonnementType));
$argsJson       = json_encode($sendArgs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS);
$typeLabels     = ['client' => 'Client', 'expert' => 'Expert', 'etudiant' => 'Étudiant', 'professeur' => 'Professeur'];
$devise         = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
?>
<div style="max-width:520px;margin:2.5rem auto;padding:0 1rem;">

    <a href="<?= $baseUrl ?>/abonnement" style="display:inline-flex;align-items:center;gap:.4rem;color:var(--color-primary,#2563eb);font-size:.9rem;margin-bottom:1.5rem;text-decoration:none;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour abonnement
    </a>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div style="padding:.75rem 1rem;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;margin-bottom:1rem;">
            <?= $e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;background:#fff;">

        <div style="background:linear-gradient(135deg,#0d9488,#14b8a6);padding:1.75rem;color:#fff;text-align:center;">
            <h1 style="font-size:1.4rem;font-weight:700;margin:0 0 .25rem;">Paiement — Abonnement <?= $e($typeLabels[$abonnementType] ?? $abonnementType) ?></h1>
            <p style="opacity:.85;font-size:.9rem;margin:0;">Checkout InTouch · Mobile Money</p>
        </div>

        <div style="background:#fff;padding:.85rem 1.25rem;border-bottom:1px solid #e2e8f0;">
            <?php $mm_logo_size = 'sm'; $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.65rem;';
            require APP_PATH . '/Views/partials/mm_operator_logos.php'; ?>
        </div>

        <div style="background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:1.25rem 1.5rem;">
            <div style="display:flex;justify-content:space-between;font-size:.9rem;color:#64748b;margin-bottom:.5rem;">
                <span>Référence</span>
                <span style="font-size:.78rem;word-break:break-all;color:#0f172a;"><?= $e($paymentId) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:.9rem;color:#64748b;margin-bottom:.5rem;">
                <span>Abonnement</span>
                <span><?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
            <?php if ($commission > 0): ?>
            <div style="display:flex;justify-content:space-between;font-size:.9rem;color:#64748b;margin-bottom:.5rem;">
                <span>Frais de service</span>
                <span><?= number_format($commission, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;color:#0f172a;border-top:1px solid #e2e8f0;padding-top:.75rem;margin-top:.5rem;">
                <span>Total</span>
                <span style="color:#0d9488;"><?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
        </div>

        <div style="padding:1.5rem;">
            <p style="font-size:.85rem;color:#64748b;margin:0 0 1.1rem;line-height:1.5;">
                Cliquez sur <strong>Payer maintenant</strong> pour être redirigé sur la page de paiement sécurisée InTouch. Choisissez votre opérateur (Orange Money, Moov, Wave) et validez.
            </p>

            <button type="button" id="btn-checkout-abo" onclick="calltouchpay()"
                style="width:100%;background:#0d9488;color:#fff;border:none;border-radius:10px;padding:.95rem;font-size:1rem;font-weight:600;cursor:pointer;margin-bottom:.75rem;display:flex;align-items:center;justify-content:center;gap:.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Payer maintenant — <?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?>
            </button>
            <p id="checkout-err" style="color:#dc2626;font-size:.85rem;margin:0 0 .75rem;min-height:1rem;"></p>

            <a href="<?= $e($altUrl) ?>" style="display:block;text-align:center;font-size:.85rem;color:#6b7280;margin-top:.5rem;">
                Payer par formulaire (saisie numéro Mobile Money)
            </a>
        </div>
    </div>
</div>

<script src="<?= $e($scriptUrl) ?>" type="text/javascript"></script>
<script type="text/javascript">
var _tpArgs = <?= $argsJson ?>;
function calltouchpay() {
    var errEl = document.getElementById('checkout-err');
    if (errEl) errEl.textContent = '';
    if (typeof SendPaymentInfos !== 'function') {
        if (errEl) errEl.textContent = 'Le script InTouch n\u0027a pas pu \u00eatre charg\u00e9. V\u00e9rifiez votre connexion.';
        return;
    }
    try {
        SendPaymentInfos.apply(null, _tpArgs);
    } catch (ex) {
        if (errEl) errEl.textContent = ex && ex.message ? ex.message : 'Erreur lors du paiement.';
    }
}
</script>
PHPVIEW;

file_put_contents('app/Views/desktop/Intouch/touchpay.php', $tpView);
$lint = shell_exec('php -l app/Views/desktop/Intouch/touchpay.php 2>&1');
echo "[7a] touchpay.php (abonnement) reecrit\nLint : " . trim($lint) . "\n";


// Vue touchpay_depot.php
$depotView = <<<'PHPVIEW'
<?php
/**
 * GLOBALO — Checkout Page InTouch (script2) — Dépôt portefeuille
 */
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$montant   = (float) ($montant ?? 0);
$total     = (float) ($total ?? $montant);
$paymentId = $payment_id ?? '';
$scriptUrl = $touchpay_script_url ?? 'https://touchpay-tz.gutouch.com/touchpay/script2/prod_touchpay-0.0.2.js';
$sendArgs  = $touchpay_send_args ?? [];
$retourUrl = $retour_url ?? ($baseUrl . '/client/portefeuille');
$argsJson  = json_encode($sendArgs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS);
$devise    = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
?>
<div style="max-width:520px;margin:2.5rem auto;padding:0 1rem;">

    <a href="<?= $e($retourUrl) ?>" style="display:inline-flex;align-items:center;gap:.4rem;color:var(--color-primary,#2563eb);font-size:.9rem;margin-bottom:1.5rem;text-decoration:none;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour portefeuille
    </a>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div style="padding:.75rem 1rem;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;margin-bottom:1rem;"><?= $e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;background:#fff;">

        <div style="background:linear-gradient(135deg,#16a34a,#15803d);padding:1.75rem;color:#fff;text-align:center;">
            <h1 style="font-size:1.4rem;font-weight:700;margin:0 0 .25rem;">Recharger le portefeuille</h1>
            <p style="opacity:.85;font-size:.9rem;margin:0;">Checkout InTouch · Mobile Money</p>
        </div>

        <div style="background:#fff;padding:.85rem 1.25rem;border-bottom:1px solid #e2e8f0;">
            <?php $mm_logo_size = 'sm'; $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.65rem;';
            require APP_PATH . '/Views/partials/mm_operator_logos.php'; ?>
        </div>

        <div style="background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:1.25rem 1.5rem;">
            <div style="display:flex;justify-content:space-between;font-size:.9rem;color:#64748b;margin-bottom:.5rem;">
                <span>Montant à déposer</span>
                <span><?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;color:#0f172a;border-top:1px solid #e2e8f0;padding-top:.75rem;margin-top:.5rem;">
                <span>Total à payer</span>
                <span style="color:#16a34a;"><?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
            <p style="font-size:.78rem;color:#64748b;margin:.5rem 0 0;">Réf : <code><?= $e($paymentId) ?></code></p>
        </div>

        <div style="padding:1.5rem;">
            <p style="font-size:.85rem;color:#64748b;margin:0 0 1.1rem;line-height:1.5;">
                Cliquez sur <strong>Payer maintenant</strong> pour être redirigé sur la page de paiement InTouch. Votre portefeuille sera crédité automatiquement après confirmation.
            </p>

            <button type="button" onclick="calltouchpay_depot()"
                style="width:100%;background:#16a34a;color:#fff;border:none;border-radius:10px;padding:.95rem;font-size:1rem;font-weight:600;cursor:pointer;margin-bottom:.75rem;display:flex;align-items:center;justify-content:center;gap:.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Payer maintenant — <?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?>
            </button>
            <p id="checkout-depot-err" style="color:#dc2626;font-size:.85rem;margin:0 0 .75rem;min-height:1rem;"></p>

            <a href="<?= $baseUrl ?>/intouch/historique" style="display:block;text-align:center;font-size:.85rem;color:#6b7280;">
                Voir l'historique des transactions
            </a>
        </div>
    </div>
</div>

<script src="<?= $e($scriptUrl) ?>" type="text/javascript"></script>
<script type="text/javascript">
var _tpDepotArgs = <?= $argsJson ?>;
function calltouchpay_depot() {
    var errEl = document.getElementById('checkout-depot-err');
    if (errEl) errEl.textContent = '';
    if (typeof SendPaymentInfos !== 'function') {
        if (errEl) errEl.textContent = 'Impossible de charger le script InTouch. V\u00e9rifiez votre connexion.';
        return;
    }
    try {
        SendPaymentInfos.apply(null, _tpDepotArgs);
    } catch (ex) {
        if (errEl) errEl.textContent = ex && ex.message ? ex.message : 'Erreur lors du paiement.';
    }
}
</script>
PHPVIEW;

file_put_contents('app/Views/desktop/Intouch/touchpay_depot.php', $depotView);
$lint = shell_exec('php -l app/Views/desktop/Intouch/touchpay_depot.php 2>&1');
echo "[7b] touchpay_depot.php reecrit\nLint : " . trim($lint) . "\n";


// Vue touchpay_session.php
$sessionView = <<<'PHPVIEW'
<?php
/**
 * GLOBALO — Checkout Page InTouch (script2) — Paiement direct session
 */
$baseUrl       = rtrim(BASE_URL ?? '', '/');
$e             = fn($s) => \App\Core\Security::escape($s ?? '');
$reservation   = $reservation ?? [];
$reservationId = (int) ($reservation_id ?? $reservation['id'] ?? 0);
$montant       = (float) ($montant ?? 0);
$total         = (float) ($total ?? $montant);
$paymentId     = $payment_id ?? '';
$scriptUrl     = $touchpay_script_url ?? 'https://touchpay-tz.gutouch.com/touchpay/script2/prod_touchpay-0.0.2.js';
$sendArgs      = $touchpay_send_args ?? [];
$payerUrl      = $payer_url ?? ($baseUrl . '/client/payer/' . $reservationId);
$argsJson      = json_encode($sendArgs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS);
$devise        = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
?>
<div style="max-width:520px;margin:2.5rem auto;padding:0 1rem;">

    <a href="<?= $e($payerUrl) ?>" style="display:inline-flex;align-items:center;gap:.4rem;color:var(--color-primary,#2563eb);font-size:.9rem;margin-bottom:1.5rem;text-decoration:none;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Retour au paiement
    </a>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div style="padding:.75rem 1rem;border-radius:8px;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;margin-bottom:1rem;"><?= $e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div style="border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;background:#fff;">

        <div style="background:linear-gradient(135deg,#7c3aed,#6d28d9);padding:1.75rem;color:#fff;text-align:center;">
            <h1 style="font-size:1.4rem;font-weight:700;margin:0 0 .25rem;">Paiement de la session</h1>
            <p style="opacity:.85;font-size:.9rem;margin:0;">Réservation #<?= $reservationId ?> · Checkout InTouch</p>
        </div>

        <div style="background:#fff;padding:.85rem 1.25rem;border-bottom:1px solid #e2e8f0;">
            <?php $mm_logo_size = 'sm'; $mm_logo_wrap_style = 'display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:.65rem;';
            require APP_PATH . '/Views/partials/mm_operator_logos.php'; ?>
        </div>

        <div style="background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:1.25rem 1.5rem;">
            <?php if (!empty($reservation['demande_titre'])): ?>
            <div style="display:flex;justify-content:space-between;font-size:.9rem;color:#64748b;margin-bottom:.5rem;">
                <span>Mission</span>
                <span style="font-weight:600;max-width:60%;text-align:right;"><?= $e($reservation['demande_titre']) ?></span>
            </div>
            <?php endif; ?>
            <div style="display:flex;justify-content:space-between;font-size:.9rem;color:#64748b;margin-bottom:.5rem;">
                <span>Montant session</span>
                <span><?= number_format($montant, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem;color:#0f172a;border-top:1px solid #e2e8f0;padding-top:.75rem;margin-top:.5rem;">
                <span>Total à payer</span>
                <span style="color:#7c3aed;"><?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?></span>
            </div>
        </div>

        <div style="background:#f5f3ff;border-bottom:1px solid #e2e8f0;padding:1rem 1.5rem;">
            <p style="font-size:.8rem;font-weight:700;color:#6d28d9;margin:0 0 .5rem;text-transform:uppercase;letter-spacing:.04em;">Comment ça marche</p>
            <ol style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:.4rem;">
                <?php foreach (['Cliquez sur « Payer maintenant » → page InTouch sécurisée.', 'Votre portefeuille est crédité après confirmation Mobile Money.', 'Revenez sur la page de paiement pour finaliser la mission.'] as $i => $step): ?>
                <li style="display:flex;align-items:flex-start;gap:.5rem;font-size:.82rem;color:#374151;">
                    <span style="flex-shrink:0;width:20px;height:20px;background:#7c3aed;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:700;"><?= $i + 1 ?></span>
                    <?= htmlspecialchars($step, ENT_QUOTES, 'UTF-8') ?>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>

        <div style="padding:1.5rem;">
            <button type="button" onclick="calltouchpay_session()"
                style="width:100%;background:#7c3aed;color:#fff;border:none;border-radius:10px;padding:.95rem;font-size:1rem;font-weight:600;cursor:pointer;margin-bottom:.75rem;display:flex;align-items:center;justify-content:center;gap:.5rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Payer maintenant — <?= number_format($total, 0, ',', ' ') ?> <?= $e($devise) ?>
            </button>
            <p id="checkout-session-err" style="color:#dc2626;font-size:.85rem;margin:0 0 .75rem;min-height:1rem;"></p>

            <p style="font-size:.8rem;color:#6b7280;margin:0;line-height:1.5;">
                Après paiement, revenez sur <a href="<?= $e($payerUrl) ?>" style="color:#7c3aed;font-weight:600;">la page de paiement</a> pour finaliser la mission.
            </p>
        </div>
    </div>
</div>

<script src="<?= $e($scriptUrl) ?>" type="text/javascript"></script>
<script type="text/javascript">
var _tpSessionArgs = <?= $argsJson ?>;
function calltouchpay_session() {
    var errEl = document.getElementById('checkout-session-err');
    if (errEl) errEl.textContent = '';
    if (typeof SendPaymentInfos !== 'function') {
        if (errEl) errEl.textContent = 'Impossible de charger le script InTouch. V\u00e9rifiez votre connexion.';
        return;
    }
    try {
        SendPaymentInfos.apply(null, _tpSessionArgs);
    } catch (ex) {
        if (errEl) errEl.textContent = ex && ex.message ? ex.message : 'Erreur lors du paiement.';
    }
}
</script>
PHPVIEW;

file_put_contents('app/Views/desktop/Intouch/touchpay_session.php', $sessionView);
$lint = shell_exec('php -l app/Views/desktop/Intouch/touchpay_session.php 2>&1');
echo "[7c] touchpay_session.php reecrit\nLint : " . trim($lint) . "\n\n";


// ── 8. Vérification finale de tous les fichiers ───────────────────────────────
echo "=== Verification finale ===\n";
$files = [
    'app/Services/IntouchPaymentService.php',
    'app/Controllers/IntouchController.php',
    'app/Views/desktop/Intouch/touchpay.php',
    'app/Views/desktop/Intouch/touchpay_depot.php',
    'app/Views/desktop/Intouch/touchpay_session.php',
    'app/Views/desktop/Intouch/echec.php',
    'config/config.php',
];
$ok = true;
foreach ($files as $f) {
    $out = trim(shell_exec('php -l ' . escapeshellarg($f) . ' 2>&1'));
    $status = strpos($out, 'No syntax errors') !== false ? 'OK' : 'ERREUR: '.$out;
    echo str_pad($f, 55) . ' ' . $status . "\n";
    if ($status !== 'OK') $ok = false;
}
echo "\n" . ($ok ? 'Tous les fichiers sont valides.' : 'Des erreurs ont ete trouvees.') . "\n";
echo "\nSupprimer ce fichier : rm implement_checkout.php\n";
