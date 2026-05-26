<?php
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$csrfField  = \App\Core\Security::getCsrfField();
$esc        = fn($v) => \App\Core\Security::escape((string)($v ?? ''));
$p          = fn(string $k, string $d = '') => $esc($config[$k] ?? $d);
$historique = $historique ?? [];

$flashOk    = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
$flashErr   = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);

$joursOptions = [
    'lundi' => 'Lun', 'mardi' => 'Mar', 'mercredi' => 'Mer',
    'jeudi' => 'Jeu', 'vendredi' => 'Ven', 'samedi' => 'Sam', 'dimanche' => 'Dim',
];
$joursActifs  = json_decode($config['social_jours_actifs'] ?? '["lundi","mercredi","vendredi","samedi"]', true) ?: [];
$planningActuel = json_decode($config['social_planning'] ?? '{}', true) ?: [];
$defaults = [
    'lundi'    => "Comment trouver le bon expert digital en Afrique de l'Ouest avec GLOBALO ?",
    'mercredi' => "Les avantages d'un abonnement GLOBALO pour les professionnels",
    'vendredi' => "Développez votre activité freelance et gagnez plus grâce à GLOBALO",
    'samedi'   => "5 conseils pour rédiger une demande de mission qui attire les meilleurs experts",
];
?>
<div class="page-admin page-admin-social">

    <!-- Hero -->
    <header class="as-hero">
        <a href="<?= $baseUrl ?>/admin" class="as-hero__back">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="as-hero__body">
            <div class="as-hero__icon" aria-hidden="true">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/></svg>
            </div>
            <div class="as-hero__text">
                <h1>Publication IA · Réseaux Sociaux</h1>
                <p>Automatisation Facebook &amp; LinkedIn — sans frais avec Gemini ou Mistral</p>
            </div>
            <div class="as-hero__badges" aria-hidden="true">
                <span class="as-badge as-badge--fb">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    Facebook
                </span>
                <span class="as-badge as-badge--li">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
                    LinkedIn
                </span>
                <span class="as-badge as-badge--ia">✦ IA</span>
            </div>
        </div>
    </header>

    <!-- Alertes flash -->
    <?php if ($flashOk): ?>
    <div class="as-flash as-flash--ok">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <?= $esc($flashOk) ?>
    </div>
    <?php endif; ?>
    <?php if ($flashErr): ?>
    <div class="as-flash as-flash--err">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <?= $esc($flashErr) ?>
    </div>
    <?php endif; ?>

    <form method="post" action="<?= $baseUrl ?>/admin/social-config">
        <?= $csrfField ?>

        <div class="as-grid">

            <!-- ── Colonne gauche ── -->
            <div class="as-col">

                <!-- IA Rédaction -->
                <div class="as-card">
                    <div class="as-card__head as-card__head--green">
                        <span class="as-card__icon" style="background:#dcfce7;color:#16a34a;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </span>
                        <div>
                            <h2>IA Rédaction</h2>
                            <p>Provider IA pour générer les posts automatiquement</p>
                        </div>
                    </div>
                    <div class="as-card__body">
                        <div class="as-field">
                            <label>Provider IA <span class="as-label-badge as-label-badge--free">★ Gemini = Gratuit</span></label>
                            <select name="social_ai_provider">
                                <option value="gemini"  <?= $p('social_ai_provider','gemini') === 'gemini'  ? 'selected':'' ?>>Google Gemini 1.5 Flash (Gratuit)</option>
                                <option value="mistral" <?= $p('social_ai_provider') === 'mistral' ? 'selected':'' ?>>Mistral Small (Gratuit)</option>
                                <option value="openai"  <?= $p('social_ai_provider') === 'openai'  ? 'selected':'' ?>>OpenAI GPT-3.5</option>
                            </select>
                        </div>
                        <div class="as-field">
                            <label>Clé API IA</label>
                            <input type="password" name="social_ai_api_key" value="<?= $p('social_ai_api_key') ?>" placeholder="AIza… (Gemini) ou sk-… (OpenAI)" autocomplete="off">
                            <small class="as-hint">
                                Gemini : <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">aistudio.google.com</a> (gratuit) ·
                                Mistral : <a href="https://console.mistral.ai" target="_blank" rel="noopener">console.mistral.ai</a>
                            </small>
                        </div>
                        <div class="as-field">
                            <label>Ton des publications</label>
                            <input type="text" name="social_ton" value="<?= $p('social_ton','professionnel et engageant') ?>">
                            <small class="as-hint">Ex : professionnel, inspirant, humoristique, éducatif</small>
                        </div>
                        <div class="as-field">
                            <label>Hashtags automatiques</label>
                            <input type="text" name="social_hashtags" value="<?= $p('social_hashtags','#GLOBALO #Freelance #AfriqueOuest #Experts') ?>">
                        </div>
                    </div>
                </div>

                <!-- Facebook -->
                <div class="as-card">
                    <div class="as-card__head as-card__head--blue">
                        <span class="as-card__icon" style="background:#dbeafe;color:#1877f2;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        </span>
                        <div>
                            <h2>Facebook Page</h2>
                            <p>Publier automatiquement sur votre Page Facebook</p>
                        </div>
                        <label class="as-toggle" style="margin-left:auto;" title="Activer Facebook">
                            <input type="checkbox" name="social_fb_enabled" value="1" <?= $p('social_fb_enabled') === '1' ? 'checked' : '' ?>>
                            <span class="as-toggle__track"><span class="as-toggle__thumb"></span></span>
                        </label>
                    </div>
                    <div class="as-card__body">
                        <div class="as-field">
                            <label>Page ID</label>
                            <input type="text" name="social_fb_page_id" value="<?= $p('social_fb_page_id') ?>" placeholder="123456789012345">
                        </div>
                        <div class="as-field">
                            <label>Page Access Token (permanent)</label>
                            <input type="password" name="social_fb_token" value="<?= $p('social_fb_token') ?>" placeholder="EAAxxxxx…" autocomplete="off">
                            <small class="as-hint">Meta for Developers → Graph API Explorer → Page → <code>pages_manage_posts</code></small>
                        </div>
                    </div>
                </div>

                <!-- LinkedIn -->
                <div class="as-card">
                    <div class="as-card__head as-card__head--linkedin">
                        <span class="as-card__icon" style="background:#dbeafe;color:#0a66c2;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6zM2 9h4v12H2z"/><circle cx="4" cy="4" r="2"/></svg>
                        </span>
                        <div>
                            <h2>LinkedIn Page</h2>
                            <p>Publier automatiquement sur votre Page LinkedIn</p>
                        </div>
                        <label class="as-toggle" style="margin-left:auto;" title="Activer LinkedIn">
                            <input type="checkbox" name="social_li_enabled" value="1" <?= $p('social_li_enabled') === '1' ? 'checked' : '' ?>>
                            <span class="as-toggle__track"><span class="as-toggle__thumb"></span></span>
                        </label>
                    </div>
                    <div class="as-card__body">
                        <div class="as-field">
                            <label>Organization ID</label>
                            <input type="text" name="social_li_org_id" value="<?= $p('social_li_org_id') ?>" placeholder="12345678">
                            <small class="as-hint">linkedin.com/company/votre-page → URL ou admin → About</small>
                        </div>
                        <div class="as-field">
                            <label>Access Token</label>
                            <input type="password" name="social_li_token" value="<?= $p('social_li_token') ?>" placeholder="AQV…" autocomplete="off">
                            <small class="as-hint">LinkedIn Developer Portal → App → OAuth 2.0 → <code>w_organization_social</code></small>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── Colonne droite ── -->
            <div class="as-col">

                <!-- Planning -->
                <div class="as-card">
                    <div class="as-card__head as-card__head--amber">
                        <span class="as-card__icon" style="background:#fef3c7;color:#d97706;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        </span>
                        <div>
                            <h2>Planning de publication</h2>
                            <p>Jours et heure de diffusion automatique</p>
                        </div>
                    </div>
                    <div class="as-card__body">
                        <div class="as-field">
                            <label>Jours de publication</label>
                            <div class="as-days">
                                <?php foreach ($joursOptions as $val => $label): ?>
                                <label class="as-day-chip <?= in_array($val, $joursActifs) ? 'is-active' : '' ?>">
                                    <input type="checkbox" name="social_jours_actifs[]" value="<?= $val ?>"
                                        <?= in_array($val, $joursActifs) ? 'checked' : '' ?>>
                                    <?= $label ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="as-field">
                            <label>Heure de publication <span class="as-hint-inline">(UTC+0)</span></label>
                            <div class="as-hour-input">
                                <input type="number" name="social_heure_publication" min="0" max="23" value="<?= $p('social_heure_publication','9') ?>">
                                <span class="as-hour-suffix">h00 UTC</span>
                            </div>
                            <small class="as-hint">9 = 09h UTC · 10h Dakar · 10h Abidjan</small>
                        </div>
                    </div>
                </div>

                <!-- Sujets par jour -->
                <div class="as-card">
                    <div class="as-card__head as-card__head--purple">
                        <span class="as-card__icon" style="background:#ede9fe;color:#7c3aed;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </span>
                        <div>
                            <h2>Sujets par jour</h2>
                            <p>Thème IA de chaque publication planifiée</p>
                        </div>
                    </div>
                    <div class="as-card__body">
                        <?php foreach (['lundi','mercredi','vendredi','samedi'] as $j): ?>
                        <div class="as-field">
                            <label><?= ucfirst($j) ?></label>
                            <input type="text" name="social_sujet_<?= $j ?>"
                                value="<?= $esc($planningActuel[$j] ?? $defaults[$j] ?? '') ?>">
                        </div>
                        <?php endforeach; ?>
                        <small class="as-hint">Laissez vide = pas de publication ce jour-là.</small>
                    </div>
                </div>

                <!-- Sécurité Cron -->
                <div class="as-card">
                    <div class="as-card__head as-card__head--red">
                        <span class="as-card__icon" style="background:#fee2e2;color:#ef4444;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </span>
                        <div>
                            <h2>Sécurité Cron</h2>
                            <p>Clé secrète pour sécuriser l'endpoint API</p>
                        </div>
                    </div>
                    <div class="as-card__body">
                        <div class="as-field">
                            <label>Cron Secret <code class="as-code-inline">X-Cron-Secret</code></label>
                            <input type="text" name="cron_secret" value="<?= $p('cron_secret') ?>" placeholder="Générez une chaîne aléatoire longue">
                            <small class="as-hint">Sécurise <code>/api/cron/social</code> depuis GitHub Actions.</small>
                        </div>
                    </div>
                </div>

                <!-- GitHub Actions -->
                <div class="as-card as-card--dark">
                    <div class="as-card__head">
                        <span class="as-card__icon" style="background:rgba(255,255,255,.1);color:#fff;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
                        </span>
                        <div>
                            <h2 style="color:#fff;">GitHub Actions</h2>
                            <p style="color:#94a3b8;">Planificateur automatique · YAML à copier</p>
                        </div>
                    </div>
                    <div class="as-card__body">
                        <p class="as-dark-hint">Copiez ce YAML dans <code>.github/workflows/social.yml</code> de votre dépôt :</p>
                        <pre class="as-code-block">name: Publication IA auto

