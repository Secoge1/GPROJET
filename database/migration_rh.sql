-- =====================================================================
-- GLOBALO - Migration Module RH avec IA Intégrée
-- Espace de gestion des ressources humaines intelligentes
-- =====================================================================

-- Table des logs des conversations IA-RH
CREATE TABLE IF NOT EXISTS `rh_ia_logs` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `agent_type`    ENUM('inscriptions','profils','marketing','manager') NOT NULL COMMENT 'Type d agent IA',
    `admin_id`      INT UNSIGNED NOT NULL COMMENT 'Admin qui a initié la conversation',
    `session_id`    VARCHAR(64) NOT NULL COMMENT 'Identifiant de session de conversation',
    `role`          ENUM('user','assistant') NOT NULL,
    `message`       TEXT NOT NULL,
    `tokens_used`   SMALLINT UNSIGNED DEFAULT 0,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_agent_session` (`agent_type`, `session_id`),
    INDEX `idx_admin` (`admin_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des analyses RH générées par l'IA
CREATE TABLE IF NOT EXISTS `rh_ia_analyses` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `agent_type`    ENUM('inscriptions','profils','marketing','manager') NOT NULL,
    `cible_type`    ENUM('professeur','etudiant','client','expert','global') NOT NULL,
    `cible_id`      INT UNSIGNED DEFAULT NULL COMMENT 'ID utilisateur si analyse individuelle',
    `titre`         VARCHAR(255) NOT NULL,
    `contenu`       TEXT NOT NULL,
    `score`         TINYINT UNSIGNED DEFAULT NULL COMMENT 'Score IA 0-100',
    `tags`          JSON DEFAULT NULL,
    `admin_id`      INT UNSIGNED NOT NULL,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_agent_cible` (`agent_type`, `cible_type`),
    INDEX `idx_cible_id` (`cible_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des notes RH manuelles
CREATE TABLE IF NOT EXISTS `rh_notes` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `utilisateur_id` INT UNSIGNED NOT NULL,
    `admin_id`      INT UNSIGNED NOT NULL,
    `type`          ENUM('inscription','profil','marketing','manager','general') DEFAULT 'general',
    `note`          TEXT NOT NULL,
    `priorite`      ENUM('basse','normale','haute','critique') DEFAULT 'normale',
    `statut`        ENUM('ouverte','en_cours','resolue','archivee') DEFAULT 'ouverte',
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_utilisateur` (`utilisateur_id`),
    INDEX `idx_statut` (`statut`),
    INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des recommandations marketing générées par l'IA
CREATE TABLE IF NOT EXISTS `rh_marketing_recommandations` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `segment`       VARCHAR(100) NOT NULL COMMENT 'Segment cible (ex: experts_mali, etudiants_actifs)',
    `titre`         VARCHAR(255) NOT NULL,
    `description`   TEXT NOT NULL,
    `action_cle`    VARCHAR(100) DEFAULT NULL,
    `priorite`      TINYINT UNSIGNED DEFAULT 5,
    `statut`        ENUM('generee','approuvee','en_cours','terminee','rejetee') DEFAULT 'generee',
    `admin_id`      INT UNSIGNED NOT NULL,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_segment` (`segment`),
    INDEX `idx_statut` (`statut`),
    INDEX `idx_priorite` (`priorite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
