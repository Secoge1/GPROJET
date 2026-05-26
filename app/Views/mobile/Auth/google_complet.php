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

<!-- En-tête -->
<div style="text-align:center;padding:1.5rem 0 1rem">
    <a href="<?= $baseUrl ?>/" style="display:inline-block;margin-bottom:1rem">
        <img src="<?= logo_url() ?>" alt="Globalo" style="height:40px;width:auto;max-width:160px;display:block;margin:0 auto">
    </a>
    <h1 style="font-size:1.25rem;font-weight:800;color:var(--text);margin:0 0 .35rem">Finalisez votre inscription</h1>
    <p style="font-size:.85rem;color:var(--text-muted);margin:0">Bienvenue <?= $e($prenom) ?> ! Choisissez votre rôle.</p>
</div>

<!-- Avatar Google -->
<?php if ($picture): ?>
<div style="display:flex;align-items:center;gap:.85rem;background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:.85rem 1rem;margin-bottom:1rem">
    <img src="<?= $e($picture) ?>" alt="" width="48" height="48"
         style="border-radius:50%;object-fit:cover;border:2px solid #e2e8f0;flex-shrink:0">
    <div>
        <p style="margin:0;font-weight:700;font-size:.9rem;color:var(--text)"><?= $e($pending['name'] ?? '') ?></p>
        <p style="margin:0;font-size:.78rem;color:var(--text-muted)"><?= $e($pending['email'] ?? '') ?></p>
        <span style="font-size:.72rem;color:#16a34a">✓ Compte Google vérifié</span>
    </div>
</div>
<?php endif; ?>

<!-- Erreurs -->
<?php if (!empty($errors)): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem">
    <?php foreach ($errors as $err): ?>
    <p style="margin:0 0 .2rem;font-size:.84rem;color:#dc2626">• <?= $e($err) ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" action="<?= $baseUrl ?>/auth/google-complet">
    <?= $csrfField ?>

    <!-- Choix du rôle -->
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:1rem;margin-bottom:1rem">
        <h2 style="font-size:.85rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text);margin:0 0 .75rem">Votre profil</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
            <?php
            $roles = [
                ['value'=>'client',     'icon'=>'🧑‍💼', 'label'=>'Client',     'desc'=>'Aide pro'],
                ['value'=>'expert',     'icon'=>'🎓',   'label'=>'Expert',     'desc'=>'Proposer services'],
                ['value'=>'etudiant',   'icon'=>'📚',   'label'=>'Étudiant',   'desc'=>'Aide scolaire'],
                ['value'=>'professeur', 'icon'=>'🏫',   'label'=>'Professeur', 'desc'=>'Corriger / aider'],
            ];
            foreach ($roles as $r):
            $checked = ($_POST['role'] ?? '') === $r['value'];
            ?>
            <label style="display:flex;flex-direction:column;align-items:center;text-align:center;padding:.75rem .5rem;gap:.25rem;border:2px solid <?= $checked ? '#16a34a' : '#e5e7eb' ?>;border-radius:10px;background:<?= $checked ? '#f0fdf4' : '#fff' ?>;cursor:pointer">
                <input type="radio" name="role" value="<?= $r['value'] ?>" style="display:none" <?= $checked ? 'checked' : '' ?>>
                <span style="font-size:1.4rem"><?= $r['icon'] ?></span>
                <span style="font-size:.82rem;font-weight:700;color:var(--text)"><?= $r['label'] ?></span>
                <span style="font-size:.68rem;color:var(--text-muted)"><?= $r['desc'] ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Pays -->
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:12px;padding:1rem;margin-bottom:1rem">
        <label style="display:block;font-size:.85rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text);margin-bottom:.5rem">Votre pays</label>
        <select name="pays" required style="display:block;width:100%;padding:.75rem .9rem;font-size:1rem;font-family:var(--font);border:1.5px solid var(--border);border-radius:10px;background:#fff;color:var(--text);box-sizing:border-box">
            <option value="">— Sélectionnez votre pays —</option>
            <?php foreach ($paysEligibles as $p): ?>
            <option value="<?= $e($p) ?>" <?= ($_POST['pays'] ?? '') === $p ? 'selected' : '' ?>><?= $e($p) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit" style="display:block;width:100%;padding:.9rem;background:#16a34a;color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:700;font-family:var(--font);cursor:pointer;margin-bottom:.75rem">
        Créer mon compte
    </button>
    <p style="text-align:center;margin:0">
        <a href="<?= $baseUrl ?>/auth/connexion" style="font-size:.82rem;color:var(--text-muted)">Annuler</a>
    </p>
</form>

<script>
/* Visuel radio role dynamique */
document.querySelectorAll('input[name="role"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('input[name="role"]').forEach(function(r) {
            var lbl = r.closest('label');
            if (!lbl) return;
            lbl.style.borderColor = r.checked ? '#16a34a' : '#e5e7eb';
            lbl.style.background  = r.checked ? '#f0fdf4' : '#fff';
        });
    });
});
</script>
