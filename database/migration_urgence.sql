-- GLOBALO - Mode Urgence (Besoin d'aide maintenant)
-- Premier expert qui accepte prend la mission

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `mission_urgence` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `demande_id` INT UNSIGNED NOT NULL,
  `statut` ENUM('en_attente', 'acceptee', 'expiree') NOT NULL DEFAULT 'en_attente',
  `expert_id` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `accepte_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `demande_id` (`demande_id`),
  KEY `statut` (`statut`),
  CONSTRAINT `fk_mission_urgence_demande` FOREIGN KEY (`demande_id`) REFERENCES `demandes_assistance` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mission_urgence_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
