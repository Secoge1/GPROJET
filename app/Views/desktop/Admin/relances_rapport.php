<?php
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$rows       = $rows ?? [];
$stats      = $stats ?? [];
$page       = (int)($page ?? 1);
$pages      = (int)($pages ?? 1);
$total      = (int)($total ?? 0);
$agent      = $agent ?? '';
$e          = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField  = \App\Core\Security::getCsrfField();

$reasonLabels = [
    'aria_unverified_email'   => 'Email non vérifié',
    'aria_prof_no_desc'       => 'Prof — sans description',
    'aria_prof_no_rate'       => 'Prof — sans tarif',
    'aria_prof_not_available' => 'Prof — non disponible',
    'aria_prof_pending_long'  => 'Prof — validation longue',
    'aria_etud_no_university' => 'Étudiant — sans université',
    'aria_etud_no_filiere'    => 'Étudiant — sans filière',
    'aria_etud_no_bio'        => 'Étudiant — sans bio',
    'profia_expert_no_title'  => 'Expert — sans titre',
    'profia_expert_no_desc'   => 'Expert — sans description',
    'profia_expert_no_rate'   => 'Expert — sans tarif',
    'profia_client_no_avatar' => 'Client — sans photo',
    'profia_client_no_phone'  => 'Client — sans téléphone',
    'profia_expert_low_score' => 'Expert — score faible',
];

$statusColors = [
    'sent'     => '#10b981',
    'resent'   => '#3b82f6',
    'failed'   => '#ef4444',
    'resolved' => '#6b7280',
];

$agentColor = fn(string $code): string => str_starts_with($code, 'aria_') ? '#6366f1' : '#0ea5e9';
$agentLabel = fn(string $code): string => str_starts_with($code, 'aria_') ? 'ARIA' : 'PROFIA';
?>

