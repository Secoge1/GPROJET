<?php
$csrfField  = \App\Core\Security::getCsrfField();
$baseUrl    = rtrim(BASE_URL ?? '', '/');
$e          = fn($s) => \App\Core\Security::escape($s ?? '');
$data       = $data ?? [];
$competences = $competences ?? [];
$errors     = $errors ?? [];
$bp         = $client_base_path ?? '/client';
$nouvelleAction = $baseUrl . ($bp === '/app' ? '/app/nouvelle' : $bp . '/demandes/nouvelle');
$demandesListUrl = $baseUrl . ($bp === '/app' ? '/app/demandes' : $bp . '/demandes');
?>

<!-- En-tête -->
<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem">
    <a href="<?= $demandesListUrl ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none;flex-shrink:0" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <div>
        <h1 style="margin:0;font-size:1.15rem;font-weight:700;color:var(--primary)">Nouvelle demande</h1>
        <p style="margin:0;font-size:0.8rem;color:var(--text-muted)">Décrivez votre besoin</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem">
    <?php foreach ($errors as $err): ?>
    <p style="margin:0 0 0.25rem;font-size:0.85rem;color:#dc2626">• <?= $e($err) ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" action="<?= $nouvelleAction ?>" class="form-mobile">
    <?= $csrfField ?>

    <div style="margin-bottom:1rem">
        <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text);margin-bottom:0.35rem">Titre <span style="color:#dc2626">*</span></label>
        <input type="text" name="titre" required maxlength="200"
               value="<?= $e($data['titre'] ?? '') ?>"
               placeholder="Ex : Aide sur Excel, traduction document…"
               style="display:block;width:100%;padding:0.75rem 1rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font)">
    </div>

    <div style="margin-bottom:1rem">
        <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text);margin-bottom:0.35rem">Description <span style="color:#dc2626">*</span></label>
        <textarea name="description" rows="5" required
                  placeholder="Décrivez précisément votre besoin, vos attentes, le contexte…"
                  style="display:block;width:100%;padding:0.75rem 1rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font);resize:vertical"><?= $e($data['description'] ?? '') ?></textarea>
    </div>

    <div style="margin-bottom:1rem">
        <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text);margin-bottom:0.35rem">Compétence requise</label>
        <select name="competence_id" style="display:block;width:100%;padding:0.75rem 1rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font)">
            <option value="">— Choisir —</option>
            <?php foreach ($competences as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= ($data['competence_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= $e($c['nom']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1.5rem">
        <div>
            <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text);margin-bottom:0.35rem">Durée (heures)</label>
            <input type="number" name="duree_estimee_heures" min="0.5" max="8" step="0.5"
                   value="<?= $e($data['duree_estimee_heures'] ?? '1') ?>"
                   style="display:block;width:100%;padding:0.75rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font)">
        </div>
        <div>
            <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text);margin-bottom:0.35rem">Urgence</label>
            <select name="urgence" style="display:block;width:100%;padding:0.75rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font)">
                <option value="normale" <?= ($data['urgence'] ?? '') === 'normale' ? 'selected' : '' ?>>Normale</option>
                <option value="urgent" <?= ($data['urgence'] ?? '') === 'urgent' ? 'selected' : '' ?>>Urgent</option>
                <option value="tres_urgent" <?= ($data['urgence'] ?? '') === 'tres_urgent' ? 'selected' : '' ?>>Très urgent</option>
            </select>
        </div>
    </div>

    <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:0.75rem">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
        Publier la demande
    </button>
    <a href="<?= $demandesListUrl ?>" class="btn-mobile btn-outline" style="display:flex;align-items:center;justify-content:center">Annuler</a>
</form>
