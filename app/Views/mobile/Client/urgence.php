<?php
$baseUrl     = rtrim(BASE_URL ?? '', '/');
$e           = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField   = \App\Core\Security::getCsrfField();
$data        = $data ?? ['titre' => '', 'description' => '', 'competence_id' => ''];
$competences = $competences ?? [];
$errors      = $errors ?? [];
$bp          = $client_base_path ?? '/client';
?>

<!-- En-tête urgence -->
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:1rem;margin-bottom:1.25rem;text-align:center">
    <div style="font-size:2rem;margin-bottom:0.35rem">🚨</div>
    <h1 style="margin:0 0 0.25rem;font-size:1.15rem;font-weight:800;color:#dc2626">Besoin d'aide maintenant</h1>
    <p style="margin:0;font-size:0.82rem;color:#dc2626;opacity:0.8">Le premier expert disponible acceptera votre mission</p>
</div>

<?php if (!empty($errors)): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem">
    <?php foreach ($errors as $err): ?>
    <p style="margin:0 0 0.25rem;font-size:0.85rem;color:#dc2626">• <?= $e($err) ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" action="<?= $baseUrl . $bp ?>/urgence" class="form-mobile">
    <?= $csrfField ?>

    <div style="margin-bottom:1rem">
        <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text);margin-bottom:0.35rem">
            Votre besoin <span style="color:#dc2626">*</span>
        </label>
        <input type="text" name="titre" required maxlength="200"
               value="<?= $e($data['titre']) ?>"
               placeholder="Ex : Aide Excel urgente, bug informatique…"
               style="display:block;width:100%;padding:0.75rem 1rem;font-size:16px;border:1.5px solid #fca5a5;border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font)">
    </div>

    <div style="margin-bottom:1rem">
        <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text);margin-bottom:0.35rem">Domaine</label>
        <select name="competence_id" style="display:block;width:100%;padding:0.75rem 1rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font)">
            <option value="">— Tous les domaines —</option>
            <?php foreach ($competences as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($data['competence_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= $e($c['nom']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="margin-bottom:1.5rem">
        <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text);margin-bottom:0.35rem">Détails (optionnel)</label>
        <textarea name="description" rows="3"
                  placeholder="Précisez votre problème…"
                  style="display:block;width:100%;padding:0.75rem 1rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font);resize:vertical"><?= $e($data['description']) ?></textarea>
    </div>

    <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:0.75rem;background:#dc2626;border-color:#dc2626">
        🚨 Lancer l'alerte urgente
    </button>
    <a href="<?= $baseUrl . $bp ?>" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center">← Retour</a>
</form>
