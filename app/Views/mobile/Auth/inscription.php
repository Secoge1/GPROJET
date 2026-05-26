<?php
$csrfField      = \App\Core\Security::getCsrfField();
$baseUrl        = rtrim(BASE_URL ?? '', '/');
$data           = $data ?? [];
$competences    = $competences ?? [];
$autreCompId    = $autre_competence_id ?? null;
$paysEligibles  = $pays_eligibles ?? ['Mali', "Côte d'Ivoire", 'Sénégal', 'Bénin', 'Niger'];
$planGratuitActif = (bool) ($abonnement_plan_gratuit_actif ?? true);
$prixClientXof    = (float) ($abonnement_prix_client_xof ?? 0);
$prixExpertXof    = (float) ($abonnement_prix_expert_xof ?? 0);
$prixEtudiantXof  = (float) ($abonnement_prix_etudiant_xof ?? 0);
$prixProfesseurXof = (float) ($abonnement_prix_professeur_xof ?? 1000);
$e   = fn($s) => \App\Core\Security::escape($s ?? '');
$ref = isset($ref) ? trim((string) $ref) : '';
// Libellés d'abonnement par rôle (montants issus du contrôleur)
$descClient     = $prixClientXof > 0 ? 'Abonnement ' . number_format((int)$prixClientXof, 0, ',', ' ') . ' Fcfa/mois' : 'Gratuit';
$descExpert     = $prixExpertXof > 0 ? 'Abonnement ' . number_format((int)$prixExpertXof, 0, ',', ' ') . ' Fcfa/mois' : 'Gratuit';
$descEtudiant   = $prixEtudiantXof > 0
    ? 'Abonnement ' . number_format((int)$prixEtudiantXof, 0, ',', ' ') . ' Fcfa/mois · exercices par matière'
    : 'Gratuit · exercices par matière';
$descProfesseur = 'Abonnement ' . number_format((int)$prixProfesseurXof, 0, ',', ' ') . ' Fcfa/mois';
$currentRole = $data['role'] ?? 'client';
$matieresInscription = [];
try {
    $matModel = new \App\Models\MatiereModel();
    $matieresInscription = $matModel->getActivesGrouped();
} catch (\Throwable $t) {}
?>

<!-- ═══ Hero ═══ -->
<div class="ins-hero">
    <a href="<?= $baseUrl ?>/" class="ins-logo-wrap">
        <img src="<?= logo_url() ?>" alt="Globalo" style="height:44px;width:auto;max-width:180px;display:block">
    </a>
    <h1 class="ins-title">Créer un compte</h1>
    <p class="ins-lead">Rejoignez des milliers d'utilisateurs sur Globalo</p>
</div>

<!-- ═══ Erreurs ═══ -->
<?php if (!empty($errors)): ?>
<div class="ins-alert">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <ul style="margin:0;padding:0 0 0 .5rem;list-style:disc">
        <?php foreach ($errors as $err): ?><li><?= $e($err) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>


