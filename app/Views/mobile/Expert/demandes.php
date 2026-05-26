<?php
$baseUrl  = rtrim(BASE_URL ?? '', '/');
$e        = fn($s) => \App\Core\Security::escape($s ?? '');
$demandes = $demandes ?? [];
$proposerUrlPrefix = $proposer_url_prefix ?? (rtrim(BASE_URL ?? '', '/') . '/app/proposer-demande/');

$urgence_lb = ['normale' => 'Normale', 'urgent' => 'Urgent', 'tres_urgent' => 'Très urgent'];
$urgence_cl = ['normale' => null,      'urgent' => '#f59e0b', 'tres_urgent' => '#dc2626'];
?>

<!-- En-tête -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
    <div>
        <h1 style="margin:0;font-size:1.2rem;font-weight:700;color:var(--primary)">Demandes clients</h1>
        <p style="margin:.15rem 0 0;font-size:.82rem;color:var(--text-muted)">
            <?= count($demandes) ?> demande<?= count($demandes) > 1 ? 's' : '' ?> correspondant à votre profil
        </p>
    </div>
</div>

<!-- Bandeau informatif -->
<?php if (!empty($demandes)): ?>
<div style="display:flex;align-items:flex-start;gap:.6rem;padding:.85rem 1rem;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:12px;margin-bottom:1.1rem">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" style="flex-shrink:0;margin-top:.05rem"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <p style="margin:0;font-size:.82rem;color:#5b21b6;line-height:1.5">
        Ces demandes correspondent à vos compétences.<br>
        Envoyez une <strong>proposition</strong> : le client choisit le prestataire qui lui convient.
    </p>
</div>
<?php endif; ?>

<?php if (empty($demandes)): ?>
<!-- État vide -->
<div style="text-align:center;padding:3rem 1rem">
    <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.2" style="margin-bottom:1rem">
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
    </svg>
    <p class="mobile-empty-hint">Aucune demande ne correspond à vos compétences pour le moment.</p>
    <a href="<?= $baseUrl ?>/expert/profil" class="btn-mobile btn-outline btn-sm" style="display:inline-flex;align-items:center;gap:.35rem;margin-top:.75rem">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Mettre à jour mon profil
    </a>
</div>

<?php else: ?>
<!-- Liste des demandes -->
<div style="display:flex;flex-direction:column;gap:.7rem">
    <?php foreach ($demandes as $d):
        $urg  = $d['urgence'] ?? 'normale';
        $uc   = $urgence_cl[$urg] ?? null;
        $client = trim(($d['client_prenom'] ?? '') . ' ' . ($d['client_nom'] ?? ''));
        $initiales = strtoupper(mb_substr($client ?: 'C', 0, 1) . (strpos($client, ' ') !== false ? mb_substr(strrchr($client, ' '), 1, 1) : ''));
        $duree = (float)($d['duree_estimee_heures'] ?? 1);
    ?>
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow)<?= $uc ? ';border-left:3px solid ' . $uc : '' ?>">

        <!-- Corps principal -->
        <div style="padding:.9rem 1rem">
            <div style="display:flex;align-items:flex-start;gap:.7rem">

                <!-- Avatar client (initiales) -->
                <div style="width:40px;height:40px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;color:#64748b;flex-shrink:0">
                    <?= $e($initiales ?: '?') ?>
                </div>

                <!-- Texte -->
                <div style="flex:1;min-width:0">
                    <p style="margin:0 0 .2rem;font-weight:700;font-size:.95rem;color:var(--primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= $e($d['titre'] ?? '') ?>
                    </p>
                    <p style="margin:0;font-size:.78rem;color:var(--text-muted)">
                        <?= $e($client ?: 'Client') ?>
                    </p>
                </div>

                <!-- Badge urgence -->
                <?php if ($uc): ?>
                <span style="flex-shrink:0;font-size:.7rem;font-weight:700;padding:.2rem .55rem;border-radius:999px;background:<?= $uc ?>18;color:<?= $uc ?>">
                    <?= $urgence_lb[$urg] ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <?php if (!empty($d['description'])): ?>
            <p style="margin:.7rem 0 0;font-size:.82rem;color:var(--text-muted);line-height:1.45">
                <?= $e(mb_substr($d['description'], 0, 110)) ?><?= mb_strlen($d['description']) > 110 ? '…' : '' ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Pied de carte : méta + badges -->
        <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;padding:.6rem 1rem;background:#f8fafc;border-top:1px solid var(--border)">
            <?php if (!empty($d['competence_nom'])): ?>
            <span style="font-size:.72rem;font-weight:600;padding:.2rem .55rem;border-radius:999px;background:var(--accent-soft);color:var(--accent)">
                <?= $e($d['competence_nom']) ?>
            </span>
            <?php endif; ?>
            <span style="font-size:.72rem;color:var(--text-muted);display:flex;align-items:center;gap:.2rem">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                ~<?= $duree <= 1 ? '1 h' : number_format($duree, 0) . ' h' ?>
            </span>
            <?php if (!empty($d['budget'])): ?>
            <span style="font-size:.72rem;font-weight:600;color:var(--accent);margin-left:auto">
                <?= number_format((float)$d['budget'], 0, ',', ' ') ?> <?= $e(devise()) ?>
            </span>
            <?php endif; ?>
        </div>
        <?php if (!empty($d['ma_proposition_id'])): ?>
        <p style="margin:.65rem 0 0;font-size:.78rem;font-weight:600;color:#7c3aed">Proposition envoyée — en attente du client</p>
        <?php else: ?>
        <a href="<?= $e($proposerUrlPrefix) ?><?= (int)($d['id'] ?? 0) ?>"
           class="btn-mobile btn-primary btn-sm" style="display:block;text-align:center;margin-top:.65rem">
            Proposer mes services
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<!-- CTA profil en bas -->
<div style="margin-top:1.5rem;padding:1rem;background:var(--accent-soft);border-radius:12px;text-align:center">
    <p style="margin:0 0 .5rem;font-size:.82rem;color:var(--text);font-weight:500">
        Votre profil est-il complet et à jour ?
    </p>
    <a href="<?= $baseUrl ?>/expert/profil" class="btn-mobile btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:.35rem">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Compléter mon profil
    </a>
</div>
<?php endif; ?>
