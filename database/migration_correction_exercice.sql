-- =============================================================================
-- GLOBALO - Migration : Paiement correction exercice étudiant → professeur
-- Exécuter après migration_etudiant.sql
--
-- SÉCURITÉ PRODUCTION : Chaque ADD COLUMN / ADD INDEX est conditionnel.
-- Peut être rejoué sans risque sur une base qui a déjà la colonne.
-- Testé sur MariaDB 10.3+ et MySQL 5.7+.
-- =============================================================================

-- 1. Colonne prix_correction
SET @exist_prix := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'exercices'
      AND COLUMN_NAME  = 'prix_correction'
);
SET @sql_prix := IF(@exist_prix = 0,
    'ALTER TABLE `exercices` ADD COLUMN `prix_correction` DECIMAL(8,2) DEFAULT NULL COMMENT \'Prix XOF pour accéder à la correction\' AFTER `commentaire_expert`',
    'SELECT 1 -- prix_correction déjà présent'
);
PREPARE stmt FROM @sql_prix; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. Colonne paiement_statut
SET @exist_statut := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'exercices'
      AND COLUMN_NAME  = 'paiement_statut'
);
SET @sql_statut := IF(@exist_statut = 0,
    "ALTER TABLE `exercices` ADD COLUMN `paiement_statut` ENUM('non_requis','en_attente','paye') NOT NULL DEFAULT 'non_requis' AFTER `prix_correction`",
    'SELECT 1 -- paiement_statut déjà présent'
);
PREPARE stmt FROM @sql_statut; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. Colonne paiement_reference
SET @exist_ref := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'exercices'
      AND COLUMN_NAME  = 'paiement_reference'
);
SET @sql_ref := IF(@exist_ref = 0,
    'ALTER TABLE `exercices` ADD COLUMN `paiement_reference` VARCHAR(100) DEFAULT NULL COMMENT \'Référence transaction portefeuille\' AFTER `paiement_statut`',
    'SELECT 1 -- paiement_reference déjà présent'
);
PREPARE stmt FROM @sql_ref; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. Index sur paiement_statut (conditionnel)
SET @exist_idx_paiement := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'exercices'
      AND INDEX_NAME   = 'idx_exercice_paiement'
);
SET @sql_idx_p := IF(@exist_idx_paiement = 0,
    'ALTER TABLE `exercices` ADD INDEX `idx_exercice_paiement` (`paiement_statut`)',
    'SELECT 1 -- idx_exercice_paiement déjà présent'
);
PREPARE stmt FROM @sql_idx_p; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. Index sur expert_id (conditionnel)
SET @exist_idx_expert := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'exercices'
      AND INDEX_NAME   = 'idx_exercice_expert'
);
SET @sql_idx_e := IF(@exist_idx_expert = 0,
    'ALTER TABLE `exercices` ADD INDEX `idx_exercice_expert` (`expert_id`)',
    'SELECT 1 -- idx_exercice_expert déjà présent'
);
PREPARE stmt FROM @sql_idx_e; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. Paramètre : prix par défaut d'une correction (modifiable par l'admin)
INSERT INTO `parametres` (`cle`, `valeur`)
VALUES ('prix_correction_exercice_xof', '500')
ON DUPLICATE KEY UPDATE `valeur` = `valeur`;

-- 7. Paramètre : commission prélevée sur les corrections (%)
INSERT INTO `parametres` (`cle`, `valeur`)
VALUES ('commission_correction_pct', '20')
ON DUPLICATE KEY UPDATE `valeur` = `valeur`;
