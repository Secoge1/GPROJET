<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$rows = $rows ?? [];
$page = (int)($page ?? 1);
$pages = (int)($pages ?? 1);
$total = (int)($total ?? 0);
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField = \App\Core\Security::getCsrfField();
?>
<div class="page-admin page-admin-assistant-emails">
    <header class="admin-tracking-hero">
        <a href="<?= $baseUrl ?>/admin" class="admin-back-link" aria-label="Retour au tableau de bord">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="admin-tracking-hero-content">
            <div class="admin-tracking-hero-icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-6l-2 3-4-6-3 3H2"/><path d="M2 6h20v12H2z"/></svg>
            </div>
            <div class="admin-tracking-hero-text">
                <h1>Emails automatiques IA</h1>
                <p class="admin-tracking-hero-subtitle">Suivi des envois proactifs, actions de renvoi et résolution.</p>
            </div>
        </div>
    </header>

    <?php if (!empty($_SESSION['flash_success']) || !empty($_SESSION['flash_error'])): ?>
    <div class="admin-tracking-flash">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <p class="admin-tracking-flash__success"><?= $e($_SESSION['flash_success']) ?></p>
        <?php unset($_SESSION['flash_success']); endif; ?>
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <p class="admin-tracking-flash__error"><?= $e($_SESSION['flash_error']) ?></p>
        <?php unset($_SESSION['flash_error']); endif; ?>
    </div>
    <?php endif; ?>

    <div class="admin-table-card admin-tracking-table-card">
        <div class="admin-table-card-header">
            <h2>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="admin-table-card-icon"><path d="M22 12h-6l-2 3-4-6-3 3H2"/><path d="M2 6h20v12H2z"/></svg>
                Journal IA (<?= $total ?>)
            </h2>
            <div class="admin-tracking-actions">
                <form method="post" action="<?= $baseUrl ?>/admin/run-assistant-emails-now" style="display:inline;">
                    <?= $csrfField ?>
                    <button type="submit" class="btn btn-primary btn-sm" title="Déclencher maintenant">
                        Lancer campagne IA
                    </button>
                </form>
            </div>
        </div>

        <div class="admin-tracking-table-wrap">
            <table class="table-desktop admin-table admin-tracking-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Raison</th>
                        <th>Statut</th>
                        <th>Sujet</th>
                        <th class="admin-table-col-action">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="7" class="admin-table-empty">Aucun email IA enregistré.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= $e($r['sent_at'] ?? '') ?></td>
                            <td><?= $e(trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? ''))) ?></td>
                            <td><?= $e($r['recipient_email'] ?? ($r['user_email'] ?? '')) ?></td>
                            <td><span class="admin-tracking-badge"><?= $e($r['reason_code'] ?? '') ?></span></td>
                            <td><?= $e($r['status'] ?? 'sent') ?></td>
                            <td><?= $e($r['subject'] ?? '') ?></td>
                            <td class="admin-tracking-cell-actions">
                                <a href="<?= $baseUrl ?>/admin/view-assistant-email/<?= (int)($r['id'] ?? 0) ?>" class="btn btn-outline btn-sm" title="Voir le contenu">Voir</a>
                                <form method="post" action="<?= $baseUrl ?>/admin/resend-assistant-email/<?= (int)($r['id'] ?? 0) ?>" style="display:inline;" onsubmit="return confirm('Renvoyer cet email ?');">
                                    <?= $csrfField ?>
                                    <button type="submit" class="btn btn-outline btn-sm" title="Renvoyer">Renvoyer</button>
                                </form>
                                <form method="post" action="<?= $baseUrl ?>/admin/resolve-assistant-email/<?= (int)($r['id'] ?? 0) ?>" style="display:inline;">
                                    <?= $csrfField ?>
                                    <button type="submit" class="btn btn-outline btn-sm" title="Marquer résolu">Résolu</button>
                                </form>
                                <form method="post" action="<?= $baseUrl ?>/admin/delete-assistant-email/<?= (int)($r['id'] ?? 0) ?>" style="display:inline;" onsubmit="return confirm('Supprimer définitivement cette entrée du journal IA ?');">
                                    <?= $csrfField ?>
                                    <button type="submit" class="btn btn-outline btn-sm" title="Supprimer">Supprimer</button>
                                </form>
                                <?php if (!empty($r['utilisateur_id'])): ?>
                                    <a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$r['utilisateur_id'] ?>" class="btn btn-outline btn-sm" title="Voir l'utilisateur">Utilisateur</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-table-footer" style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap;">
            <span class="admin-table-count">Page <?= $page ?> / <?= $pages ?></span>
            <div style="display:flex;gap:.5rem;">
                <?php if ($page > 1): ?>
                    <a class="btn btn-outline btn-sm" href="<?= $baseUrl ?>/admin/assistant-emails?page=<?= $page - 1 ?>">Précédent</a>
                <?php endif; ?>
                <?php if ($page < $pages): ?>
                    <a class="btn btn-outline btn-sm" href="<?= $baseUrl ?>/admin/assistant-emails?page=<?= $page + 1 ?>">Suivant</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

