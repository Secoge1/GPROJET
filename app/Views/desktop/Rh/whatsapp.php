<?php
$baseUrl        = rtrim(BASE_URL ?? '', '/');
$wa_configured  = $wa_configured ?? false;
$stats          = $stats ?? [];
$webhook_url    = $webhook_url ?? '';
$verify_token   = $verify_token ?? '';
$ia_active      = $ia_active ?? false;
$migrationResult= $migrationResult ?? null;
$e = fn($s) => \App\Core\Security::escape((string)($s ?? ''));
?>
<link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/rh.css?v=<?= time() ?>">

<div class="rh-page">

    <!-- ══ HEADER ════════════════════════════════════════════════════════ -->
    <div class="rh-header rh-header--wa">
        <div>
            <div class="rh-header__badge">
                <span class="rh-pulse"></span>
                WhatsApp IA
            </div>
            <h1 class="rh-header__title">
                <span class="rh-wa-header-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" style="color:#fff">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </span>
                GAIA — WhatsApp IA
            </h1>
            <p class="rh-header__sub">Agent IA automatique pour répondre à vos clients 24h/24 sur WhatsApp Business</p>
        </div>
        <div class="rh-header__actions">
            <div class="rh-wa-live-badge <?= ($wa_configured && $ia_active) ? 'rh-wa-live-badge--on' : 'rh-wa-live-badge--off' ?>">
                <span class="rh-pulse-dot <?= ($wa_configured && $ia_active) ? 'rh-pulse-dot--active' : '' ?>"></span>
                <?= ($wa_configured && $ia_active) ? 'Agent en ligne' : 'Agent hors ligne' ?>
            </div>
            <a href="<?= $baseUrl ?>/admin" class="rh-btn rh-btn--ghost">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                Admin
            </a>
        </div>
    </div>

    <!-- ══ NAVIGATION RH ══════════════════════════════════════════════════ -->
    <nav class="rh-subnav" aria-label="Navigation RH">
        <a href="<?= $baseUrl ?>/rh" class="rh-subnav__item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>
        <a href="<?= $baseUrl ?>/rh/inscriptions" class="rh-subnav__item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
            Inscriptions
        </a>
        <a href="<?= $baseUrl ?>/rh/profils" class="rh-subnav__item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profils
        </a>
        <a href="<?= $baseUrl ?>/rh/marketing" class="rh-subnav__item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            Marketing
        </a>
        <a href="<?= $baseUrl ?>/rh/manager" class="rh-subnav__item">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Manager
        </a>
        <a href="<?= $baseUrl ?>/rh/whatsapp" class="rh-subnav__item rh-subnav__item--active">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            WhatsApp IA
        </a>
    </nav>

    <?php if ($migrationResult !== null): ?>
    <div class="rh-alerte <?= $migrationResult['success'] ? 'rh-alerte--info' : 'rh-alerte--error' ?>" style="margin-bottom:20px;padding:14px 16px;">
        <?php if ($migrationResult['success']): ?>
            ✅ Migration réussie ! <?= (int) $migrationResult['ok'] ?> table(s) créée(s).
            <a href="<?= $baseUrl ?>/rh/whatsapp" style="color:inherit;text-decoration:underline;margin-left:6px">Recharger</a>
        <?php else: ?>
            ❌ Erreurs : <pre style="margin:8px 0 0;font-size:.75rem"><?= $e(implode("\n", $migrationResult['errors'])) ?></pre>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- ══ GRILLE PRINCIPALE ══════════════════════════════════════════════ -->
    <div class="rh-wa-layout">

        <!-- ══ COLONNE GAUCHE ════════════════════════════════════════════ -->
        <div class="rh-wa-left">

            <!-- Statut agent -->
            <div class="rh-wa-agent-card">
                <div class="rh-wa-agent-card__glow"></div>
                <div class="rh-wa-agent-card__top">
                    <div class="rh-wa-agent-avatar">🤖</div>
                    <div class="rh-wa-agent-info">
                        <h2>Agent GAIA</h2>
                        <p>Assistante IA WhatsApp GLOBALO</p>
                    </div>
                    <div class="rh-wa-status-badge <?= ($wa_configured && $ia_active) ? 'rh-wa-status-badge--active' : 'rh-wa-status-badge--inactive' ?>">
                        <span class="rh-pulse-dot <?= ($wa_configured && $ia_active) ? 'rh-pulse-dot--active' : '' ?>"></span>
                        <?= ($wa_configured && $ia_active) ? 'En ligne' : 'Hors ligne' ?>
                    </div>
                </div>

                <div class="rh-wa-checks">
                    <div class="rh-wa-check <?= $ia_active ? 'rh-wa-check--ok' : 'rh-wa-check--err' ?>">
                        <span class="rh-wa-check__icon"><?= $ia_active ? '✅' : '❌' ?></span>
                        <span class="rh-wa-check__name">IA Mistral</span>
                        <span class="rh-wa-check__status"><?= $ia_active ? 'Configurée' : 'Clé API manquante' ?></span>
                    </div>
                    <div class="rh-wa-check <?= $wa_configured ? 'rh-wa-check--ok' : 'rh-wa-check--err' ?>">
                        <span class="rh-wa-check__icon"><?= $wa_configured ? '✅' : '❌' ?></span>
                        <span class="rh-wa-check__name">WhatsApp Business API</span>
                        <span class="rh-wa-check__status"><?= $wa_configured ? 'Configurée' : 'Token manquant' ?></span>
                    </div>
                    <div class="rh-wa-check rh-wa-check--ok">
                        <span class="rh-wa-check__icon">✅</span>
                        <span class="rh-wa-check__name">Webhook URL</span>
                        <span class="rh-wa-check__status">Prête</span>
                    </div>
                </div>
            </div>

            <!-- Statistiques -->
            <?php if (!empty($stats)): ?>
            <div class="rh-wa-stats">
                <div class="rh-wa-stat">
                    <span class="rh-wa-stat__icon">💬</span>
                    <div>
                        <span class="rh-wa-stat__val"><?= number_format((int)($stats['total_conversations'] ?? 0)) ?></span>
                        <span class="rh-wa-stat__label">Conversations</span>
                    </div>
                </div>
                <div class="rh-wa-stat">
                    <span class="rh-wa-stat__icon">📨</span>
                    <div>
                        <span class="rh-wa-stat__val"><?= number_format((int)($stats['messages_24h'] ?? 0)) ?></span>
                        <span class="rh-wa-stat__label">Messages 24h</span>
                    </div>
                </div>
                <div class="rh-wa-stat">
                    <span class="rh-wa-stat__icon">👥</span>
                    <div>
                        <span class="rh-wa-stat__val"><?= number_format((int)($stats['actifs_7j'] ?? 0)) ?></span>
                        <span class="rh-wa-stat__label">Actifs 7 jours</span>
                    </div>
                </div>
                <div class="rh-wa-stat">
                    <span class="rh-wa-stat__icon">📊</span>
                    <div>
                        <span class="rh-wa-stat__val"><?= number_format((int)($stats['total_messages'] ?? 0)) ?></span>
                        <span class="rh-wa-stat__label">Total messages</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Migration BDD -->
            <div class="rh-wa-section">
                <div class="rh-wa-section__header">
                    <span class="rh-wa-section__icon">🗄️</span>
                    <div>
                        <h3>Base de données</h3>
                        <p>Créer les tables WhatsApp si nécessaire.</p>
                    </div>
                </div>
                <form method="POST" action="<?= $baseUrl ?>/rh/migration-whatsapp">
                    <button type="submit" class="rh-btn rh-btn--ghost rh-btn--full"
                            onclick="return confirm('Créer les tables WhatsApp ?')">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
                        Exécuter la migration WhatsApp
                    </button>
                </form>
            </div>

        </div><!-- /colonne gauche -->

        <!-- ══ COLONNE DROITE : Guide de configuration ═══════════════════ -->
        <div class="rh-wa-right">

            <div class="rh-wa-guide">
                <div class="rh-wa-guide__header">
                    <div class="rh-wa-guide__icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polyline points="13 2 13 9 20 9"/><path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9.34"/><polygon points="18 2 22 6 12 16 8 16 8 12 18 2"/></svg>
                    </div>
                    <div>
                        <h3>Guide de configuration</h3>
                        <p>5 étapes pour activer GAIA sur WhatsApp Business</p>
                    </div>
                </div>

                <div class="rh-wa-steps">

                    <div class="rh-wa-step">
                        <div class="rh-wa-step__num">1</div>
                        <div class="rh-wa-step__content">
                            <h4>Créer un compte Meta for Developers</h4>
                            <p>Connectez-vous sur <a href="https://developers.facebook.com" target="_blank" rel="noopener" class="rh-link">developers.facebook.com</a> avec votre compte Facebook professionnel.</p>
                            <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener" class="rh-wa-step__cta">
                                Ouvrir Meta for Developers
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
                            </a>
                        </div>
                    </div>

                    <div class="rh-wa-step">
                        <div class="rh-wa-step__num">2</div>
                        <div class="rh-wa-step__content">
                            <h4>Créer une application WhatsApp Business</h4>
                            <p>Nouvelle app → Type <strong style="color:#fff">Business</strong> → Produit <strong style="color:#fff">WhatsApp</strong>. Vous obtiendrez :</p>
                            <ul class="rh-wa-step__list">
                                <li>Un <strong style="color:#fff">Phone Number ID</strong></li>
                                <li>Un <strong style="color:#fff">Access Token</strong> (System User permanent recommandé)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="rh-wa-step">
                        <div class="rh-wa-step__num">3</div>
                        <div class="rh-wa-step__content">
                            <h4>Configurer le webhook</h4>
                            <p>Meta → WhatsApp → Configuration → Webhooks :</p>
                            <div class="rh-wa-code-block">
                                <label>URL de callback</label>
                                <code><?= $e($webhook_url ?: 'https://globalo.secogesarl.com/api/whatsapp/webhook') ?></code>
                                <button class="rh-wa-copy-btn" onclick="rhCopy(this, '<?= $e($webhook_url ?: 'https://globalo.secogesarl.com/api/whatsapp/webhook') ?>')">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    Copier
                                </button>
                            </div>
                            <div class="rh-wa-code-block" style="margin-top:8px">
                                <label>Token de vérification</label>
                                <code><?= $e($verify_token ?: 'globalo_webhook_2026') ?></code>
                                <button class="rh-wa-copy-btn" onclick="rhCopy(this, '<?= $e($verify_token ?: 'globalo_webhook_2026') ?>')">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    Copier
                                </button>
                            </div>
                            <p class="rh-wa-step__note">⚠️ Abonnez-vous au champ <strong style="color:#fff">messages</strong> dans les webhooks.</p>
                        </div>
                    </div>

                    <div class="rh-wa-step">
                        <div class="rh-wa-step__num">4</div>
                        <div class="rh-wa-step__content">
                            <h4>Ajouter les credentials dans le <code style="background:rgba(255,255,255,.08);padding:1px 5px;border-radius:4px;font-size:.8em">.env</code> du serveur</h4>
                            <div class="rh-wa-code-block rh-wa-code-block--full">
                                <button class="rh-wa-copy-btn" style="margin-bottom:8px" onclick="rhCopy(this, 'WHATSAPP_ACCESS_TOKEN=EAAxxxxxxxx...\nWHATSAPP_PHONE_NUMBER_ID=123456789012345\nWHATSAPP_WEBHOOK_VERIFY_TOKEN=<?= $e($verify_token ?: 'globalo_webhook_2026') ?>')">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                    Tout copier
                                </button>
                                <pre>WHATSAPP_ACCESS_TOKEN=EAAxxxxxxxx...
