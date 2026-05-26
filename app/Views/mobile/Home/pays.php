<?php
$baseUrl      = rtrim(BASE_URL ?? '', '/');
$e            = fn($s) => \App\Core\Security::escape($s ?? '');
$user         = $user ?? null;
$country      = $country ?? ['name' => 'Mali', 'code' => 'ML', 'capital' => 'Bamako', 'flag' => '🇲🇱'];
$country_slug = $country_slug ?? 'mali';
$experts      = $experts ?? [];
$allCountries = \App\Services\SeoService::$COUNTRIES;
?>
<div style="padding-bottom:80px;background:#f9fafb;min-height:100vh;">

    <!-- Hero -->
    <div style="background:linear-gradient(135deg,#16a34a 0%,#166534 100%);padding:2.5rem 1.25rem 2rem;text-align:center;color:#fff;">
        <div style="font-size:2.8rem;margin-bottom:.4rem;"><?= $e($country['flag'] ?? '') ?></div>
        <div style="font-size:.72rem;font-weight:700;letter-spacing:1px;background:rgba(255,255,255,.2);display:inline-block;padding:.2rem .8rem;border-radius:20px;margin-bottom:.75rem;">
            GLOBALO <?= $e(strtoupper($country['name'])) ?>
        </div>
        <h1 style="font-size:1.5rem;font-weight:800;margin:.25rem 0 .75rem;line-height:1.3;">
            Experts &amp; Professeurs à <?= $e($country['capital']) ?>
        </h1>
        <p style="font-size:.875rem;opacity:.9;line-height:1.6;margin:0 0 1.5rem;">
            Trouvez un professionnel qualifié en <strong><?= $e($country['name']) ?></strong> — chat, visio ou partage d'écran. Paiement Wave, Orange &amp; Moov.
        </p>
        <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;">
            <a href="<?= $baseUrl ?>/experts"
               style="background:#fff;color:#16a34a;font-weight:700;font-size:.875rem;padding:.6rem 1.4rem;border-radius:8px;text-decoration:none;">
                Voir les experts
            </a>
            <a href="<?= $baseUrl ?>/professeurs"
               style="border:2px solid rgba(255,255,255,.7);color:#fff;font-weight:700;font-size:.875rem;padding:.6rem 1.4rem;border-radius:8px;text-decoration:none;">
                Professeurs
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1px;background:#e5e7eb;margin:0;border-top:1px solid #e5e7eb;">
        <?php foreach ([
            ['val' => count($experts) . '+', 'lbl' => 'Experts'],
            ['val' => '5 min',               'lbl' => 'Réponse moyenne'],
            ['val' => '7j/7',                'lbl' => 'Disponibilité'],
            ['val' => 'XOF',                 'lbl' => 'Paiements locaux'],
        ] as $stat): ?>
        <div style="background:#fff;padding:1rem;text-align:center;">
            <div style="font-size:1.3rem;font-weight:800;color:#16a34a;"><?= $e($stat['val']) ?></div>
            <div style="font-size:.72rem;color:#6b7280;"><?= $e($stat['lbl']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Experts disponibles -->
    <?php if (!empty($experts)): ?>
    <div style="padding:1.25rem;">
        <h2 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 1rem;">Experts disponibles</h2>
        <div style="display:flex;flex-direction:column;gap:.75rem;">
            <?php foreach (array_slice($experts, 0, 4) as $exp): ?>
            <div style="background:#fff;border-radius:12px;padding:1rem;display:flex;align-items:center;gap:.875rem;box-shadow:0 1px 4px rgba(0,0,0,.06);">
                <div style="width:44px;height:44px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:800;color:#16a34a;flex-shrink:0;">
                    <?= mb_strtoupper(mb_substr($exp['prenom'] ?? 'E', 0, 1)) ?>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:.875rem;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        <?= $e(($exp['prenom'] ?? '') . ' ' . mb_strtoupper(mb_substr($exp['nom'] ?? '', 0, 1)) . '.') ?>
                    </div>
                    <div style="font-size:.75rem;color:#6b7280;"><?= $e(mb_strtrimwidth($exp['titre'] ?? '', 0, 40, '…')) ?></div>
                    <?php if (!empty($exp['tarif_horaire'])): ?>
                    <div style="font-size:.75rem;font-weight:600;color:#16a34a;"><?= number_format((float)$exp['tarif_horaire'], 0, ',', ' ') ?> XOF/h</div>
                    <?php endif; ?>
                </div>
                <a href="<?= $baseUrl ?>/expert/<?= $e($exp['slug'] ?? 'show/' . (int)$exp['id']) ?>"
                   style="background:#16a34a;color:#fff;font-size:.75rem;font-weight:700;padding:.4rem .85rem;border-radius:7px;text-decoration:none;flex-shrink:0;">
                    Voir
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:1rem;">
            <a href="<?= $baseUrl ?>/experts" style="color:#16a34a;font-weight:600;font-size:.875rem;text-decoration:none;">
                Tous les experts →
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Services -->
    <div style="padding:0 1.25rem 1.25rem;">
        <h2 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 .875rem;">Services disponibles</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
            <?php foreach ([
                ['icon' => '💻', 'label' => 'Dev web & mobile'],
                ['icon' => '📊', 'label' => 'Compta & gestion'],
                ['icon' => '🎓', 'label' => 'Aide scolaire'],
                ['icon' => '✍️', 'label' => 'Rédaction'],
                ['icon' => '🎨', 'label' => 'Design'],
                ['icon' => '⚖️', 'label' => 'Juridique'],
            ] as $svc): ?>
            <div style="background:#fff;border-radius:10px;padding:.875rem;text-align:center;box-shadow:0 1px 3px rgba(0,0,0,.05);">
                <div style="font-size:1.6rem;margin-bottom:.25rem;"><?= $svc['icon'] ?></div>
                <div style="font-size:.75rem;font-weight:600;color:#374151;"><?= $e($svc['label']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- FAQ -->
    <div style="padding:0 1.25rem 1.25rem;">
        <h2 style="font-size:1rem;font-weight:700;color:#111827;margin:0 0 .875rem;">Questions fréquentes</h2>
        <?php foreach ([
            ['q' => 'Comment trouver un expert en ' . $country['name'] . ' ?',
             'a' => 'Recherchez sur Globalo par domaine, consultez le profil et réservez une session en chat ou visio.'],
            ['q' => 'Comment payer depuis ' . $country['name'] . ' ?',
             'a' => 'Wave, Orange Money et Moov Africa — paiements 100 % sécurisés en XOF.'],
            ['q' => 'Puis-je avoir des cours en ligne en ' . $country['name'] . ' ?',
             'a' => 'Oui ! Nos professeurs proposent du tutorat à la demande, directement en ligne.'],
        ] as $faq): ?>
        <details style="background:#fff;border-radius:10px;padding:.875rem 1rem;margin-bottom:.625rem;border:1px solid #e5e7eb;">
            <summary style="font-weight:700;font-size:.875rem;color:#111827;list-style:none;display:flex;align-items:center;justify-content:space-between;">
                <?= $e($faq['q']) ?>
                <span style="color:#16a34a;font-size:1.1rem;flex-shrink:0;margin-left:.5rem;">+</span>
            </summary>
            <p style="margin:.625rem 0 0;font-size:.8rem;color:#4b5563;line-height:1.6;"><?= $e($faq['a']) ?></p>
        </details>
        <?php endforeach; ?>
    </div>

    <!-- CTA inscription -->
    <div style="margin:0 1.25rem 1.5rem;background:linear-gradient(135deg,#16a34a,#166534);border-radius:14px;padding:1.5rem;text-align:center;color:#fff;">
        <div style="font-size:1.5rem;margin-bottom:.5rem;">🚀</div>
        <div style="font-weight:800;font-size:1rem;margin-bottom:.375rem;">Rejoignez Globalo <?= $e($country['name']) ?></div>
        <p style="font-size:.8rem;opacity:.9;margin:0 0 1rem;">Inscription gratuite — commencez en 2 minutes.</p>
        <a href="<?= $baseUrl ?>/auth/inscription"
           style="background:#fff;color:#16a34a;font-weight:700;font-size:.875rem;padding:.65rem 1.75rem;border-radius:8px;text-decoration:none;display:inline-block;">
            S'inscrire gratuitement
        </a>
    </div>

    <!-- Autres pays -->
    <div style="padding:0 1.25rem 1.5rem;">
        <h3 style="font-size:.8rem;font-weight:700;color:#6b7280;text-transform:uppercase;letter-spacing:.5px;margin:0 0 .75rem;">
            Aussi disponible dans ces pays
        </h3>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <?php foreach ($allCountries as $s => $c): ?>
                <?php if ($s !== $country_slug): ?>
                <a href="<?= $baseUrl ?>/experts/<?= $e($s) ?>"
                   style="display:flex;align-items:center;gap:.3rem;background:#fff;padding:.4rem .875rem;border-radius:20px;text-decoration:none;color:#374151;font-size:.8rem;font-weight:600;border:1px solid #e5e7eb;">
                    <?= $e($c['flag']) ?> <?= $e($c['name']) ?>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

</div>
