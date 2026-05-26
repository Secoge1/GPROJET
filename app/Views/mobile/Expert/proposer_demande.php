<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$demande = $demande ?? [];
$propExistante = $proposition_existante ?? null;
$propData = $prop_data ?? [];
$errors = $errors ?? [];
$competencesNoms = $competences_noms ?? [];
$csrf = \App\Core\Security::getCsrfField();
$demandeId = (int) ($demande['id'] ?? 0);
$demandesListUrl = $demandes_list_url ?? ($baseUrl . '/app/expert-demandes');
$formAction = $proposer_form_action ?? ($baseUrl . '/app/proposer-demande/' . $demandeId);
?>

<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem">
    <a href="<?= $e($demandesListUrl) ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <div style="flex:1;min-width:0">
        <h1 style="margin:0;font-size:1.05rem;font-weight:700;color:var(--primary)">Proposer mes services</h1>
        <p style="margin:.2rem 0 0;font-size:.8rem;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= $e($demande['titre'] ?? '') ?></p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:12px;padding:.85rem 1rem;margin-bottom:1rem">
    <ul style="margin:0;padding-left:1.1rem;font-size:.82rem;color:#dc2626">
        <?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:1rem;margin-bottom:1rem">
    <h2 style="margin:0 0 .6rem;font-size:.88rem;font-weight:700;color:var(--primary)">Besoin du client</h2>
    <?php if (!empty($demande['competence_nom'])): ?>
    <p style="margin:0 0 .4rem;font-size:.8rem"><strong>Compétence :</strong> <?= $e($demande['competence_nom']) ?></p>
    <?php endif; ?>
    <?php if (!empty($demande['description'])): ?>
    <p style="margin:0;font-size:.82rem;color:var(--text-muted);line-height:1.5"><?= nl2br($e($demande['description'])) ?></p>
    <?php endif; ?>
</div>

<?php if ($propExistante): ?>
<div style="text-align:center;padding:1.5rem 1rem;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:14px">
    <p style="margin:0 0 1rem;font-size:.88rem;color:#5b21b6">Proposition déjà envoyée (<?= $e($propExistante['statut'] ?? '') ?>).</p>
    <a href="<?= $e($demandesListUrl) ?>" class="btn-mobile btn-outline btn-sm">Retour aux demandes</a>
</div>
<?php else: ?>
<form method="post" action="<?= $e($formAction) ?>" style="background:var(--card-bg);border:1px solid var(--border);border-radius:14px;padding:1rem">
    <?= $csrf ?>
    <?php if (!empty($competencesNoms)): ?>
    <p style="margin:0 0 1rem;font-size:.78rem;color:var(--text-muted)">Vos compétences : <strong><?= $e(implode(', ', $competencesNoms)) ?></strong></p>
    <?php endif; ?>

    <div style="display:flex;flex-direction:column;gap:.85rem">
        <div>
            <label for="prop-presentation" style="display:block;font-size:.78rem;font-weight:600;margin-bottom:.35rem">Présentation courte *</label>
            <input type="text" id="prop-presentation" name="presentation" maxlength="500" required
                   value="<?= $e($propData['presentation'] ?? '') ?>"
                   style="width:100%;padding:.6rem .75rem;border:1px solid var(--border);border-radius:10px;font-size:.88rem;box-sizing:border-box"
                   placeholder="Ex. Expert Excel, 8 ans d'expérience">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
            <div>
                <label for="prop-tarif" style="display:block;font-size:.78rem;font-weight:600;margin-bottom:.35rem">Tarif (FCFA) *</label>
                <input type="number" id="prop-tarif" name="tarif_propose" min="500" required
                       value="<?= $e((string)($propData['tarif_propose'] ?? '')) ?>"
                       style="width:100%;padding:.6rem .75rem;border:1px solid var(--border);border-radius:10px;font-size:.88rem;box-sizing:border-box">
            </div>
            <div>
                <label for="prop-delai" style="display:block;font-size:.78rem;font-weight:600;margin-bottom:.35rem">Délai (jours) *</label>
                <input type="number" id="prop-delai" name="delai_jours" min="1" max="90" required
                       value="<?= $e((string)($propData['delai_jours'] ?? '3')) ?>"
                       style="width:100%;padding:.6rem .75rem;border:1px solid var(--border);border-radius:10px;font-size:.88rem;box-sizing:border-box">
            </div>
        </div>
        <div>
            <label for="prop-competences" style="display:block;font-size:.78rem;font-weight:600;margin-bottom:.35rem">Compétences clés</label>
            <input type="text" id="prop-competences" name="competences_cles" maxlength="500"
                   value="<?= $e($propData['competences_cles'] ?? '') ?>"
                   style="width:100%;padding:.6rem .75rem;border:1px solid var(--border);border-radius:10px;font-size:.88rem;box-sizing:border-box">
        </div>
        <div>
            <label for="prop-message" style="display:block;font-size:.78rem;font-weight:600;margin-bottom:.35rem">Message détaillé *</label>
            <textarea id="prop-message" name="message" rows="5" maxlength="5000" required
                      style="width:100%;padding:.6rem .75rem;border:1px solid var(--border);border-radius:10px;font-size:.88rem;box-sizing:border-box;resize:vertical"
                      placeholder="Votre approche, livrables, garanties…"><?= $e($propData['message'] ?? '') ?></textarea>
        </div>
    </div>

    <button type="submit" class="btn-mobile btn-primary" style="width:100%;margin-top:1.1rem">Envoyer ma proposition</button>
    <a href="<?= $e($demandesListUrl) ?>" class="btn-mobile btn-outline btn-sm" style="display:block;text-align:center;margin-top:.65rem">Annuler</a>
</form>
<?php endif; ?>
