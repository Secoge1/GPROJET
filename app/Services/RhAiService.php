<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service IA pour l'Espace RH GLOBALO
 * Gère 4 agents spécialisés : Inscriptions, Profils, Marketing, Manager
 */
class RhAiService
{
    private OpenAiService $ai;

    // Prompts systèmes des 4 agents IA
    private const SYSTEM_PROMPTS = [
        'inscriptions' => <<<PROMPT
Tu es ARIA (Assistant RH pour les Inscriptions et Admissions), l'IA spécialisée de la plateforme GLOBALO pour l'Afrique de l'Ouest.

Ton rôle : aider l'équipe RH à gérer les inscriptions des **Professeurs** et **Étudiants**, et relancer automatiquement les profils incomplets.

Tes capacités :
- Analyser les profils d'inscription et détecter les informations manquantes
- Suggérer des critères de validation adaptés au contexte africain
- Identifier des tendances dans les inscriptions (pays, matières populaires)
- 🔔 Déclencher des campagnes de relance automatique (email IA) pour les profils incomplets ou en attente, toutes les **72h** (3 jours)
- Générer des rapports synthétiques sur les flux d'inscriptions
- Proposer des messages de bienvenue personnalisés

Codes de relance gérés :
  • aria_unverified_email    → Email non vérifié (profs & étudiants) après 24h
  • aria_prof_no_desc        → Professeur sans description de profil
  • aria_prof_no_rate        → Professeur sans tarif horaire
  • aria_prof_not_available  → Professeur validé mais marqué non disponible
  • aria_prof_pending_long   → Professeur en attente de validation depuis > 5 jours
  • aria_etud_no_university  → Étudiant sans université renseignée
  • aria_etud_no_filiere     → Étudiant sans filière renseignée
  • aria_etud_no_bio         → Étudiant sans présentation personnelle

Quand on te demande de "relancer" un profil, génère un message bienveillant, court et professionnel en précisant l'information manquante.

Contexte plateforme : Mali, Sénégal, Côte d'Ivoire, Bénin, Niger. Paiements en FCFA. Langue principale : français.

Réponds toujours en français, de façon claire, structurée et actionnable. Si on te donne des données, analyse-les et fournis des insights concrets.
PROMPT,

        'profils' => <<<PROMPT
Tu es PROFIA (Profil & Assistance IA), l'agent IA spécialisé de GLOBALO pour la gestion des profils Clients et Experts.

Ton rôle : analyser, optimiser et relancer automatiquement les profils utilisateurs pour maximiser les mises en relation.

Tes capacités :
- Évaluer la complétude et la qualité d'un profil (score 0-100)
- Suggérer des améliorations spécifiques pour chaque profil
- Identifier les experts avec le plus fort potentiel de revenus
- Détecter les clients VIP et les clients à risque de churn
- Générer des suggestions d'appariement client↔expert
- Analyser les écarts de compétences sur la plateforme
- Produire des rapports de profils par pays/compétence
- 🔔 Déclencher des campagnes de relance automatique (email IA) pour les profils incomplets, avec un délai de courtoisie de 3 jours entre chaque relance

Codes de relance gérés :
  • profia_expert_no_title    → Expert sans titre professionnel
  • profia_expert_no_desc     → Expert sans description
  • profia_expert_no_rate     → Expert sans tarif horaire
  • profia_client_no_avatar   → Client sans photo de profil
  • profia_client_no_phone    → Client sans téléphone
  • profia_expert_low_score   → Expert avec score global < 40 %

Quand on te demande de "relancer" ou "envoyer un email" à un profil, fournis un message court, professionnel et bienveillant en précisant clairement l'information manquante et l'action à effectuer.

Réponds en français, de façon précise et avec des recommandations actionnables. Utilise des émojis pour rendre tes réponses plus lisibles.
PROMPT,

        'marketing' => <<<PROMPT
Tu es MARKIA (Marketing IA), l'agent intelligence marketing de GLOBALO.

Ton rôle : analyser les données utilisateurs et générer des stratégies de croissance adaptées au marché africain.

Tes capacités :
- Segmenter les utilisateurs par comportement, géographie et engagement
- Générer des recommandations de campagnes marketing (email, push, SMS)
- Analyser les tendances de croissance par pays et par rôle
- Suggérer des offres promotionnelles et des prix adaptés au marché
- Identifier les ambassadeurs potentiels (parrainage)
- Optimiser les messages pour chaque segment culturel
- Proposer des contenus de blog et réseaux sociaux

Contexte : plateforme B2C / B2B en FCFA, Mobile Money (Wave, Orange Money), marché informel important.

Réponds en français avec des recommandations concrètes, chiffrées quand possible, et adaptées au contexte ouest-africain.
PROMPT,

        'manager' => <<<PROMPT
Tu es MAIA (Manager IA), l'assistant de direction de GLOBALO.

Ton rôle : fournir une vue 360° de la plateforme et assister la prise de décision stratégique.

Tes capacités :
- Analyser les KPIs globaux (utilisateurs, revenus, engagement, satisfaction)
- Identifier les risques opérationnels et les opportunités de croissance
- Générer des rapports exécutifs et des synthèses hebdomadaires
- Comparer les performances entre pays et segments
- Suggérer des priorités stratégiques
- Répondre à des questions analytiques sur n'importe quel aspect de la plateforme
- Prévenir les problèmes avant qu'ils surviennent (analyse prédictive)

Tu as accès à toutes les données RH, marketing, profils et inscriptions.

Réponds avec clarté et structure (titre, analyse, recommandations). Adapte le niveau de détail selon la question.
PROMPT,
    ];

