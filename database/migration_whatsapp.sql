-- ================================================================
-- GLOBALO — Migration WhatsApp IA
-- Exécuter via : https://globalo.secogesarl.com/rh/migration-whatsapp
-- ================================================================

-- Table : conversations WhatsApp
CREATE TABLE IF NOT EXISTS `whatsapp_conversations` (
  `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone_number`        VARCHAR(30) NOT NULL COMMENT 'Numéro WhatsApp (format international, sans +)',
  `role`                ENUM('user','assistant') NOT NULL DEFAULT 'user',
  `message`             TEXT NOT NULL,
  `external_message_id` VARCHAR(100) DEFAULT NULL COMMENT 'ID du message côté Meta',
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_phone_number` (`phone_number`),
  KEY `idx_created_at`   (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historique des conversations WhatsApp avec l''IA GAIA';

-- Table : contacts WhatsApp (optionnelle, pour CRM basique)
CREATE TABLE IF NOT EXISTS `whatsapp_contacts` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `phone_number` VARCHAR(30) NOT NULL UNIQUE,
  `nom`          VARCHAR(100) DEFAULT NULL,
  `pays`         VARCHAR(50) DEFAULT NULL,
  `langue`       VARCHAR(10) DEFAULT 'fr',
  `actif`        TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_seen_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_phone` (`phone_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Contacts WhatsApp identifiés';

-- Table : diffusions (broadcast) — pour envoyer des messages en masse
CREATE TABLE IF NOT EXISTS `whatsapp_broadcasts` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `titre`       VARCHAR(200) NOT NULL,
  `message`     TEXT NOT NULL,
  `segment`     VARCHAR(50) DEFAULT 'all' COMMENT 'all | clients | experts | profs',
  `statut`      ENUM('draft','sent','cancelled') NOT NULL DEFAULT 'draft',
  `envoye_a`    INT UNSIGNED DEFAULT 0 COMMENT 'Nombre de destinataires',
  `created_by`  INT UNSIGNED DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at`     DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Campagnes de diffusion WhatsApp';
