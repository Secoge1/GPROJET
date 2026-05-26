<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = fn($s) => \App\Core\Security::escape($s ?? '');
$user         = $user ?? null;
$country      = $country ?? ['name' => 'Mali', 'code' => 'ML', 'capital' => 'Bamako', 'flag' => '🇲🇱'];
$country_slug = $country_slug ?? 'mali';
$experts      = $experts ?? [];
$allCountries = \App\Services\SeoService::$COUNTRIES;
?>
<div class="page-pays-geo">

    <!-- Hero géolocalisé -->
    <header class="pays-hero" style="background:linear-gradient(135deg,#f0fdf4 0%,#dcfce7 100%);padding:3rem 0 2.5rem;text-align:center;">
        <div class="container">
            <div style="font-size:3.5rem;margin-bottom:.5rem;"><?= $e($country['flag'] ?? '') ?></div>
            <div style="display:inline-block;background:#16a34a;color:#fff;font-size:.8rem;font-weight:700;padding:.25rem .9rem;border-radius:20px;letter-spacing:.5px;margin-bottom:1rem;">
                GLOBALO <?= $e($country['name']) ?>
            </div>
            <h1 style="font-size:2.2rem;font-weight:800;color:#111827;margin:.5rem 0 1rem;">
                Experts &amp; Professeurs à <?= $e($country['capital']) ?>
            </h1>
            <p style="max-width:600px;margin:0 auto 1.8rem;color:#4b5563;font-size:1.05rem;line-height:1.7;">
                Trouvez un expert ou un professeur qualifié en <strong><?= $e($country['name']) ?></strong> — disponible maintenant pour du chat, de la visio ou un partage d'écran. Paiements sécurisés via Wave, Orange Money &amp; Moov Africa.
            </p>
            <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
                <a href="<?= $baseUrl ?>/experts" class="btn btn-primary btn-lg" style="background:#16a34a;color:#fff;padding:.75rem 2rem;border-radius:8px;font-weight:700;text-decoration:none;">
                    Voir les experts
                </a>
                <a href="<?= $baseUrl ?>/professeurs" class="btn btn-outline btn-lg" style="border:2px solid #16a34a;color:#16a34a;padding:.75rem 2rem;border-radius:8px;font-weight:700;text-decoration:none;">
                    Voir les professeurs
                </a>
            </div>
        </div>
    </header>

    <!-- Stats rapides -->
    <section style="background:#fff;border-bottom:1px solid #e5e7eb;padding:1.5rem 0;">
        <div class="container" style="display:flex;gap:2rem;justify-content:center;flex-wrap:wrap;text-align:center;">
            <div>
                <div style="font-size:1.8rem;font-weight:800;color:#16a34a;"><?= count($experts) ?>+</div>
                <div style="font-size:.85rem;color:#6b7280;">Experts disponibles</div>
            </div>
            <div>
                <div style="font-size:1.8rem;font-weight:800;color:#16a34a;">5 min</div>
                <div style="font-size:.85rem;color:#6b7280;">Temps de réponse moyen</div>
            </div>
            <div>
                <div style="font-size:1.8rem;font-weight:800;color:#16a34a;">7j/7</div>
                <div style="font-size:.85rem;color:#6b7280;">Disponibilité</div>
            </div>
            <div>
                <div style="font-size:1.8rem;font-weight:800;color:#16a34a;">XOF</div>
                <div style="font-size:.85rem;color:#6b7280;">Paiements locaux sécurisés</div>
            </div>
        </div>
    </section>

    <!-- Experts mis en avant -->
    <?php if (!empty($experts)): ?>
    <section style="padding:3rem 0;background:#f9fafb;">
        <div class="container">
            <h2 style="font-size:1.5rem;font-weight:700;color:#111827;margin-bottom:1.5rem;">
                Experts disponibles maintenant
            </h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1.25rem;">
                <?php foreach (array_slice($experts, 0, 6) as $exp): ?>
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:1.25rem;display:flex;gap:1rem;align-items:flex-start;">
                    <div style="width:48px;height:48px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.4rem;font-weight:700;color:#16a34a;">
                        <?= mb_strtoupper(mb_substr($exp['prenom'] ?? 'E', 0, 1)) ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;color:#111827;font-size:.95rem;">
                            <?= $e(($exp['prenom'] ?? '') . ' ' . mb_strtoupper(mb_substr($exp['nom'] ?? '', 0, 1)) . '.') ?>
                        </div>
                        <div style="font-size:.8rem;color:#6b7280;margin:.2rem 0;"><?= $e($exp['titre'] ?? '') ?></div>
                        <?php if (!empty($exp['tarif_horaire'])): ?>
                        <div style="font-size:.8rem;font-weight:600;color:#16a34a;"><?= number_format((float)$exp['tarif_horaire'], 0, ',', ' ') ?> XOF/h</div>
                        <?php endif; ?>
                        <a href="<?= $baseUrl ?>/expert/<?= $e($exp['slug'] ?? 'show/' . (int)$exp['id']) ?>"
                           style="display:inline-block;margin-top:.5rem;font-size:.8rem;background:#16a34a;color:#fff;padding:.3rem .8rem;border-radius:6px;text-decoration:none;font-weight:600;">
                            Contacter
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align:center;margin-top:2rem;">
                <a href="<?= $baseUrl ?>/experts" style="color:#16a34a;font-weight:600;text-decoration:none;">
                    Voir tous les experts →
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Services disponibles -->
    <section style="padding:3rem 0;background:#fff;">
        <div class="container">
            <h2 style="font-size:1.5rem;font-weight:700;color:#111827;margin-bottom:1.5rem;text-align:center;">
                Ce qu'on fait pour vous en <?= $e($country['name']) ?>
            </h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.25rem;">
                <?php foreach ([
                    ['icon' => '💻', 'label' => 'Développement web & mobile', 'desc' => 'Sites, apps, APIs, WordPress…'],
                    ['icon' => '📊', 'label' => 'Comptabilité & gestion',      'desc' => 'Bilans, devis, tableaux de bord…'],
                    ['icon' => '🎓', 'label' => 'Aide scolaire & tutorat',      'desc' => 'Cours, devoirs, corrections…'],
                    ['icon' => '✍️', 'label' => 'Rédaction & traduction',       'desc' => 'CV, lettres, articles, traduction…'],
                    ['icon' => '🎨', 'label' => 'Design & graphisme',           'desc' => 'Logos, flyers, identité visuelle…'],
                    ['icon' => '⚖️', 'label' => 'Juridique & administratif',   'desc' => 'Contrats, dossiers, démarches…'],
                ] as $svc): ?>
                <div style="background:#f9fafb;border-radius:12px;padding:1.25rem;text-align:center;">
                    <div style="font-size:2rem;margin-bottom:.5rem;"><?= $svc['icon'] ?></div>
                    <div style="font-weight:700;color:#111827;font-size:.9rem;margin-bottom:.3rem;"><?= $svc['label'] ?></div>
                    <div style="font-size:.8rem;color:#6b7280;"><?= $svc['desc'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- FAQ SEO -->
    <section style="padding:3rem 0;background:#f0fdf4;">
        <div class="container" style="max-width:760px;">
            <h2 style="font-size:1.5rem;font-weight:700;color:#111827;margin-bottom:1.5rem;text-align:center;">
                Questions fréquentes — <?= $e($country['name']) ?>
            </h2>
            <?php foreach ([
                ['q' => 'Comment trouver un expert en ' . $country['name'] . ' ?',
                 'a' => 'Recherchez sur Globalo par domaine ou compétence, consultez le profil de l\'expert et réservez une session en chat, visio ou partage d\'écran.'],
                ['q' => 'Comment payer un expert sur Globalo depuis ' . $country['name'] . ' ?',
                 'a' => 'Globalo accepte Wave, Orange Money et Moov Africa. Les paiements sont 100 % sécurisés et libellés en XOF.'],
                ['q' => 'Puis-je demander un cours particulier en ' . $country['name'] . ' ?',
                 'a' => 'Oui ! Nos professeurs proposent des séances de cours et de tutorat disponibles à la demande, directement en ligne.'],
            ] as $faq): ?>
            <details style="background:#fff;border-radius:10px;padding:1rem 1.25rem;margin-bottom:.75rem;border:1px solid #d1fae5;cursor:pointer;">
                <summary style="font-weight:700;color:#111827;font-size:.95rem;list-style:none;display:flex;align-items:center;justify-content:space-between;">
                    <?= $e($faq['q']) ?>
                    <span style="color:#16a34a;font-size:1.2rem;flex-shrink:0;margin-left:1rem;">+</span>
                </summary>
                <p style="margin:.75rem 0 0;color:#4b5563;font-size:.9rem;line-height:1.6;"><?= $e($faq['a']) ?></p>
            </details>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Autres pays -->
    <section style="padding:2.5rem 0;background:#fff;border-top:1px solid #e5e7eb;">
        <div class="container">
            <h3 style="font-size:1rem;font-weight:700;color:#6b7280;margin-bottom:1rem;text-align:center;text-transform:uppercase;letter-spacing:.5px;">
                Aussi disponible dans ces pays
            </h3>
            <div style="display:flex;gap:.75rem;flex-wrap:wrap;justify-content:center;">
                <?php foreach ($allCountries as $s => $c): ?>
                    <?php if ($s !== $country_slug): ?>
                    <a href="<?= $baseUrl ?>/experts/<?= $e($s) ?>"
                       style="display:flex;align-items:center;gap:.4rem;background:#f3f4f6;padding:.5rem 1rem;border-radius:20px;text-decoration:none;color:#374151;font-size:.85rem;font-weight:600;transition:background .2s;">
                        <?= $e($c['flag']) ?> <?= $e($c['name']) ?>
                    </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

</div>
