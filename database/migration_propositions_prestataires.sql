-- Propositions de service : experts → demandes clients, professeurs → exercices étudiants

CREATE TABLE IF NOT EXISTS `demande_propositions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `demande_id` INT UNSIGNED NOT NULL,
    `expert_id` INT UNSIGNED NOT NULL COMMENT 'profils_experts.id',
    `presentation` VARCHAR(500) NOT NULL DEFAULT '',
    `message` TEXT NOT NULL,
    `tarif_propose` DECIMAL(10,2) NOT NULL,
    `delai_jours` SMALLINT UNSIGNED NOT NULL DEFAULT 3,
    `competences_cles` VARCHAR(500) DEFAULT NULL,
    `statut` ENUM('en_attente','acceptee','refusee','retiree') NOT NULL DEFAULT 'en_attente',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_demande_expert` (`demande_id`, `expert_id`),
    KEY `idx_demande_statut` (`demande_id`, `statut`),
    KEY `idx_expert` (`expert_id`),
    CONSTRAINT `fk_prop_demande` FOREIGN KEY (`demande_id`) REFERENCES `demandes_assistance` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_prop_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exercice_propositions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exercice_id` INT UNSIGNED NOT NULL,
    `profil_professeur_id` INT UNSIGNED NOT NULL,
    `presentation` VARCHAR(500) NOT NULL DEFAULT '',
    `message` TEXT NOT NULL,
    `tarif_propose` DECIMAL(10,2) NOT NULL,
    `delai_jours` SMALLINT UNSIGNED NOT NULL DEFAULT 3,
    `competences_cles` VARCHAR(500) DEFAULT NULL,
    `statut` ENUM('en_attente','acceptee','refusee','retiree') NOT NULL DEFAULT 'en_attente',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_exercice_prof` (`exercice_id`, `profil_professeur_id`),
    KEY `idx_exercice_statut` (`exercice_id`, `statut`),
    KEY `idx_prof` (`profil_professeur_id`),
    CONSTRAINT `fk_prop_exercice` FOREIGN KEY (`exercice_id`) REFERENCES `exercices` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_prop_prof` FOREIGN KEY (`profil_professeur_id`) REFERENCES `profils_professeurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
