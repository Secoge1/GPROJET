<?php
$baseUrl   = rtrim(BASE_URL ?? '', '/');
$e         = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField = \App\Core\Security::getCsrfField();
$roles     = [
    'all'        => ['label' => 'Tous les utilisateurs actifs',  'badge' => 'default', 'icon' => '👥'],
    'client'     => ['label' => 'Clients',                       'badge' => 'info',    'icon' => '🧑'],
    'expert'     => ['label' => 'Experts',                       'badge' => 'success', 'icon' => '⭐'],
    'etudiant'   => ['label' => 'Étudiants',                     'badge' => 'warning', 'icon' => '🎓'],
    'professeur' => ['label' => 'Professeurs',                   'badge' => 'default', 'icon' => '📚'],
];
$selectedRole = $_POST['role'] ?? '';
?>
<div class="page-admin page-admin-send-mail-group">

    <header class="admin-page-hero">
        <a href="<?= $baseUrl ?>/admin/users" class="admin-back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Utilisateurs
        </a>
        <div class="admin-page-hero__content">
            <div class="admin-page-hero__icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            </div>
            <div>
                <h1>Email groupé</h1>
                <p>Envoyer un email à un groupe d'utilisateurs</p>
            </div>
        </div>
    </header>

    <?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="admin-alert admin-alert--success"><?= $e($_SESSION['flash_success']) ?></div>
    <?php unset($_SESSION['flash_success']); endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="admin-alert admin-alert--danger"><?= $e($_SESSION['flash_error']) ?></div>
    <?php unset($_SESSION['flash_error']); endif; ?>

    <div class="admin-mail-layout">

        <!-- Panneau gauche : sélection du groupe -->
        <div class="admin-table-card" style="padding:1.5rem;flex:0 0 280px;">
            <h2 style="font-size:.9rem;font-weight:700;margin:0 0 1.1rem;">Destinataires</h2>

            <div style="display:flex;flex-direction:column;gap:.5rem;" id="role-selector">
                <?php foreach ($roles as $rk => $rv): ?>
                <label style="display:flex;align-items:center;gap:.65rem;padding:.7rem .85rem;border:1.5px solid <?= $selectedRole === $rk ? '#2563eb' : '#e2e8f0' ?>;border-radius:8px;cursor:pointer;background:<?= $selectedRole === $rk ? '#eff6ff' : '#fff' ?>;">
                    <input type="radio" name="role_pick" value="<?= $e($rk) ?>" <?= $selectedRole === $rk ? 'checked' : '' ?>
                           style="display:none;" data-target="role-input">
                    <span style="font-size:1.1rem"><?= $rv['icon'] ?></span>
                    <span style="font-size:.87rem;font-weight:600;"><?= $e($rv['label']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- Info -->
            <div style="margin-top:1.25rem;padding:.85rem;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;font-size:.78rem;color:#92400e;">
                <strong>⚠ Attention</strong><br>
                Cet envoi est massif. Chaque utilisateur reçoit l'email + une notification interne. Vérifiez votre SMTP avant envoi.
            </div>
        </div>

        <!-- Formulaire -->
        <div class="admin-table-card" style="flex:1;padding:1.75rem;">
            <h2 style="font-size:.9rem;font-weight:700;margin:0 0 1.5rem;">Rédiger le message</h2>
            <form method="post" action="<?= $baseUrl ?>/admin/send-mail-group" id="group-mail-form">
                <?= $csrfField ?>
                <input type="hidden" name="role" id="role-input" value="<?= $e($selectedRole ?: 'all') ?>">

                <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:.85rem 1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.6rem;font-size:.85rem;color:#0369a1;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>Groupe sélectionné : <strong id="role-label">Tous les utilisateurs actifs</strong></span>
                </div>

                <div class="admin-form-group">
                    <label class="admin-form-label" for="gsubject">Objet de l'email <span style="color:#ef4444">*</span></label>
                    <input type="text" id="gsubject" name="subject" required maxlength="200"
                           placeholder="Ex : Mise à jour de la plateforme GLOBALO"
                           class="admin-form-input"
                           value="<?= $e($_POST['subject'] ?? '') ?>">
                </div>
                <div class="admin-form-group" style="margin-top:1rem;">
                    <label class="admin-form-label" for="gmessage">Message <span style="color:#ef4444">*</span></label>
                    <textarea id="gmessage" name="message" required maxlength="10000" rows="12"
                              placeholder="Rédigez votre message... Il sera personnalisé avec le prénom de chaque destinataire et envoyé avec le template HTML GLOBALO."
                              class="admin-form-input"
                              style="resize:vertical;line-height:1.6;"><?= $e($_POST['message'] ?? '') ?></textarea>
                    <small style="color:#64748b;font-size:.75rem;">Une notification interne est aussi créée pour chaque destinataire (même si l'email SMTP échoue).</small>
                </div>

                <div style="display:flex;gap:.75rem;margin-top:1.5rem;flex-wrap:wrap;align-items:center;">
                    <button type="button" id="confirm-send-btn" class="btn btn-primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Envoyer à ce groupe
                    </button>
                    <a href="<?= $baseUrl ?>/admin/users" class="btn btn-outline">Annuler</a>
                    <span style="font-size:.78rem;color:#94a3b8;">Confirmation requise avant envoi.</span>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.admin-form-input { width:100%;padding:.65rem .85rem;border:1.5px solid var(--admin-border,#e2e8f0);border-radius:8px;font-size:.9rem;color:var(--admin-text,#1e293b);font-family:inherit;box-sizing:border-box;transition:border-color .15s; }
.admin-form-input:focus { border-color:#2563eb;outline:none;box-shadow:0 0 0 3px rgba(37,99,235,.1); }
.admin-form-label { display:block;font-size:.82rem;font-weight:600;color:var(--admin-text,#374151);margin-bottom:.35rem; }
.admin-mail-layout { display:flex;gap:1.25rem;align-items:flex-start;flex-wrap:wrap; }
</style>

<script>
(function() {
    var roleLabels = <?= json_encode(array_column($roles, 'label', 'key') ?: array_combine(array_keys($roles), array_column($roles, 'label'))) ?>;
    var radios = document.querySelectorAll('[data-target="role-input"]');
    var input  = document.getElementById('role-input');
    var label  = document.getElementById('role-label');
    var labels = <?= json_encode(array_map(fn($r) => $r['label'], $roles)) ?>;
    var keys   = <?= json_encode(array_keys($roles)) ?>;

    radios.forEach(function(radio, i) {
        radio.parentElement.addEventListener('click', function() {
            radios.forEach(function(r) {
                r.parentElement.style.borderColor = '#e2e8f0';
                r.parentElement.style.background  = '#fff';
            });
            this.style.borderColor = '#2563eb';
            this.style.background  = '#eff6ff';
            input.value = keys[i];
            label.textContent = labels[i];
        });
    });

    document.getElementById('confirm-send-btn').addEventListener('click', function() {
        var role = input.value;
        var rl = '';
        keys.forEach(function(k, i) { if (k === role) rl = labels[i]; });
        if (confirm('Envoyer cet email à tous les utilisateurs du groupe "' + rl + '" ?\n\nCette action ne peut pas être annulée.')) {
            document.getElementById('group-mail-form').submit();
        }
    });
})();
</script>
