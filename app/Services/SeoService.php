<?php
declare(strict_types=1);

namespace App\Services;

/**
 * SEO: meta tags, OpenGraph, Twitter Card, Schema.org JSON-LD, canonical, hreflang.
 * Controllers pass the result to the view as $seo.
 */
class SeoService
{
    private static string $baseUrl   = '';
    private static string $siteName  = 'GLOBALO';
    private static string $defaultImage = '';
    private static string $twitterHandle = '';

    /** Pays éligibles avec métadonnées géo */
    public static array $COUNTRIES = [
        'mali'         => ['name' => 'Mali',         'code' => 'ML', 'capital' => 'Bamako',      'lang' => 'fr', 'flag' => '🇲🇱'],
        'senegal'      => ['name' => 'Sénégal',       'code' => 'SN', 'capital' => 'Dakar',       'lang' => 'fr', 'flag' => '🇸🇳'],
        'cote-divoire' => ['name' => "Côte d'Ivoire", 'code' => 'CI', 'capital' => 'Abidjan',     'lang' => 'fr', 'flag' => '🇨🇮'],
        'benin'        => ['name' => 'Bénin',          'code' => 'BJ', 'capital' => 'Cotonou',      'lang' => 'fr', 'flag' => '🇧🇯'],
        'niger'        => ['name' => 'Niger',          'code' => 'NE', 'capital' => 'Niamey',      'lang' => 'fr', 'flag' => '🇳🇪'],
    ];

    public static function init(): void
    {
        self::$baseUrl  = rtrim(BASE_URL ?? '', '/');
        self::$siteName = defined('SEO_SITE_NAME') ? (string) SEO_SITE_NAME : 'GLOBALO';
        $ogPng = defined('PUBLIC_PATH') ? PUBLIC_PATH . '/assets/images/og-default.png' : '';
        if ($ogPng && is_file($ogPng)) {
            self::$defaultImage = self::$baseUrl . '/assets/images/og-default.png';
        } else {
            self::$defaultImage = self::$baseUrl . '/assets/images/og-default.svg';
        }
        self::$twitterHandle = defined('SEO_TWITTER') ? (string) SEO_TWITTER : '';
    }