on:
  schedule:
    - cron: '0 9 * * 1,3,5,6'
  workflow_dispatch:

jobs:
  publish:
    runs-on: ubuntu-latest
    steps:
      - name: Déclencher publication GLOBALO
        run: |
          curl -s -X POST \
            "<?= rtrim(BASE_URL ?? 'https://globalo.secogesarl.com', '/') ?>/api/cron/social" \
            -H "X-Cron-Secret: ${{ secrets.CRON_SECRET }}" \
            -H "Content-Type: application/json"</pre>
                        <p class="as-dark-hint" style="margin-top:.625rem;">
                            Ajoutez <code>CRON_SECRET</code> dans Settings → Secrets → Actions de votre dépôt GitHub.
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Bouton Sauvegarder -->
        <div class="as-footer-bar">
            <button type="submit" class="as-submit">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Enregistrer la configuration
            </button>
        </div>
    </form>

    <!-- Tester maintenant -->
    <div class="as-card as-card--test">
        <div class="as-card__head as-card__head--green">
            <span class="as-card__icon" style="background:#dcfce7;color:#16a34a;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            </span>
            <div>
                <h2>Tester maintenant</h2>
                <p>Générer un aperçu IA ou publier immédiatement</p>
            </div>
        </div>
        <div class="as-card__body">
            <div class="as-test-row">
                <div class="as-field" style="flex:1;min-width:260px;">
                    <label>Sujet du post test</label>
                    <input type="text" id="test-sujet" placeholder="Ex: Comment GLOBALO aide les freelances…">
                </div>
                <div class="as-test-actions">
                    <button type="button" class="as-btn-ghost" id="btn-apercu">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        Aperçu IA
                    </button>
                    <button type="button" class="as-btn-publish" id="btn-publier-test">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        Publier maintenant
                    </button>
                </div>
            </div>
            <div id="test-result" class="as-test-result" style="display:none;"></div>
        </div>
    </div>

    <!-- Historique -->
    <?php if (!empty($historique)): ?>
    <div class="as-card" style="margin-top:1.25rem;">
        <div class="as-card__head">
            <span class="as-card__icon" style="background:#f1f5f9;color:#475569;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <div><h2>Historique des publications</h2></div>
        </div>
        <div style="overflow-x:auto;">
            <table class="admin-table as-history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Sujet</th>
                        <th>Facebook</th>
                        <th>LinkedIn</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($historique as $h): ?>
                <tr>
                    <td style="white-space:nowrap;font-size:.8125rem;"><?= $esc(date('d/m/Y H:i', strtotime($h['publie_le']))) ?></td>
                    <td><?= $esc(mb_substr($h['sujet'] ?? '', 0, 60)) ?><?= mb_strlen($h['sujet'] ?? '') > 60 ? '…' : '' ?></td>
                    <td>
                        <?php if ($h['fb_post_id']): ?>
                        <span class="as-status as-status--ok"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?= $esc($h['fb_post_id']) ?></span>
                        <?php else: ?><span class="as-status as-status--off">—</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($h['li_post_id']): ?>
                        <span class="as-status as-status--ok"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> <?= $esc($h['li_post_id']) ?></span>
                        <?php else: ?><span class="as-status as-status--off">—</span><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
