<?php
$csrfField        = \App\Core\Security::getCsrfField();
$baseUrl          = rtrim(BASE_URL ?? '', '/');
$e                = fn($s) => \App\Core\Security::escape($s ?? '');
$data             = $data ?? [];
$competences      = $competences ?? [];
$expertCompetences = $expertCompetences ?? [];
$errors           = $errors ?? [];
$autreId          = $autre_competence_id ?? 0;
$devise           = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
$disponible       = !empty($data['disponible']);
?>

<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem">
    <a href="<?= $baseUrl ?>/expert" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none;flex-shrink:0" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <h1 style="margin:0;font-size:1.15rem;font-weight:700;color:var(--primary)">Mon profil expert</h1>
</div>

<?php if (!empty($errors)): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem">
    <?php foreach ($errors as $err): ?><p style="margin:0 0 0.25rem;font-size:0.85rem;color:#dc2626">• <?= $e($err) ?></p><?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="mobile-flash-success"><?= $e($_SESSION['flash_success']) ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>

<form method="post" action="<?= $baseUrl ?>/expert/profil" class="form-mobile">
    <?= $csrfField ?>

    <!-- Disponibilité -->
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
        <label style="display:flex;align-items:center;justify-content:space-between;cursor:pointer">
            <div>
                <p style="margin:0 0 0.1rem;font-weight:700;font-size:0.92rem;color:var(--primary)">Disponibilité</p>
                <p style="margin:0;font-size:0.78rem;color:var(--text-muted)">Recevoir de nouvelles missions</p>
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem">
                <span style="font-size:0.82rem;font-weight:600;color:<?= $disponible ? '#16a34a' : '#6b7280' ?>"><?= $disponible ? 'Disponible' : 'Hors ligne' ?></span>
                <input type="checkbox" name="disponible" value="1" <?= $disponible ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--accent)">
            </div>
        </label>
    </div>

    <!-- Infos principales -->
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1rem">
        <h2 style="margin:0 0 0.85rem;font-size:0.88rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.04em">Informations</h2>

        <div style="margin-bottom:0.85rem">
            <label style="display:block;font-size:0.82rem;font-weight:600;color:var(--text);margin-bottom:0.3rem">Titre du profil <span style="color:#dc2626">*</span></label>
            <input type="text" name="titre" required maxlength="150"
                   value="<?= $e($data['titre'] ?? '') ?>"
                   placeholder="Ex : Expert Excel & Comptabilité"
                   style="display:block;width:100%;padding:0.7rem 1rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font)">
        </div>

        <div style="margin-bottom:0.85rem">
            <label style="display:block;font-size:0.82rem;font-weight:600;color:var(--text);margin-bottom:0.3rem">Description</label>
            <textarea name="description" rows="4"
                      placeholder="Décrivez vos compétences, votre expérience, vos domaines d'expertise…"
                      style="display:block;width:100%;padding:0.7rem 1rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font);resize:vertical"><?= $e($data['description'] ?? '') ?></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem">
            <div>
                <label style="display:block;font-size:0.82rem;font-weight:600;color:var(--text);margin-bottom:0.3rem">Tarif/heure (<?= $e($devise) ?>)</label>
                <input type="number" name="tarif_horaire" min="0" step="100"
                       value="<?= $e($data['tarif_horaire'] ?? '') ?>"
                       placeholder="Ex : 5000"
                       style="display:block;width:100%;padding:0.7rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font)">
            </div>
            <div>
                <label style="display:block;font-size:0.82rem;font-weight:600;color:var(--text);margin-bottom:0.3rem">Niveau</label>
                <select name="niveau_experience"
                        style="display:block;width:100%;padding:0.7rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font)">
                    <option value="debutant" <?= ($data['niveau_experience'] ?? '') === 'debutant' ? 'selected' : '' ?>>Débutant</option>
                    <option value="intermediaire" <?= ($data['niveau_experience'] ?? '') === 'intermediaire' ? 'selected' : '' ?>>Intermédiaire</option>
                    <option value="confirme" <?= ($data['niveau_experience'] ?? '') === 'confirme' ? 'selected' : '' ?>>Confirmé</option>
                    <option value="expert" <?= ($data['niveau_experience'] ?? '') === 'expert' ? 'selected' : '' ?>>Expert</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Compétences -->
    <div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1rem;margin-bottom:1.25rem">
        <h2 style="margin:0 0 0.85rem;font-size:0.88rem;font-weight:700;color:var(--primary);text-transform:uppercase;letter-spacing:0.04em">Compétences</h2>
        <div style="display:flex;flex-wrap:wrap;gap:0.5rem">
            <?php foreach ($competences as $c): ?>
            <?php $checked = in_array((int)$c['id'], $expertCompetences); ?>
            <label style="display:inline-flex;align-items:center;gap:0.4rem;padding:0.35rem 0.75rem;border-radius:999px;font-size:0.82rem;font-weight:500;cursor:pointer;border:1.5px solid <?= $checked ? 'var(--accent)' : 'var(--border)' ?>;background:<?= $checked ? 'var(--accent-soft)' : 'transparent' ?>;color:<?= $checked ? 'var(--accent)' : 'var(--text-muted)' ?>">
                <input type="checkbox" name="competences[]" value="<?= (int)$c['id'] ?>"
                       <?= $checked ? 'checked' : '' ?>
                       data-competence-id="<?= (int)$c['id'] ?>"
                       style="display:none"
                       onchange="this.closest('label').style.background=this.checked?'var(--accent-soft)':'transparent';this.closest('label').style.borderColor=this.checked?'var(--accent)':'var(--border)';this.closest('label').style.color=this.checked?'var(--accent)':'var(--text-muted)';<?= $autreId && (int)$c['id'] === $autreId ? 'toggleAutreMobile(this.checked);' : '' ?>">
                <?= $e($c['nom']) ?>
            </label>
            <?php endforeach; ?>
        </div>
        <?php if ($autreId): ?>
        <div id="competences-autres-wrap-mobile" style="<?= in_array($autreId, $expertCompetences) ? '' : 'display:none' ?>;margin-top:0.75rem">
            <label style="display:block;font-size:0.82rem;font-weight:600;color:var(--text);margin-bottom:0.3rem">Précisez les « autres » compétences</label>
            <input type="text" name="competences_autres" maxlength="255"
                   value="<?= $e($data['competences_autres'] ?? '') ?>"
                   placeholder="Ex. Power BI, Python, Figma…"
                   style="display:block;width:100%;padding:0.7rem 1rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font)">
        </div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem;margin-bottom:0.75rem">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
        Enregistrer le profil
    </button>
</form>

<script>
function toggleAutreMobile(checked) {
    var wrap = document.getElementById('competences-autres-wrap-mobile');
    if (wrap) wrap.style.display = checked ? '' : 'none';
}
</script>
