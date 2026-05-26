<?php
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$e          = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField  = \App\Core\Security::getCsrfField();
$u          = $userToMail ?? [];
$userId     = (int)($u['id'] ?? 0);
$userName   = trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? '')) ?: ($u['email'] ?? '—');
$userRole   = $u['role'] ?? '—';
$userEmail  = $u['email'] ?? '';
$roleBadgeClass = ['client'=>'info','expert'=>'success','etudiant'=>'warning','professeur'=>'default','admin'=>'danger'][$userRole] ?? 'default';
$signatureUrl = $mailSignatureImageUrl ?? '';
?>
<div class="page-admin page-admin-send-mail">

    <header class="admin-page-hero">
        <a href="<?= $baseUrl ?>/admin/edit-user/<?= $userId ?>" class="admin-back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Fiche utilisateur
        </a>
        <div class="admin-page-hero__content">
            <div class="admin-page-hero__icon">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div>
                <h1>Envoyer un email</h1>
                <p>Message individuel à <strong><?= $e($userName) ?></strong></p>
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

        <!-- Carte destinataire -->
        <div class="admin-table-card" style="padding:1.5rem;flex:0 0 280px;">
            <h2 style="font-size:.9rem;font-weight:700;margin:0 0 1.25rem;color:var(--admin-text);">Destinataire</h2>
            <div style="display:flex;flex-direction:column;gap:.75rem;">
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <div style="width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,#2563eb,#16a34a);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span style="color:#fff;font-weight:700;font-size:1.1rem;"><?= strtoupper(mb_substr($u['prenom'] ?? 'U', 0, 1)) ?></span>
                    </div>
                    <div>
                        <p style="margin:0;font-weight:700;font-size:.93rem;"><?= $e($userName) ?></p>
                        <p style="margin:0;font-size:.78rem;color:#64748b;"><?= $e($userEmail) ?></p>
                    </div>
                </div>
                <span class="admin-badge admin-badge--<?= $e($roleBadgeClass) ?>"><?= $e(ucfirst($userRole)) ?></span>
                <a href="mailto:<?= $e($userEmail) ?>" style="font-size:.8rem;color:#2563eb;">Ouvrir dans votre client mail</a>
            </div>
        </div>

        <!-- Formulaire -->
        <div class="admin-table-card" style="flex:1;padding:1.75rem;">
            <h2 style="font-size:.9rem;font-weight:700;margin:0 0 1.5rem;color:var(--admin-text);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:.35rem;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Rédiger le message
            </h2>
            <form method="post" action="<?= $baseUrl ?>/admin/send-mail-user/<?= $userId ?>">
                <?= $csrfField ?>
                <div class="admin-form-group">
                    <label class="admin-form-label" for="subject">Objet de l'email <span style="color:#ef4444">*</span></label>
                    <input type="text" id="subject" name="subject" required maxlength="200"
                           placeholder="Ex : Information importante sur votre compte GLOBALO"
                           class="admin-form-input"
                           value="<?= $e($_POST['subject'] ?? '') ?>">
                </div>
                <div class="admin-form-group" style="margin-top:1rem;">
                    <label class="admin-form-label" for="message">Message <span style="color:#ef4444">*</span></label>
                    <textarea id="message" name="message" required maxlength="10000" rows="10"
                              placeholder="Rédigez votre message ici... Il sera envoyé par email avec le template GLOBALO et créera aussi une notification interne."
                              class="admin-form-input"
                              style="resize:vertical;line-height:1.6;"><?= $e($_POST['message'] ?? '') ?></textarea>
                    <small style="color:#64748b;font-size:.75rem;">Le message sera automatiquement mis en forme avec le template HTML GLOBALO. Une notification interne sera aussi créée.</small>
                </div>
                <div style="display:flex;gap:.75rem;margin-top:1.5rem;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Envoyer l'email
                    </button>
                    <a href="<?= $baseUrl ?>/admin/edit-user/<?= $userId ?>" class="btn btn-outline">Annuler</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Aperçu template -->
    <div class="admin-table-card" style="margin-top:1.5rem;padding:1.5rem;">
        <h2 style="font-size:.85rem;font-weight:700;margin:0 0 .75rem;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">Aperçu du template d'email</h2>
        <div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;max-width:500px;">
            <div style="background:linear-gradient(135deg,#16a34a,#15803d);padding:1rem 1.5rem;text-align:center;">
                <span style="font-size:1.1rem;font-weight:800;color:#fff;">GLOBALO</span>
            </div>
            <div style="padding:1.25rem 1.5rem;font-size:.85rem;color:#1e293b;line-height:1.6;">
                <p style="margin:0 0 .5rem;">Bonjour <strong><?= $e($userName) ?></strong>,</p>
                <p style="margin:0;color:#64748b;font-style:italic;">[ Contenu de votre message ]</p>
                <?php if (!empty($signatureUrl)): ?>
                <div style="margin:1rem 0 .4rem;text-align:center;">
                    <div style="display:inline-block;width:100%;max-width:320px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;">
                        <img src="<?= $e($signatureUrl) ?>" alt="Signature email" style="display:block;width:100%;max-width:320px;height:auto;border:0;">
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div style="background:#f8fafc;padding:.85rem 1.5rem;text-align:center;border-top:1px solid #e2e8f0;">
                <p style="margin:0;font-size:.72rem;color:#94a3b8;">© <?= date('Y') ?> GLOBALO · Tous droits réservés</p>
            </div>
        </div>
        <p style="margin:.7rem 0 0;font-size:.75rem;color:#64748b;">
            Signature image : <?= !empty($signatureUrl) ? 'active' : 'non définie' ?>.
            Vous pouvez la configurer dans <a href="<?= $baseUrl ?>/admin/chatbot">Admin &gt; Chatbot IA</a>.
        </p>
    </div>
</div>

<style>
.admin-mail-layout { display: flex; gap: 1.25rem; align-items: flex-start; flex-wrap: wrap; }
.admin-form-input  { width: 100%; padding: .65rem .85rem; border: 1.5px solid var(--admin-border, #e2e8f0); border-radius: 8px; font-size: .9rem; color: var(--admin-text, #1e293b); font-family: inherit; box-sizing: border-box; transition: border-color .15s; }
.admin-form-input:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
.admin-form-label  { display: block; font-size: .82rem; font-weight: 600; color: var(--admin-text, #374151); margin-bottom: .35rem; }
</style>
