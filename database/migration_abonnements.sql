-- GLOBALO - Modèle abonnement (option : remplacer commissions par abonnement unique)
-- Exécuter une seule fois.

-- Table: abonnements
CREATE TABLE IF NOT EXISTS `abonnements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `type` ENUM('client', 'expert') NOT NULL,
  `plan` ENUM('gratuit', 'premium') NOT NULL DEFAULT 'gratuit',
  `date_debut` DATE NOT NULL,
  `date_fin` DATE NOT NULL,
  `statut` ENUM('actif', 'expire', 'annule') NOT NULL DEFAULT 'actif',
  `payment_provider` VARCHAR(20) DEFAULT NULL,
  `external_reference` VARCHAR(120) DEFAULT NULL,
  `montant_paye` DECIMAL(12,2) DEFAULT NULL,
  `devise` VARCHAR(3) DEFAULT 'XOF',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `type_statut` (`type`, `statut`),
  KEY `date_fin` (`date_fin`),
  CONSTRAINT `fk_abonnement_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paramètres abonnement (ne pas écraser si déjà présents)
INSERT INTO `parametres` (`cle`, `valeur`) VALUES
('monetisation_mode', 'commission'),
('abonnement_provider', 'gratuit'),
('abonnement_plan_gratuit_actif', '1'),
('abonnement_prix_client_xof', '0'),
('abonnement_prix_expert_xof', '0')
ON DUPLICATE KEY UPDATE `cle` = `cle`;
