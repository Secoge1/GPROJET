<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$csrfField = \App\Core\Security::getCsrfField();
$e = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$logoCustom = ($logo_custom ?? '0') === '1';
$aboSel = strtolower(trim((string) ($abonnement_provider ?? 'intouch')));
if (!in_array($aboSel, ['gratuit', 'intouch', 'paytech'], true)) {
    $aboSel = 'intouch';
}
$maintenanceOn   = $maintenance_on   ?? false;
$maintenanceMeta = $maintenance_meta ?? [];
?>
<div class="page-admin page-admin-parametres">
    <header class="admin-parametres-hero">
        <a href="<?= $baseUrl ?>/admin" class="admin-back-link" aria-label="Retour au tableau de bord">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="admin-parametres-hero-content">
            <div class="admin-parametres-hero-icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </div>
            <div class="admin-parametres-hero-text">
                <h1>Paramètres</h1>
                <p class="admin-parametres-hero-subtitle">Configuration de la plateforme, commissions, paiement et identité</p>
            </div>
        </div>
    </header>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="admin-alert admin-alert--success"><?= $e($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="admin-alert admin-alert--error"><?= $e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="admin-alert admin-alert--error">
            <?php foreach ($errors as $err): ?><p style="margin:2px 0"><?= $e($err) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="admin-parametres-form" enctype="multipart/form-data">
        <?= $csrfField ?>

        <div class="admin-parametres-blocks">
            <section class="admin-parametres-block admin-parametres-block--commissions">
                <div class="admin-parametres-block-header">
                    <span class="admin-parametres-block-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    </span>
                    <h2>Commissions</h2>
                </div>
                <div class="admin-parametres-block-body">
                    <div class="form-group">
                        <label for="commission_pourcent">Commission par défaut (%)</label>
                        <input type="number" name="commission_pourcent" id="commission_pourcent" min="0" max="100" step="0.5" value="<?= $e((string)($commission_pourcent ?? 10)) ?>">
                        <small class="form-hint">Ex. 15, 20, 25 — appliquée à chaque transaction.</small>
                    </div>
                    <div class="form-group">
                        <label for="commission_premium_pourcent">Commission experts premium (%)</label>
                        <input type="number" name="commission_premium_pourcent" id="commission_premium_pourcent" min="0" max="100" step="0.5" value="<?= $e((string)($commission_premium_pourcent ?? 10)) ?>">
                        <small class="form-hint">Experts certifiés : taux réduit.</small>
                    </div>
                </div>
            </section>

            <section class="admin-parametres-block admin-parametres-block--abonnement">
                <div class="admin-parametres-block-header">
                    <span class="admin-parametres-block-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                    </span>
                    <h2>Abonnement (monétisation)</h2>
                </div>
                <div class="admin-parametres-block-body">
                    <div class="form-group">
                        <label for="monetisation_mode">Mode de monétisation</label>
                        <select name="monetisation_mode" id="monetisation_mode">
                            <option value="commission" <?= ($monetisation_mode ?? 'commission') === 'commission' ? 'selected' : '' ?>>Commission (par transaction)</option>
                            <option value="abonnement" <?= ($monetisation_mode ?? '') === 'abonnement' ? 'selected' : '' ?>>Abonnement (un abonnement unique)</option>
                        </select>
                        <small class="form-hint">En mode abonnement, la commission par transaction est à 0 %.</small>
                    </div>
                    <div class="form-group">
                        <label for="abonnement_provider">Fournisseur abonnement</label>
                        <select name="abonnement_provider" id="abonnement_provider">
                            <option value="gratuit" <?= $aboSel === 'gratuit' ? 'selected' : '' ?>>Gratuit (sans paiement)</option>
                            <option value="intouch" <?= $aboSel === 'intouch' ? 'selected' : '' ?>>Payant — Service de paiement / Mobile Money (réglage hérité <code>intouch</code>)</option>
                            <option value="paytech" <?= $aboSel === 'paytech' ? 'selected' : '' ?>>Payant — Service de paiement (explicite)</option>
                        </select>
                        <small class="form-hint">Les libellés utilisateur privilégient <strong>Service de paiement</strong> lorsque cette option ou les clés <code>PAYTECH_*</code> sont actives · repli technique InTouch uniquement hors Service de paiement.</small>
                    </div>
                    <div class="form-group">
                        <label class="admin-parametres-checkbox">
                            <input type="checkbox" name="abonnement_plan_gratuit_actif" value="1" <?= (($abonnement_plan_gratuit_actif ?? '1') === '1') ? 'checked' : '' ?>>
                            <span>Plan gratuit activé (souscription sans paiement)</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="abonnement_prix_client_xof">Prix abonnement <strong>client</strong> (FCFA/mois)</label>
                        <input type="number" name="abonnement_prix_client_xof" id="abonnement_prix_client_xof" min="0" value="<?= $e($abonnement_prix_client_xof ?? '2500') ?>">
                        <small class="form-hint">Tarif mensuel affiché aux clients.</small>
                    </div>
                    <div class="form-group">
                        <label for="abonnement_prix_expert_xof">Prix abonnement <strong>expert</strong> (FCFA/mois)</label>
                        <input type="number" name="abonnement_prix_expert_xof" id="abonnement_prix_expert_xof" min="0" value="<?= $e($abonnement_prix_expert_xof ?? '3000') ?>">
                        <small class="form-hint">Tarif mensuel affiché aux experts.</small>
                    </div>
                    <div class="form-group">
                        <label for="abonnement_prix_etudiant_xof">Prix abonnement <strong>étudiant</strong> (FCFA/mois)</label>
                        <input type="number" name="abonnement_prix_etudiant_xof" id="abonnement_prix_etudiant_xof" min="0" value="<?= $e($abonnement_prix_etudiant_xof ?? '2000') ?>">
                        <small class="form-hint">Tarif mensuel affiché aux étudiants.</small>
                    </div>
                    <div class="form-group">
                        <label for="abonnement_prix_professeur_xof">Prix abonnement <strong>professeur</strong> (FCFA/mois)</label>
                        <input type="number" name="abonnement_prix_professeur_xof" id="abonnement_prix_professeur_xof" min="0" value="<?= $e($abonnement_prix_professeur_xof ?? '3000') ?>">
                        <small class="form-hint">Tarif mensuel affiché aux professeurs.</small>
                    </div>
                    <div class="form-group">
                        <label for="abonnement_duree_jours">Durée abonnement (jours)</label>
                        <input type="number" name="abonnement_duree_jours" id="abonnement_duree_jours" min="1" max="365" value="<?= $e($abonnement_duree_jours ?? '30') ?>">
                        <small class="form-hint">30 = mensuel, 365 = annuel.</small>
                    </div>
                </div>
            </section>

            <section class="admin-parametres-block admin-parametres-block--paiement">
                <div class="admin-parametres-block-header">
                    <span class="admin-parametres-block-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    </span>
                    <h2>Paiement & devise</h2>
                </div>
                <div class="admin-parametres-block-body">
                    <div class="form-group">
                        <label for="devise_plateforme">Devise</label>
                        <input type="text" name="devise_plateforme" id="devise_plateforme" maxlength="10" value="<?= $e($devise_plateforme ?? 'XOF') ?>" placeholder="XOF, EUR…">
                        <small class="form-hint">Devise par défaut : <strong>XOF</strong> (FCFA Afrique de l'Ouest). Ex. EUR pour l'euro.</small>
                    </div>
                    <div class="form-group">
                        <label for="paiement_moyens">Moyen de paiement</label>
                        <input type="text" name="paiement_moyens" id="paiement_moyens" value="<?= $e($paiement_moyens ?? 'intouch') ?>" readonly class="input-readonly">
                        <small class="form-hint">Passerelle active : <strong>Service de paiement</strong> lorsque configurée (<code>PAYTECH_API_KEY</code>, <code>PAYTECH_API_SECRET</code>, <code>PAYTECH_ENV</code>). Repli Mobile Money (<code>INTOUCH_*</code>) possible hors Service de paiement.</small>
                    </div>
                    <div class="form-group">
                        <label for="mm_commission_pct">Frais de service Mobile Money sur l’abonnement (%)</label>
                        <input type="number" name="mm_commission_pct" id="mm_commission_pct"
                               value="<?= $e((string)($mm_commission_pct ?? '0')) ?>"
                               min="0" max="50" step="0.5" style="max-width:120px;">
                        <small class="form-hint">
                            Pourcentage ajouté au montant payé par l’utilisateur (facturé à la transaction). Grille indicative : <strong>2,5 %</strong>. Laisser <strong>0</strong> applique le taux de secours technique (2,5 %). Stocké sous <code>wave_commission_pct</code> — utilisé également pour les frais Service de paiement/abonnements.
                        </small>
                    </div>
                </div>
            </section>

            <section class="admin-parametres-block admin-parametres-block--logo">
                <div class="admin-parametres-block-header">
                    <span class="admin-parametres-block-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </span>
                    <h2>Logo de la plateforme</h2>
                </div>
                <div class="admin-parametres-block-body">
                    <div class="admin-parametres-logo-preview">
                        <div class="admin-parametres-logo-box">
                            <img src="<?= $e(logo_url()) ?>?t=<?= time() ?>" alt="Logo actuel" width="180" height="56">
                        </div>
                        <div class="admin-parametres-logo-upload">
                            <div class="form-group">
                                <label for="logo">Remplacer le logo</label>
                                <input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp" class="admin-parametres-file">
                                <small class="form-hint">PNG, JPG, GIF ou WebP. Taille max. 2 Mo. Recommandé : largeur ~200–400 px.</small>
                            </div>
                            <?php if ($logoCustom): ?>
                            <div class="form-group admin-parametres-logo-delete">
                                <label class="admin-parametres-checkbox">
                                    <input type="checkbox" name="logo_supprimer" value="1">
                                    <span>Supprimer le logo personnalisé et revenir au logo par défaut</span>
                                </label>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section class="admin-parametres-block admin-parametres-block--plateforme">
                <div class="admin-parametres-block-header">
                    <span class="admin-parametres-block-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    </span>
                    <h2>Plateforme</h2>
                </div>
                <div class="admin-parametres-block-body">
                    <div class="form-group">
                        <label for="plateforme_nom">Nom de la plateforme</label>
                        <input type="text" name="plateforme_nom" id="plateforme_nom" value="<?= $e($plateforme_nom ?? 'GLOBALO') ?>">
                    </div>
                    <div class="form-group">
                        <label for="plateforme_email">Email contact</label>
                        <input type="email" name="plateforme_email" id="plateforme_email" value="<?= $e($plateforme_email ?? '') ?>" placeholder="contact@globalo.com">
                    </div>
                </div>
            </section>

            <section class="admin-parametres-block admin-parametres-block--smtp">
                <div class="admin-parametres-block-header">
                    <span class="admin-parametres-block-icon" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </span>
                    <h2>Configuration SMTP (envoi d'emails)</h2>
                </div>
                <div class="admin-parametres-block-body">
                    <?php
                    $smtpOk = !empty($smtp_host);
                    $smtpBadge = $smtpOk
                        ? '<span style="display:inline-flex;align-items:center;gap:5px;background:#dcfce7;color:#15803d;border-radius:99px;padding:3px 10px;font-size:12px;font-weight:600;">● Configuré</span>'
                        : '<span style="display:inline-flex;align-items:center;gap:5px;background:#fef2f2;color:#dc2626;border-radius:99px;padding:3px 10px;font-size:12px;font-weight:600;">● Non configuré — les emails échouent</span>';
                    echo '<p style="margin:0 0 14px;">' . $smtpBadge . '</p>';
                    ?>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 16px;">
                        <div class="form-group" style="margin:0;">
                            <label for="smtp_host">Serveur SMTP (host)</label>
                            <input type="text" name="smtp_host" id="smtp_host"
                                   value="<?= $e($smtp_host ?? '') ?>"
                                   placeholder="smtp.gmail.com / smtp.brevo.com">
                            <small class="form-hint">Ex. <code>smtp.gmail.com</code>, <code>smtp.brevo.com</code>, <code>mail.votre-hebergeur.com</code></small>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label for="smtp_port">Port</label>
                            <input type="number" name="smtp_port" id="smtp_port"
                                   value="<?= $e($smtp_port ?? '587') ?>"
                                   placeholder="587" min="1" max="65535" style="max-width:120px;">
                            <small class="form-hint"><strong>587</strong> (STARTTLS) ou <strong>465</strong> (SSL/SMTPS)</small>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label for="smtp_user">Identifiant (login)</label>
                            <input type="text" name="smtp_user" id="smtp_user"
                                   value="<?= $e($smtp_user ?? '') ?>"
                                   placeholder="votre@email.com" autocomplete="username">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label for="smtp_pass">Mot de passe</label>
                            <input type="password" name="smtp_pass" id="smtp_pass"
                                   value=""
                                   placeholder="<?= !empty($smtp_pass ?? '') ? '••••••••  (déjà configuré — laisser vide pour conserver)' : 'Mot de passe SMTP' ?>"
                                   autocomplete="new-password">
                            <small class="form-hint">Gmail : utilisez un <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">mot de passe d'application</a>.</small>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label for="smtp_secure">Chiffrement</label>
                            <select name="smtp_secure" id="smtp_secure">
                                <option value=""   <?= (($smtp_secure ?? '') === '')    ? 'selected' : '' ?>>Aucun (déconseillé)</option>
                                <option value="tls" <?= (($smtp_secure ?? '') === 'tls') ? 'selected' : '' ?>>TLS / STARTTLS (port 587)</option>
                                <option value="1"   <?= (($smtp_secure ?? '') === '1')   ? 'selected' : '' ?>>SSL implicite (port 465)</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label for="mail_from">Email expéditeur (From)</label>
                            <input type="email" name="mail_from" id="mail_from"
                                   value="<?= $e($mail_from ?? '') ?>"
                                   placeholder="noreply@globalo.fr">
                            <small class="form-hint">Doit correspondre à l'adresse autorisée par votre serveur SMTP.</small>
                        </div>
                    </div>
                    <p style="margin:14px 0 0;padding:10px 14px;background:#fffbeb;border-left:3px solid #f59e0b;border-radius:0 6px 6px 0;font-size:12px;color:#92400e;">
                        <strong>Note :</strong> si ces paramètres sont également définis dans le fichier <code>.env</code> (<code>SMTP_HOST</code>, etc.), le <code>.env</code> a priorité sur les valeurs ci-dessous.
                    </p>

                </div>
            </section>
        </div>

        <div class="admin-parametres-actions">
            <button type="submit" class="btn btn-primary btn-lg admin-parametres-submit">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Enregistrer les paramètres
            </button>
        </div>
    </form>

    <!-- ════════════════════════════════════════════════════════════════════
         BLOC MAINTENANCE — formulaire séparé (hors du form principal)
         ════════════════════════════════════════════════════════════════════ -->
    <div class="admin-maintenance-block" id="maintenance">
        <div class="admin-maintenance-block__header">
            <span class="admin-maintenance-block__icon <?= $maintenanceOn ? 'admin-maintenance-block__icon--on' : '' ?>">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </span>
            <div>
                <h2>Mode Maintenance</h2>
                <p>Affiche une page de maintenance aux visiteurs pendant les mises à jour.</p>
            </div>
            <div class="admin-maintenance-block__status <?= $maintenanceOn ? 'admin-maintenance-block__status--on' : 'admin-maintenance-block__status--off' ?>">
                <span class="admin-maintenance-dot <?= $maintenanceOn ? 'admin-maintenance-dot--on' : '' ?>"></span>
                <?= $maintenanceOn ? 'ACTIF — site en maintenance' : 'Inactif — site en ligne' ?>
            </div>
        </div>

        <?php if ($maintenanceOn): ?>
        <div class="admin-maintenance-active-info">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div>
                <strong>Maintenance en cours</strong> —
                Action : <code><?= $e($maintenanceMeta['action'] ?? 'deploy') ?></code>
                <?php if (!empty($maintenanceMeta['message'])): ?>
                    · "<?= $e($maintenanceMeta['message']) ?>"
                <?php endif; ?>
                <?php if (!empty($maintenanceMeta['eta'])): ?>
                    · ETA : <?= $e($maintenanceMeta['eta']) ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= $baseUrl ?>/admin/maintenance" class="admin-maintenance-form" id="form-maintenance">
            <?= $csrfField ?>

            <div class="admin-maintenance-fields" id="maintenance-fields" <?= $maintenanceOn ? '' : 'style="display:none"' ?>>
                <div class="admin-maintenance-row">
                    <div class="form-group">
                        <label for="maintenance_action">Type de mise à jour</label>
                        <select name="maintenance_action" id="maintenance_action">
                            <option value="deploy"    <?= ($maintenanceMeta['action'] ?? '') === 'deploy'    ? 'selected' : '' ?>>🚀 Déploiement fichiers</option>
                            <option value="migration" <?= ($maintenanceMeta['action'] ?? '') === 'migration' ? 'selected' : '' ?>>🗄️ Migration base de données</option>
                            <option value="pays"      <?= ($maintenanceMeta['action'] ?? '') === 'pays'      ? 'selected' : '' ?>>🌍 Changement zone géographique</option>
                            <option value="patch"     <?= ($maintenanceMeta['action'] ?? '') === 'patch'     ? 'selected' : '' ?>>🔧 Correctif urgent</option>
                            <option value="backup"    <?= ($maintenanceMeta['action'] ?? '') === 'backup'    ? 'selected' : '' ?>>💾 Sauvegarde & maintenance</option>
                            <option value="config"    <?= ($maintenanceMeta['action'] ?? '') === 'config'    ? 'selected' : '' ?>>⚙️ Reconfiguration serveur</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="maintenance_progress">Progression (%)</label>
                        <div class="admin-maintenance-progress-wrap">
                            <input type="range" name="maintenance_progress" id="maintenance_progress"
                                   min="0" max="100" step="5"
                                   value="<?= (int)($maintenanceMeta['progress'] ?? 10) ?>"
                                   oninput="document.getElementById('maintenance_progress_val').textContent = this.value + '%'">
                            <span id="maintenance_progress_val"><?= (int)($maintenanceMeta['progress'] ?? 10) ?>%</span>
                        </div>
                        <small class="form-hint">Affiché sur la page maintenance (0 = pas de barre).</small>
                    </div>
                </div>
                <div class="admin-maintenance-row">
                    <div class="form-group">
                        <label for="maintenance_message">Message personnalisé</label>
                        <input type="text" name="maintenance_message" id="maintenance_message"
                               value="<?= $e($maintenanceMeta['message'] ?? '') ?>"
                               placeholder="Nous améliorons la plateforme, retour imminent…" maxlength="300">
                        <small class="form-hint">Laisser vide pour le message par défaut.</small>
                    </div>
                    <div class="form-group">
                        <label for="maintenance_eta">Retour estimé (optionnel)</label>
                        <input type="datetime-local" name="maintenance_eta" id="maintenance_eta"
                               value="<?= $e(isset($maintenanceMeta['eta']) ? date('Y-m-d\TH:i', strtotime($maintenanceMeta['eta'])) : '') ?>">
                        <small class="form-hint">Affiche un compteur à rebours sur la page.</small>
                    </div>
                </div>
                <div class="form-group">
                    <label for="maintenance_contact">Email de contact urgence</label>
                    <input type="email" name="maintenance_contact" id="maintenance_contact"
                           value="<?= $e($maintenanceMeta['contact'] ?? 'admin@globalo.secogesarl.com') ?>"
                           placeholder="admin@globalo.secogesarl.com">
                </div>
            </div>

            <div class="admin-maintenance-actions">
                <?php if (!$maintenanceOn): ?>
                <button type="button" class="admin-maintenance-btn admin-maintenance-btn--configure" onclick="toggleMaintenanceFields()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    Configurer
                </button>
                <button type="submit" name="maintenance_toggle" value="on"
                        class="admin-maintenance-btn admin-maintenance-btn--on"
                        onclick="return confirm('Activer le mode maintenance ? Le site sera inaccessible aux visiteurs.')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                    Activer la maintenance
                </button>
                <?php else: ?>
                <button type="submit" name="maintenance_toggle" value="on"
                        class="admin-maintenance-btn admin-maintenance-btn--update">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    Mettre à jour
                </button>
                <button type="submit" name="maintenance_toggle" value="off"
                        class="admin-maintenance-btn admin-maintenance-btn--off"
                        onclick="return confirm('Désactiver la maintenance et remettre le site en ligne ?')">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
                    Remettre en ligne
                </button>
                <?php endif; ?>
                <a href="<?= $baseUrl ?>/maintenance.php" target="_blank" class="admin-maintenance-btn admin-maintenance-btn--preview">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    Prévisualiser
                </a>
            </div>
        </form>
    </div>

    <style>
    .admin-maintenance-block {
        margin: 28px 0 0;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        background: #fff;
        overflow: hidden;
    }
    .admin-maintenance-block__header {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 20px 24px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        flex-wrap: wrap;
    }
    .admin-maintenance-block__icon {
        width: 44px; height: 44px;
        border-radius: 10px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        display: flex; align-items: center; justify-content: center;
        color: #64748b;
        flex-shrink: 0;
    }
    .admin-maintenance-block__icon--on {
        background: #fef2f2; border-color: #fecaca; color: #dc2626;
    }
    .admin-maintenance-block__header h2 {
        font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0 0 3px;
    }
    .admin-maintenance-block__header p {
        font-size: .8125rem; color: #64748b; margin: 0;
    }
    .admin-maintenance-block__status {
        margin-left: auto;
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px;
        border-radius: 999px;
        font-size: .8rem; font-weight: 700;
        border: 1.5px solid;
    }
    .admin-maintenance-block__status--off {
        background: #f0fdf4; border-color: #bbf7d0; color: #15803d;
    }
    .admin-maintenance-block__status--on {
        background: #fef2f2; border-color: #fecaca; color: #dc2626;
    }
    .admin-maintenance-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #22c55e;
        flex-shrink: 0;
    }
    .admin-maintenance-dot--on {
        background: #ef4444;
        animation: mPulse 1.2s ease-in-out infinite;
    }
    @keyframes mPulse {
        0%,100% { box-shadow: 0 0 0 0 rgba(239,68,68,.5); }
        50%      { box-shadow: 0 0 0 5px rgba(239,68,68,0); }
    }
    .admin-maintenance-active-info {
        display: flex; align-items: flex-start; gap: 10px;
        padding: 12px 24px;
        background: #fffbeb;
        border-bottom: 1px solid #fde68a;
        font-size: .8125rem; color: #92400e;
        line-height: 1.5;
    }
    .admin-maintenance-active-info svg { flex-shrink: 0; margin-top: 1px; color: #f59e0b; }
    .admin-maintenance-active-info code {
        background: rgba(245,158,11,.15); padding: 1px 6px;
        border-radius: 4px; font-size: .78rem;
    }
    .admin-maintenance-form { padding: 20px 24px; }
    .admin-maintenance-fields { margin-bottom: 20px; }
    .admin-maintenance-row {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 16px; margin-bottom: 14px;
    }
    @media (max-width: 700px) { .admin-maintenance-row { grid-template-columns: 1fr; } }
    .admin-maintenance-progress-wrap {
        display: flex; align-items: center; gap: 10px;
    }
    .admin-maintenance-progress-wrap input[type="range"] {
        flex: 1; accent-color: #0f172a;
    }
    .admin-maintenance-progress-wrap span {
        min-width: 42px; font-weight: 700; font-size: .875rem;
        color: #0f172a; text-align: right;
    }
    .admin-maintenance-actions {
        display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    }
    .admin-maintenance-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 9px 18px;
        border-radius: 8px;
        font-size: .875rem; font-weight: 600;
        cursor: pointer; border: none; text-decoration: none;
        transition: all .18s;
    }
    .admin-maintenance-btn--configure {
        background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;
    }
    .admin-maintenance-btn--configure:hover { background: #e2e8f0; }
    .admin-maintenance-btn--on {
        background: #dc2626; color: #fff;
    }
    .admin-maintenance-btn--on:hover { background: #b91c1c; }
    .admin-maintenance-btn--update {
        background: #f59e0b; color: #fff;
    }
    .admin-maintenance-btn--update:hover { background: #d97706; }
    .admin-maintenance-btn--off {
        background: #16a34a; color: #fff;
    }
    .admin-maintenance-btn--off:hover { background: #15803d; }
    .admin-maintenance-btn--preview {
        background: #f8fafc; color: #475569;
        border: 1px solid #cbd5e1; margin-left: auto;
    }
    .admin-maintenance-btn--preview:hover { background: #f1f5f9; color: #0f172a; }
    </style>

    <script>
    function toggleMaintenanceFields() {
        var fields = document.getElementById('maintenance-fields');
        var btn    = document.querySelector('.admin-maintenance-btn--configure');
        if (fields.style.display === 'none') {
            fields.style.display = '';
            if (btn) btn.textContent = '▲ Masquer';
        } else {
            fields.style.display = 'none';
            if (btn) { btn.innerHTML = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg> Configurer'; }
        }
    }
    </script>

    <!-- Bloc test SMTP — EN DEHORS du formulaire principal pour éviter la soumission imbriquée -->
    <div style="margin:24px 0 0;padding:18px 20px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
        <p style="margin:0 0 6px;font-size:14px;font-weight:700;color:#0f172a;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:5px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            Tester la configuration SMTP
        </p>
        <p style="margin:0 0 14px;font-size:12px;color:#64748b;line-height:1.5;">
            Envoie un email de test avec la configuration actuellement active.
            <strong>Sauvegardez d'abord</strong> vos paramètres avant de tester.
        </p>
        <form method="POST" action="<?= $baseUrl ?>/admin/test-smtp" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <?= $csrfField ?>
            <input type="email" name="test_email"
                   required
                   placeholder="votre@email.com"
                   style="flex:1;min-width:200px;padding:.55rem .85rem;border:1.5px solid #cbd5e1;border-radius:8px;font-size:13px;color:#0f172a;background:#fff;outline:none;">
            <button type="submit"
                    style="display:inline-flex;align-items:center;gap:6px;padding:.55rem 1.2rem;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;transition:background .15s;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Envoyer un email de test
            </button>
        </form>
    </div>
</div>