<style>
.relances-page { padding: 1.5rem; max-width: 1400px; margin: 0 auto; }
.relances-hero { display:flex; align-items:center; gap:1rem; margin-bottom:2rem; flex-wrap:wrap; }
.relances-hero-icon { width:48px;height:48px;background:linear-gradient(135deg,#6366f1,#0ea5e9);border-radius:12px;display:flex;align-items:center;justify-content:center; }
.relances-hero h1 { font-size:1.5rem;font-weight:700;margin:0; }
.relances-hero p { color:#64748b;margin:.25rem 0 0; font-size:.9rem; }
.relances-back { color:#64748b;text-decoration:none;font-size:.875rem;display:flex;align-items:center;gap:.4rem;margin-bottom:1rem; }
.relances-back:hover { color:#1e293b; }

.relances-stats { display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:1rem;margin-bottom:1.5rem; }
.stat-card { background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem 1.25rem; }
.stat-card .val { font-size:1.75rem;font-weight:700;line-height:1; }
.stat-card .lbl { font-size:.78rem;color:#64748b;margin-top:.35rem; }
.stat-aria { border-top:3px solid #6366f1; }
.stat-profia { border-top:3px solid #0ea5e9; }
.stat-green { border-top:3px solid #10b981; }
.stat-red { border-top:3px solid #ef4444; }

.relances-toolbar { display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem; }
.tab-btn { padding:.45rem 1rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;font-size:.875rem;font-weight:500;text-decoration:none;color:#374151; }
.tab-btn.active { background:#1e293b;color:#fff;border-color:#1e293b; }
.tab-aria.active { background:#6366f1;border-color:#6366f1; }
.tab-profia.active { background:#0ea5e9;border-color:#0ea5e9; }
.relances-actions { margin-left:auto;display:flex;gap:.5rem;flex-wrap:wrap; }

.relances-table-wrap { overflow-x:auto; }
.relances-table { width:100%;border-collapse:collapse;font-size:.875rem; }
.relances-table th { background:#f8fafc;padding:.65rem .85rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0;white-space:nowrap; }
.relances-table td { padding:.6rem .85rem;border-bottom:1px solid #f1f5f9;vertical-align:middle; }
.relances-table tr:hover td { background:#f8fafc; }

.badge-agent { display:inline-block;padding:.2rem .6rem;border-radius:999px;font-size:.7rem;font-weight:700;color:#fff; }
.badge-aria { background:#6366f1; }
.badge-profia { background:#0ea5e9; }
.badge-status { display:inline-block;padding:.2rem .6rem;border-radius:999px;font-size:.7rem;font-weight:600;color:#fff; }
.badge-reason { display:inline-block;padding:.15rem .55rem;border-radius:6px;font-size:.72rem;background:#f1f5f9;color:#475569;white-space:nowrap; }

.row-actions { display:flex;gap:.35rem;flex-wrap:wrap; }
.btn-xs { padding:.25rem .6rem;font-size:.75rem;border-radius:6px;border:1px solid #e2e8f0;background:#fff;cursor:pointer;text-decoration:none;color:#374151;white-space:nowrap; }
.btn-xs:hover { background:#f1f5f9; }

.relances-footer { display:flex;justify-content:space-between;align-items:center;margin-top:1rem;flex-wrap:wrap;gap:.5rem; }
.pagination { display:flex;gap:.4rem; }

.flash-ok { background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem; }
.flash-err { background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:.75rem 1rem;border-radius:8px;margin-bottom:1rem; }
</style>

<div class="relances-page">
    <a href="<?= $baseUrl ?>/admin" class="relances-back">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Tableau de bord admin
    </a>

    <div class="relances-hero">
        <div class="relances-hero-icon">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        </div>
        <div>
            <h1>Rapport Relances — ARIA &amp; PROFIA</h1>
            <p>Historique de tous les emails de relance envoyés automatiquement aux profils incomplets.</p>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="flash-ok"><?= $e($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="flash-err"><?= $e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Stats -->
    <div class="relances-stats">
        <div class="stat-card">
            <div class="val"><?= (int)($stats['total'] ?? 0) ?></div>
            <div class="lbl">Total relances</div>
        </div>
        <div class="stat-card stat-aria">
            <div class="val" style="color:#6366f1"><?= (int)($stats['total_aria'] ?? 0) ?></div>
            <div class="lbl">ARIA (Profs &amp; Étudiants)</div>
        </div>
        <div class="stat-card stat-profia">
            <div class="val" style="color:#0ea5e9"><?= (int)($stats['total_profia'] ?? 0) ?></div>
            <div class="lbl">PROFIA (Clients &amp; Experts)</div>
        </div>
        <div class="stat-card stat-green">
            <div class="val" style="color:#10b981"><?= (int)($stats['envoyes'] ?? 0) ?></div>
            <div class="lbl">Envoyés avec succès</div>
        </div>
        <div class="stat-card stat-red">
            <div class="val" style="color:#ef4444"><?= (int)($stats['echecs'] ?? 0) ?></div>
            <div class="lbl">Échecs d'envoi</div>
        </div>
        <div class="stat-card">
            <div class="val"><?= (int)($stats['cette_semaine'] ?? 0) ?></div>
            <div class="lbl">Cette semaine</div>
        </div>
        <div class="stat-card">
            <div class="val"><?= (int)($stats['ce_mois'] ?? 0) ?></div>
            <div class="lbl">Ce mois</div>
        </div>
    </div>

    <!-- Barre filtres + actions -->
    <div class="relances-toolbar">
        <a href="<?= $baseUrl ?>/admin/relances-rapport" class="tab-btn <?= $agent === '' ? 'active' : '' ?>">Tous (<?= (int)($stats['total'] ?? 0) ?>)</a>
        <a href="<?= $baseUrl ?>/admin/relances-rapport?agent=aria" class="tab-btn tab-aria <?= $agent === 'aria' ? 'active' : '' ?>">ARIA (<?= (int)($stats['total_aria'] ?? 0) ?>)</a>
        <a href="<?= $baseUrl ?>/admin/relances-rapport?agent=profia" class="tab-btn tab-profia <?= $agent === 'profia' ? 'active' : '' ?>">PROFIA (<?= (int)($stats['total_profia'] ?? 0) ?>)</a>

        <div class="relances-actions">
            <form method="post" action="<?= $baseUrl ?>/admin/run-aria-relance-now" style="display:inline" onsubmit="return confirm('Lancer ARIA maintenant ? Des emails seront envoyés.');">
                <?= $csrfField ?>
                <button type="submit" class="btn btn-sm" style="background:#6366f1;color:#fff;border:none;padding:.45rem 1rem;border-radius:8px;cursor:pointer;font-size:.875rem;font-weight:500;">
                    Lancer ARIA maintenant
                </button>
            </form>
            <form method="post" action="<?= $baseUrl ?>/admin/run-profia-relance-now" style="display:inline" onsubmit="return confirm('Lancer PROFIA maintenant ? Des emails seront envoyés.');">
                <?= $csrfField ?>
                <button type="submit" class="btn btn-sm" style="background:#0ea5e9;color:#fff;border:none;padding:.45rem 1rem;border-radius:8px;cursor:pointer;font-size:.875rem;font-weight:500;">
                    Lancer PROFIA maintenant
                </button>
            </form>
        </div>
    </div>

    <!-- Tableau -->
    <div class="admin-table-card" style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem;">
        <div class="relances-table-wrap">
            <table class="relances-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Agent</th>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Raison</th>
                        <th>Statut</th>
                        <th>Sujet</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:2rem;">Aucune relance enregistrée.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r):
                            $rc      = (string)($r['reason_code'] ?? '');
                            $isAria  = str_starts_with($rc, 'aria_');
                            $statusColor = $statusColors[$r['status'] ?? 'sent'] ?? '#6b7280';
                            $label   = $reasonLabels[$rc] ?? $rc;
                        ?>
                        <tr>
                            <td style="white-space:nowrap;color:#64748b;font-size:.8rem"><?= $e(substr((string)($r['sent_at'] ?? ''), 0, 16)) ?></td>
                            <td>
                                <span class="badge-agent <?= $isAria ? 'badge-aria' : 'badge-profia' ?>">
                                    <?= $isAria ? 'ARIA' : 'PROFIA' ?>
                                </span>
                            </td>
                            <td><?= $e(trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? ''))) ?></td>
                            <td style="font-size:.8rem;color:#64748b"><?= $e($r['recipient_email'] ?? ($r['user_email'] ?? '')) ?></td>
                            <td style="font-size:.78rem;color:#94a3b8"><?= $e($r['role'] ?? '') ?></td>
                            <td><span class="badge-reason"><?= $e($label) ?></span></td>
                            <td>
                                <span class="badge-status" style="background:<?= $statusColor ?>">
                                    <?= $e($r['status'] ?? 'sent') ?>
                                </span>
                            </td>
                            <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.82rem" title="<?= $e($r['subject'] ?? '') ?>">
                                <?= $e($r['subject'] ?? '—') ?>
                            </td>
                            <td>
                                <div class="row-actions">
                                    <a href="<?= $baseUrl ?>/admin/view-assistant-email/<?= (int)($r['id'] ?? 0) ?>" class="btn-xs">Voir</a>
                                    <form method="post" action="<?= $baseUrl ?>/admin/resend-assistant-email/<?= (int)($r['id'] ?? 0) ?>" style="display:inline" onsubmit="return confirm('Renvoyer cet email ?');">
                                        <?= $csrfField ?>
                                        <button type="submit" class="btn-xs">Renvoyer</button>
                                    </form>
                                    <form method="post" action="<?= $baseUrl ?>/admin/resolve-assistant-email/<?= (int)($r['id'] ?? 0) ?>" style="display:inline">
                                        <?= $csrfField ?>
                                        <button type="submit" class="btn-xs">Résolu</button>
                                    </form>
                                    <form method="post" action="<?= $baseUrl ?>/admin/delete-assistant-email/<?= (int)($r['id'] ?? 0) ?>" style="display:inline" onsubmit="return confirm('Supprimer cette entrée ?');">
                                        <?= $csrfField ?>
                                        <button type="submit" class="btn-xs" style="color:#ef4444">Suppr.</button>
                                    </form>
                                    <?php if (!empty($r['utilisateur_id'])): ?>
                                        <a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$r['utilisateur_id'] ?>" class="btn-xs">Profil</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="relances-footer">
            <span style="color:#64748b;font-size:.875rem">Page <?= $page ?> / <?= $pages ?> — <?= $total ?> relance(s)</span>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a class="btn-xs" href="<?= $baseUrl ?>/admin/relances-rapport?agent=<?= $e($agent) ?>&page=<?= $page - 1 ?>">Précédent</a>
                <?php endif; ?>
                <?php if ($page < $pages): ?>
                    <a class="btn-xs" href="<?= $baseUrl ?>/admin/relances-rapport?agent=<?= $e($agent) ?>&page=<?= $page + 1 ?>">Suivant</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
