<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$user = $user ?? [];
$profil = $profil ?? null;
$nomComplet = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
?>
<div class="mob-page mob-en-attente">
    <div class="mob-en-attente__card">
        <div class="mob-en-attente__icon" aria-hidden="true">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </div>
        <h1 class="mob-en-attente__title">Profil en cours de vérification</h1>
        <p class="mob-en-attente__lead">Bonjour<?= $nomComplet ? ' ' . $e($nomComplet) : '' ?>, votre compte expert a bien été créé. Notre équipe vérifie votre profil et vos pièces jointes. Vous recevrez un email dès que votre accès sera activé.</p>
        <p class="mob-en-attente__hint">Vous pouvez compléter ou modifier votre <strong>photo</strong> et votre <strong>pièce d'identité</strong> depuis Mon compte.</p>
        <a href="<?= $baseUrl ?>/expert/compte" class="mob-en-attente__btn">Compléter mon profil</a>
    </div>
</div>
<style>
.mob-en-attente { padding: 1.5rem 1rem; }
.mob-en-attente__card {
    background: var(--card-bg, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 14px;
    padding: 1.75rem 1.25rem;
    text-align: center;
}
.mob-en-attente__icon { color: #f59e0b; margin-bottom: 1rem; }
.mob-en-attente__title { font-size: 1.2rem; font-weight: 700; margin: 0 0 0.6rem; color: var(--text, #1f2937); }
.mob-en-attente__lead { font-size: .9rem; color: var(--text-muted, #6b7280); margin: 0 0 0.75rem; line-height: 1.5; }
.mob-en-attente__hint { font-size: .85rem; color: var(--text-muted); margin: 0 0 1.25rem; line-height: 1.45; }
.mob-en-attente__btn {
    display: inline-block;
    padding: .75rem 1.5rem;
    background: var(--accent, #16a34a);
    color: #fff;
    border-radius: 10px;
    font-weight: 600;
    font-size: .95rem;
    text-decoration: none;
}
</style>
