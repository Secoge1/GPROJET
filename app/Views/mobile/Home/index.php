<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$user    = $user ?? null;
$experts = $experts ?? [];
$competences = $competences ?? [];
$esc = fn($s) => \App\Core\Security::escape($s ?? '');
$prixClient = (int) ($prix_client_xof ?? 1000);
$prixExpert = (int) ($prix_expert_xof ?? 1500);
$prixEtudiant = (int) ($prix_etudiant_xof ?? 500);
$prixProfesseur = (int) ($prix_professeur_xof ?? 1000);
$formatFcfa = fn(int $n) => $n > 0 ? number_format($n, 0, ',', ' ') . ' Fcfa/mois' : '';

/* ── Bibliothèque d'icônes — mêmes chemins SVG que la bottom-nav ── */
$ico = [
    'home'      => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
    'experts'   => '<circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
    'demandes'  => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
    'missions'  => '<rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/><line x1="12" y1="12" x2="12" y2="16"/><line x1="10" y1="14" x2="14" y2="14"/>',
    'messages'  => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
    'profil'    => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    'login'     => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>',
    'exercices' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4z"/>',
    'matieres'  => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
    'professeurs' => '<path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/>',
    /* icônes complémentaires — même style stroke */
    'urgence'   => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
    'info'      => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>',
    'revenus'   => '<line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
    'nouveau'   => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
    'fleche'    => '<path d="M5 12h14M12 5l7 7-7 7"/>',
];

/* Helper : génère un SVG complet avec les attributs standard */
$svg = function(string $key, int $size = 20) use ($ico): string {
    $paths = $ico[$key] ?? $ico['home'];
    return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths.'</svg>';
};
?>

<?php if (!$user): ?>
<!-- ════════════════════════════════════════
     LANDING — Visiteur non connecté
     ════════════════════════════════════════ -->