WHATSAPP_PHONE_NUMBER_ID=123456789012345
WHATSAPP_WEBHOOK_VERIFY_TOKEN=<?= $e($verify_token ?: 'globalo_webhook_2026') ?></pre>
                            </div>
                        </div>
                    </div>

                    <div class="rh-wa-step rh-wa-step--last">
                        <div class="rh-wa-step__num rh-wa-step__num--done">✓</div>
                        <div class="rh-wa-step__content">
                            <h4>Tester et mettre en production</h4>
                            <p>Avec le numéro test Meta, envoyez un message à votre numéro WhatsApp Business. GAIA devrait répondre automatiquement.</p>
                            <p style="margin-top:6px">Pour la production : soumettez votre app à la <strong style="color:#fff">vérification Meta</strong> et ajoutez vos numéros clients.</p>
                            <div class="rh-wa-tip">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                Avec un compte <strong>gratuit</strong>, vous pouvez envoyer jusqu'à <strong>1 000 conversations/mois</strong> gratuitement.
                            </div>
                        </div>
                    </div>

                </div><!-- /.rh-wa-steps -->
            </div>

        </div><!-- /colonne droite -->

    </div><!-- /.rh-wa-layout -->

</div><!-- /.rh-page -->

<script>
function rhCopy(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
        const svg = btn.querySelector('svg');
        const orig = btn.innerHTML;
        btn.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Copié !';
        btn.style.color = '#34d399';
        btn.style.borderColor = 'rgba(52,211,153,.4)';
        setTimeout(() => { btn.innerHTML = orig; btn.style.color = ''; btn.style.borderColor = ''; }, 2200);
    }).catch(() => {
        const ta = document.createElement('textarea');
        ta.value = text; document.body.appendChild(ta);
        ta.select(); document.execCommand('copy');
        document.body.removeChild(ta);
    });
}
</script>
