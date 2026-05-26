<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = function ($s) { return \App\Core\Security::escape($s ?? ''); };
$user = $user ?? [];
$profil = $profil ?? null;
$nomComplet = trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''));
?>
<section class="section-desktop expert-en-attente">
    <div class="expert-en-attente__card">
        <div class="expert-en-attente__icon" aria-hidden="true">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        </div>
        <h1 class="expert-en-attente__title">Profil en cours de vérification</h1>
        <p class="expert-en-attente__lead">Bonjour<?= $nomComplet ? ' ' . $e($nomComplet) : '' ?>, votre compte expert a bien été créé. Notre équipe vérifie votre profil et vos pièces jointes. Vous recevrez un email dès que votre accès sera activé.</p>
        <p class="expert-en-attente__hint">Vous pouvez en attendant compléter ou modifier votre <strong>photo de profil</strong> et votre <strong>pièce d'identité</strong> depuis la page Mon compte.</p>
        <a href="<?= $baseUrl ?>/expert/compte" class="btn btn-primary">Compléter mon profil</a>
    </div>
</section>
<style>
.expert-en-attente { padding: 2rem 1rem; max-width: 520px; margin: 0 auto; }
.expert-en-attente__card { background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.08); padding: 2.5rem; text-align: center; }
.expert-en-attente__icon { color: #f59e0b; margin-bottom: 1rem; }
.expert-en-attente__title { font-size: 1.35rem; margin: 0 0 0.75rem; color: #1f2937; }
.expert-en-attente__lead { color: #4b5563; margin: 0 0 1rem; line-height: 1.5; }
.expert-en-attente__hint { font-size: 0.9rem; color: #6b7280; margin: 0 0 1.5rem; line-height: 1.5; }
</style>