    /**
     * Build SEO data for a page type.
     * Returns array: title, description, canonical, robots, hreflang, og_*, twitter_*, structured_data.
     */
    public static function forPage(string $type, array $data = []): array
    {
        self::init();
        $out = [
            'title'          => $data['title']       ?? self::$siteName,
            'description'    => $data['description'] ?? 'Plateforme d\'assistance professionnelle à la demande. Trouvez un expert en chat, visio ou partage d\'écran.',
            'canonical'      => $data['canonical']   ?? self::currentCanonical(),
            'robots'         => $data['robots']      ?? 'index, follow',
            'og_type'        => 'website',
            'og_image'       => $data['image']       ?? self::$defaultImage,
            'og_site_name'   => self::$siteName,
            'twitter_card'   => 'summary_large_image',
            'structured_data' => '',
        ];

        switch ($type) {

            case 'home':
                $out['title']       = self::$siteName . ' — Expert freelance au Mali, Côte d\'Ivoire, Sénégal | Assistance à la demande';
                $out['description'] = 'GLOBALO : trouvez un expert freelance en 5 min à Bamako, Abidjan, Dakar, Cotonou, Niamey. Développeur, comptable, juriste, aide scolaire — chat, visio, partage d\'écran. Paiement Wave & Orange Money.';
                $out['canonical']   = self::$baseUrl . '/';
                $out['hreflang']    = self::hreflangPair(self::$baseUrl . '/', self::$baseUrl . '/?lang=en');
                $out['structured_data'] = self::organizationSchema()
                    . "\n" . self::webSiteSchema()
                    . "\n" . self::faqSchema([
                        ['q' => 'Comment trouver un expert freelance au Mali sur Globalo ?',     'a' => 'Recherchez un expert par compétence (développement, comptabilité, droit, design…), consultez son profil, puis réservez une session de chat, visio ou partage d\'écran. Le paiement se fait via Wave ou Orange Money.'],
                        ['q' => 'Quels modes de paiement sont acceptés sur Globalo ?',            'a' => 'Globalo accepte Wave, Orange Money et Moov Africa. Tous les paiements sont 100 % sécurisés et adaptés à l\'Afrique de l\'Ouest.'],
                        ['q' => 'Globalo est-il disponible dans mon pays ?',                      'a' => 'Globalo est disponible au Mali, en Côte d\'Ivoire, au Sénégal, au Bénin et au Niger. La plateforme est accessible sur mobile et desktop.'],
                        ['q' => 'Comment publier une demande d\'assistance sur Globalo ?',        'a' => 'Créez un compte client, cliquez sur « Publier une demande », décrivez votre besoin et votre budget. Les experts disponibles vous contactent directement.'],
                        ['q' => 'Un expert freelance à Bamako peut-il m\'aider en ligne ?',      'a' => 'Oui. Tous les experts Globalo travaillent à distance via chat, visio ou partage d\'écran. Vous pouvez aussi choisir un expert à Bamako, Abidjan, Dakar ou Cotonou.'],
                    ]);
                break;

            case 'experts_list':
                $out['title']       = 'Experts freelance Mali, Côte d\'Ivoire, Sénégal — ' . self::$siteName;
                $out['description'] = 'Parcourez ' . ($data['count'] ?? 'des centaines d\'') . 'experts vérifiés : développeurs web, designers, comptables, juristes, consultants à Bamako, Abidjan, Dakar, Cotonou. Réservez en un clic, payez par Wave.';
                $out['canonical']   = self::$baseUrl . '/experts';
                $out['hreflang']    = self::hreflangPair(self::$baseUrl . '/experts', self::$baseUrl . '/experts?lang=en');
                $out['structured_data'] = self::breadcrumbSchema([
                    ['name' => 'Accueil', 'url' => self::$baseUrl . '/'],
                    ['name' => 'Experts freelance Afrique de l\'Ouest', 'url' => self::$baseUrl . '/experts'],
                ]);
                break;

            case 'expert_profile':
                $title = ($data['expert_title'] ?? 'Expert') . ' — ' . self::$siteName;
                $canon = $data['canonical'] ?? self::$baseUrl . '/experts/show/' . (int)($data['expert_id'] ?? 0);
                $out['title']       = $title;
                $out['description'] = $data['description'] ?? (($data['expert_title'] ?? '') . '. Réservez une session avec cet expert sur Globalo. Tarif : ' . ($data['tarif_horaire'] ?? '') . ' XOF/h.');
                $out['canonical']   = $canon;
                $out['og_type']     = 'profile';
                $out['og_image']    = $data['image'] ?? self::$defaultImage;
                $out['hreflang']    = self::hreflangPair($canon, $canon . '?lang=en');
                $out['structured_data'] = self::personServiceSchema($data)
                    . "\n" . self::breadcrumbSchema([
                        ['name' => 'Accueil', 'url' => self::$baseUrl . '/'],
                        ['name' => 'Experts', 'url' => self::$baseUrl . '/experts'],
                        ['name' => $data['expert_prenom'] ?? 'Expert', 'url' => $canon],
                    ]);
                break;

            case 'professeurs_list':
                $out['title']       = 'Professeurs & Tuteurs en ligne — Mali, Sénégal, Côte d\'Ivoire | ' . self::$siteName;
                $out['description'] = 'Trouvez un professeur en ligne au Mali, Sénégal, Côte d\'Ivoire, Bénin et Niger : cours particuliers, tutorat universitaire, aide aux devoirs. Réservez une session, payez via Wave ou Orange Money.';
                $out['canonical']   = self::$baseUrl . '/professeurs';
                $out['hreflang']    = self::hreflangPair(self::$baseUrl . '/professeurs', self::$baseUrl . '/professeurs?lang=en');
                $out['structured_data'] = self::breadcrumbSchema([
                    ['name' => 'Accueil',                          'url' => self::$baseUrl . '/'],
                    ['name' => 'Professeurs & tuteurs en ligne', 'url' => self::$baseUrl . '/professeurs'],
                ]);
                break;

            case 'professeur_profile':
                $canonProf = $data['canonical'] ?? self::$baseUrl . '/professeurs/show/' . (int)($data['expert_id'] ?? 0);
                $out['title']       = ($data['expert_title'] ?? 'Professeur') . ' — ' . self::$siteName;
                $out['description'] = $data['description'] ?? (($data['expert_title'] ?? '') . '. Réservez une session avec ce professeur sur Globalo. Tarif : ' . ($data['tarif_horaire'] ?? '') . ' XOF/h.');
                $out['canonical']   = $canonProf;
                $out['og_type']     = 'profile';
                $out['og_image']    = $data['image'] ?? self::$defaultImage;
                $out['hreflang']    = self::hreflangPair($canonProf, $canonProf . '?lang=en');
                $out['structured_data'] = self::personServiceSchema($data)
                    . "\n" . self::breadcrumbSchema([
                        ['name' => 'Accueil',     'url' => self::$baseUrl . '/'],
                        ['name' => 'Professeurs', 'url' => self::$baseUrl . '/professeurs'],
                        ['name' => $data['expert_prenom'] ?? 'Professeur', 'url' => $canonProf],
                    ]);
                break;

            case 'country_page':
                $slug    = $data['country_slug'] ?? 'mali';
                $country = self::$COUNTRIES[$slug] ?? self::$COUNTRIES['mali'];
                $canon   = self::$baseUrl . '/experts/' . $slug;
                $out['title']       = 'Expert freelance ' . $country['name'] . ' à ' . $country['capital'] . ' — Trouvez un expert en ligne | ' . self::$siteName;
                $out['description'] = 'Besoin d\'un expert freelance en ' . $country['name'] . ' ? Trouvez des développeurs, comptables, juristes et consultants à ' . $country['capital'] . ' disponibles maintenant. Chat, visio. Paiement Wave, Orange Money.';
                $out['canonical']   = $canon;
                $out['robots']      = 'index, follow';
                $out['hreflang']    = self::hreflangPair($canon, $canon . '?lang=en');
                $out['structured_data'] = self::localBusinessSchema($country)
                    . "\n" . self::faqSchema([
                        ['q' => 'Comment trouver un expert freelance en ' . $country['name'] . ' ?',
                         'a' => 'Sur GLOBALO, filtrez les experts par pays et compétence. Vous obtenez instantanément la liste des experts disponibles en ' . $country['name'] . '. Réservez directement en ligne, payez via Wave ou Orange Money.'],
                        ['q' => 'Quelles compétences sont disponibles chez les experts de ' . $country['capital'] . ' ?',
                         'a' => 'Développement web, design graphique, comptabilité, fiscalité, droit, marketing digital, rédaction, aide scolaire et bien plus encore.'],
                    ])
                    . "\n" . self::breadcrumbSchema([
                        ['name' => 'Accueil', 'url' => self::$baseUrl . '/'],
                        ['name' => 'Experts', 'url' => self::$baseUrl . '/experts'],
                        ['name' => 'Experts ' . $country['name'], 'url' => $canon],
                    ]);
                break;

            case 'about':
                $out['title']       = 'À propos de GLOBALO — La plateforme freelance #1 en Afrique de l\'Ouest';
                $out['description'] = 'GLOBALO connecte clients et experts freelance en Afrique de l\'Ouest depuis Bamako, Abidjan, Dakar, Cotonou et Niamey. Paiements sécurisés Wave, Orange Money, Moov Africa. Découvrez notre mission.';
                $out['canonical']   = self::$baseUrl . '/home/apropos';
                $out['hreflang']    = self::hreflangPair(self::$baseUrl . '/home/apropos', self::$baseUrl . '/home/apropos?lang=en');
                break;

            case 'contact':
                $out['title']       = 'Contact — ' . self::$siteName;
                $out['description'] = 'Contactez l\'équipe Globalo pour toute question : support, partenariat, signalement. Nous répondons sous 24h.';
                $out['canonical']   = self::$baseUrl . '/home/contact';
                $out['hreflang']    = self::hreflangPair(self::$baseUrl . '/home/contact', self::$baseUrl . '/home/contact?lang=en');
                break;

            case 'demandes_public':
                $out['title']       = 'Missions freelance ouvertes — Mali, Sénégal, Côte d\'Ivoire | ' . self::$siteName;
                $out['description'] = 'Missions freelance disponibles maintenant : développement web, design, comptabilité, aide scolaire au Mali, Sénégal, Côte d\'Ivoire. Créez votre profil d\'expert et postulez gratuitement.';
                $out['canonical']   = self::$baseUrl . '/demandes';
                $out['hreflang']    = self::hreflangPair(self::$baseUrl . '/demandes', self::$baseUrl . '/demandes?lang=en');
                $out['structured_data'] = self::breadcrumbSchema([
                    ['name' => 'Accueil',                     'url' => self::$baseUrl . '/'],
                    ['name' => 'Missions freelance ouvertes', 'url' => self::$baseUrl . '/demandes'],
                ]);
                break;

            case 'blog_post':
                $out['title']       = ($data['title'] ?? 'Article') . ' — ' . self::$siteName;
                $out['description'] = $data['meta_description'] ?? $data['description'] ?? '';
                $out['og_type']     = 'article';
                $out['structured_data'] = self::articleSchema($data);
                break;

            case 'job':
                $out['title']       = ($data['title'] ?? 'Mission') . ' — Trouver un expert — ' . self::$siteName;
                $out['description'] = $data['description'] ?? 'Mission d\'assistance professionnelle. Postulez ou trouvez un expert qualifié sur Globalo.';
                $out['canonical']   = $data['canonical'] ?? self::$baseUrl . '/jobs/' . ($data['slug'] ?? '');
                $out['og_type']     = 'website';
                $out['structured_data'] = self::jobPostingSchema($data);
                break;

            default:
                if (!empty($data['title']))       { $out['title']       = $data['title']; }
                if (!empty($data['description'])) { $out['description'] = $data['description']; }
                if (!empty($data['canonical']))   { $out['canonical']   = $data['canonical']; }
                break;
        }

        // Copie consolidée vers og_* / twitter_*
        $out['og_title']            = $out['og_title']            ?? $out['title'];
        $out['og_description']      = $out['og_description']      ?? $out['description'];
        $out['og_url']              = $out['og_url']              ?? $out['canonical'];
        $out['twitter_title']       = $out['twitter_title']       ?? $out['title'];
        $out['twitter_description'] = $out['twitter_description'] ?? $out['description'];
        $out['twitter_image']       = $out['twitter_image']       ?? $out['og_image'];
        if (self::$twitterHandle) {
            $out['twitter_site'] = self::$twitterHandle;
        }

        return $out;
    }

