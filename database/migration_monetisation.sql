-- GLOBALO - Migration Monétisation (escrow, commissions, revenus)
-- À exécuter après schema.sql

SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Solde plateforme (trésorerie: commissions + montants en escrow)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `solde_plateforme` (
  `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `solde` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `devise` VARCHAR(3) NOT NULL DEFAULT 'XOF',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `solde_plateforme` (`id`, `solde`, `devise`) VALUES (1, 0.00, 'XOF')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- --------------------------------------------------------
-- Config commissions (défaut, premium, par pays)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `commission_config` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` ENUM('defaut', 'premium', 'pays') NOT NULL DEFAULT 'defaut',
  `valeur_pourcent` DECIMAL(5,2) NOT NULL,
  `pays_code` VARCHAR(3) DEFAULT NULL COMMENT 'ISO 3166-1 alpha-2 (ex: SN, FR)',
  `expert_profil_id` INT UNSIGNED DEFAULT NULL COMMENT 'Pour type premium sur un expert',
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_premium_expert` (`type`, `expert_profil_id`),
  UNIQUE KEY `uq_pays` (`type`, `pays_code`),
  KEY `type` (`type`),
  CONSTRAINT `fk_commission_expert` FOREIGN KEY (`expert_profil_id`) REFERENCES `profils_experts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Commission par défaut (priorité basse: utilisé si pas de règle premium/pays)
INSERT IGNORE INTO `commission_config` (`id`, `type`, `valeur_pourcent`) VALUES (1, 'defaut', 20.00);

-- --------------------------------------------------------
-- Litiges (blocage libération / remboursement)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `litiges` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` INT UNSIGNED NOT NULL,
  `ouvert_par` INT UNSIGNED NOT NULL COMMENT 'utilisateur_id (client ou expert)',
  `statut` ENUM('ouvert', 'rembourse_client', 'libere_expert', 'clos') NOT NULL DEFAULT 'ouvert',
  `motif` TEXT,
  `decision_admin` TEXT,
  `decide_par` INT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `traite_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reservation_id` (`reservation_id`),
  KEY `statut` (`statut`),
  CONSTRAINT `fk_litige_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_litige_ouvert` FOREIGN KEY (`ouvert_par`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_litige_decide` FOREIGN KEY (`decide_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Paiements: statut escrow (bloque = en attente libération)
-- Si erreur "Duplicate column", les colonnes existent déjà : ignorer.
-- --------------------------------------------------------
ALTER TABLE `paiements` ADD COLUMN `statut_escrow` ENUM('bloque', 'libere', 'rembourse') DEFAULT NULL AFTER `statut`;
ALTER TABLE `paiements` ADD COLUMN `libere_at` DATETIME DEFAULT NULL AFTER `statut_escrow`;

-- --------------------------------------------------------
-- Paramètres supplémentaires
-- --------------------------------------------------------
INSERT INTO `parametres` (`cle`, `valeur`) VALUES
('commission_premium_pourcent', '15'),
('devise_plateforme', 'XOF'),
('paiement_moyens', 'wave_money_mobile'),
('wave_commission_numero', '+223 94 03 54 56')
ON DUPLICATE KEY UPDATE `cle` = `cle`;