(function () {
    var cronSecret = <?= json_encode($config['cron_secret'] ?? '') ?>;
    var baseUrl    = <?= json_encode(rtrim(BASE_URL ?? '', '/')) ?>;

    function showResult(text) {
        var el = document.getElementById('test-result');
        el.style.display = 'block';
        el.textContent = text;
    }

    function getSujet() {
        var v = document.getElementById('test-sujet').value.trim();
        if (!v) { alert('Saisissez un sujet de test.'); return null; }
        return v;
    }

    document.getElementById('btn-apercu').addEventListener('click', function () {
        var sujet = getSujet(); if (!sujet) return;
        showResult('Génération en cours…');
        fetch(baseUrl + '/api/cron/test-social', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Cron-Secret': cronSecret },
            body: JSON.stringify({ sujet: sujet, secret: cronSecret })
        }).then(r => r.json()).then(d => showResult(d.apercu || d.error || JSON.stringify(d)))
          .catch(() => showResult('Erreur réseau.'));
    });

    document.getElementById('btn-publier-test').addEventListener('click', function () {
        var sujet = getSujet(); if (!sujet) return;
        if (!confirm('Publier MAINTENANT sur Facebook et LinkedIn ?')) return;
        showResult('Publication en cours…');
        fetch(baseUrl + '/api/cron/social', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Cron-Secret': cronSecret },
            body: JSON.stringify({ sujet: sujet, secret: cronSecret })
        }).then(r => r.json()).then(d => showResult(JSON.stringify(d, null, 2)))
          .catch(() => showResult('Erreur réseau.'));
    });

    /* Chip jours : toggle classe is-active */
    document.querySelectorAll('.as-day-chip input').forEach(function (cb) {
        cb.addEventListener('change', function () {
            cb.closest('.as-day-chip').classList.toggle('is-active', cb.checked);
        });
    });
})();
</script>
