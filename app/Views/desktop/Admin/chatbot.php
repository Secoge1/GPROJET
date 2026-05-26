<?php
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$csrfField = \App\Core\Security::getCsrfField();
$config    = $config ?? [];
$esc       = fn($v) => \App\Core\Security::escape((string)($v ?? ''));

$flashOk  = $_SESSION['flash_ok']    ?? null; unset($_SESSION['flash_ok']);
$flashErr = $_SESSION['flash_error'] ?? null; unset($_SESSION['flash_error']);
?>
<div class="page-admin page-admin-chatbot">

    <!-- Hero -->
    <header class="admin-chatbot-hero">
        <a href="<?= $baseUrl ?>/admin" class="admin-back-link" aria-label="Retour au tableau de bord">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="admin-chatbot-hero-content">
            <div class="admin-chatbot-hero-icon" aria-hidden="true">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="admin-chatbot-hero-text">
                <h1>Chatbot IA</h1>
                <p class="admin-chatbot-hero-subtitle">Configuration de l'assistant conversationnel et de ses réponses</p>
            </div>
            <div class="admin-chatbot-hero-status" aria-hidden="true">
                <span class="admin-chatbot-status-dot <?= ($chatbot_enabled ?? '1') === '1' ? 'is-on' : 'is-off' ?>"></span>
                <span><?= ($chatbot_enabled ?? '1') === '1' ? 'Actif' : 'Désactivé' ?></span>
            </div>
        </div>
    </header>

    <!-- Alertes flash -->
    <?php if ($flashOk): ?>
    <div class="admin-chatbot-flash admin-chatbot-flash--ok">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        <?= $esc($flashOk) ?>
    </div>
    <?php endif; ?>
    <?php if ($flashErr): ?>
    <div class="admin-chatbot-flash admin-chatbot-flash--err">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        <?= $esc($flashErr) ?>
    </div>
    <?php endif; ?>

    <form method="post" class="admin-chatbot-form">
        <?= $csrfField ?>

        <div class="admin-chatbot-layout">

            <!-- Colonne principale -->
            <div class="admin-chatbot-main">

                <!-- Activation & API -->
                <section class="admin-chatbot-block admin-chatbot-block--api">
                    <div class="admin-chatbot-block-header">
                        <span class="admin-chatbot-block-icon" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        </span>
                        <h2>Activation &amp; API</h2>
                    </div>
                    <div class="admin-chatbot-block-body">

                        <!-- Toggle principal -->
                        <div class="admin-chatbot-toggle-row">
                            <div class="admin-chatbot-toggle-info">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                <div>
                                    <strong>Widget chatbot actif</strong>
                                    <small>Afficher le bouton de chat flottant sur le site</small>
                                </div>
                            </div>
                            <label class="as-toggle as-toggle--lg" title="Activer / Désactiver le chatbot">
                                <input type="checkbox" name="chatbot_enabled" value="1" <?= ($chatbot_enabled ?? '1') === '1' ? 'checked' : '' ?>>
                                <span class="as-toggle__track"><span class="as-toggle__thumb"></span></span>
                            </label>
                        </div>

                        <div class="admin-chatbot-divider"></div>

                        <div class="form-group">
                            <label for="chatbot_ai_provider">
                                Provider IA
                                <span class="as-label-badge as-label-badge--free">★ Gemini = Gratuit</span>
                            </label>
                            <select name="chatbot_ai_provider" id="chatbot_ai_provider">
                                <option value="openai"  <?= ($chatbot_ai_provider ?? 'openai') === 'openai'  ? 'selected' : '' ?>>OpenAI GPT-3.5 (quota payant)</option>
                                <option value="gemini"  <?= ($chatbot_ai_provider ?? '') === 'gemini'  ? 'selected' : '' ?>>Google Gemini 1.5 Flash (Gratuit ✓)</option>
                                <option value="mistral" <?= ($chatbot_ai_provider ?? '') === 'mistral' ? 'selected' : '' ?>>Mistral Small (Gratuit ✓)</option>
                            </select>
                            <small class="form-hint">
                                Gemini : <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">aistudio.google.com</a> ·
                                Mistral : <a href="https://console.mistral.ai" target="_blank" rel="noopener">console.mistral.ai</a>
                            </small>
                        </div>
                        <div class="form-group">
                            <label for="chatbot_openai_api_key">Clé API</label>
                            <input type="password" name="chatbot_openai_api_key" id="chatbot_openai_api_key"
                                value="<?= $esc($chatbot_openai_api_key ?? '') ?>"
                                placeholder="AIza… (Gemini) · sk-… (OpenAI) · … (Mistral)" autocomplete="off">
                            <small class="form-hint">Stockée chiffrée dans les paramètres. Ne jamais exposer côté client.</small>
                        </div>
                        <div class="form-group">
                            <label for="mail_signature_image_url">Image de signature email (URL)</label>
                            <input type="url" name="mail_signature_image_url" id="mail_signature_image_url"
                                value="<?= $esc($mail_signature_image_url ?? '') ?>"
                                placeholder="https://.../signature.png">
                            <small class="form-hint">Cette image sera ajoutée en bas des emails automatiques IA.</small>
                        </div>
                        <div class="form-group">
                            <label for="chatbot_max_history_messages">Messages d'historique max <span class="as-hint-inline">(5–50)</span></label>
                            <div class="admin-chatbot-number-row">
                                <input type="range" min="5" max="50" step="5"
                                    value="<?= $esc($chatbot_max_history_messages ?? '20') ?>"
                                    id="chatbot_history_range"
                                    oninput="document.getElementById('chatbot_max_history_messages').value=this.value;document.getElementById('chatbot_history_val').textContent=this.value">
                                <input type="number" name="chatbot_max_history_messages" id="chatbot_max_history_messages" min="5" max="50"
                                    value="<?= $esc($chatbot_max_history_messages ?? '20') ?>"
                                    oninput="document.getElementById('chatbot_history_range').value=this.value;document.getElementById('chatbot_history_val').textContent=this.value"
                                    style="width:70px;">
                                <span id="chatbot_history_val" class="admin-chatbot-range-val"><?= $esc($chatbot_max_history_messages ?? '20') ?></span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Personnalité -->
                <section class="admin-chatbot-block admin-chatbot-block--prompt">
                    <div class="admin-chatbot-block-header">
                        <span class="admin-chatbot-block-icon" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        </span>
                        <h2>Personnalité <span class="admin-chatbot-block-sub">Prompt système</span></h2>
                    </div>
                    <div class="admin-chatbot-block-body">
                        <div class="form-group">
                            <label for="system_prompt">Prompt système</label>
                            <textarea name="system_prompt" id="system_prompt" rows="8"
                                placeholder="Tu es l'assistant virtuel de GLOBALO, plateforme de mise en relation entre clients et experts freelances en Afrique de l'Ouest…"><?= $esc($config['system_prompt'] ?? '') ?></textarea>
                            <small class="form-hint">Définit le rôle, le ton et les intents du chatbot (<code>find_expert</code>, <code>create_task</code>, <code>help_*</code>).</small>
                        </div>
                    </div>
                </section>

                <!-- Réponses par défaut -->
                <section class="admin-chatbot-block admin-chatbot-block--defaults">
                    <div class="admin-chatbot-block-header">
                        <span class="admin-chatbot-block-icon" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        </span>
                        <h2>Réponses par défaut</h2>
                    </div>
                    <div class="admin-chatbot-block-body">
                        <div class="admin-chatbot-intent-grid">
                            <div class="form-group">
                                <label for="default_find_expert">
                                    <code class="admin-chatbot-intent-code admin-chatbot-intent-code--blue">find_expert</code>
                                </label>
                                <input type="text" name="default_find_expert" id="default_find_expert"
                                    value="<?= $esc($config['default_find_expert'] ?? '') ?>"
                                    placeholder="Réponse par défaut pour la recherche d'expert">
                            </div>
                            <div class="form-group">
                                <label for="default_create_task">
                                    <code class="admin-chatbot-intent-code admin-chatbot-intent-code--purple">create_task</code>
                                </label>
                                <input type="text" name="default_create_task" id="default_create_task"
                                    value="<?= $esc($config['default_create_task'] ?? '') ?>"
                                    placeholder="Réponse par défaut pour la création de tâche">
                            </div>
                        </div>
                    </div>
                </section>

            </div>

            <!-- Colonne latérale : Documentation -->
            <div class="admin-chatbot-sidebar">

                <section class="admin-chatbot-block admin-chatbot-block--help">
                    <div class="admin-chatbot-block-header">
                        <span class="admin-chatbot-block-icon" aria-hidden="true">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </span>
                        <h2>Documentation <span class="admin-chatbot-block-sub">Réponses help_*</span></h2>
                    </div>
                    <div class="admin-chatbot-block-body">
                        <?php
                        $helpFields = [
                            'help_payment'    => ['Paiements', 'admin-chatbot-intent-code--teal', 'Aide sur les moyens de paiement acceptés'],
                            'help_withdrawal' => ['Retraits', 'admin-chatbot-intent-code--teal', 'Aide sur les délais et modes de retrait'],
                            'help_booking'    => ['Réservations', 'admin-chatbot-intent-code--teal', 'Aide sur le processus de réservation'],
                            'help_commission' => ['Commission', 'admin-chatbot-intent-code--teal', 'Aide sur les frais de commission'],
                        ];
                        foreach ($helpFields as $name => [$label, $cls, $placeholder]):
                        ?>
                        <div class="form-group">
                            <label for="<?= $name ?>">
                                <code class="admin-chatbot-intent-code <?= $cls ?>"><?= $name ?></code>
                                <span class="admin-chatbot-field-sub"><?= $label ?></span>
                            </label>
                            <textarea name="<?= $name ?>" id="<?= $name ?>" rows="3"
                                placeholder="<?= $placeholder ?>"><?= $esc($config[$name] ?? '') ?></textarea>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Aide contextuelle -->
                <div class="admin-chatbot-tip">
                    <div class="admin-chatbot-tip__icon" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <div>
                        <strong>Comment fonctionne l'IA ?</strong>
                        <p>L'assistant reçoit votre <em>prompt système</em> à chaque message. Les réponses <code>help_*</code> et les réponses par défaut sont injectées dans le contexte selon l'intent détecté.</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Bouton sauvegarder -->
        <div class="admin-chatbot-actions">
            <button type="submit" class="btn btn-primary btn-lg admin-chatbot-submit">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Enregistrer la configuration
            </button>
        </div>
    </form>

</div>
