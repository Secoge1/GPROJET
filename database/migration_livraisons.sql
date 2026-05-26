-- ============================================================
-- GLOBALO — Livraison de travaux par les experts
-- Exécuter : mysql -u root globalo < database/migration_livraisons.sql
-- OU via phpMyAdmin → Import
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `livraisons` (
    `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `reservation_id` INT UNSIGNED    NOT NULL,
    `expert_id`      INT UNSIGNED    NOT NULL COMMENT 'profils_experts.id',
    `client_id`      INT UNSIGNED    NOT NULL COMMENT 'utilisateurs.id',
    `type`           ENUM('fichier','video') NOT NULL DEFAULT 'fichier',
    `nom_fichier`    VARCHAR(255)    DEFAULT NULL  COMMENT 'Nom original du fichier',
    `chemin`         VARCHAR(500)    DEFAULT NULL  COMMENT 'Chemin relatif dans uploads/',
    `taille`         INT UNSIGNED    DEFAULT NULL  COMMENT 'Taille en octets',
    `type_mime`      VARCHAR(100)    DEFAULT NULL,
    `lien_externe`   VARCHAR(1000)   DEFAULT NULL  COMMENT 'URL WeTransfer/Smash/etc. pour vidéos',
    `commentaire`    TEXT            DEFAULT NULL,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_livraison_reservation` (`reservation_id`),
    KEY `idx_livraison_client`      (`client_id`),
    CONSTRAINT `fk_livraison_res` FOREIGN KEY (`reservation_id`) REFERENCES `reservations`  (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_livraison_exp` FOREIGN KEY (`expert_id`)      REFERENCES `profils_experts`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_livraison_cli` FOREIGN KEY (`client_id`)      REFERENCES `utilisateurs`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Fichiers et liens livrés par les experts aux clients';
