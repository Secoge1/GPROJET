<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = fn($s) => \App\Core\Security::escape($s ?? '');
$profil       = $profil ?? [];
$missions     = $missions ?? [];
$referralLink = $referral_link ?? '';
$user         = $user ?? null;
$prenom       = $e($user['prenom'] ?? 'Expert');
$disponible   = !empty($profil['disponible']);

$statut_lb = ['en_cours'=>'En cours','terminee'=>'Terminée','en_attente'=>'En attente','annulee'=>'Annulée'];
$statut_cl = ['en_cours'=>'#16a34a','terminee'=>'#6b7280','en_attente'=>'#f59e0b','annulee'=>'#dc2626'];
?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="mobile-flash-success"><?= $e($_SESSION['flash_success']) ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>

<!-- Salutation + Statut disponibilité -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;gap:0.75rem">
    <div>
        <h2 style="margin:0 0 0.2rem;font-size:1.2rem;font-weight:700;color:var(--primary)">Bonjour, <?= $prenom ?> 👋</h2>
        <div style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.82rem;font-weight:600;color:<?= $disponible ? '#16a34a' : '#6b7280' ?>">
            <span style="width:8px;height:8px;border-radius:50%;background:<?= $disponible ? '#16a34a' : '#9ca3af' ?>"></span>
            <?= $disponible ? 'Disponible' : 'Hors ligne' ?>
        </div>
    </div>
    <a href="<?= $baseUrl ?>/expert/compte" class="icon-avatar" aria-label="Mon compte"><?= mb_strtoupper(mb_substr($prenom, 0, 1)) ?></a>
</div>

<!-- Stats rapides -->
<?php if (!empty($profil)): ?>
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.6rem;margin-bottom:1.25rem">
    <div class="mobile-stat-card">
        <span class="mobile-stat-card__num" style="color:#16a34a"><?= (int)($profil['nb_missions_terminees'] ?? 0) ?></span>
        <span class="mobile-stat-card__lbl">Missions</span>
    </div>
    <div class="mobile-stat-card">
        <span class="mobile-stat-card__num" style="color:#f59e0b"><?= !empty($profil['note_moyenne']) ? number_format((float)$profil['note_moyenne'], 1) : '—' ?></span>
        <span class="mobile-stat-card__lbl">Note ⭐</span>
    </div>
    <div class="mobile-stat-card">
        <span class="mobile-stat-card__num" style="color:#2563eb"><?= (int)($profil['nb_avis'] ?? 0) ?></span>
        <span class="mobile-stat-card__lbl">Avis</span>
    </div>
</div>
<?php endif; ?>

<!-- Actions rapides -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:0.65rem;margin-bottom:1.25rem">
    <a href="<?= $baseUrl ?>/expert/demandes" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.4rem;font-size:0.88rem">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Demandes
    </a>
    <a href="<?= $baseUrl ?>/expert/urgences" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.4rem;font-size:0.88rem;background:#dc2626;border-color:#dc2626">
        🚨 Urgences
    </a>
    <a href="<?= $baseUrl ?>/app/expert-reservations" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center;gap:0.4rem;font-size:0.88rem">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Réservations
    </a>
    <a href="<?= $baseUrl ?>/expert/revenus" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center;gap:0.4rem;font-size:0.88rem">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Revenus
    </a>
    <a href="<?= $baseUrl ?>/expert/profil" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center;gap:0.4rem;font-size:0.88rem">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Mon profil
    </a>
    <a href="<?= $baseUrl ?>/expert/compte" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center;gap:0.4rem;font-size:0.88rem">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        Mon compte
    </a>
</div>

<!-- Dernières missions -->
<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;margin-bottom:1rem">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:0.85rem 1rem;border-bottom:1px solid var(--border)">
        <h3 style="margin:0;font-size:0.9rem;font-weight:700;color:var(--primary)">Dernières missions</h3>
        <a href="<?= $baseUrl ?>/expert/missions" style="font-size:0.8rem;color:var(--accent);text-decoration:none;font-weight:500">Tout voir →</a>
    </div>
    <?php if (empty($missions)): ?>
    <div style="padding:1.25rem 1rem;text-align:center">
        <p class="mobile-empty-hint" style="margin:0">Aucune mission pour le moment.</p>
    </div>
    <?php else: ?>
    <?php foreach (array_slice($missions, 0, 5) as $m): ?>
    <?php $sc = $statut_cl[$m['statut']] ?? '#6b7280'; ?>
    <div style="padding:0.75rem 1rem;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem">
        <div style="flex:1;min-width:0">
            <p style="margin:0 0 0.15rem;font-weight:600;font-size:0.87rem;color:var(--primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= $e($m['demande_titre'] ?? '') ?></p>
            <p style="margin:0;font-size:0.73rem;color:var(--text-muted)"><?= $e(trim(($m['prenom'] ?? '') . ' ' . ($m['nom'] ?? ''))) ?></p>
        </div>
        <span style="flex-shrink:0;font-size:0.7rem;font-weight:600;padding:0.15rem 0.5rem;border-radius:999px;background:<?= $sc ?>18;color:<?= $sc ?>">
            <?= $statut_lb[$m['statut']] ?? $e($m['statut']) ?>
        </span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Parrainage -->
<?php if ($referralLink): ?>
<div style="background:var(--accent-soft);border:1px solid #bbf7d0;border-radius:var(--radius);padding:1rem">
    <h3 style="margin:0 0 0.65rem;font-size:0.88rem;font-weight:700;color:var(--accent)">🎁 Lien de parrainage</h3>
    <div style="display:flex;gap:0.5rem;align-items:center">
        <input type="text" readonly value="<?= $e($referralLink) ?>" id="expert-referral-input"
               style="flex:1;padding:0.6rem 0.75rem;font-size:0.8rem;border:1px solid #bbf7d0;border-radius:8px;background:#fff;color:var(--text);min-width:0">
        <button type="button" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('expert-referral-input').value);this.textContent='✓ Copié!';"
                class="btn-mobile btn-outline btn-sm" style="flex-shrink:0;border-color:var(--accent);color:var(--accent)">Copier</button>
    </div>
</div>
<?php endif; ?>
