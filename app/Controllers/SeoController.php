<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Models\ProfilExpertModel;
use App\Models\DemandeModel;
use App\Models\CompetenceModel;
use App\Models\BlogPostModel;
use App\Services\SeoService;

class SeoController extends Controller
{
    public function __construct(Router $router)
    {
        parent::__construct($router);
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600');

        $base  = rtrim(BASE_URL ?? '', '/');
        $today = date('Y-m-d');

        $profilModel     = new ProfilExpertModel();
        $competenceModel = new CompetenceModel();
        $blogPostModel   = new BlogPostModel();
        $experts         = $profilModel->getListDisponibles(null, null, 2000);
        $competences     = $competenceModel->getActives();

        // Pages statiques principales (pages auth exclues : faible valeur SEO)
        $urls = [
            ['loc' => $base . '/',            'priority' => '1.0', 'changefreq' => 'daily',   'lastmod' => $today],
            ['loc' => $base . '/experts',     'priority' => '1.0', 'changefreq' => 'daily',   'lastmod' => $today],
            ['loc' => $base . '/professeurs', 'priority' => '0.9', 'changefreq' => 'daily',   'lastmod' => $today],
            ['loc' => $base . '/demandes',    'priority' => '0.9', 'changefreq' => 'daily',   'lastmod' => $today],
            ['loc' => $base . '/blog',        'priority' => '0.8', 'changefreq' => 'weekly',  'lastmod' => $today],
            ['loc' => $base . '/home/apropos','priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $today],
            ['loc' => $base . '/home/contact','priority' => '0.5', 'changefreq' => 'monthly', 'lastmod' => $today],
        ];

        // Pages géolocalisées par pays (SEO local)
        foreach (array_keys(SeoService::$COUNTRIES) as $slug) {
            $urls[] = [
                'loc'        => $base . '/experts/' . $slug,
                'priority'   => '0.8',
                'changefreq' => 'weekly',
                'lastmod'    => $today,
            ];
        }

        // Pages de compétences — URL propre avec le slug de la compétence
        foreach ($competences as $c) {
            $cSlug = $c['slug'] ?? '';
            if ($cSlug === '') {
                continue; // pas de slug = URL propre impossible → on ignore
            }
            $urls[] = [
                'loc'        => $base . '/experts?competence=' . $cSlug,
                'priority'   => '0.7',
                'changefreq' => 'weekly',
                'lastmod'    => $today,
            ];
        }

        // Profils experts publics
        foreach ($experts as $exp) {
            $slug    = $exp['slug'] ?? ('expert-' . (int)$exp['id']);
            $lastmod = !empty($exp['updated_at']) ? substr($exp['updated_at'], 0, 10) : $today;
            $urls[]  = [
                'loc'        => $base . '/expert/' . $slug,
                'priority'   => '0.8',
                'changefreq' => 'weekly',
                'lastmod'    => $lastmod,
            ];
        }

        // Profils professeurs publics
        try {
            $profs = \App\Core\Database::getInstance()
                ->query("SELECT pp.id, u.prenom, u.nom, pp.updated_at
                         FROM profils_professeurs pp
                         JOIN utilisateurs u ON u.id = pp.utilisateur_id
                         WHERE pp.valide_par_admin = 1
                         LIMIT 2000")
                ->fetchAll();
            foreach ($profs as $p) {
                $profSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim(($p['prenom'] ?? '') . '-' . ($p['nom'] ?? '')))) . '-' . (int)$p['id'];
                $urls[] = [
                    'loc'        => $base . '/professeurs/show/' . (int)$p['id'],
                    'priority'   => '0.7',
                    'changefreq' => 'monthly',
                    'lastmod'    => !empty($p['updated_at']) ? substr($p['updated_at'], 0, 10) : $today,
                ];
            }
        } catch (\Throwable $e) {}

        // Demandes publiques (jobs)
        try {
            $jobs = \App\Core\Database::getInstance()
                ->query("SELECT slug, updated_at FROM demandes_assistance WHERE slug IS NOT NULL AND statut = 'ouverte' LIMIT 2000")
                ->fetchAll();
            foreach ($jobs as $j) {
                if (!empty($j['slug'])) {
                    $urls[] = [
                        'loc'        => $base . '/jobs/' . $j['slug'],
                        'priority'   => '0.7',
                        'changefreq' => 'weekly',
                        'lastmod'    => !empty($j['updated_at']) ? substr($j['updated_at'], 0, 10) : $today,
                    ];
                }
            }
        } catch (\Throwable $e) {}

        // Articles de blog
        try {
            $posts = $blogPostModel->getPublishedList(null, 500);
            foreach ($posts as $p) {
                $urls[] = [
                    'loc'        => $base . '/blog/' . $p['slug'],
                    'priority'   => '0.7',
                    'changefreq' => 'monthly',
                    'lastmod'    => !empty($p['updated_at']) ? substr($p['updated_at'], 0, 10) : $today,
                ];
            }
        } catch (\Throwable $e) {}

        // Génération XML
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        foreach ($urls as $u) {
            echo '  <url>' . "\n";
            echo '    <loc>'        . htmlspecialchars($u['loc'],        ENT_XML1, 'UTF-8') . '</loc>'        . "\n";
            echo '    <lastmod>'    . htmlspecialchars($u['lastmod'],    ENT_XML1)           . '</lastmod>'    . "\n";
            echo '    <changefreq>' . $u['changefreq']                                       . '</changefreq>' . "\n";
            echo '    <priority>'   . $u['priority']                                         . '</priority>'   . "\n";
            echo '  </url>' . "\n";
        }
        echo '</urlset>';
        exit;
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: public, max-age=86400');
        $base = rtrim(BASE_URL ?? '', '/');

        $lines = [
            '# GLOBALO — robots.txt',
            '# Généré le ' . date('Y-m-d'),
            '',
            '# ── Règles générales ──────────────────────────────',
            'User-agent: *',
            'Allow: /$',
            'Allow: /experts',
            'Allow: /experts$',
            'Allow: /experts/',
            'Allow: /expert/',
            'Allow: /professeurs',
            'Allow: /professeurs$',
            'Allow: /professeurs/',
            'Allow: /blog',
            'Allow: /blog/',
            'Allow: /jobs',
            'Allow: /jobs/',
            'Allow: /demandes',
            'Allow: /home/apropos',
            'Allow: /home/contact',
            'Allow: /sitemap.xml',
            '',
            '# Pages géolocalisées',
        ];

        foreach (array_keys(SeoService::$COUNTRIES) as $slug) {
            $lines[] = 'Allow: /experts/' . $slug;
        }

        $lines = array_merge($lines, [
            '',
            '# Espaces privés utilisateurs',
            'Disallow: /admin',
            'Disallow: /admin/',
            'Disallow: /client',
            'Disallow: /client/',
            'Disallow: /expert/missions',
            'Disallow: /expert/reservations',
            'Disallow: /expert/revenus',
            'Disallow: /expert/retrait',
            'Disallow: /expert/demandes',
            'Disallow: /expert/urgences',
            'Disallow: /expert/profil',
            'Disallow: /expert/compte',
            'Disallow: /expert/prestations',
            'Disallow: /etudiant',
            'Disallow: /etudiant/',
            '',
            '# Routes techniques / fonctionnelles',
            'Disallow: /messages',
            'Disallow: /session',
            'Disallow: /fichier',
            'Disallow: /api',
            'Disallow: /wave',
            'Disallow: /abonnement',
            'Disallow: /app',
            'Disallow: /auth/deconnexion',
            'Disallow: /auth/google',
            'Disallow: /auth/google-callback',
            'Disallow: /auth/google-complet',
            'Disallow: /auth/lang',
            'Disallow: /auth/view',
            '',
            '# Formulaires auth — faible valeur SEO, indexés inutilement',
            'Disallow: /auth/',
            '',
            '# Paramètres de tracking, duplication et session',
            'Disallow: /*?utm_',
            'Disallow: /*?ref=',
            'Disallow: /*?vue=',
            'Disallow: /*?page=',
            'Disallow: /*?sort=',
            '',
            'Crawl-delay: 1',
            '',
            '# ── Bot Google Images : autoriser les uploads publics ──',
            'User-agent: Googlebot-Image',
            'Allow: /uploads/',
            'Allow: /assets/images/',
            '',
            '# ── Sitemap ────────────────────────────────────────',
            'Sitemap: ' . $base . '/sitemap.xml',
        ]);

        echo implode("\n", $lines) . "\n";
        exit;
    }
}