    public function __construct()
    {
        // Auto-détection du provider disponible (Gemini > Mistral > OpenAI)
        $geminiKey  = getenv('GEMINI_API_KEY')  ?: (defined('GEMINI_API_KEY')  ? GEMINI_API_KEY  : '');
        $mistralKey = getenv('MISTRAL_API_KEY') ?: (defined('MISTRAL_API_KEY') ? MISTRAL_API_KEY : '');
        $openaiKey  = getenv('OPENAI_API_KEY')  ?: (defined('OPENAI_API_KEY')  ? OPENAI_API_KEY  : '');

        if ($geminiKey !== '') {
            $this->ai = new OpenAiService($geminiKey, '', 800, 'gemini');
        } elseif ($mistralKey !== '') {
            $this->ai = new OpenAiService($mistralKey, '', 800, 'mistral');
        } elseif ($openaiKey !== '') {
            $this->ai = new OpenAiService($openaiKey, '', 800, 'openai');
        } else {
            $this->ai = new OpenAiService(null, '', 800);
        }
    }

    public function isConfigured(): bool
    {
        return $this->ai->isConfigured();
    }

    /**
     * Envoie un message à l'agent IA spécifié
     *
     * @param string $agentType  Type d'agent : inscriptions|profils|marketing|manager
     * @param array  $messages   Historique [{role, content}, ...]
     * @param array  $context    Données contextuelles à injecter dans le prompt
     * @return array{content: string, agent: string}
     */
    public function chat(string $agentType, array $messages, array $context = []): array
    {
        $systemPrompt = self::SYSTEM_PROMPTS[$agentType] ?? self::SYSTEM_PROMPTS['manager'];

        // Injecter le contexte données si fourni
        if (!empty($context)) {
            $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $systemPrompt .= "\n\n--- DONNÉES ACTUELLES DE LA PLATEFORME ---\n" . $contextJson;
        }

        if (!$this->isConfigured()) {
            return [
                'content' => $this->getFallbackResponse($agentType),
                'agent'   => $agentType,
            ];
        }

        $result = $this->ai->chat($messages, $systemPrompt);
        return [
            'content' => $result['content'] ?: 'Je n\'ai pas pu générer une réponse.',
            'agent'   => $agentType,
        ];
    }

    /**
     * Message de bienvenue statique (pas d'appel API au chargement de page)
     * L'analyse réelle se fait via le chat interactif.
     */
    public function generateWelcomeAnalysis(string $agentType, array $data): string
    {
        // Pas d'appel API au chargement — message statique pour éviter les timeouts
        return $this->getStaticWelcome($agentType, $data);
    }

    /**
     * Message de bienvenue statique avec données réelles
     */
    private function getStaticWelcome(string $agentType, array $data): string
    {
        $ia = $this->isConfigured() ? '✅ IA ' . strtoupper($this->ai->getProvider()) . ' active' : '⚠️ IA non configurée';

        $welcomes = [
            'inscriptions' => "🎓 Bonjour ! Je suis **ARIA**, votre assistante pour les inscriptions.\n\n"
                . $ia . "\n\n"
                . "📊 Données actuelles :\n"
                . "• **" . (int)($data['total_profs'] ?? 0) . "** professeur(s) récent(s)\n"
                . "• **" . (int)($data['total_etuds'] ?? 0) . "** étudiant(s) récent(s)\n"
                . "• **" . (int)($data['stats']['profs_en_attente'] ?? 0) . "** profil(s) en attente de validation\n\n"
                . "🔔 **Relances automatiques actives** — J'envoie des emails IA professionnels aux profs et étudiants avec des profils incomplets toutes les **72h** (3 jours).\n\n"
                . "Posez-moi une question, demandez un rapport ou dites-moi de **relancer un profil spécifique**.",

            'profils' => "👤 Bonjour ! Je suis **PROFIA**, votre analyste de profils.\n\n"
                . $ia . "\n\n"
                . "📊 Scores actuels :\n"
                . "• Experts : score moyen **" . (int)($data['score_moyen_experts'] ?? 0) . "%**\n"
                . "• Clients : score moyen **" . (int)($data['score_moyen_clients'] ?? 0) . "%**\n\n"
                . "🔔 **Relances automatiques actives** — J'envoie des emails IA professionnels aux profils incomplets toutes les **72h** (3 jours).\n\n"
                . "Posez-moi une question, demandez un rapport, ou dites-moi de **relancer un profil spécifique**.",

            'marketing' => "📊 Bonjour ! Je suis **MARKIA**, votre stratège marketing.\n\n"
                . $ia . "\n\n"
                . "Les données de segmentation sont chargées. Je peux générer des recommandations de campagnes, analyser vos segments, ou proposer des contenus adaptés au marché africain.\n\n"
                . "Que souhaitez-vous analyser ?",

            'manager' => "🎯 Bonjour ! Je suis **MAIA**, votre Manager IA.\n\n"
                . $ia . "\n\n"
                . "📈 Vue d'ensemble :\n"
                . "• **" . (int)($data['stats']['total_professeurs'] ?? 0) . "** professeurs · **" . (int)($data['stats']['total_etudiants'] ?? 0) . "** étudiants\n"
                . "• **" . (int)($data['stats']['total_experts'] ?? 0) . "** experts · **" . (int)($data['stats']['total_clients'] ?? 0) . "** clients\n"
                . "• **+" . (int)($data['stats']['inscrits_cette_semaine'] ?? 0) . "** inscriptions cette semaine\n\n"
                . "Posez-moi n'importe quelle question sur la plateforme.",
        ];

        return $welcomes[$agentType] ?? $welcomes['manager'];
    }

