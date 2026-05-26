<?php
$baseUrl       = rtrim(BASE_URL ?? '', '/');
$abonnements   = $abonnements ?? [];
$statutFilter  = $statutFilter ?? null;
$typeFilter    = $typeFilter ?? null;
$countScheduled = $countScheduled ?? 0;
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField = \App\Core\Security::getCsrfField();
$today = date('Y-m-d');

$nbActifs      = count(array_filter($abonnements, fn($a) => ($a['statut'] ?? '') === 'actif' && ($a['date_debut'] ?? '') <= $today));
$nbExpires     = count(array_filter($abonnements, fn($a) => ($a['statut'] ?? '') === 'expire'));
$nbEnAttente   = count(array_filter($abonnements, fn($a) => ($a['statut'] ?? '') === 'actif' && ($a['date_debut'] ?? '') > $today));

/** Retourne les jours restants (négatif = expiré) ou null si pas de date_fin. */
$daysLeft = function(?string $dateFin, string $statut): ?int {
    if (!$dateFin || $statut !== 'actif') return null;
    $diff = (int) ceil((strtotime($dateFin) - time()) / 86400);
    return $diff;
};

$statutColors = ['actif' => 'success', 'expire' => 'danger', 'annule' => 'default'];
$statutLabels = ['actif' => 'Actif', 'expire' => 'Expiré', 'annule' => 'Annulé'];
$planColors = ['premium' => 'warning', 'gratuit' => 'default'];
?>
<div class="page-admin page-admin-abonnements">

    <header class="admin-page-hero">
        <a href="<?= $baseUrl ?>/admin" class="admin-back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Tableau de bord
        </a>
        <div class="admin-page-hero__content">
            <div class="admin-page-hero__icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
            </div>
            <div>
                <h1>Abonnements</h1>
                <p>Gérer les abonnements (clients, experts, professeurs, étudiants)</p>
            </div>
            <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                <span class="admin-badge admin-badge--success"><?= $nbActifs ?> actif<?= $nbActifs > 1 ? 's' : '' ?></span>
                <span class="admin-badge admin-badge--danger"><?= $nbExpires ?> expiré<?= $nbExpires > 1 ? 's' : '' ?></span>
                <?php if ($countScheduled > 0): ?>
                <a href="<?= $baseUrl ?>/admin/abonnements?statut=en_attente"
                   class="admin-badge admin-badge--info" style="text-decoration:none;"
                   title="Abonnements programmés, actifs à partir de demain">
                    ⏳ <?= $countScheduled ?> en attente
                </a>
                <?php endif; ?>
                <form method="post" action="<?= $baseUrl ?>/admin/expire-abonnements-old" style="margin:0;">
                    <?= $csrfField ?>
                    <button type="submit" class="btn btn-sm btn-outline"
                            onclick="return confirm('Expirer automatiquement tous les abonnements dont la date de fin est dépassée ?')"
                            title="Marque comme expirés tous les abonnements dont la date_fin est passée"
                            style="font-size:.75rem;padding:.25rem .7rem;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        Expirer les périmés
                    </button>
                </form>
            </div>
        </div>
        <div class="admin-page-hero__filters">
            <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Statut :</span>
            <a href="<?= $baseUrl ?>/admin/abonnements<?= $typeFilter ? '?type='.$typeFilter : '' ?>" class="admin-filter-pill <?= $statutFilter === null ? 'active' : '' ?>">Tous</a>
            <a href="<?= $baseUrl ?>/admin/abonnements?statut=actif<?= $typeFilter ? '&type='.$typeFilter : '' ?>" class="admin-filter-pill <?= $statutFilter === 'actif' ? 'active' : '' ?>">Actifs</a>
            <a href="<?= $baseUrl ?>/admin/abonnements?statut=en_attente<?= $typeFilter ? '&type='.$typeFilter : '' ?>" class="admin-filter-pill <?= $statutFilter === 'en_attente' ? 'active' : '' ?>" title="Programmés — démarrent demain">⏳ En attente</a>
            <a href="<?= $baseUrl ?>/admin/abonnements?statut=expire<?= $typeFilter ? '&type='.$typeFilter : '' ?>" class="admin-filter-pill <?= $statutFilter === 'expire' ? 'active' : '' ?>">Expirés</a>
            <span style="margin:0 0.25rem;color:rgba(255,255,255,0.3);">|</span>
            <span style="font-size:0.8rem;color:rgba(255,255,255,0.6);font-weight:600;text-transform:uppercase;letter-spacing:0.05em;">Type :</span>
            <a href="<?= $baseUrl ?>/admin/abonnements<?= $statutFilter ? '?statut='.$statutFilter : '' ?>" class="admin-filter-pill <?= $typeFilter === null ? 'active' : '' ?>">Tous</a>
            <a href="<?= $baseUrl ?>/admin/abonnements?type=client<?= $statutFilter ? '&statut='.$statutFilter : '' ?>" class="admin-filter-pill <?= $typeFilter === 'client' ? 'active' : '' ?>">Client</a>
            <a href="<?= $baseUrl ?>/admin/abonnements?type=expert<?= $statutFilter ? '&statut='.$statutFilter : '' ?>" class="admin-filter-pill <?= $typeFilter === 'expert' ? 'active' : '' ?>">Expert</a>
            <a href="<?= $baseUrl ?>/admin/abonnements?type=professeur<?= $statutFilter ? '&statut='.$statutFilter : '' ?>" class="admin-filter-pill <?= $typeFilter === 'professeur' ? 'active' : '' ?>">Professeur</a>
            <a href="<?= $baseUrl ?>/admin/abonnements?type=etudiant<?= $statutFilter ? '&statut='.$statutFilter : '' ?>" class="admin-filter-pill <?= $typeFilter === 'etudiant' ? 'active' : '' ?>">Étudiant</a>
        </div>
    </header>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="admin-alert admin-alert--success"><?= $e($_SESSION['flash_success']) ?></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <div class="admin-table-card">
        <div class="admin-table-card-header">
            <h2>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                <?= count($abonnements) ?> abonnement<?= count($abonnements) > 1 ? 's' : '' ?>
            </h2>
            <div class="admin-table-toolbar">
                <div class="admin-search-wrap">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="search" id="abo-search" placeholder="Utilisateur, email, plan…" class="admin-search-input">
                </div>
                <div class="admin-table-actions">
                    <button type="button" class="btn btn-outline btn-sm admin-export-excel" data-table-id="abo-table" data-export-name="abonnements">Excel</button>
                    <button type="button" class="btn btn-outline btn-sm admin-export-print">Imprimer</button>
                </div>
            </div>
        </div>
        <div class="admin-table-wrap">
            <table class="table-desktop admin-table" id="abo-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Plan</th>
                        <th>Début</th>
                        <th>Expiration</th>
                        <th>Restant</th>
                        <th>Montant payé</th>
                        <th>Fournisseur / Réf.</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($abonnements)): ?>
                    <tr><td colspan="12" class="admin-table-empty">Aucun abonnement trouvé.</td></tr>
                <?php else: foreach ($abonnements as $a): ?>
                <?php
                    $isPending      = ($a['statut'] ?? '') === 'actif' && ($a['date_debut'] ?? '') > $today;
                    $isExpiringSoon = !$isPending && ($a['statut'] ?? '') === 'actif' && isset($a['date_fin']) && strtotime($a['date_fin']) <= strtotime('+7 days');
                    $rowStyle = $isPending ? 'background:#eff6ff;' : ($isExpiringSoon ? 'background:#fffbeb;' : '');
                ?>
                    <tr <?= $rowStyle ? 'style="'.$rowStyle.'"' : '' ?>>
                        <td><?= (int)$a['id'] ?></td>
                        <td><strong><?= $e(trim(($a['prenom'] ?? '') . ' ' . ($a['nom'] ?? ''))) ?></strong></td>
                        <td><?= $e($a['email'] ?? '') ?></td>
                        <td>
                            <?php
                            $typeBadgeClass = 'default';
                            if (($a['type'] ?? '') === 'expert') $typeBadgeClass = 'info';
                            elseif (($a['type'] ?? '') === 'professeur') $typeBadgeClass = 'warning';
                            elseif (($a['type'] ?? '') === 'etudiant') $typeBadgeClass = 'success';
                            ?>
                            <span class="admin-badge admin-badge--<?= $typeBadgeClass ?>">
                                <?= ucfirst($e($a['type'] ?? '—')) ?>
                            </span>
                        </td>
                        <td>
                            <span class="admin-badge admin-badge--<?= $planColors[$a['plan'] ?? ''] ?? 'default' ?>">
                                <?= ucfirst($e($a['plan'] ?? '—')) ?>
                            </span>
                        </td>
                        <td>
                            <?= $e(isset($a['date_debut']) ? date('d/m/Y', strtotime($a['date_debut'])) : '—') ?>
                            <?php if ($isPending): ?>
                                <span style="font-size:0.72rem;color:#2563eb;font-weight:700;margin-left:0.3rem;">⏳ Demain</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $e(isset($a['date_fin']) ? date('d/m/Y', strtotime($a['date_fin'])) : '—') ?>
                            <?php if ($isExpiringSoon): ?>
                                <span style="font-size:0.75rem;color:#d97706;margin-left:0.25rem;">⚠ Bientôt</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $jours = $daysLeft($a['date_fin'] ?? null, $a['statut'] ?? '');
                            if ($jours === null): ?>
                                <span class="text-muted">—</span>
                            <?php elseif ($jours > 7): ?>
                                <span style="color:#16a34a;font-weight:600;"><?= $jours ?>j</span>
                            <?php elseif ($jours > 0): ?>
                                <span style="color:#d97706;font-weight:700;">⚠ <?= $jours ?>j</span>
                            <?php else: ?>
                                <span style="color:#dc2626;font-weight:700;">Périmé</span>
                            <?php endif; ?>
                        </td>
                        <td><?= (float)($a['montant_paye'] ?? 0) > 0 ? number_format((float)$a['montant_paye'], 0, ',', ' ') . ' XOF' : '<span class="text-muted">Gratuit</span>' ?></td>
                        <td>
                            <?php
                            $provider = $a['payment_provider'] ?? '';
                            $extRef   = $a['external_reference'] ?? '';
            if ($provider === 'intouch' || $provider === 'paytech'): ?>
                                <span style="font-size:0.78rem;font-weight:600;color:#0f766e">Service de paiement</span>
                                <?php if ($extRef): ?>
                                <div style="font-size:0.68rem;color:#6b7280;font-family:monospace;margin-top:2px;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= $e($extRef) ?>">
                                    <?= $e(substr($extRef, 0, 18)) ?><?= strlen($extRef) > 18 ? '…' : '' ?>
                                </div>
                                <?php endif; ?>
                            <?php elseif ($provider === 'jemenipay'): ?>
                                <div style="display:flex;align-items:center;gap:5px;">
                                    <span style="font-size:0.78rem;font-weight:600;color:#64748b">Jɛmɛnipay</span>
                                    <span style="font-size:0.65rem;color:#94a3b8">(historique)</span>
                                </div>
                            <?php elseif ($provider === 'wave_api' || $provider === 'wave'): ?>
                                <div style="display:flex;align-items:center;gap:5px;">
                                    <span style="font-size:0.78rem;color:#64748b">Wave</span>
                                    <span style="font-size:0.65rem;color:#94a3b8">(historique)</span>
                                </div>
                            <?php elseif ($provider): ?>
                                <span style="font-size:0.8rem;color:#374151"><?= $e($provider) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isPending): ?>
                                <span class="admin-badge admin-badge--info" title="Abonnement programmé, actif à partir du <?= $e(isset($a['date_debut']) ? date('d/m/Y', strtotime($a['date_debut'])) : '') ?>">
                                    ⏳ En attente
                                </span>
                            <?php else:
                                $sc = $statutColors[$a['statut'] ?? ''] ?? 'default'; ?>
                                <span class="admin-badge admin-badge--<?= $sc ?>"><?= $statutLabels[$a['statut'] ?? ''] ?? $e($a['statut'] ?? '—') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="admin-table-col-action">
                            <div class="admin-action-group">
                                <!-- Voir l'utilisateur -->
                                <a href="<?= $baseUrl ?>/admin/edit-user/<?= (int)$a['utilisateur_id'] ?>" class="admin-action-btn admin-action-btn--neutral" title="Voir l'utilisateur">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                                    Profil
                                </a>

                                <?php if (($a['statut'] ?? '') === 'actif'): ?>
                                <!-- Expirer / Annuler (fonctionne aussi pour les "en attente") -->
                                <form method="post" action="<?= $baseUrl ?>/admin/expire-abonnement/<?= (int)$a['id'] ?>">
                                    <?= $csrfField ?>
                                    <button type="submit" class="admin-action-btn admin-action-btn--danger"
                                            title="<?= $isPending ? 'Annuler cet abonnement programmé' : 'Expirer l\'abonnement' ?>"
                                            onclick="return confirm('<?= $isPending ? 'Annuler cet abonnement programmé ?' : 'Expirer cet abonnement ?' ?>')">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                        <?= $isPending ? 'Annuler' : 'Expirer' ?>
                                    </button>
                                </form>

                                <?php elseif (in_array($a['statut'] ?? '', ['expire', 'annule'], true)): ?>
                                <!-- Renouveler -->
                                <form method="post" action="<?= $baseUrl ?>/admin/renouveler-abonnement/<?= (int)$a['utilisateur_id'] ?>" style="display:flex;align-items:center;gap:0.4rem;">
                                    <?= $csrfField ?>
                                    <input type="hidden" name="type" value="<?= $e($a['type'] ?? 'client') ?>">
                                    <input type="number" name="duree_jours" value="30" min="1" max="365"
                                        style="width:58px;padding:0.2rem 0.4rem;border:1px solid #e2e8f0;border-radius:5px;font-size:0.8rem;text-align:center;"
                                        title="Durée en jours">
                                    <button type="submit" class="admin-action-btn admin-action-btn--success" title="Renouveler l'abonnement">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                        Renouveler
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('abo-search')?.addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#abo-table tbody tr').forEach(function(tr) {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
