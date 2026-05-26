-- GLOBALO - Avis experts → clients (notation par étoiles + commentaire)
-- À exécuter après schema.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `avis_clients` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` INT UNSIGNED NOT NULL,
  `expert_id` INT UNSIGNED NOT NULL COMMENT 'profil expert (profils_experts.id)',
  `client_id` INT UNSIGNED NOT NULL COMMENT 'utilisateur client',
  `note` TINYINT UNSIGNED NOT NULL CHECK (`note` BETWEEN 1 AND 5),
  `commentaire` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reservation_id` (`reservation_id`),
  KEY `client_id` (`client_id`),
  KEY `expert_id` (`expert_id`),
  CONSTRAINT `fk_avis_client_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_avis_client_client` FOREIGN KEY (`client_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_avis_client_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