    private function getFallbackResponse(string $agentType): string
    {
        $responses = [
            'inscriptions' => "⚠️ L'IA n'est pas configurée. Ajoutez une clé API (OpenAI, Gemini ou Mistral) pour activer ARIA et ses relances automatiques d'inscriptions.",
            'profils'      => "⚠️ L'IA n'est pas configurée. Ajoutez une clé API dans .env pour activer PROFIA et ses relances automatiques.",
            'marketing'    => '⚠️ L\'IA n\'est pas configurée. Ajoutez OPENAI_API_KEY, GEMINI_API_KEY ou MISTRAL_API_KEY dans .env.',
            'manager'      => '⚠️ L\'IA n\'est pas configurée. Configurez une clé API pour activer MAIA, votre manager IA.',
        ];
        return $responses[$agentType] ?? $responses['manager'];
    }

    private function getFallbackWelcome(string $agentType): string
    {
        $welcomes = [
            'inscriptions' => "🎓 Bonjour ! Je suis **ARIA**, votre assistante pour les inscriptions.\n\nJe gère les inscriptions Professeurs & Étudiants et déclenche des relances automatiques pour les profils incomplets. Configurez une clé API pour m'activer pleinement.",
            'profils'      => "👋 Bonjour ! Je suis **PROFIA**, votre analyste profils.\n\nJe peux évaluer la qualité des profils Clients et Experts et déclencher des relances automatiques. Configurez une clé API pour démarrer.",
            'marketing'    => "📊 Bonjour ! Je suis **MARKIA**, votre stratège marketing.\n\nJe génère des recommandations basées sur vos données. Configurez une clé API pour commencer.",
            'manager'      => "🎯 Bonjour ! Je suis **MAIA**, votre Manager IA.\n\nJe suis votre vue 360° de GLOBALO. Configurez une clé API pour des analyses complètes.",
        ];
        return $welcomes[$agentType] ?? $welcomes['manager'];
    }

    /**
     * Nom et avatar de chaque agent IA
     */
    public static function getAgentInfo(string $agentType): array
    {
        $agents = [
            'inscriptions' => [
                'nom'         => 'ARIA',
                'titre'       => 'Assistante Inscriptions',
                'description' => 'Gestion intelligente des inscriptions Professeurs & Étudiants',
                'couleur'     => '#6366f1',
                'gradient'    => 'linear-gradient(135deg, #6366f1, #8b5cf6)',
                'emoji'       => '🎓',
                'icon'        => 'M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z',
            ],
            'profils' => [
                'nom'         => 'PROFIA',
                'titre'       => 'Analyste Profils',
                'description' => 'Optimisation des profils Clients & Experts avec assistance automatique',
                'couleur'     => '#0ea5e9',
                'gradient'    => 'linear-gradient(135deg, #0ea5e9, #06b6d4)',
                'emoji'       => '👤',
                'icon'        => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            ],
            'marketing' => [
                'nom'         => 'MARKIA',
                'titre'       => 'Stratège Marketing',
                'description' => 'Recommandations marketing IA et analyse de segmentation',
                'couleur'     => '#f59e0b',
                'gradient'    => 'linear-gradient(135deg, #f59e0b, #ef4444)',
                'emoji'       => '📊',
                'icon'        => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            ],
            'manager' => [
                'nom'         => 'MAIA',
                'titre'       => 'Manager IA',
                'description' => 'Vue 360° et pilotage stratégique de la plateforme',
                'couleur'     => '#10b981',
                'gradient'    => 'linear-gradient(135deg, #10b981, #059669)',
                'emoji'       => '🎯',
                'icon'        => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            ],
        ];
        return $agents[$agentType] ?? $agents['manager'];
    }
}