    public static function currentCanonical(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        return self::$baseUrl . $path;
    }

    /** Génère un tableau hreflang [{lang, url}] avec x-default. */
    public static function hreflangPair(string $urlFr, string $urlEn): array
    {
        self::init();
        return [
            ['lang' => 'fr',        'url' => $urlFr],
            ['lang' => 'en',        'url' => $urlEn],
            ['lang' => 'x-default', 'url' => self::$baseUrl . '/'],
        ];
    }

    // ─── Schemas JSON-LD ────────────────────────────────────────────────────

    private static function organizationSchema(): string
    {
        $sameAs = defined('SEO_SOCIAL_LINKS') && is_array(SEO_SOCIAL_LINKS)
            ? SEO_SOCIAL_LINKS
            : [];

        $json = [
            '@context'    => 'https://schema.org',
            '@type'       => ['Organization', 'LocalBusiness'],
            'name'        => self::$siteName,
            'url'         => self::$baseUrl,
            'logo'        => [
                '@type' => 'ImageObject',
                'url'   => self::$baseUrl . '/assets/images/logo.png',
            ],
            'image'       => self::$defaultImage,
            'sameAs'      => $sameAs,
            'description' => 'GLOBALO — plateforme freelance #1 en Afrique de l\'Ouest. Trouvez un expert en développement, design, comptabilité, droit, aide scolaire à Bamako, Abidjan, Dakar, Cotonou, Niamey. Paiement Wave, Orange Money, Moov Africa.',
            'address'     => [
                '@type'           => 'PostalAddress',
                'addressCountry'  => 'ML',
                'addressLocality' => 'Bamako',
                'addressRegion'   => 'Bamako',
            ],
            'areaServed'  => [
                ['@type' => 'Country', 'name' => 'Mali',          'identifier' => 'ML'],
                ['@type' => 'Country', 'name' => 'Sénégal',        'identifier' => 'SN'],
                ['@type' => 'Country', 'name' => "Côte d'Ivoire",  'identifier' => 'CI'],
                ['@type' => 'Country', 'name' => 'Bénin',           'identifier' => 'BJ'],
                ['@type' => 'Country', 'name' => 'Niger',           'identifier' => 'NE'],
            ],
            'paymentAccepted' => 'Wave, Orange Money, Moov Africa',
            'priceRange'      => 'XOF',
            'openingHours'    => 'Mo-Su 00:00-23:59',
            'contactPoint'    => [
                '@type'             => 'ContactPoint',
                'contactType'       => 'customer support',
                'availableLanguage' => ['French'],
                'url'               => self::$baseUrl . '/home/contact',
            ],
            'foundingDate'     => '2024',
            'knowsLanguage'    => ['fr'],
        ];
        return self::jsonLd($json);
    }

