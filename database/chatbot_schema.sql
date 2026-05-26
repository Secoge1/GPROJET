-- GLOBALO - Chatbot IA - Tables et paramètres
-- À exécuter après schema.sql principal

SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Table: chatbot_conversations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatbot_conversations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED DEFAULT NULL,
  `session_id` VARCHAR(64) DEFAULT NULL COMMENT 'Pour utilisateurs non connectés',
  `conversation_uid` VARCHAR(36) NOT NULL COMMENT 'UUID côté client',
  `context` JSON DEFAULT NULL COMMENT 'Intent en cours, paramètres create_task, etc.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversation_uid` (`conversation_uid`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `fk_chatbot_conv_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: chatbot_messages
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatbot_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` INT UNSIGNED NOT NULL,
  `role` ENUM('user', 'assistant', 'system') NOT NULL DEFAULT 'user',
  `content` TEXT NOT NULL,
  `intent` VARCHAR(50) DEFAULT NULL,
  `payload` JSON DEFAULT NULL COMMENT 'Experts list, quick_actions, etc.',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_chatbot_msg_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `chatbot_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: chatbot_config (admin - personnalité, réponses, aide)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `chatbot_config` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cle` VARCHAR(80) NOT NULL,
  `valeur` TEXT,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cle` (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données initiales chatbot_config
INSERT INTO `chatbot_config` (`cle`, `valeur`) VALUES
('system_prompt', 'Tu es l''assistant virtuel de GLOBALO, une plateforme qui met en relation des clients avec des experts (développement, design, conseil, etc.). Tu réponds en français de manière courtoise et professionnelle. Tu peux : aider à trouver un expert, expliquer comment réserver, créer une demande d''assistance, expliquer les paiements et les retraits.'),
('default_find_expert', 'Je peux vous aider à trouver un expert. Voici des profils disponibles.'),
('default_create_task', 'Je peux créer une demande d''assistance pour vous. Indiquez la durée estimée (en heures) et votre budget si vous en avez un.'),
('help_payment', 'Sur GLOBALO, vous payez à la réservation. Le montant est débité de votre portefeuille. La plateforme prélève une commission (voir Paramètres).'),
('help_withdrawal', 'Les experts peuvent demander un retrait depuis leur tableau de bord (Revenus > Demander un retrait). Le virement est traité sous quelques jours ouvrés.'),
('help_booking', 'Pour réserver : 1) Trouvez un expert, 2) Choisissez un créneau, 3) Validez la réservation. Le paiement est débité à la réservation.'),
('help_commission', 'La plateforme prélève une commission sur chaque session (configurable par l''administrateur, par défaut 15%).')
ON DUPLICATE KEY UPDATE `valeur` = VALUES(`valeur`);

-- Paramètres optionnels (dans parametres existant)
INSERT INTO `parametres` (`cle`, `valeur`) VALUES
('chatbot_openai_api_key', ''),
('chatbot_enabled', '1'),
('chatbot_max_history_messages', '20')
ON DUPLICATE KEY UPDATE `valeur` = VALUES(`valeur`);