<div class="mob-landing">

    <!-- ── Hero Carousel Stories ── -->
    <style>
    .ghero-wrap{position:relative;margin:-1rem -1rem 0;overflow:hidden}
    .ghero-track{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;-webkit-overflow-scrolling:touch;scrollbar-width:none}
    .ghero-track::-webkit-scrollbar{display:none}
    .ghero-slide{scroll-snap-align:start;flex-shrink:0;width:100vw;height:72vw;max-height:320px;min-height:230px;position:relative;overflow:hidden}
    .ghero-slide__img{width:100%;height:100%;object-fit:cover;object-position:top center;display:block}
    .ghero-slide__overlay{position:absolute;inset:0;background:linear-gradient(170deg,rgba(0,0,0,.08) 0%,rgba(0,0,0,.7) 100%)}
    .ghero-slide__overlay--green{background:linear-gradient(170deg,rgba(0,0,0,.04) 0%,rgba(10,60,20,.78) 100%)}
    .ghero-slide__overlay--violet{background:linear-gradient(170deg,rgba(30,0,80,.15) 0%,rgba(60,10,100,.82) 100%)}
    .ghero-slide__top{position:absolute;top:.85rem;left:1rem;right:1rem;display:flex;align-items:center;justify-content:space-between}
    .ghero-slide__badge{display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .7rem;border-radius:20px;font-size:.67rem;font-weight:800;letter-spacing:.04em;backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.25)}
    .ghero-slide__badge--before{background:rgba(220,38,38,.8);color:#fff}
    .ghero-slide__badge--after{background:rgba(22,163,74,.85);color:#fff}
    .ghero-slide__counter{font-size:.65rem;font-weight:700;color:rgba(255,255,255,.75);background:rgba(0,0,0,.35);padding:.2rem .5rem;border-radius:20px;backdrop-filter:blur(6px)}
    .ghero-slide__body{position:absolute;bottom:0;left:0;right:0;padding:1rem 1.1rem 1.1rem}
    .ghero-slide__tag{display:inline-block;font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:rgba(255,255,255,.7);margin-bottom:.3rem}
    .ghero-slide__title{display:block;font-size:1.12rem;font-weight:900;color:#fff;line-height:1.2;letter-spacing:-.03em;text-shadow:0 2px 8px rgba(0,0,0,.5)}
    .ghero-slide__sub{display:block;font-size:.74rem;color:rgba(255,255,255,.82);font-weight:500;margin-top:.28rem;line-height:1.35}
    .ghero-dots{display:flex;align-items:center;justify-content:center;gap:.35rem;padding:.6rem 0 .15rem;position:absolute;bottom:.6rem;left:0;right:0}
    .ghero-dot{width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,.4);border:none;padding:0;cursor:pointer;transition:all .3s}
    .ghero-dot.active{width:22px;border-radius:4px;background:#fff}
    /* CTA overlay en bas du carousel */
    .ghero-cta{margin:0 -1rem;padding:.9rem 1rem .5rem;background:linear-gradient(180deg,rgba(255,255,255,0) 0%,var(--bg,#f8fafc) 40%)}
    </style>

    <div class="mob-landing__animate" style="--mob-delay:0s">
    <div class="ghero-wrap">
        <div class="ghero-track" id="gheroTrack">

            <!-- Slide 1 — Homme stressé (Avant) -->
            <div class="ghero-slide">
                <img src="<?= $baseUrl ?>/assets/images/hero/homme_stresse.png" alt="" class="ghero-slide__img" loading="eager">
                <div class="ghero-slide__overlay"></div>
                <div class="ghero-slide__top">
                    <span class="ghero-slide__badge ghero-slide__badge--before">
                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Avant GLOBALO
                    </span>
                    <span class="ghero-slide__counter">1 / 4</span>
                </div>
                <div class="ghero-slide__body">
                    <span class="ghero-slide__tag">Pour les clients</span>
                    <span class="ghero-slide__title">Bloqué. Seul face&nbsp;à&nbsp;la&nbsp;complexité.</span>
                    <span class="ghero-slide__sub">Dossiers empilés · Délais qui approchent · Aucune aide en vue</span>
                </div>
                <div class="ghero-dots">
                    <button class="ghero-dot active" onclick="gheroGo(0)"></button>
                    <button class="ghero-dot" onclick="gheroGo(1)"></button>
                    <button class="ghero-dot" onclick="gheroGo(2)"></button>
                    <button class="ghero-dot" onclick="gheroGo(3)"></button>
                </div>
            </div>

            <!-- Slide 2 — Homme heureux (Après) -->
            <div class="ghero-slide">
                <img src="<?= $baseUrl ?>/assets/images/hero/homme_heureux.png" alt="" class="ghero-slide__img" loading="lazy">
                <div class="ghero-slide__overlay ghero-slide__overlay--green"></div>
                <div class="ghero-slide__top">
                    <span class="ghero-slide__badge ghero-slide__badge--after">
                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        Après GLOBALO
                    </span>
                    <span class="ghero-slide__counter">2 / 4</span>
                </div>
                <div class="ghero-slide__body">
                    <span class="ghero-slide__tag">Pour les clients</span>
                    <span class="ghero-slide__title">Résolu en&nbsp;1&nbsp;h. Mission&nbsp;accomplie.</span>
                    <span class="ghero-slide__sub">Expert trouvé · Session en direct · Problème réglé</span>
                </div>
                <div class="ghero-dots">
                    <button class="ghero-dot" onclick="gheroGo(0)"></button>
                    <button class="ghero-dot active" onclick="gheroGo(1)"></button>
                    <button class="ghero-dot" onclick="gheroGo(2)"></button>
                    <button class="ghero-dot" onclick="gheroGo(3)"></button>
                </div>
            </div>

            <!-- Slide 3 — Étudiante stressée (Avant) -->
            <div class="ghero-slide">
                <img src="<?= $baseUrl ?>/assets/images/hero/etudiante_stresse.png" alt="" class="ghero-slide__img" loading="lazy">
                <div class="ghero-slide__overlay ghero-slide__overlay--violet"></div>
                <div class="ghero-slide__top">
                    <span class="ghero-slide__badge ghero-slide__badge--before">
                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Avant GLOBALO
                    </span>
                    <span class="ghero-slide__counter">3 / 4</span>
                </div>
                <div class="ghero-slide__body">
                    <span class="ghero-slide__tag">Pour les étudiants</span>
                    <span class="ghero-slide__title">Cours incompris. Examen&nbsp;demain.</span>
                    <span class="ghero-slide__sub">Seule face aux manuels · Professeur introuvable · Panique totale</span>
                </div>
                <div class="ghero-dots">
                    <button class="ghero-dot" onclick="gheroGo(0)"></button>
                    <button class="ghero-dot" onclick="gheroGo(1)"></button>
                    <button class="ghero-dot active" onclick="gheroGo(2)"></button>
                    <button class="ghero-dot" onclick="gheroGo(3)"></button>
                </div>
            </div>

            <!-- Slide 4 — Étudiante heureuse (Après) -->
            <div class="ghero-slide">
                <img src="<?= $baseUrl ?>/assets/images/hero/etudiante_heureuse.png" alt="" class="ghero-slide__img" loading="lazy">
                <div class="ghero-slide__overlay" style="background:linear-gradient(170deg,rgba(0,0,0,.04) 0%,rgba(10,55,25,.78) 100%)"></div>
                <div class="ghero-slide__top">
                    <span class="ghero-slide__badge ghero-slide__badge--after">
                        <svg width="8" height="8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        Après GLOBALO
                    </span>
                    <span class="ghero-slide__counter">4 / 4</span>
                </div>
                <div class="ghero-slide__body">
                    <span class="ghero-slide__tag">Pour les étudiants</span>
                    <span class="ghero-slide__title">Cours compris. Examen&nbsp;réussi&nbsp;!</span>
                    <span class="ghero-slide__sub">Professeur trouvé · Session vidéo · Confiance retrouvée</span>
                </div>
                <div class="ghero-dots">
                    <button class="ghero-dot" onclick="gheroGo(0)"></button>
                    <button class="ghero-dot" onclick="gheroGo(1)"></button>
                    <button class="ghero-dot" onclick="gheroGo(2)"></button>
                    <button class="ghero-dot active" onclick="gheroGo(3)"></button>
                </div>
            </div>

        </div>
    </div>

    <!-- Trust pills + CTA sous le carousel -->
    <div class="ghero-cta">
        <div style="display:flex;align-items:center;justify-content:center;gap:.65rem;margin-bottom:.85rem;flex-wrap:wrap">
            <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.7rem;font-weight:600;color:#64748b">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Experts vérifiés
            </span>
            <span style="width:3px;height:3px;border-radius:50%;background:#cbd5e1;flex-shrink:0"></span>
            <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.7rem;font-weight:600;color:#64748b">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                Disponibles 24h/7
            </span>
            <span style="width:3px;height:3px;border-radius:50%;background:#cbd5e1;flex-shrink:0"></span>
            <span style="display:inline-flex;align-items:center;gap:.3rem;font-size:.7rem;font-weight:600;color:#64748b">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                Paiement Mobile Money
            </span>
        </div>
        <a href="<?= $baseUrl ?>/auth/inscription" style="display:flex;align-items:center;justify-content:center;gap:.5rem;padding:.88rem 1.5rem;background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;border-radius:14px;font-weight:800;font-size:.95rem;text-decoration:none;box-shadow:0 6px 20px rgba(22,163,74,.35);letter-spacing:-.01em">
            <?= $svg('profil', 17) ?> Créer un compte gratuit
        </a>
        <p style="margin:.6rem 0 0;text-align:center;font-size:.78rem;color:#64748b">
            Déjà un compte ? <a href="<?= $baseUrl ?>/auth/connexion" style="color:#16a34a;font-weight:700;text-decoration:none">Se connecter</a>
        </p>
    </div>
    </div>

    <script>
    (function(){
        var track = document.getElementById('gheroTrack');
        if (!track) return;
        var slides = track.children; // 4 slides
        var total = slides.length;
        var current = 0;
        var autoplay;

        function updateDots(idx) {
            document.querySelectorAll('.ghero-dot').forEach(function(d,i){
                d.classList.toggle('active', Math.floor(i / total) === 0 && i % total === idx);
            });
        }

        window.gheroGo = function(idx) {
            current = idx;
            track.scrollTo({left: idx * track.offsetWidth, behavior:'smooth'});
            clearInterval(autoplay);
            startAuto();
        };

        function startAuto() {
            autoplay = setInterval(function(){
                current = (current + 1) % total;
                track.scrollTo({left: current * track.offsetWidth, behavior:'smooth'});
            }, 4000);
        }

        track.addEventListener('scroll', function(){
            var idx = Math.round(track.scrollLeft / track.offsetWidth);
            if (idx !== current) {
                current = idx;
                document.querySelectorAll('.ghero-dots').forEach(function(dotsEl, si){
                    dotsEl.querySelectorAll('.ghero-dot').forEach(function(d,i){
                        d.classList.toggle('active', i === current);
                    });
                });
            }
        }, {passive:true});

        startAuto();
    })();
    </script>

    <!-- Explorer Experts & Professeurs -->
    <div class="mob-landing__animate" style="--mob-delay: 0.2s;margin-bottom:.25rem">
        <p style="margin:0 0 .65rem;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted,#64748b)">Explorer la plateforme</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem">
            <!-- Experts -->
            <a href="<?= $baseUrl ?>/app/experts"
               style="display:flex;flex-direction:column;align-items:flex-start;gap:.45rem;background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:16px;padding:.9rem .85rem;text-decoration:none;position:relative;overflow:hidden">
                <span style="position:absolute;right:-.5rem;bottom:-.5rem;opacity:.08;font-size:3.5rem;line-height:1">🔍</span>
                <span style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;background:#16a34a">
                    <?= str_replace('stroke="currentColor"','stroke="#fff"',$svg('experts',18)) ?>
                </span>
                <div>
                    <span style="display:block;font-size:.88rem;font-weight:800;color:#15803d;line-height:1.2">Experts</span>
                    <span style="display:block;font-size:.72rem;color:#16a34a;margin-top:.15rem">Disponibles maintenant</span>
                </div>
                <span style="display:inline-flex;align-items:center;gap:.2rem;font-size:.72rem;font-weight:700;color:#16a34a">
                    Voir tout <?= str_replace(['stroke="currentColor"','width="18" height="18"'],['stroke="#16a34a"','width="13" height="13"'],$svg('fleche',13)) ?>
                </span>
            </a>
            <!-- Professeurs -->
            <a href="<?= $baseUrl ?>/app/professeurs"
               style="display:flex;flex-direction:column;align-items:flex-start;gap:.45rem;background:#f5f3ff;border:1.5px solid #ddd6fe;border-radius:16px;padding:.9rem .85rem;text-decoration:none;position:relative;overflow:hidden">
                <span style="position:absolute;right:-.5rem;bottom:-.5rem;opacity:.08;font-size:3.5rem;line-height:1">🎓</span>
                <span style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:10px;background:#7c3aed">
                    <?= str_replace('stroke="currentColor"','stroke="#fff"',$svg('professeurs',18)) ?>
                </span>
                <div>
                    <span style="display:block;font-size:.88rem;font-weight:800;color:#6d28d9;line-height:1.2">Professeurs</span>
                    <span style="display:block;font-size:.72rem;color:#7c3aed;margin-top:.15rem">Université & soutien</span>
                </div>
                <span style="display:inline-flex;align-items:center;gap:.2rem;font-size:.72rem;font-weight:700;color:#7c3aed">
                    Voir tout <?= str_replace(['stroke="currentColor"','width="18" height="18"'],['stroke="#7c3aed"','width="13" height="13"'],$svg('fleche',13)) ?>
                </span>
            </a>
        </div>
        <!-- Ligne secondaire -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.65rem;margin-top:.65rem">
            <a href="<?= $baseUrl ?>/home/apropos"
               style="display:flex;align-items:center;gap:.55rem;background:var(--card-bg,#fff);border:1.5px solid var(--border,#e2e8f0);border-radius:12px;padding:.7rem .8rem;text-decoration:none">
                <span style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:#eff6ff;flex-shrink:0">
                    <?= str_replace('stroke="currentColor"','stroke="#2563eb"',$svg('info',15)) ?>
                </span>
                <span style="font-size:.78rem;font-weight:600;color:var(--primary,#0f172a);line-height:1.2">Comment ça marche&nbsp;?</span>
            </a>
            <a href="<?= $baseUrl ?>/home/contact"
               style="display:flex;align-items:center;gap:.55rem;background:var(--card-bg,#fff);border:1.5px solid var(--border,#e2e8f0);border-radius:12px;padding:.7rem .8rem;text-decoration:none">
                <span style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:#fff7ed;flex-shrink:0">
                    <?= str_replace('stroke="currentColor"','stroke="#d97706"',$svg('messages',15)) ?>
                </span>
                <span style="font-size:.78rem;font-weight:600;color:var(--primary,#0f172a);line-height:1.2">Contacter le support</span>
            </a>
        </div>
    </div>

    <!-- S'inscrire pour : 4 profils -->
    <div class="mob-landing__pour-qui mob-landing__animate" style="--mob-delay: 0.32s">
        <p class="mob-landing__pour-qui-title">S'inscrire pour ?</p>
        <div class="mob-landing__profils">
            <a href="<?= $baseUrl ?>/auth/inscription?role=client" class="mob-landing__profil mob-landing__profil--client">
                <span class="mob-landing__profil-icon">💼</span>
                <span class="mob-landing__profil-label">Client</span>
            </a>
            <a href="<?= $baseUrl ?>/auth/inscription?role=expert" class="mob-landing__profil mob-landing__profil--expert">
                <span class="mob-landing__profil-icon">🎯</span>
                <span class="mob-landing__profil-label">Expert</span>
            </a>
            <a href="<?= $baseUrl ?>/auth/inscription?role=etudiant" class="mob-landing__profil mob-landing__profil--etudiant">
                <span class="mob-landing__profil-icon">🎓</span>
                <span class="mob-landing__profil-label">Étudiant</span>
            </a>
            <a href="<?= $baseUrl ?>/auth/inscription?role=professeur" class="mob-landing__profil mob-landing__profil--professeur">
                <span class="mob-landing__profil-icon">👨‍🏫</span>
                <span class="mob-landing__profil-label">Professeur</span>
            </a>
        </div>
    </div>

    <!-- Features -->
    <div class="mob-landing__features mob-landing__animate" style="--mob-delay: 0.4s">
        <div class="mob-feature-item">
            <span class="mob-feature-item__icon"><?= $svg('messages', 22) ?></span>
            <div><strong>Chat en direct</strong><span>Échangez instantanément</span></div>
        </div>
        <div class="mob-feature-item">
            <span class="mob-feature-item__icon"><?= $svg('profil', 22) ?></span>
            <div><strong>Experts vérifiés</strong><span>Profils validés par notre équipe</span></div>
        </div>
    </div>

    <section class="mob-pay-card mob-landing__animate" style="--mob-delay: 0.42s" aria-labelledby="mob-pay-card-title">
        <div class="mob-pay-card__header">
            <span class="mob-pay-card__icon" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
            </span>
            <div class="mob-pay-card__headtext">
                <h2 id="mob-pay-card-title" class="mob-pay-card__title">Paiement Mobile Money</h2>
                <p class="mob-pay-card__subtitle">Paiement mobile</p>
            </div>
        </div>
        <p class="mob-pay-card__desc">Abonnements et portefeuille : après confirmation sur GLOBALO, Service de paiement vous redirige pour payer en Mobile Money (opérateurs selon Pays) — devise XOF.</p>
        <?php
        $mm_logo_size = 'sm';
        $mm_logo_wrap_class = 'mob-pay-card__operators mm-operator-logos';
        $mm_logo_no_default_flex = true;
        require APP_PATH . '/Views/partials/mm_operator_logos.php';
        ?>
        <a href="<?= $baseUrl ?>/abonnement" class="mob-pay-card__cta">Voir les abonnements</a>
    </section>

    <!-- ── Missions en attente d'un expert ── -->
    <?php if (!empty($demandes_recentes ?? [])): ?>
    <section class="mob-landing__animate" style="--mob-delay:0.46s;padding:0 1rem 0.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.75rem;">
            <div>
                <span style="display:inline-block;background:#dcfce7;color:#166534;font-size:0.65rem;font-weight:700;padding:2px 8px;border-radius:20px;letter-spacing:0.4px;text-transform:uppercase;margin-bottom:0.3rem;">Demandes ouvertes</span>
                <h2 style="font-size:1rem;font-weight:800;color:#0f172a;margin:0;line-height:1.2;">Missions disponibles</h2>
            </div>
            <a href="<?= $baseUrl ?>/demandes" style="font-size:0.75rem;font-weight:600;color:#16a34a;text-decoration:none;white-space:nowrap;">Voir tout →</a>
        </div>
        <div style="display:flex;flex-direction:column;gap:0.6rem;">
        <?php
        $urgenceLbMob     = ['urgent' => 'Urgent', 'tres_urgent' => 'Très urgent'];
        $urgenceColorsMob = ['urgent' => '#f59e0b', 'tres_urgent' => '#ef4444'];
        $mobColorsArr     = ['#2563eb','#16a34a','#7c3aed','#0d9488','#d97706','#dc2626'];
        foreach (array_slice($demandes_recentes, 0, 4) as $drm):
            $mTitre   = \App\Core\Security::escape($drm['titre'] ?? '');
            $mComp    = \App\Core\Security::escape($drm['competence_nom'] ?? '');
            $mUrgence = $drm['urgence'] ?? 'normale';
            $mDate    = isset($drm['created_at']) ? date('d/m/Y', strtotime($drm['created_at'])) : '';
            $mCp      = trim((string) ($drm['client_prenom'] ?? ''));
            $mCn      = trim((string) ($drm['client_nom'] ?? ''));
            $mInit    = strtoupper(mb_substr($mCp, 0, 1) . mb_substr($mCn, 0, 1)) ?: '?';
            $mBg      = $mobColorsArr[abs(crc32($mCp . $mCn)) % count($mobColorsArr)];
            $mAvatarUrl  = \App\Helpers\PublicUserPresentation::publicAvatarUrl($drm['client_avatar'] ?? null, $baseUrl);
            $mHasPhoto   = \App\Helpers\PublicUserPresentation::hasUploadedAvatar($drm['client_avatar'] ?? null);
            $mFlag       = \App\Helpers\PublicUserPresentation::countryFlagEmoji($drm['client_pays'] ?? null);
            $mFlagLabel  = \App\Helpers\PublicUserPresentation::countryLabel($drm['client_pays'] ?? null);
            $mClientLabel = $mCp !== '' ? ($mCn !== '' ? $mCp . ' ' . mb_substr($mCn, 0, 1) . '.' : $mCp) : 'Client';
        ?>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:0.85rem 1rem;display:flex;flex-direction:column;gap:0.5rem;">
            <?php if ($mUrgence !== 'normale'): ?>
            <span style="display:inline-block;background:<?= $urgenceColorsMob[$mUrgence] ?? '#f59e0b' ?>;color:#fff;font-size:0.65rem;font-weight:700;padding:1px 7px;border-radius:20px;align-self:flex-start;text-transform:uppercase;"><?= \App\Core\Security::escape($urgenceLbMob[$mUrgence] ?? $mUrgence) ?></span>
            <?php endif; ?>
            <p style="margin:0;font-weight:700;font-size:0.88rem;color:#1e293b;line-height:1.35;"><?= $mTitre ?></p>

            <!-- Demandeur : avatar + drapeau -->
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <?php if ($mHasPhoto): ?>
                <img src="<?= \App\Core\Security::escape($mAvatarUrl) ?>" alt="<?= \App\Core\Security::escape($mClientLabel) ?>"
                     style="width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1.5px solid #e2e8f0;">
                <?php else: ?>
                <span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;background:<?= $mBg ?>;color:#fff;font-size:0.68rem;font-weight:700;flex-shrink:0;">
                    <?= \App\Core\Security::escape($mInit) ?>
                </span>
                <?php endif; ?>
                <div style="display:flex;flex-direction:column;min-width:0;gap:1px;">
                    <span style="font-size:0.75rem;font-weight:600;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= \App\Core\Security::escape($mClientLabel) ?></span>
                    <?php if ($mFlag !== ''): ?>
                    <span style="font-size:0.72rem;color:#64748b;" title="<?= \App\Core\Security::escape($mFlagLabel) ?>"><?= $mFlag ?> <?= \App\Core\Security::escape($mFlagLabel) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($mComp): ?>
                <span style="margin-left:auto;background:#f0fdf4;color:#15803d;font-size:0.68rem;font-weight:600;padding:2px 8px;border-radius:20px;border:1px solid #bbf7d0;flex-shrink:0;"><?= $mComp ?></span>
                <?php endif; ?>
            </div>

            <a href="<?= $baseUrl ?>/auth/inscription?role=expert"
               style="display:block;background:#f0fdf4;color:#16a34a;font-size:0.78rem;font-weight:600;padding:6px 12px;border-radius:8px;text-decoration:none;text-align:center;border:1.5px solid #bbf7d0;">
                Répondre à cette demande
            </a>
        </div>
        <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Bannière urgence -->
    <a href="<?= $baseUrl ?>/home/contact" class="mob-urgence-banner mob-landing__animate" style="--mob-delay: 0.5s">
        <span class="mob-urgence-banner__icon"><?= $svg('urgence') ?></span>
        <div class="mob-urgence-banner__text">
            <strong>Besoin d'aide urgente ?</strong>
            <span>Notre équipe support est disponible maintenant</span>
        </div>
        <?= $svg('fleche', 16) ?>
    </a>

</div>

<?php else: ?>
<?php
$professeurs = $professeurs ?? [];
$role   = $user['role'] ?? '';
$prenom = $esc($user['prenom'] ?? '');
$heure  = (int)date('H');
$salut  = $heure < 12 ? 'Bonjour' : ($heure < 18 ? 'Bon après-midi' : 'Bonsoir');
$pending_payment_reservations = $pending_payment_reservations ?? [];
?>

<!-- ════════════════════════════════════════
     ACCUEIL — Utilisateur connecté
     ════════════════════════════════════════ -->
<div class="mob-home-wrap">

<!-- Greeting -->
<div class="mob-home-greeting">
    <div>
        <p class="mob-home-greeting__salut"><?= $salut ?> 👋</p>
        <h2 class="mob-home-greeting__name"><?= $prenom ?></h2>
    </div>
    <a href="<?= $baseUrl ?>/app/messages" class="mob-home-greeting__notif" aria-label="Messages">
        <?= $svg('messages', 22) ?>
    </a>
</div>

<?php if ($role === 'client' && !empty($pending_payment_reservations)): ?>
<?php
$nbPay = count($pending_payment_reservations);
$firstPay = $pending_payment_reservations[0];
$payBannerHref = $nbPay === 1
    ? $baseUrl . '/client/payer/' . (int) ($firstPay['id'] ?? 0)
    : $baseUrl . '/app/reservations';
$payBannerCta = $nbPay === 1 ? 'Payer maintenant' : 'Voir mes réservations';
?>
<div class="client-payment-banner client-payment-banner--pulse" role="alert">
    <div class="client-payment-banner__title">Paiement requis</div>
    <p class="client-payment-banner__text">
        <?= $nbPay === 1
            ? 'L’expert a accepté votre mission. Réglez le montant pour que la session démarre.'
            : 'Vous avez <strong>' . (int) $nbPay . ' réservation(s)</strong> en attente de paiement — obligatoire pour lancer la mission.' ?>
    </p>
    <a href="<?= $payBannerHref ?>" class="btn-mobile btn-primary client-payment-banner__btn"><?= $esc($payBannerCta) ?></a>
</div>
<?php endif; ?>

<!-- ── Actions rapides selon rôle ── -->
<?php if ($role === 'client'): ?>
<div class="mob-quick-actions">
    <a href="<?= $baseUrl ?>/client/demandes/nouvelle" class="mob-quick-btn mob-quick-btn--primary">
        <span class="mob-quick-btn__icon"><?= $svg('nouveau') ?></span>
        <span>Nouvelle<br>demande</span>
    </a>
    <a href="<?= $baseUrl ?>/app/experts" class="mob-quick-btn mob-quick-btn--teal">
        <span class="mob-quick-btn__icon"><?= $svg('experts') ?></span>
        <span>Trouver<br>un expert</span>
    </a>
    <a href="<?= $baseUrl ?>/client/urgence" class="mob-quick-btn mob-quick-btn--red">
        <span class="mob-quick-btn__icon"><?= $svg('urgence') ?></span>
        <span>Mission<br>urgente</span>
    </a>
    <a href="<?= $baseUrl ?>/home/contact" class="mob-quick-btn mob-quick-btn--gray">
        <span class="mob-quick-btn__icon"><?= $svg('messages') ?></span>
        <span>Support<br>/ Aide</span>
    </a>
</div>

<?php elseif ($role === 'expert'): ?>
<div class="mob-quick-actions">
    <a href="<?= $baseUrl ?>/app/expert-demandes" class="mob-quick-btn mob-quick-btn--primary">
        <span class="mob-quick-btn__icon"><?= $svg('demandes') ?></span>
        <span>Nouvelles<br>demandes</span>
    </a>
    <a href="<?= $baseUrl ?>/app/missions" class="mob-quick-btn mob-quick-btn--teal">
        <span class="mob-quick-btn__icon"><?= $svg('missions') ?></span>
        <span>Mes<br>missions</span>
    </a>
    <a href="<?= $baseUrl ?>/app/urgences" class="mob-quick-btn mob-quick-btn--red">
        <span class="mob-quick-btn__icon"><?= $svg('urgence') ?></span>
        <span>Urgences<br>dispo</span>
    </a>
    <a href="<?= $baseUrl ?>/home/contact" class="mob-quick-btn mob-quick-btn--gray">
        <span class="mob-quick-btn__icon"><?= $svg('messages') ?></span>
        <span>Support<br>/ Aide</span>
    </a>
</div>

<?php elseif ($role === 'etudiant'): ?>
<div class="mob-quick-actions">
    <a href="<?= $baseUrl ?>/etudiant/exercices" class="mob-quick-btn mob-quick-btn--primary">
        <span class="mob-quick-btn__icon"><?= $svg('exercices') ?></span>
        <span>Mes<br>exercices</span>
    </a>
    <a href="<?= $baseUrl ?>/app/experts" class="mob-quick-btn mob-quick-btn--teal">
        <span class="mob-quick-btn__icon"><?= $svg('experts') ?></span>
        <span>Tuteurs<br>experts</span>
    </a>
    <a href="<?= $baseUrl ?>/app/professeurs" class="mob-quick-btn mob-quick-btn--violet">
        <span class="mob-quick-btn__icon"><?= $svg('professeurs') ?></span>
        <span>Professeurs<br>univ.</span>
    </a>
    <a href="<?= $baseUrl ?>/home/contact" class="mob-quick-btn mob-quick-btn--gray">
        <span class="mob-quick-btn__icon"><?= $svg('messages') ?></span>
        <span>Support<br>/ Aide</span>
    </a>
</div>
<?php endif; ?>

<!-- ── Bannière urgence / support ── -->
<?php if ($role === 'client'): ?>
<a href="<?= $baseUrl ?>/client/urgence" class="mob-urgence-banner mob-urgence-banner--red">
    <span class="mob-urgence-banner__icon"><?= $svg('urgence') ?></span>
    <div class="mob-urgence-banner__text">
        <strong>Mission urgente ?</strong>
        <span>Trouvez un expert disponible maintenant</span>
    </div>
    <?= $svg('fleche', 16) ?>
</a>
<?php elseif ($role === 'expert'): ?>
<a href="<?= $baseUrl ?>/app/urgences" class="mob-urgence-banner mob-urgence-banner--red">
    <span class="mob-urgence-banner__icon"><?= $svg('urgence') ?></span>
    <div class="mob-urgence-banner__text">
        <strong>Urgences disponibles</strong>
        <span>Des clients attendent votre aide maintenant</span>
    </div>
    <?= $svg('fleche', 16) ?>
</a>
<?php else: ?>
<a href="<?= $baseUrl ?>/home/contact" class="mob-urgence-banner">
    <span class="mob-urgence-banner__icon"><?= $svg('messages') ?></span>
    <div class="mob-urgence-banner__text">
        <strong>Besoin d'aide ?</strong>
        <span>Notre support répond rapidement</span>
    </div>
    <?= $svg('fleche', 16) ?>
</a>
<?php endif; ?>

<!-- ── Guides rapides ── -->
<div class="mob-guides-section">
    <p class="mob-guides-section__label">Guides rapides</p>
    <div class="mob-guide-grid">
        <a href="<?= $baseUrl ?>/home/apropos" class="mob-guide-card">
            <span class="mob-guide-card__icon mob-guide-card__icon--blue"><?= $svg('info', 18) ?></span>
            <span class="mob-guide-card__text">Comment<br>ça marche ?</span>
        </a>
        <a href="<?= $baseUrl ?>/home/contact" class="mob-guide-card">
            <span class="mob-guide-card__icon mob-guide-card__icon--violet"><?= $svg('messages', 18) ?></span>
            <span class="mob-guide-card__text">Contacter<br>le support</span>
        </a>
        <?php if ($role === 'client'): ?>
        <a href="<?= $baseUrl ?>/app/demandes" class="mob-guide-card">
            <span class="mob-guide-card__icon mob-guide-card__icon--green"><?= $svg('demandes', 18) ?></span>
            <span class="mob-guide-card__text">Mes<br>demandes</span>
        </a>
        <?php elseif ($role === 'expert'): ?>
        <a href="<?= $baseUrl ?>/app/revenus" class="mob-guide-card">
            <span class="mob-guide-card__icon mob-guide-card__icon--green"><?= $svg('revenus', 18) ?></span>
            <span class="mob-guide-card__text">Mes<br>revenus</span>
        </a>
        <?php else: ?>
        <a href="<?= $baseUrl ?>/etudiant/matieres" class="mob-guide-card">
            <span class="mob-guide-card__icon mob-guide-card__icon--green"><?= $svg('matieres', 18) ?></span>
            <span class="mob-guide-card__text">Mes<br>matières</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── Filtre compétences ── -->
<?php if (!empty($competences)): ?>
<div class="mob-experts__cats mob-home-cats" role="list" aria-label="Filtrer par compétence">
    <a href="<?= $baseUrl ?>/app/experts" class="mobile-cat active" role="listitem">Tous</a>
    <?php foreach ($competences as $c): ?>
    <a href="<?= $baseUrl ?>/app/experts?competence=<?= (int)$c['id'] ?>" class="mobile-cat" role="listitem">
        <?= $esc($c['nom']) ?>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Experts disponibles ── -->
<section class="mobile-section-experts">
    <div class="mobile-section-title">
        <h3>Experts disponibles</h3>
        <a href="<?= $baseUrl ?>/app/experts">Voir tout</a>
    </div>
    <?php if (!empty($experts)): ?>
    <ul style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:.5rem">
        <?php
        $avatarColors = ['#16a34a','#0d9488','#7c3aed','#d97706','#2563eb'];
        foreach ($experts as $exp):
            $initials = strtoupper(trim(mb_substr($exp['prenom'] ?? '', 0, 1) . mb_substr($exp['nom'] ?? '', 0, 1)));
            if ($initials === '') { $initials = strtoupper(mb_substr($exp['titre'] ?? '', 0, 1)) ?: '?'; }
            $colorIdx  = abs(crc32($exp['nom'] ?? '')) % count($avatarColors);
            $fullNameRaw = trim(($exp['prenom'] ?? '') . ' ' . ($exp['nom'] ?? ''));
            $fullName  = $esc($fullNameRaw);
        ?>
        <li>
            <a href="<?= $baseUrl ?>/app/experts/<?= (int)$exp['id'] ?>" class="mob-expert-card"
               aria-label="Voir le profil de <?= $fullName ?>">
                <div class="mob-expert-card__avatar-wrap mob-expert-card__avatar-wrap--stack">
                    <?php
                    $avatarBg     = $avatarColors[$colorIdx];
                    $avatarColumn = $exp['avatar'] ?? null;
                    $pays         = $exp['pays'] ?? null;
                    $alt          = $fullNameRaw !== '' ? 'Photo de ' . $fullNameRaw : '';
                    $size         = 'md';
                    require APP_PATH . '/Views/partials/public_user_thumb.php';
                    ?>
                    <span class="mob-expert-card__dispo"></span>
                </div>
                <div class="mob-expert-card__body">
                    <p class="mob-expert-card__name"><?= $fullName ?></p>
                    <p class="mob-expert-card__titre"><?= $esc($exp['titre'] ?? '') ?></p>
                    <div class="mob-expert-card__foot">
                        <?php if (isset($exp['note_moyenne']) && $exp['note_moyenne'] !== null): ?>
                        <span class="mob-expert-card__note">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="#f59e0b" stroke="none" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                            <?= number_format((float)$exp['note_moyenne'], 1) ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($exp['tarif_horaire'])): ?>
                        <span class="mob-expert-card__tarif"><?= number_format((float)$exp['tarif_horaire'], 0, ',', ' ') ?> <?= $esc(devise()) ?>/h</span>
                        <?php endif; ?>
                    </div>
                </div>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mob-expert-card__arrow" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="mobile-empty-hint">Aucun expert disponible pour le moment.</p>
    <a href="<?= $baseUrl ?>/app/experts" class="btn-mobile btn-mobile-outline">Voir tous les experts</a>
    <?php endif; ?>
</section>

<?php if ($role === 'etudiant'): ?>
<!-- ── Professeurs d'université (étudiants) ── -->
<section class="mobile-section-experts mobile-section-profs mob-professeurs">
    <div class="mobile-section-title">
        <h3>Professeurs d'université</h3>
        <a href="<?= $baseUrl ?>/app/professeurs">Voir tout</a>
    </div>
    <?php if (!empty($professeurs)): ?>
    <ul class="mob-home-profs-list">
        <?php
        $pcolors = ['#7c3aed','#6d28d9','#5b21b6','#4c1d95'];
        foreach ($professeurs as $pr):
            $ini = strtoupper(trim(mb_substr($pr['prenom'] ?? '', 0, 1) . mb_substr($pr['nom'] ?? '', 0, 1)));
            if ($ini === '') { $ini = strtoupper(mb_substr($pr['titre'] ?? '', 0, 1)) ?: '?'; }
            $ci = abs(crc32($pr['nom'] ?? '')) % count($pcolors);
            $fnRaw = trim(($pr['prenom'] ?? '') . ' ' . ($pr['nom'] ?? ''));
            $fn = $esc($fnRaw);
        ?>
        <li>
            <a href="<?= $baseUrl ?>/app/professeurs/<?= (int)$pr['id'] ?>" class="mob-expert-card mob-professeurs__card"
               aria-label="Profil <?= $fn ?>">
                <div class="mob-expert-card__avatar-wrap mob-expert-card__avatar-wrap--stack">
                    <?php
                    $initials     = $ini;
                    $avatarBg     = $pcolors[$ci];
                    $avatarColumn = $pr['avatar'] ?? null;
                    $pays         = $pr['pays'] ?? null;
                    $alt          = $fnRaw !== '' ? 'Photo de ' . $fnRaw : '';
                    $size         = 'md';
                    require APP_PATH . '/Views/partials/public_user_thumb.php';
                    ?>
                    <span class="mob-expert-card__dispo mob-professeurs__dispo" title="Disponible"></span>
                </div>
                <div class="mob-expert-card__body">
                    <p class="mob-expert-card__name"><?= $fn ?></p>
                    <p class="mob-expert-card__titre"><?= $esc($pr['titre'] ?? '') ?></p>
                    <div class="mob-expert-card__foot">
                        <?php if (!empty($pr['tarif_horaire'])): ?>
                        <span class="mob-expert-card__tarif mob-professeurs__tarif"><?= number_format((float)$pr['tarif_horaire'], 0, ',', ' ') ?> <?= $esc(devise()) ?>/h</span>
                        <?php endif; ?>
                    </div>
                </div>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mob-expert-card__arrow"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p class="mobile-empty-hint">Aucun professeur validé pour le moment.</p>
    <a href="<?= $baseUrl ?>/app/professeurs" class="btn-mobile btn-mobile-outline">Parcourir les professeurs</a>
    <?php endif; ?>
</section>
<?php endif; ?>

</div><!-- .mob-home-wrap -->

<?php endif; ?>