    private static function webSiteSchema(): string
    {
        $json = [
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            'name'            => self::$siteName,
            'url'             => self::$baseUrl . '/',
            'description'     => 'Assistance professionnelle & académique à la demande.',
            'inLanguage'      => 'fr',
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => self::$baseUrl . '/experts?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
        return self::jsonLd($json);
    }

    public static function breadcrumbSchema(array $items): string
    {
        $list = [];
        foreach ($items as $i => $item) {
            $list[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $item['name'],
                'item'     => $item['url'],
            ];
        }
        return self::jsonLd(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list]);
    }

    /** FAQ schema pour boîtes de réponse enrichies dans les SERPs. */
    public static function faqSchema(array $faqs): string
    {
        $items = [];
        foreach ($faqs as $faq) {
            $items[] = [
                '@type'          => 'Question',
                'name'           => $faq['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
            ];
        }
        return self::jsonLd(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items]);
    }

    /** LocalBusiness schema pour les pages géolocalisées par pays. */
    public static function localBusinessSchema(array $country): string
    {
        $json = [
            '@context'    => 'https://schema.org',
            '@type'       => 'LocalBusiness',
            'name'        => self::$siteName . ' ' . $country['name'],
            'url'         => self::$baseUrl . '/experts/' . array_search($country, self::$COUNTRIES),
            'image'       => self::$defaultImage,
            'description' => 'Assistance professionnelle & académique à la demande en ' . $country['name'] . '. Experts vérifiés disponibles pour du chat, visio et partage d\'écran.',
            'address'     => [
                '@type'          => 'PostalAddress',
                'addressCountry' => $country['code'],
                'addressLocality'=> $country['capital'],
            ],
            'areaServed'  => ['@type' => 'Country', 'name' => $country['name'], 'identifier' => $country['code']],
            'paymentAccepted' => 'Wave, Orange Money, Moov Africa',
            'priceRange'  => 'XOF',
        ];
        return self::jsonLd($json);
    }

