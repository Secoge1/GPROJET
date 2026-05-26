<section class="section-desktop section-form">
    <h1>Vérification de l'email</h1>
    <div class="form-card">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                ✅ Votre adresse email a été vérifiée avec succès.
                <?php if (!empty($startDate)): ?>
                    <br><strong>Votre abonnement démarrera le <?= \App\Core\Security::escape($startDate) ?></strong> (J+1).
                <?php endif; ?>
            </div>
            <p><a href="<?= rtrim(BASE_URL ?? '', '/') ?>/auth/connexion" class="btn btn-primary">Se connecter</a></p>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-error"><?= \App\Core\Security::escape($error) ?></div>
            <p><a href="<?= rtrim(BASE_URL ?? '', '/') ?>/" class="btn btn-primary">Retour à l'accueil</a></p>
        <?php endif; ?>
    </div>
</section>
