<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$convPrefix   = $messages_conversation_prefix ?? '/messages/conversation';
$e            = fn($s) => \App\Core\Security::escape($s ?? '');
$reservations = $reservations ?? [];
$user         = $user ?? null;
$role         = $user['role'] ?? 'client';
$unreadConversationIds = isset($unreadConversationIds) && is_array($unreadConversationIds) ? array_map('intval', $unreadConversationIds) : [];

$statut_lb = ['en_attente'=>'En attente','confirme'=>'Confirmée','annule'=>'Annulée','termine'=>'Terminée','paye'=>'Payée'];
$statut_cl = ['en_attente'=>'#f59e0b','confirme'=>'#16a34a','annule'=>'#dc2626','termine'=>'#6b7280','paye'=>'#2563eb'];

function msg_initiales(string $prenom, string $nom): string {
    return strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1)) ?: '?';
}
function msg_avatar_color(string $seed): string {
    $colors = ['#2563eb','#16a34a','#7c3aed','#0d9488','#d97706','#dc2626'];
    return $colors[abs(crc32($seed)) % count($colors)];
}
?>

<!-- Titre -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
    <h1 style="margin:0;font-size:1.2rem;font-weight:800;color:var(--primary)">Messages</h1>
    <?php if (!empty($reservations)): ?>
    <span style="font-size:0.78rem;color:var(--text-muted)"><?= count($reservations) ?> conversation<?= count($reservations) > 1 ? 's' : '' ?></span>
    <?php endif; ?>
</div>

<?php if (empty($reservations)): ?>
<!-- État vide -->
<div style="text-align:center;padding:3.5rem 1rem">
    <div style="width:64px;height:64px;border-radius:50%;background:var(--accent-soft);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="1.5">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
    </div>
    <p style="margin:0 0 0.4rem;font-size:1rem;font-weight:700;color:var(--primary)">Aucun message</p>
    <p style="margin:0 0 1.25rem;font-size:0.85rem;color:var(--text-muted)">Vos conversations apparaîtront ici après une réservation.</p>
    <a href="<?= $baseUrl ?>/experts" class="btn-mobile btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:0.4rem">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        Trouver un expert
    </a>
</div>

<?php else: ?>
<!-- Liste des conversations -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden">
    <?php foreach ($reservations as $i => $r):
        if ($role === 'client') {
            $nom     = trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? ''));
            $titre   = $r['expert_titre'] ?? 'Expert';
        } else {
            $nom     = trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? ''));
            $titre   = $r['demande_titre'] ?? 'Mission';
        }
        $initials = msg_initiales($r['prenom'] ?? '', $r['nom'] ?? '');
        $color    = msg_avatar_color($nom);
        $statut   = $r['statut'] ?? 'en_attente';
        $sColor   = $statut_cl[$statut] ?? '#6b7280';
        $sLabel   = $statut_lb[$statut] ?? $e($statut);
        $date     = !empty($r['date_session']) ? date('d/m/Y', strtotime($r['date_session'])) : (
                    !empty($r['created_at']) ? date('d/m/Y', strtotime($r['created_at'])) : '');
    ?>
    <a href="<?= $baseUrl . $convPrefix ?>/<?= (int)$r['id'] ?>"
       style="display:flex;align-items:center;gap:0.85rem;padding:0.95rem 1rem;text-decoration:none;color:inherit;border-bottom:<?= $i < count($reservations)-1 ? '1px solid var(--border)' : 'none' ?>;transition:background 0.12s"
       ontouchstart="this.style.background='var(--accent-soft)'" ontouchend="this.style.background=''">

        <!-- Avatar -->
        <div style="position:relative;width:46px;height:46px;flex-shrink:0">
        <div style="width:46px;height:46px;border-radius:50%;background:<?= $color ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.9rem">
            <?= $initials ?>
        </div>
        <?php if (in_array((int)$r['id'], $unreadConversationIds, true)): ?>
        <span class="mobile-list-unread-dot" title="Nouveau message" aria-label="Nouveau message"></span>
        <?php endif; ?>
        </div>

        <!-- Texte -->
        <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:baseline;justify-content:space-between;gap:0.4rem;margin-bottom:0.2rem">
                <p style="margin:0;font-size:0.9rem;font-weight:<?= in_array((int)$r['id'], $unreadConversationIds, true) ? '800' : '700' ?>;color:var(--primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= $e($nom) ?></p>
                <?php if ($date): ?>
                <span style="font-size:0.7rem;color:var(--text-muted);flex-shrink:0"><?= $date ?></span>
                <?php endif; ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:0.4rem">
                <p style="margin:0;font-size:0.8rem;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1"><?= $e($titre) ?></p>
                <span style="flex-shrink:0;font-size:0.68rem;font-weight:600;padding:0.15rem 0.5rem;border-radius:999px;background:<?= $sColor ?>18;color:<?= $sColor ?>"><?= $sLabel ?></span>
            </div>
        </div>

        <!-- Flèche -->
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2" style="flex-shrink:0"><path d="M9 18l6-6-6-6"/></svg>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>
