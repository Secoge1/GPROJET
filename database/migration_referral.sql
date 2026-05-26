-- GLOBALO - Système de parrainage
-- Lien d'invitation : /auth/inscription?ref=CODE

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `parrainages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parrain_id` INT UNSIGNED NOT NULL COMMENT 'Utilisateur qui invite',
  `filleul_id` INT UNSIGNED DEFAULT NULL COMMENT 'Invitée (rempli à l\'inscription)',
  `code` VARCHAR(32) NOT NULL,
  `email_invite` VARCHAR(255) DEFAULT NULL,
  `statut` ENUM('envoye', 'inscrit', 'recompense_parrain', 'recompense_filleul') NOT NULL DEFAULT 'envoye',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `inscrit_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `parrain_id` (`parrain_id`),
  KEY `filleul_id` (`filleul_id`),
  KEY `statut` (`statut`),
  CONSTRAINT `fk_parrain_parrain` FOREIGN KEY (`parrain_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_parrain_filleul` FOREIGN KEY (`filleul_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paramètres: récompense parrainage (crédits)
INSERT INTO `parametres` (`cle`, `valeur`) VALUES
('referral_reward_parrain', '500'),
('referral_reward_filleul', '500'),
('referral_reward_after', 'email_verified')
ON DUPLICATE KEY UPDATE `cle` = `cle`;