    private static function personServiceSchema(array $data): string
    {
        self::init();
        $name   = trim(($data['expert_prenom'] ?? '') . ' ' . ($data['expert_nom'] ?? ''));
        $canon  = $data['canonical'] ?? self::$baseUrl . '/experts/show/' . (int)($data['expert_id'] ?? 0);
        $person = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Person',
            'name'        => $name,
            'jobTitle'    => $data['expert_title'] ?? 'Expert',
            'description' => $data['description'] ?? '',
            'url'         => $canon,
        ];
        if (!empty($data['image']) && $data['image'] !== self::$defaultImage) {
            $person['image'] = $data['image'];
        }
        $service = [
            '@context' => 'https://schema.org',
            '@type'    => 'Service',
            'name'     => $data['expert_title'] ?? 'Session avec expert',
            'provider' => ['@type' => 'Person', 'name' => $name],
            'offers'   => [
                '@type'         => 'Offer',
                'price'         => (float)($data['tarif_horaire'] ?? 0),
                'priceCurrency' => 'XOF',
                'availability'  => 'https://schema.org/InStock',
            ],
        ];
        return self::jsonLd($person) . "\n" . self::jsonLd($service);
    }

    private static function jobPostingSchema(array $data): string
    {
        self::init();
        $countryCode = 'ML'; // défaut Mali ; peut être passé via $data['country_code']
        if (!empty($data['country_code'])) {
            $countryCode = strtoupper(substr($data['country_code'], 0, 2));
        }
        $json = [
            '@context'          => 'https://schema.org',
            '@type'             => 'JobPosting',
            'title'             => $data['title'] ?? 'Mission',
            'description'       => $data['description'] ?? '',
            'datePosted'        => $data['created_at'] ?? date('c'),
            'validThrough'      => $data['expires_at']  ?? date('c', strtotime('+30 days')),
            'hiringOrganization'=> ['@type' => 'Organization', 'name' => self::$siteName, 'sameAs' => self::$baseUrl],
            'jobLocation'       => [
                '@type'   => 'Place',
                'address' => ['@type' => 'PostalAddress', 'addressCountry' => $countryCode],
            ],
            'employmentType'    => 'CONTRACTOR',
        ];
        if (!empty($data['canonical'])) {
            $json['url'] = $data['canonical'];
        }
        return self::jsonLd($json);
    }

    private static function articleSchema(array $data): string
    {
        $json = [
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => $data['title'] ?? '',
            'description'   => $data['meta_description'] ?? $data['description'] ?? '',
            'datePublished' => $data['published_at'] ?? date('c'),
            'dateModified'  => $data['updated_at']   ?? ($data['published_at'] ?? date('c')),
            'inLanguage'    => 'fr',
            'author'        => ['@type' => 'Organization', 'name' => self::$siteName],
            'publisher'     => [
                '@type' => 'Organization',
                'name'  => self::$siteName,
                'logo'  => ['@type' => 'ImageObject', 'url' => self::$baseUrl . '/assets/images/logo.png'],
            ],
        ];
        if (!empty($data['image'])) {
            $json['image'] = $data['image'];
        }
        return self::jsonLd($json);
    }

    /** Wrapper générique pour un bloc JSON-LD. */
    private static function jsonLd(array $json): string
    {
        return '<script type="application/ld+json">'
            . json_encode($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . '</script>';
    }
}
