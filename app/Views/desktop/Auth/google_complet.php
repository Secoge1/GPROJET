<?php
$csrfField     = \App\Core\Security::getCsrfField();
$baseUrl       = rtrim(BASE_URL ?? '', '/');
$e             = fn($s) => \App\Core\Security::escape($s ?? '');
$pending       = $pending ?? [];
$errors        = $errors ?? [];
$paysEligibles = $pays_eligibles ?? ['Mali', "Côte d'Ivoire", 'Sénégal', 'Bénin', 'Niger'];
$prenom        = $pending['given_name'] ?? explode(' ', $pending['name'] ?? 'Utilisateur')[0];
$picture       = $pending['picture'] ?? '';
?>
<div class="page-inscription">
    <div class="auth-page-backdrop" aria-hidden="true"></div>
    <div class="auth-page-content">

        <header class="auth-intro">
            <a href="<?= $baseUrl ?>/" class="auth-logo-link" aria-label="Globalo - Accueil">
                <span class="auth-logo-circle">
                    <img src="<?= logo_url() ?>" alt="Globalo" class="auth-logo" width="160" height="160">
                </span>
            </a>
            <span class="auth-badge">Presque prêt !</span>
            <h1>Finalisez votre inscription</h1>
            <p class="auth-intro-lead">
                Bienvenue <?= $e($prenom) ?> ! Votre compte Google a bien été reconnu.<br>
                Choisissez votre rôle pour accéder à la plateforme.
            </p>
        </header>

        <div class="auth-form-wrapper">

            <!-- Avatar Google (si disponible) -->
            <?php if ($picture): ?>
            <div style="display:flex;align-items:center;gap:.85rem;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:.85rem 1rem;margin-bottom:1.5rem">
                <img src="<?= $e($picture) ?>" alt="Photo Google" width="52" height="52"
                     style="border-radius:50%;object-fit:cover;border:2px solid #e2e8f0">
                <div>
                    <p style="margin:0;font-weight:700;font-size:.95rem"><?= $e($pending['name'] ?? '') ?></p>
                    <p style="margin:0;font-size:.82rem;color:#6b7280"><?= $e($pending['email'] ?? '') ?></p>
                    <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.72rem;color:#16a34a;margin-top:.2rem">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Compte Google vérifié
                    </span>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-error" role="alert">
                <span class="alert-icon" aria-hidden="true">!</span>
                <ul style="margin:0;padding:0 0 0 1rem">
                    <?php foreach ($errors as $err): ?>
                    <li><?= $e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="post" action="<?= $baseUrl ?>/auth/google-complet" class="auth-form">
                <?= $csrfField ?>

                <!-- Choix du rôle -->
                <section class="auth-form-block" aria-labelledby="block-role">
                    <h2 id="block-role" class="auth-form-block-title">
                        <span class="block-title-num">1</span> Quel est votre profil ?
                    </h2>
                    <div class="role-cards">
                        <?php
                        $roles = [
                            ['value'=>'client',     'icon'=>'🧑‍💼', 'label'=>'Client',     'desc'=>'Je cherche de l\'aide pour mes tâches pro'],
                            ['value'=>'expert',     'icon'=>'🎓',   'label'=>'Expert',     'desc'=>'Je propose mes services et compétences'],
                            ['value'=>'etudiant',   'icon'=>'📚',   'label'=>'Étudiant',   'desc'=>'J\'ai besoin d\'aide pour mes exercices universitaires'],
                            ['value'=>'professeur', 'icon'=>'🏫',   'label'=>'Professeur', 'desc'=>'Je corrige et aide les étudiants'],
                        ];
                        foreach ($roles as $r):
                        ?>
                        <label class="role-card" for="role_<?= $r['value'] ?>">
                            <input type="radio" name="role" id="role_<?= $r['value'] ?>"
                                   value="<?= $r['value'] ?>" class="role-card__input"
                                   <?= ($_POST['role'] ?? '') === $r['value'] ? 'checked' : '' ?>>
                            <span class="role-card__icon"><?= $r['icon'] ?></span>
                            <span class="role-card__label"><?= $r['label'] ?></span>
                            <span class="role-card__desc"><?= $r['desc'] ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </section>

                <!-- Pays -->
                <section class="auth-form-block" aria-labelledby="block-pays">
                    <h2 id="block-pays" class="auth-form-block-title">
                        <span class="block-title-num">2</span> Votre pays
                    </h2>
                    <div class="form-group">
                        <label for="pays">Pays de résidence</label>
                        <select name="pays" id="pays" required class="form-control">
                            <option value="">— Sélectionnez votre pays —</option>
                            <?php foreach ($paysEligibles as $p): ?>
                            <option value="<?= $e($p) ?>" <?= ($_POST['pays'] ?? '') === $p ? 'selected' : '' ?>><?= $e($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </section>

                <div class="auth-form-actions">
                    <button type="submit" class="btn btn-primary btn-lg btn-block btn-auth-submit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align:-2px"><polyline points="20 6 9 17 4 12"/></svg>
                        Créer mon compte &amp; accéder à GLOBALO
                    </button>
                    <p class="auth-form-link" style="font-size:.8rem;color:#9ca3af">
                        <a href="<?= $baseUrl ?>/auth/connexion" style="color:#6b7280">Annuler et revenir à la connexion</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.role-cards {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: .75rem;
    margin-top: .5rem;
}
.role-card {
    display: flex; flex-direction: column; align-items: center; text-align: center;
    padding: 1rem .75rem; gap: .35rem;
    border: 2px solid #e5e7eb; border-radius: 12px;
    cursor: pointer; transition: border-color .15s, background .15s;
    background: #fff;
}
.role-card:has(.role-card__input:checked),
.role-card__input:checked + * { border-color: var(--color-primary, #16a34a); background: #f0fdf4; }
.role-card:hover { border-color: #86efac; }
.role-card__input { display: none; }
.role-card__icon  { font-size: 1.6rem; }
.role-card__label { font-size: .9rem; font-weight: 700; color: #1c1917; }
.role-card__desc  { font-size: .72rem; color: #6b7280; line-height: 1.3; }
@media (max-width: 480px) {
    .role-cards { grid-template-columns: 1fr 1fr; }
    .role-card   { padding: .85rem .5rem; }
}
</style>
