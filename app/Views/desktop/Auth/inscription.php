<?php
$csrfField = \App\Core\Security::getCsrfField();
$baseUrl = rtrim(BASE_URL ?? '', '/');
$data = $data ?? [];
$competences = $competences ?? [];
$autreCompetenceId = $autre_competence_id ?? null;
$isExpert = ($data['role'] ?? '') === 'expert';
$planGratuitActif = (bool) ($abonnement_plan_gratuit_actif ?? true);
$prixClientXof = (float) ($abonnement_prix_client_xof ?? 0);
$prixExpertXof = (float) ($abonnement_prix_expert_xof ?? 0);
$prixEtudiantXof = (float) ($abonnement_prix_etudiant_xof ?? 0);
$prixProfesseurXof = (float) ($abonnement_prix_professeur_xof ?? 1000);
$showAbonnementBlock = $planGratuitActif || $prixClientXof > 0 || $prixExpertXof > 0 || $prixEtudiantXof > 0 || $prixProfesseurXof > 0;
$isEtudiant = ($data['role'] ?? '') === 'etudiant';
$isProfesseur = ($data['role'] ?? '') === 'professeur';
$showTimeline = $isEtudiant || $isProfesseur;
$paysEligibles = $pays_eligibles ?? ['Mali', "Côte d'Ivoire", 'Sénégal', 'Bénin', 'Niger'];
$matieresInscription = [];
try {
    $matModel = new \App\Models\MatiereModel();
    $matieresInscription = $matModel->getActivesGrouped();
} catch (\Throwable $t) {}
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
            <span class="auth-badge"><?= __("auth.signup.badge") ?></span>
            <h1><?= __("auth.signup.title") ?></h1>
            <p class="auth-intro-lead"><?= __("auth.signup.lead") ?></p>
        </header>


        <div class="auth-form-wrapper">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" role="alert">
                    <span class="alert-icon" aria-hidden="true">!</span>
                    <ul><?php foreach ($errors as $e): ?><li><?= \App\Core\Security::escape($e) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <?php if ($googleEnabled ?? (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID !== '')): ?>
            <div class="auth-social">
                <a href="<?= $baseUrl ?>/auth/google<?= !empty($ref) ? '?ref=' . urlencode($ref) : '' ?>" class="btn-google-unified" id="btn-google-signup" aria-label="S'inscrire avec Google">
                    <span class="btn-google-icon-wrap" aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        </svg>
                    </span>
                    <span class="btn-google-text">S'inscrire avec Google</span>
                    <span class="btn-google-shine" aria-hidden="true"></span>
                </a>
                <div class="auth-divider-google"><span class="auth-divider-google__line"></span><span class="auth-divider-google__label">ou avec email</span><span class="auth-divider-google__line"></span></div>
            </div>
            <style>
            .btn-google-unified{position:relative;display:flex;align-items:center;justify-content:center;gap:12px;width:100%;padding:14px 20px;background:#fff;color:#3c4043;border:1.5px solid #e0e0e0;border-radius:12px;font-size:.9375rem;font-weight:600;font-family:inherit;text-decoration:none;cursor:pointer;overflow:hidden;transition:box-shadow .28s cubic-bezier(.22,1,.36,1),border-color .2s,transform .22s cubic-bezier(.22,1,.36,1);box-shadow:0 1px 4px rgba(0,0,0,.08),0 1px 2px rgba(0,0,0,.05);letter-spacing:.01em;user-select:none;-webkit-user-select:none}
            .btn-google-unified:hover{box-shadow:0 6px 20px rgba(66,133,244,.18),0 2px 8px rgba(0,0,0,.1);border-color:#c5c5c5;transform:translateY(-2px)}
            .btn-google-unified:active{transform:translateY(0);box-shadow:0 1px 4px rgba(0,0,0,.08)}
            .btn-google-unified:focus-visible{outline:3px solid #4285F4;outline-offset:2px}
            .btn-google-unified.loading{pointer-events:none;opacity:.8}
            .btn-google-icon-wrap{display:flex;align-items:center;justify-content:center;width:22px;height:22px;flex-shrink:0;transition:transform .2s}
            .btn-google-unified:hover .btn-google-icon-wrap{transform:scale(1.08)}
            .btn-google-text{flex:1;text-align:center;transition:letter-spacing .2s}
            .btn-google-shine{position:absolute;top:0;left:-80%;width:50%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.55),transparent);transform:skewX(-15deg);pointer-events:none;transition:left .55s ease}
            .btn-google-unified:hover .btn-google-shine{left:130%}
            .auth-divider-google{display:flex;align-items:center;gap:10px;margin:18px 0 4px;color:#9ca3af;font-size:.8125rem;font-weight:500}
            .auth-divider-google__line{flex:1;height:1px;background:linear-gradient(90deg,transparent,#e5e7eb,transparent)}
            .auth-divider-google__label{white-space:nowrap;padding:0 4px}
            </style>
            <script>
            (function(){
                var btn = document.getElementById('btn-google-signup');
                if (!btn) return;
                btn.addEventListener('click', function() {
                    btn.classList.add('loading');
                    btn.querySelector('.btn-google-text').textContent = 'Redirection…';
                });
            })();
            </script>
            <?php endif; ?>

            <form method="post" action="<?= $baseUrl ?>/auth/inscription" class="auth-form" novalidate>
                <?= $csrfField ?>
                <?php if (!empty($ref)): ?>
                <input type="hidden" name="ref" value="<?= \App\Core\Security::escape($ref) ?>">
                <?php endif; ?>

                <?php if ($showTimeline): ?>
                <nav class="inscription-timeline" aria-label="Étapes d'inscription">
                    <ol class="inscription-timeline__steps">
                        <li class="inscription-timeline__step is-active" data-step="1"><span class="inscription-timeline__num">1</span> Identité</li>
                        <li class="inscription-timeline__step" data-step="2"><span class="inscription-timeline__num">2</span> Rôle & profil</li>
                        <li class="inscription-timeline__step" data-step="3"><span class="inscription-timeline__num">3</span> Abonnement</li>
                        <li class="inscription-timeline__step" data-step="4"><span class="inscription-timeline__num">4</span> Mot de passe</li>
                    </ol>
                </nav>
                <style>
                .inscription-timeline { margin-bottom: 1.5rem; }
                .inscription-timeline__steps { display: flex; justify-content: space-between; list-style: none; margin: 0; padding: 0; gap: 0.5rem; }
                .inscription-timeline__step { flex: 1; text-align: center; padding: 0.5rem 0.25rem; font-size: 0.85rem; color: #6b7280; border-bottom: 3px solid #e5e7eb; position: relative; }
                .inscription-timeline__step.is-active { color: var(--accent, #2563eb); border-bottom-color: var(--accent, #2563eb); font-weight: 600; }
                .inscription-timeline__num { display: inline-block; width: 1.5rem; height: 1.5rem; line-height: 1.5rem; border-radius: 50%; background: #e5e7eb; color: #374151; margin-right: 0.25rem; font-size: 0.8rem; }
                .inscription-timeline__step.is-active .inscription-timeline__num { background: var(--accent, #2563eb); color: #fff; }
                @media (max-width: 640px) { .inscription-timeline__step { font-size: 0.75rem; } .inscription-timeline__step span:not(.inscription-timeline__num) { display: none; } }
                </style>
                <?php endif; ?>

                <section class="auth-form-block" aria-labelledby="block-identite" data-timeline-step="1">
                    <h2 id="block-identite" class="auth-form-block-title">
                        <span class="block-title-num">1</span> <?= __("auth.signup.identity") ?>
                    </h2>
                    <div class="auth-form-row">
                        <div class="form-group">
                            <label for="prenom"><?= __("auth.signup.firstname") ?></label>
                            <input type="text" id="prenom" name="prenom" required autocomplete="given-name" value="<?= \App\Core\Security::escape($data['prenom'] ?? '') ?>" placeholder="Jean">
                        </div>
                        <div class="form-group">
                            <label for="nom"><?= __("auth.signup.lastname") ?></label>
                            <input type="text" id="nom" name="nom" required autocomplete="family-name" value="<?= \App\Core\Security::escape($data['nom'] ?? '') ?>" placeholder="Dupont">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email"><?= __("auth.signup.email") ?></label>
                        <input type="email" id="email" name="email" required autocomplete="email" value="<?= \App\Core\Security::escape($data['email'] ?? '') ?>" placeholder="vous@exemple.fr">
                    </div>
                    <div class="form-group">
                        <label for="pays">Pays <span style="color:#e53e3e">*</span></label>
                        <select id="pays" name="pays" required class="auth-form-select">
                            <option value="">— Sélectionnez votre pays —</option>
                            <?php foreach ($paysEligibles as $p): ?>
                            <option value="<?= \App\Core\Security::escape($p) ?>" <?= ($data['pays'] ?? '') === $p ? 'selected' : '' ?>>
                                <?= \App\Core\Security::escape($p) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </section>

                <section class="auth-form-block" aria-labelledby="block-role" data-timeline-step="2">
                    <h2 id="block-role" class="auth-form-block-title">
                        <span class="block-title-num">2</span> <?= __("auth.signup.iam") ?>
                    </h2>
                    <?php
                    $iconClientRole = icon_illustration('client');
                    $iconExpertRole = icon_illustration('expert');
                    $iconClientRoleImg = \App\Helpers\IconsIllustrations::hasIllustration('client') ? \App\Helpers\IconsIllustrations::illustrationUrl('client', $baseUrl) : null;
                    $iconExpertRoleImg = \App\Helpers\IconsIllustrations::hasIllustration('expert') ? \App\Helpers\IconsIllustrations::illustrationUrl('expert', $baseUrl) : null;
                    ?>
                    <div class="role-select" role="group" aria-label="Choisir un type de compte">
                        <label class="role-option <?= ($data['role'] ?? 'client') === 'client' ? 'is-selected' : '' ?>">
                            <input type="radio" name="role" value="client" <?= ($data['role'] ?? 'client') === 'client' ? 'checked' : '' ?>>
                            <span class="role-option-icon" aria-hidden="true">
                                <?php if ($iconClientRoleImg): ?>
                                    <img src="<?= \App\Core\Security::escape($iconClientRoleImg) ?>" alt="" width="28" height="28">
                                <?php else: ?>
                                    <span class="role-option-emoji"><?= $iconClientRole['emoji_fallback'] ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="role-option-label"><?= __("auth.signup.client") ?></span>
                            <span class="role-option-desc"><?= __("auth.signup.client_desc") ?></span>
                        </label>
                        <label class="role-option <?= ($data['role'] ?? '') === 'expert' ? 'is-selected' : '' ?>">
                            <input type="radio" name="role" value="expert" <?= ($data['role'] ?? '') === 'expert' ? 'checked' : '' ?>>
                            <span class="role-option-icon" aria-hidden="true">
                                <?php if ($iconExpertRoleImg): ?>
                                    <img src="<?= \App\Core\Security::escape($iconExpertRoleImg) ?>" alt="" width="28" height="28">
                                <?php else: ?>
                                    <span class="role-option-emoji"><?= $iconExpertRole['emoji_fallback'] ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="role-option-label"><?= __("auth.signup.expert") ?></span>
                            <span class="role-option-desc"><?= __("auth.signup.expert_desc") ?></span>
                        </label>
                        <label class="role-option <?= ($data['role'] ?? '') === 'etudiant' ? 'is-selected' : '' ?>">
                            <input type="radio" name="role" value="etudiant" <?= ($data['role'] ?? '') === 'etudiant' ? 'checked' : '' ?>>
                            <span class="role-option-icon" aria-hidden="true">
                                <span class="role-option-emoji">🎓</span>
                            </span>
                            <span class="role-option-label"><?= __("auth.signup.etudiant") ?></span>
                            <span class="role-option-desc"><?php
                                echo $prixEtudiantXof > 0
                                    ? 'Abonnement ' . number_format((int) $prixEtudiantXof, 0, ',', ' ') . ' Fcfa/mois — ' . __("auth.signup.etudiant_desc")
                                    : __("auth.signup.etudiant_desc") . ' — ' . __("auth.signup.etudiant_sub_free");
                            ?></span>
                        </label>
                        <label class="role-option <?= ($data['role'] ?? '') === 'professeur' ? 'is-selected' : '' ?>">
                            <input type="radio" name="role" value="professeur" <?= ($data['role'] ?? '') === 'professeur' ? 'checked' : '' ?>>
                            <span class="role-option-icon" aria-hidden="true">
                                <span class="role-option-emoji">👨‍🏫</span>
                            </span>
                            <span class="role-option-label"><?= __("auth.signup.professeur") ?></span>
                            <span class="role-option-desc"><?= __("auth.signup.professeur_desc") ?> <?= number_format((int)$prixProfesseurXof, 0, ',', ' ') ?> Fcfa</span>
                        </label>
                    </div>

                    <!-- Bloc étudiant / professeur : matières universitaires (tous les champs profil) -->
                    <div class="auth-form-block auth-form-block--competences" id="inscription-etudiant-wrap" style="<?= ($data['role'] ?? '') === 'etudiant' || ($data['role'] ?? '') === 'professeur' ? '' : 'display:none' ?>">
                        <h2 id="block-matieres" class="auth-form-block-title">
                            <span class="block-title-num">2b</span> Vos matières universitaires
                        </h2>
                        <p class="auth-form-block-desc">
                            Sélectionnez les matières que vous maîtrisez (vous pourrez en ajouter d'autres plus tard dans votre profil).
                        </p>
                        <?php
                        $selectedMatiereIds = array_map('intval', $data['matieres_etudiant'] ?? []);
                        ?>
                        <?php if (!empty($matieresInscription)): ?>
                        <div class="etd-matieres-inscription">
                            <?php foreach ($matieresInscription as $cat => $mats): ?>
                            <details class="etd-mini-group" <?php
                                foreach ($mats as $m) {
                                    if (in_array((int)$m['id'], $selectedMatiereIds, true)) { echo 'open'; break; }
                                }
                            ?>>
                                <summary><?= \App\Core\Security::escape($cat) ?></summary>
                                <div class="etd-mini-pills">
                                    <?php foreach ($mats as $mat): ?>
                                    <label class="etd-mini-pill">
                                        <input type="checkbox" name="matieres_etudiant[]" value="<?= (int)$mat['id'] ?>"
                                               <?= in_array((int)$mat['id'], $selectedMatiereIds, true) ? 'checked' : '' ?>>
                                        <?= \App\Core\Security::escape($mat['nom']) ?>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="auth-form-muted">Chargement des matières… Si la liste n’apparaît pas, vérifiez que les matières universitaires sont bien configurées.</p>
                        <?php endif; ?>
                    </div>

                    <div class="auth-form-block auth-form-block--competences" id="inscription-competences-wrap" style="<?= $isExpert ? '' : 'display:none' ?>">
                        <h2 id="block-competences" class="auth-form-block-title">
                            <span class="block-title-num">2b</span> Compétences
                        </h2>
                        <p class="auth-form-block-desc">Choisissez votre domaine principal et indiquez votre niveau (vous pourrez compléter plus tard dans votre profil).</p>
                        <?php if (empty($competences)): ?>
                        <p class="auth-form-muted">Aucune compétence disponible pour le moment. Vous pourrez les renseigner après inscription dans votre espace expert.</p>
                        <?php else: ?>
                        <div class="form-group">
                            <label for="competence_id">Compétence</label>
                            <select id="competence_id" name="competence_id" class="auth-form-select">
                                <option value="">— Choisir une compétence —</option>
                                <?php foreach ($competences as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" data-competence-id="<?= (int)$c['id'] ?>" <?= ($autreCompetenceId && (int)$c['id'] === $autreCompetenceId) ? 'data-is-autres="1"' : '' ?>><?= \App\Core\Security::escape($c['nom'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" id="inscription-niveau-wrap" style="display:none">
                            <label for="competence_niveau">Niveau</label>
                            <select id="competence_niveau" name="competence_niveau" class="auth-form-select">
                                <option value="debutant">Débutant</option>
                                <option value="intermediaire" selected>Intermédiaire</option>
                                <option value="avance">Avancé</option>
                                <option value="expert">Expert</option>
                            </select>
                        </div>
                        <?php if ($autreCompetenceId): ?>
                        <div class="form-group form-group-autres-precision" id="inscription-competences-autres-wrap" style="display:none">
                            <label for="competences_autres">Précisez (ex. Power BI, Python…)</label>
                            <input type="text" id="competences_autres" name="competences_autres" maxlength="255" placeholder="Décrivez vos autres compétences">
                        </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <label for="expert_bio">Votre parcours &amp; compétences clés <span class="form-label-hint">(visible publiquement)</span></label>
                            <textarea id="expert_bio" name="expert_bio" rows="4" maxlength="1000" placeholder="Décrivez brièvement votre parcours, vos expertises ou ce qui vous distingue (ex. 5 ans en développement mobile, spécialiste React Native, certifié AWS…)"><?= \App\Core\Security::escape($data['expert_bio'] ?? '') ?></textarea>
                            <span class="form-hint">Ce texte sera affiché sur votre profil public. Maximum 1000 caractères.</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

                <?php if ($showAbonnementBlock): ?>
                <?php
                $prixAbonnements = $prixClientXof > 0 || $prixExpertXof > 0 || $prixEtudiantXof > 0 || $prixProfesseurXof > 0;
                $seulementPayant = !$planGratuitActif && $prixAbonnements;
                ?>
                <section class="auth-form-block auth-form-block--abonnement" aria-labelledby="block-abonnement" data-timeline-step="3">
                    <h2 id="block-abonnement" class="auth-form-block-title">
                        <span class="block-title-num">3</span> Votre abonnement
                    </h2>
                    <p class="auth-form-muted" style="margin: -0.25rem 0 1rem; font-size: 0.9rem;"><?php
                        echo $planGratuitActif
                            ? 'Le montant affiché correspond au rôle choisi ci-dessus (étudiant, client, expert ou professeur). Vous pouvez commencer gratuitement si l’option est proposée.'
                            : 'Le montant mensuel correspond à votre rôle. Après inscription, vous serez guidé vers le paiement sécurisé si un abonnement est requis.';
                    ?></p>
                    <div class="abonnement-pills" role="group" aria-label="Type d'abonnement">
                        <?php if ($planGratuitActif || !$prixAbonnements): ?>
                        <label class="abonnement-pill <?= !$prixAbonnements ? 'is-selected' : '' ?>" id="abonnement-pill-gratuit">
                            <input type="radio" name="abonnement_plan" value="gratuit" <?= !$prixAbonnements ? 'checked' : '' ?>>
                            <span class="abonnement-pill-name">Gratuit</span>
                            <span class="abonnement-pill-price">0 XOF<span class="abonnement-pill-unit">/mois</span></span>
                        </label>
                        <?php endif; ?>
                        <?php if ($prixAbonnements): ?>
                        <label class="abonnement-pill <?= $prixAbonnements ? 'is-selected' : '' ?>" id="abonnement-pill-premium">
                            <input type="radio" name="abonnement_plan" value="premium" <?= $prixAbonnements ? 'checked' : '' ?>>
                            <span class="abonnement-pill-name"><?= $seulementPayant ? 'Abonnement' : 'Premium' ?></span>
                            <span class="abonnement-pill-price abonnement-pill-price--accent">
                                <span id="abonnement-prix-client" style="<?= ($isExpert || $isProfesseur || $isEtudiant) ? 'display:none' : '' ?>"><?= number_format((int)$prixClientXof, 0, ',', ' ') ?> XOF<span class="abonnement-pill-unit">/mois</span></span>
                                <span id="abonnement-prix-expert" style="<?= (!$isExpert || $isProfesseur || $isEtudiant) ? 'display:none' : '' ?>"><?= number_format((int)$prixExpertXof, 0, ',', ' ') ?> XOF<span class="abonnement-pill-unit">/mois</span></span>
                                <span id="abonnement-prix-etudiant" style="<?= !$isEtudiant ? 'display:none' : '' ?>"><?= number_format((int)$prixEtudiantXof, 0, ',', ' ') ?> Fcfa<span class="abonnement-pill-unit">/mois</span></span>
                                <span id="abonnement-prix-professeur" style="<?= !$isProfesseur ? 'display:none' : '' ?>"><?= number_format((int)$prixProfesseurXof, 0, ',', ' ') ?> Fcfa<span class="abonnement-pill-unit">/mois</span></span>
                            </span>
                        </label>
                        <?php endif; ?>
                    </div>
                    <p class="abonnement-note">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                        <?php if ($seulementPayant): ?>
                        Accès complet · Messagerie illimitée · Support prioritaire
                        <?php else: ?>
                        Accès complet · Messagerie illimitée · Sans engagement · Passage au premium possible
                        <?php endif; ?>
                    </p>
                </section>
                <?php endif; ?>

                <section class="auth-form-block" aria-labelledby="block-password" data-timeline-step="4">
                    <h2 id="block-password" class="auth-form-block-title">
                        <span class="block-title-num"><?= $showAbonnementBlock ? '4' : '3' ?></span> <?= __("auth.signup.password_section") ?>
                    </h2>
                    <div class="form-group">
                        <label for="password"><?= __("auth.signup.password") ?></label>
                        <input type="password" id="password" name="password" required minlength="<?= PASSWORD_MIN_LENGTH ?>" autocomplete="new-password" placeholder="Minimum 8 caractères">
                    </div>
                    <div class="form-group">
                        <label for="password_confirm"><?= __("auth.signup.password_confirm") ?></label>
                        <input type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password" placeholder="Saisissez à nouveau le mot de passe">
                    </div>
                </section>

                <div class="auth-form-actions">
                    <button type="submit" class="btn btn-primary btn-lg btn-block btn-inscription">
                        <?= __("auth.signup.submit") ?>
                    </button>
                    <p class="auth-form-link"><?= __("auth.signup.has_account") ?> <a href="<?= $baseUrl ?>/auth/connexion"><?= __("auth.signup.login_link") ?></a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    var options = document.querySelectorAll('.role-option');
    var competencesWrap = document.getElementById('inscription-competences-wrap');
    var etudiantWrap = document.getElementById('inscription-etudiant-wrap');
    var expertRadio = document.querySelector('input[name="role"][value="expert"]');
    var etudiantRadio = document.querySelector('input[name="role"][value="etudiant"]');
    var competenceSelect = document.getElementById('competence_id');
    var niveauWrap = document.getElementById('inscription-niveau-wrap');
    var wrapAutres = document.getElementById('inscription-competences-autres-wrap');

    var professeurRadio = document.querySelector('input[name="role"][value="professeur"]');
    var timelineEl = document.querySelector('.inscription-timeline');

    function updateCompetencesVisibility() {
        if (competencesWrap) {
            competencesWrap.style.display = expertRadio && expertRadio.checked ? '' : 'none';
        }
        var showEtudiant = (etudiantRadio && etudiantRadio.checked) || (professeurRadio && professeurRadio.checked);
        if (etudiantWrap) {
            etudiantWrap.style.display = showEtudiant ? '' : 'none';
        }
        if (timelineEl) {
            timelineEl.style.display = showEtudiant ? '' : 'none';
        }
    }

    function updateNiveauEtAutres() {
        if (!competenceSelect) return;
        var val = competenceSelect.value;
        var selectedOption = competenceSelect.options[competenceSelect.selectedIndex];
        if (niveauWrap) {
            niveauWrap.style.display = val ? '' : 'none';
        }
        if (wrapAutres && selectedOption) {
            var isAutres = selectedOption.getAttribute('data-is-autres') === '1';
            wrapAutres.style.display = isAutres ? '' : 'none';
        }
    }

    options.forEach(function(el) {
        el.addEventListener('click', function() {
            options.forEach(function(o) { o.classList.remove('is-selected'); });
            el.classList.add('is-selected');
        });
    });

    document.querySelectorAll('input[name="role"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            updateCompetencesVisibility();
            updateAbonnementPrixVisibility();
        });
    });
    updateCompetencesVisibility();
    if (timelineEl && !(etudiantRadio && etudiantRadio.checked) && !(professeurRadio && professeurRadio.checked)) {
        timelineEl.style.display = 'none';
    }

    if (competenceSelect) {
        competenceSelect.addEventListener('change', updateNiveauEtAutres);
    }
    updateNiveauEtAutres();

    function updateAbonnementPrixVisibility() {
        var isExpert = expertRadio && expertRadio.checked;
        var isProfesseur = professeurRadio && professeurRadio.checked;
        var isEtudiant = etudiantRadio && etudiantRadio.checked;
        var elClient = document.getElementById('abonnement-prix-client');
        var elExpert = document.getElementById('abonnement-prix-expert');
        var elEtudiant = document.getElementById('abonnement-prix-etudiant');
        var elProfesseur = document.getElementById('abonnement-prix-professeur');
        if (elClient) elClient.style.display = (isExpert || isProfesseur || isEtudiant) ? 'none' : '';
        if (elExpert) elExpert.style.display = isExpert && !isProfesseur && !isEtudiant ? '' : 'none';
        if (elEtudiant) elEtudiant.style.display = isEtudiant ? '' : 'none';
        if (elProfesseur) elProfesseur.style.display = isProfesseur ? '' : 'none';
    }
    updateAbonnementPrixVisibility();

    var abonnementPills = document.querySelectorAll('.abonnement-pill');
    abonnementPills.forEach(function(el) {
        el.addEventListener('click', function() {
            abonnementPills.forEach(function(o) { o.classList.remove('is-selected'); });
            el.classList.add('is-selected');
        });
    });
})();
</script>