<?php if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== ''): ?>
<style>
.btn-google-mob{position:relative;display:flex;align-items:center;justify-content:center;gap:12px;width:100%;padding:15px 18px;background:#fff;color:#3c4043;border:1.5px solid #e0e0e0;border-radius:14px;font-size:.9375rem;font-weight:600;font-family:inherit;text-decoration:none;cursor:pointer;overflow:hidden;transition:box-shadow .28s cubic-bezier(.22,1,.36,1),border-color .2s,transform .2s;box-shadow:0 2px 8px rgba(0,0,0,.08),0 1px 3px rgba(0,0,0,.05);letter-spacing:.01em;box-sizing:border-box;margin-bottom:.9rem}
.btn-google-mob:active{transform:scale(.98);box-shadow:0 1px 4px rgba(0,0,0,.08)}
.btn-google-mob:focus-visible{outline:3px solid #4285F4;outline-offset:2px}
.btn-google-mob.loading{pointer-events:none;opacity:.8}
.btn-google-mob__icon{display:flex;align-items:center;justify-content:center;width:22px;height:22px;flex-shrink:0}
.btn-google-mob__text{flex:1;text-align:center}
.btn-google-mob__shine{position:absolute;top:0;left:-80%;width:50%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.6),transparent);transform:skewX(-15deg);pointer-events:none;animation:google-shine 3s 1s ease-in-out forwards}
@keyframes google-shine{0%{left:-80%}100%{left:130%}}
.google-or-divider{display:flex;align-items:center;gap:10px;margin:.25rem 0 1rem;color:#9ca3af;font-size:.8rem;font-weight:500}
.google-or-divider__line{flex:1;height:1px;background:linear-gradient(90deg,transparent,#e5e7eb 30%,#e5e7eb 70%,transparent)}
.google-or-divider__text{white-space:nowrap;padding:0 4px}
</style>
<a href="<?= $baseUrl ?>/auth/google<?= $ref !== '' ? '?ref=' . urlencode($ref) : '' ?>" class="btn-google-mob" id="btn-google-mob-signup" aria-label="S'inscrire avec Google">
    <span class="btn-google-mob__icon" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 48 48">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
        </svg>
    </span>
    <span class="btn-google-mob__text">S'inscrire avec Google</span>
    <span class="btn-google-mob__shine" aria-hidden="true"></span>
</a>
<div class="google-or-divider"><span class="google-or-divider__line"></span><span class="google-or-divider__text">ou avec email</span><span class="google-or-divider__line"></span></div>
<script>
(function(){
    var b=document.getElementById('btn-google-mob-signup');
    if(!b) return;
    b.addEventListener('click',function(){b.classList.add('loading');b.querySelector('.btn-google-mob__text').textContent='Redirection…';});
})();
</script>
<?php endif; ?>

<!-- ═══ Formulaire ═══ -->
<form method="post" action="<?= $baseUrl ?>/auth/inscription" class="ins-form" id="ins-form">
    <?= $csrfField ?>
    <?php if (!empty($ref)): ?>
    <input type="hidden" name="ref" value="<?= $e($ref) ?>">
    <?php endif; ?>

    <!-- ── Section 1 : Identité ── -->
    <div class="ins-section">
        <p class="ins-section-label">
            <span class="ins-step">1</span>
            Informations personnelles
        </p>

        <div class="ins-row2">
            <div class="ins-field">
                <label class="ins-label">Prénom <span class="ins-req">*</span></label>
                <input type="text" name="prenom" class="ins-input" required autocomplete="given-name"
                       value="<?= $e($data['prenom'] ?? '') ?>" placeholder="Votre prénom">
            </div>
            <div class="ins-field">
                <label class="ins-label">Nom <span class="ins-req">*</span></label>
                <input type="text" name="nom" class="ins-input" required autocomplete="family-name"
                       value="<?= $e($data['nom'] ?? '') ?>" placeholder="Votre nom">
            </div>
        </div>

        <div class="ins-field">
            <label class="ins-label">Adresse e-mail <span class="ins-req">*</span></label>
            <div class="ins-input-wrap">
                <svg class="ins-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                <input type="email" name="email" class="ins-input ins-input--icon" required autocomplete="email"
                       value="<?= $e($data['email'] ?? '') ?>" placeholder="vous@exemple.fr">
            </div>
        </div>

        <div class="ins-field">
            <label class="ins-label">Pays <span class="ins-req">*</span></label>
            <div class="ins-input-wrap">
                <svg class="ins-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <select name="pays" class="ins-input ins-input--icon ins-select" required>
                    <option value="">— Votre pays —</option>
                    <?php foreach ($paysEligibles as $p): ?>
                    <option value="<?= $e($p) ?>" <?= ($data['pays'] ?? '') === $p ? 'selected' : '' ?>><?= $e($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- ── Section 2 : Rôle ── -->
    <div class="ins-section">
        <p class="ins-section-label">
            <span class="ins-step">2</span>
            Je m'inscris en tant que…
        </p>

        <div class="ins-roles" role="group">
            <?php
            $roles = [
                ['val'=>'client',     'icon'=>'💼', 'label'=>'Client(e)',     'desc'=>$descClient],
                ['val'=>'expert',     'icon'=>'🎯', 'label'=>'Expert(e)',     'desc'=>$descExpert],
                ['val'=>'etudiant',   'icon'=>'🎓', 'label'=>'Étudiant(e)',   'desc'=>$descEtudiant],
                ['val'=>'professeur', 'icon'=>'👨‍🏫', 'label'=>'Professeur(e)', 'desc'=>$descProfesseur],
            ];
            foreach ($roles as $r):
                $active = $currentRole === $r['val'];
            ?>
            <label class="ins-role <?= $active ? 'ins-role--active' : '' ?>">
                <input type="radio" name="role" value="<?= $r['val'] ?>" <?= $active ? 'checked' : '' ?>>
                <span class="ins-role__emoji"><?= $r['icon'] ?></span>
                <div class="ins-role__text">
                    <span class="ins-role__label"><?= $r['label'] ?></span>
                    <span class="ins-role__desc"><?= $r['desc'] ?></span>
                </div>
                <span class="ins-role__check">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                </span>
            </label>
            <?php endforeach; ?>
        </div>

        <!-- Champs expert (masqués si autre rôle) -->
        <div id="ins-expert-wrap" style="<?= $currentRole === 'expert' ? '' : 'display:none' ?>;margin-top:.75rem;display:<?= $currentRole === 'expert' ? 'flex' : 'none' ?>;flex-direction:column;gap:.75rem">
            <?php if (!empty($competences)): ?>
            <div class="ins-field">
                <label class="ins-label">Compétence principale</label>
                <select name="competence_id" id="ins-comp-select" class="ins-input ins-select">
                    <option value="">— Choisir une compétence —</option>
                    <?php foreach ($competences as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ($autreCompId && (int)$c['id'] === $autreCompId) ? 'data-is-autres="1"' : '' ?>>
                        <?= $e($c['nom'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ins-field" id="ins-niveau-wrap" style="display:none">
                <label class="ins-label">Niveau</label>
                <select name="competence_niveau" class="ins-input ins-select">
                    <option value="debutant">Débutant</option>
                    <option value="intermediaire" selected>Intermédiaire</option>
                    <option value="avance">Avancé</option>
                    <option value="expert">Expert</option>
                </select>
            </div>
            <div class="ins-field" id="ins-autres-wrap" style="display:none">
                <label class="ins-label">Précisez vos compétences</label>
                <input type="text" name="competences_autres" class="ins-input" maxlength="255" placeholder="Ex : Python, Power BI…">
            </div>
            <div class="ins-field">
                <label class="ins-label">Votre parcours <span style="font-weight:400;color:var(--text-muted);font-size:.8rem">(visible publiquement)</span></label>
                <textarea name="expert_bio" class="ins-input ins-textarea" rows="3" maxlength="1000"
                          placeholder="Décrivez votre expérience et vos expertises…"><?= $e($data['expert_bio'] ?? '') ?></textarea>
            </div>
            <?php endif; ?>
        </div>

        <!-- Bloc étudiant / professeur : matières (tous les champs profil) -->
        <div id="ins-etudiant-wrap" style="margin-top:.75rem; display:<?= ($currentRole === 'etudiant' || $currentRole === 'professeur') ? 'flex' : 'none' ?>; flex-direction:column; gap:.75rem">
            <p class="ins-section-label" style="margin-bottom:.5rem"><span class="ins-step">2b</span> Matières</p>
            <?php
            $selectedMatiereIds = array_map('intval', $data['matieres_etudiant'] ?? []);
            ?>
            <?php if (!empty($matieresInscription)): ?>
            <p style="font-size:.8rem;color:var(--text-muted);margin:0 0 .5rem">Sélectionnez au moins une matière.</p>
            <div class="ins-matieres-list">
                <?php foreach ($matieresInscription as $cat => $mats): ?>
                <details class="ins-matieres-details" <?php
                    foreach ($mats as $m) {
                        if (in_array((int)$m['id'], $selectedMatiereIds, true)) { echo 'open'; break; }
                    }
                ?>>
                    <summary><?= $e($cat) ?></summary>
                    <div class="ins-matieres-pills">
                        <?php foreach ($mats as $mat): ?>
                        <label class="ins-matiere-pill">
                            <input type="checkbox" name="matieres_etudiant[]" value="<?= (int)$mat['id'] ?>"
                                   <?= in_array((int)$mat['id'], $selectedMatiereIds, true) ? 'checked' : '' ?>>
                            <span><?= $e($mat['nom']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </details>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="font-size:.85rem;color:var(--text-muted)">Chargement des matières…</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Section 3 : Mot de passe ── -->
    <div class="ins-section">
        <p class="ins-section-label">
            <span class="ins-step">3</span>
            Sécurité
        </p>

        <div class="ins-field">
            <label class="ins-label">Mot de passe <span class="ins-req">*</span></label>
            <div class="ins-input-wrap ins-pwd-wrap">
                <svg class="ins-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" name="password" id="ins-pwd" class="ins-input ins-input--icon" required
                       autocomplete="new-password" placeholder="Minimum <?= PASSWORD_MIN_LENGTH ?> caractères">
                <button type="button" class="ins-pwd-toggle" data-target="ins-pwd" aria-label="Afficher/masquer">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            <!-- Barre de force -->
            <div class="ins-pwd-strength" id="ins-pwd-strength" style="display:none">
                <div class="ins-pwd-bars">
                    <span class="ins-pwd-bar" data-lvl="1"></span>
                    <span class="ins-pwd-bar" data-lvl="2"></span>
                    <span class="ins-pwd-bar" data-lvl="3"></span>
                    <span class="ins-pwd-bar" data-lvl="4"></span>
                </div>
                <span class="ins-pwd-label" id="ins-pwd-label">Faible</span>
            </div>
        </div>

        <div class="ins-field">
            <label class="ins-label">Confirmer le mot de passe <span class="ins-req">*</span></label>
            <div class="ins-input-wrap ins-pwd-wrap">
                <svg class="ins-input-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <input type="password" name="password_confirm" id="ins-pwd2" class="ins-input ins-input--icon" required
                       autocomplete="new-password" placeholder="Répétez le mot de passe">
                <button type="button" class="ins-pwd-toggle" data-target="ins-pwd2" aria-label="Afficher/masquer">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- ── Bouton soumission ── -->
    <button type="submit" class="ins-submit">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
        Créer mon compte
    </button>

    <p style="text-align:center;margin-top:.85rem;font-size:.875rem;color:var(--text-muted)">
        Déjà inscrit ?
        <a href="<?= $baseUrl ?>/auth/connexion" style="color:var(--accent);font-weight:700;text-decoration:none">Se connecter</a>
    </p>
</form>

<style>
/* ═══ Hero ═══ */
.ins-hero {
    text-align: center;
    padding: 1.75rem 0 1.5rem;
    margin-bottom: .25rem;
}
.ins-logo-wrap { display: inline-block; margin-bottom: 1rem; }
.ins-title { font-size: 1.4rem; font-weight: 800; color: var(--primary); margin: 0 0 .35rem; letter-spacing: -.02em; }
.ins-lead  { font-size: .875rem; color: var(--text-muted); margin: 0; }

/* ═══ Alerte erreurs ═══ */
.ins-alert {
    display: flex; align-items: flex-start; gap: .5rem;
    background: #fef2f2; border: 1px solid #fca5a5;
    color: #b91c1c; border-radius: 10px;
    padding: .85rem 1rem; font-size: .85rem; margin-bottom: 1rem;
}

/* ═══ Formulaire ═══ */
.ins-form { display: flex; flex-direction: column; gap: .85rem; }

/* ═══ Section ═══ */
.ins-section {
    background: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 1.1rem 1rem;
}
.ins-section-label {
    display: flex; align-items: center; gap: .6rem;
    font-size: .78rem; font-weight: 700;
    color: var(--text-muted); text-transform: uppercase; letter-spacing: .05em;
    margin: 0 0 1rem;
}
.ins-step {
    width: 20px; height: 20px; border-radius: 50%;
    background: var(--accent); color: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .7rem; font-weight: 800; flex-shrink: 0;
}
.ins-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: .65rem; margin-bottom: .75rem; }

/* ═══ Champs ═══ */
.ins-field { display: flex; flex-direction: column; gap: .35rem; margin-bottom: .65rem; }
.ins-field:last-child { margin-bottom: 0; }
.ins-label { font-size: .875rem; font-weight: 600; color: var(--text); }
.ins-req   { color: #e53e3e; }

.ins-input-wrap { position: relative; }
.ins-input-icon {
    position: absolute; left: .85rem; top: 50%; transform: translateY(-50%);
    color: var(--text-muted); pointer-events: none;
}
.ins-input-wrap .ins-textarea ~ .ins-input-icon,
.ins-input-wrap:has(.ins-textarea) .ins-input-icon { top: .9rem; transform: none; }

.ins-input {
    display: block; width: 100%; box-sizing: border-box;
    padding: .78rem 1rem; font-size: .9375rem; font-family: var(--font);
    border: 1.5px solid var(--border); border-radius: 10px;
    background: var(--surface); color: var(--text);
    transition: border-color .15s, box-shadow .15s;
    min-height: 48px;
}
.ins-input--icon { padding-left: 2.75rem; }
.ins-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(22,163,74,.15); }
.ins-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right .85rem center; padding-right: 2.5rem; }
.ins-textarea { resize: vertical; min-height: 80px; }

/* ═══ Bouton afficher/masquer MDP ═══ */
.ins-pwd-wrap .ins-input { padding-right: 2.75rem; }
.ins-pwd-toggle {
    position: absolute; right: .75rem; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer; color: var(--text-muted);
    padding: .25rem; display: flex; align-items: center;
}

/* ═══ Force mot de passe ═══ */
.ins-pwd-strength { display: flex; align-items: center; gap: .5rem; margin-top: .4rem; }
.ins-pwd-bars { display: flex; gap: 3px; flex: 1; }
.ins-pwd-bar {
    flex: 1; height: 4px; border-radius: 2px;
    background: var(--border); transition: background .2s;
}
.ins-pwd-bar[data-active="1"][data-lvl="1"] { background: #dc2626; }
.ins-pwd-bar[data-active="1"][data-lvl="2"] { background: #f59e0b; }
.ins-pwd-bar[data-active="1"][data-lvl="3"] { background: #22c55e; }
.ins-pwd-bar[data-active="1"][data-lvl="4"] { background: #16a34a; }
.ins-pwd-label { font-size: .72rem; font-weight: 600; color: var(--text-muted); white-space: nowrap; }

/* ═══ Boutons rôle ═══ */
.ins-roles { display: flex; flex-direction: column; gap: .5rem; }
.ins-role {
    display: flex; align-items: center; gap: .75rem;
    padding: .85rem .9rem; border-radius: 10px;
    border: 2px solid var(--border); cursor: pointer;
    background: var(--surface); transition: border-color .15s, background .15s;
}
.ins-role input[type="radio"] { display: none; }
.ins-role--active { border-color: var(--accent); background: var(--accent-soft); }
.ins-role__emoji { font-size: 1.35rem; flex-shrink: 0; }
.ins-role__text  { flex: 1; min-width: 0; }
.ins-role__label { display: block; font-weight: 700; font-size: .9375rem; color: var(--primary); }
.ins-role__desc  { display: block; font-size: .775rem; color: var(--text-muted); }
.ins-role__check {
    width: 20px; height: 20px; border-radius: 50%;
    border: 2px solid var(--border); display: flex; align-items: center; justify-content: center;
    color: #fff; flex-shrink: 0; transition: background .15s, border-color .15s;
}
.ins-role--active .ins-role__check { background: var(--accent); border-color: var(--accent); }

/* ═══ Matières (étudiant / professeur) ═══ */
.ins-matieres-list { display: flex; flex-direction: column; gap: .5rem; }
.ins-matieres-details { border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
.ins-matieres-details summary { padding: .6rem .85rem; font-weight: 600; font-size: .875rem; cursor: pointer; background: var(--surface); }
.ins-matieres-pills { display: flex; flex-wrap: wrap; gap: .4rem; padding: .6rem .85rem; }
.ins-matiere-pill { display: inline-flex; align-items: center; gap: .35rem; padding: .4rem .65rem; border-radius: 8px; border: 1.5px solid var(--border); font-size: .8rem; cursor: pointer; background: var(--surface); }
.ins-matiere-pill:has(input:checked) { border-color: var(--accent); background: var(--accent-soft); }
.ins-matiere-pill input { display: none; }

/* ═══ Bouton soumission ═══ */
.ins-submit {
    display: flex; align-items: center; justify-content: center; gap: .5rem;
    width: 100%; padding: .95rem 1.5rem;
    background: var(--accent); color: #fff;
    border: none; border-radius: 12px;
    font-size: 1rem; font-weight: 700; font-family: var(--font);
    cursor: pointer; min-height: 52px;
    transition: opacity .2s, transform .1s;
    box-shadow: 0 4px 14px rgba(22,163,74,.35);
    margin-top: .25rem;
}
.ins-submit:active { opacity: .92; transform: scale(.99); }
</style>

<script>
(function () {
    /* ── Sélection de rôle ── */
    var roleLabels  = document.querySelectorAll('.ins-role');
    var expertWrap  = document.getElementById('ins-expert-wrap');
    var compSelect  = document.getElementById('ins-comp-select');
    var niveauWrap  = document.getElementById('ins-niveau-wrap');
    var autresWrap  = document.getElementById('ins-autres-wrap');

    var etudiantWrap = document.getElementById('ins-etudiant-wrap');

    roleLabels.forEach(function (lbl) {
        lbl.addEventListener('click', function () {
            roleLabels.forEach(function (r) { r.classList.remove('ins-role--active'); });
            lbl.classList.add('ins-role--active');
            var val = lbl.querySelector('input').value;
            if (expertWrap) expertWrap.style.display = val === 'expert' ? 'flex' : 'none';
            if (etudiantWrap) etudiantWrap.style.display = (val === 'etudiant' || val === 'professeur') ? 'flex' : 'none';
        });
    });

    if (compSelect) {
        compSelect.addEventListener('change', function () {
            var sel = compSelect.options[compSelect.selectedIndex];
            if (niveauWrap) niveauWrap.style.display = compSelect.value ? '' : 'none';
            if (autresWrap) autresWrap.style.display = sel.getAttribute('data-is-autres') === '1' ? '' : 'none';
        });
    }

    /* ── Afficher/masquer mot de passe ── */
    document.querySelectorAll('.ins-pwd-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.getAttribute('data-target'));
            if (!target) return;
            target.type = target.type === 'password' ? 'text' : 'password';
            btn.style.color = target.type === 'text' ? 'var(--accent)' : 'var(--text-muted)';
        });
    });

    /* ── Force du mot de passe ── */
    var pwdInput   = document.getElementById('ins-pwd');
    var pwdStrWrap = document.getElementById('ins-pwd-strength');
    var pwdLabel   = document.getElementById('ins-pwd-label');
    var bars       = document.querySelectorAll('.ins-pwd-bar');
    var levels     = ['', 'Faible', 'Moyen', 'Fort', 'Très fort'];
    var colors     = ['', '#dc2626', '#f59e0b', '#22c55e', '#16a34a'];

    function calcStrength(v) {
        if (!v) return 0;
        var s = 0;
        if (v.length >= 8)  s++;
        if (v.length >= 12) s++;
        if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
        if (/[^A-Za-z0-9]/.test(v) || /[0-9]/.test(v)) s++;
        return Math.min(s, 4);
    }

    if (pwdInput) {
        pwdInput.addEventListener('input', function () {
            var val = pwdInput.value;
            if (!val) { pwdStrWrap.style.display = 'none'; return; }
            pwdStrWrap.style.display = 'flex';
            var lvl = calcStrength(val);
            bars.forEach(function (b) {
                var bLvl = parseInt(b.getAttribute('data-lvl'));
                b.setAttribute('data-active', bLvl <= lvl ? '1' : '0');
                b.style.background = bLvl <= lvl ? colors[lvl] : 'var(--border)';
            });
            pwdLabel.textContent  = levels[lvl];
            pwdLabel.style.color  = colors[lvl];
        });
    }
})();
</script>
