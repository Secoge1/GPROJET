<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Agent IA WhatsApp — GLOBALO
 * Répond automatiquement aux clients via WhatsApp + Mistral IA
 */
class WhatsAppAiService
{
    private OpenAiService $ai;
    private Database      $db;

    /** Historique max par conversation (pour ne pas dépasser les tokens) */
    private const MAX_HISTORY = 8;

    /** Prompt système de l'agent client GLOBALO */
    private const SYSTEM_PROMPT = <<<PROMPT
Tu es **GAIA** (GLOBALO AI Assistant), l'assistante officielle de la plateforme GLOBALO pour l'Afrique de l'Ouest.

GLOBALO est une plateforme de mise en relation entre **Clients** et **Experts** (consultants, formateurs, professeurs, spécialistes) opérant au Mali, Sénégal, Côte d'Ivoire, Bénin et Niger.

Tes responsabilités :
- Accueillir les clients et prospects chaleureusement
- Expliquer comment fonctionne GLOBALO (inscription, recherche d'expert, réservation)
- Aider à trouver le bon type d'expert selon le besoin
- Répondre aux questions sur les tarifs, les paiements (Wave, Orange Money, FCFA)
- Guider vers l'inscription sur le site : https://globalo.secogesarl.com
- Gérer les réclamations basiques avec empathie et professionnalisme
- Prendre note des demandes urgentes (escalade humaine si nécessaire)

Règles importantes :
- Toujours répondre en **français** (ou en langue du client s'il écrit en wolof/bambara/dioula)
- Être chaleureuse, professionnelle et concise (messages courts adaptés à WhatsApp)
- Ne jamais inventer des prix ou des disponibilités spécifiques
- Si tu ne peux pas aider, dire clairement et proposer de contacter l'équipe : globalo@secogesarl.com
- Utiliser des emojis avec modération pour rendre les messages vivants
- Garder le contexte de la conversation en mémoire

Services disponibles sur GLOBALO :
- Cours particuliers (maths, français, anglais, informatique, etc.)
- Consultants business et juridiques
- Experts en comptabilité et finance
- Formateurs professionnels
- Tuteurs académiques pour étudiants

Paiements acceptés : Wave, Orange Money, Free Money, virement bancaire.
Site web : https://globalo.secogesarl.com
Email support : globalo@secogesarl.com
PROMPT;

    /** Messages de bienvenue aléatoires */
    private const WELCOME_MESSAGES = [
        "👋 Bonjour ! Je suis *GAIA*, votre assistante GLOBALO.\n\nComment puis-je vous aider aujourd'hui ?\n\n✅ Trouver un expert\n✅ En savoir plus sur GLOBALO\n✅ Aide & Support",
        "🌟 Bienvenue sur GLOBALO !\n\nJe suis *GAIA*, votre assistante virtuelle. Je suis là pour vous aider à trouver l'expert qu'il vous faut.\n\nQue recherchez-vous ?",
        "Bonjour ! 😊 Je suis *GAIA* de GLOBALO, la plateforme qui connecte clients et experts en Afrique de l'Ouest.\n\nComment puis-je vous aider ?",
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();

        $mistralKey = getenv('MISTRAL_API_KEY') ?: (defined('MISTRAL_API_KEY') ? MISTRAL_API_KEY : '');
        $geminiKey  = getenv('GEMINI_API_KEY')  ?: (defined('GEMINI_API_KEY')  ? GEMINI_API_KEY  : '');
        $openaiKey  = getenv('OPENAI_API_KEY')  ?: (defined('OPENAI_API_KEY')  ? OPENAI_API_KEY  : '');

        if ($mistralKey !== '') {
            $this->ai = new OpenAiService($mistralKey, '', 500, 'mistral');
        } elseif ($geminiKey !== '') {
            $this->ai = new OpenAiService($geminiKey, '', 500, 'gemini');
        } elseif ($openaiKey !== '') {
            $this->ai = new OpenAiService($openaiKey, '', 500, 'openai');
        } else {
            $this->ai = new OpenAiService(null, '', 500);
        }
    }

    /**
     * Traite un message entrant et retourne la réponse IA
     */
    public function processMessage(string $phoneNumber, string $messageText, string $whatsappMessageId): string
    {
        // Sauvegarder le message entrant
        $this->saveMessage($phoneNumber, 'user', $messageText, $whatsappMessageId);

        // Message de bienvenue si première interaction
        if ($this->isFirstMessage($phoneNumber)) {
            $welcome = self::WELCOME_MESSAGES[array_rand(self::WELCOME_MESSAGES)];
            $this->saveMessage($phoneNumber, 'assistant', $welcome);
            return $welcome;
        }

        // Récupérer l'historique de conversation
        $history = $this->getHistory($phoneNumber);

        // Si l'IA n'est pas configurée
        if (!$this->ai->isConfigured()) {
            $fallback = "Je suis désolée, notre service IA est momentanément indisponible.\n\nPour toute assistance, contactez-nous directement :\n📧 globalo@secogesarl.com\n🌐 https://globalo.secogesarl.com";
            $this->saveMessage($phoneNumber, 'assistant', $fallback);
            return $fallback;
        }

        // Générer la réponse IA
        $result  = $this->ai->chat($history, self::SYSTEM_PROMPT);
        $content = $result['content'] ?? '';

        // Fallback si réponse vide
        if (empty($content) || strpos($content, '⚠️') === 0) {
            $content = "Je n'ai pas bien compris votre demande. 😊\n\nPouvez-vous reformuler ou me dire :\n• 👩‍🏫 Vous cherchez un expert ?\n• ℹ️ Vous voulez en savoir plus sur GLOBALO ?\n• 🆘 Vous avez besoin d'assistance ?";
        }

        // Sauvegarder la réponse
        $this->saveMessage($phoneNumber, 'assistant', $content);

        return $content;
    }

    /**
     * Retourne un message de bienvenue statique (pour les tests)
     */
    public function getWelcomeMessage(): string
    {
        return self::WELCOME_MESSAGES[0];
    }

    /**
     * Détermine si c'est la première interaction du numéro
     */
    private function isFirstMessage(string $phoneNumber): bool
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM whatsapp_conversations WHERE phone_number = :phone"
            );
            $stmt->execute([':phone' => $phoneNumber]);
            return (int)$stmt->fetchColumn() === 1; // 1 = uniquement le message qu'on vient d'insérer
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Récupère l'historique de conversation (format messages IA)
     */
    private function getHistory(string $phoneNumber): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT role, message FROM whatsapp_conversations
                 WHERE phone_number = :phone
                 ORDER BY created_at ASC
                 LIMIT :limit"
            );
            $stmt->bindValue(':phone', $phoneNumber);
            $stmt->bindValue(':limit', self::MAX_HISTORY * 2, \PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            return array_map(fn($r) => [
                'role'    => $r['role'] === 'assistant' ? 'assistant' : 'user',
                'content' => $r['message'],
            ], $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Sauvegarde un message en base
     */
    private function saveMessage(string $phoneNumber, string $role, string $message, string $externalId = ''): void
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO whatsapp_conversations (phone_number, role, message, external_message_id, created_at)
                 VALUES (:phone, :role, :message, :ext_id, NOW())"
            );
            $stmt->execute([
                ':phone'   => $phoneNumber,
                ':role'    => $role,
                ':message' => $message,
                ':ext_id'  => $externalId,
            ]);
        } catch (\Throwable $e) {
            error_log('[WhatsApp] Impossible de sauvegarder le message : ' . $e->getMessage());
        }
    }

    /**
     * Récupère les statistiques du chatbot pour le dashboard RH
     */
    public function getStats(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT
                    COUNT(DISTINCT phone_number) AS total_conversations,
                    COUNT(*) AS total_messages,
                    COUNT(CASE WHEN role = 'user' THEN 1 END) AS messages_clients,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) AS messages_24h,
                    COUNT(DISTINCT CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN phone_number END) AS actifs_7j
                 FROM whatsapp_conversations"
            );
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
