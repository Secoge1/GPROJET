-- GLOBALO - Growth Loop System
-- Expert/job slugs, referral codes, blog, tracking, achievement cards

SET NAMES utf8mb4;

-- --------------------------------------------------------
-- 1. Expert profile slug (public SEO URL: /expert/amadou-flutter-developer)
-- --------------------------------------------------------
ALTER TABLE `profils_experts` ADD COLUMN `slug` VARCHAR(120) DEFAULT NULL AFTER `nombre_avis`;
UPDATE `profils_experts` p
JOIN `utilisateurs` u ON u.id = p.utilisateur_id
SET p.slug = LOWER(CONCAT(
  REPLACE(REPLACE(REPLACE(TRIM(u.prenom), ' ', '-'), '''', ''), 'é', 'e'),
  '-',
  REPLACE(REPLACE(REPLACE(SUBSTRING_INDEX(p.titre, ' ', 1), ' ', '-'), '''', ''), 'é', 'e'),
  '-', p.id
)) WHERE p.slug IS NULL;
UPDATE `profils_experts` SET slug = CONCAT('expert-', id) WHERE slug = '' OR slug IS NULL;
ALTER TABLE `profils_experts` ADD UNIQUE KEY `slug` (`slug`);

-- --------------------------------------------------------
-- 2. Job (demande) slug (public SEO URL: /jobs/flutter-bug-fix)
-- --------------------------------------------------------
ALTER TABLE `demandes_assistance` ADD COLUMN `slug` VARCHAR(120) DEFAULT NULL AFTER `statut`;
UPDATE `demandes_assistance` d
SET d.slug = LOWER(CONCAT(
  REPLACE(REPLACE(REPLACE(SUBSTRING(d.titre, 1, 50), ' ', '-'), '''', ''), 'é', 'e'),
  '-', d.id
)) WHERE d.slug IS NULL;
UPDATE `demandes_assistance` SET slug = CONCAT('job-', id) WHERE slug = '' OR slug IS NULL;
ALTER TABLE `demandes_assistance` ADD UNIQUE KEY `slug` (`slug`);

-- --------------------------------------------------------
-- 3. Referral: table parrainages (si absente) + GLOBALO-XXXXX + reward_status
-- --------------------------------------------------------
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

-- Colonnes growth (ignorer si déjà présentes)
ALTER TABLE `parrainages` ADD COLUMN `referral_code` VARCHAR(20) DEFAULT NULL AFTER `code`;
ALTER TABLE `parrainages` ADD COLUMN `reward_status` ENUM('pending', 'credited_parrain', 'credited_filleul', 'both_credited') NOT NULL DEFAULT 'pending' AFTER `statut`;
UPDATE `parrainages` SET referral_code = CONCAT('GLOBALO-', LPAD(id, 5, '0')) WHERE referral_code IS NULL;
ALTER TABLE `parrainages` ADD UNIQUE KEY `referral_code` (`referral_code`);

-- --------------------------------------------------------
-- 4. Growth page views (analytics: expert/job visits)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `growth_page_views` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_type` ENUM('expert', 'job', 'blog') NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `viewed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `session_id` VARCHAR(64) DEFAULT NULL,
  `referer` VARCHAR(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_type_entity` (`page_type`, `entity_id`),
  KEY `viewed_at` (`viewed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. Session achievements (shareable cards)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `session_achievements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` INT UNSIGNED NOT NULL,
  `expert_id` INT UNSIGNED NOT NULL,
  `client_id` INT UNSIGNED NOT NULL,
  `titre_session` VARCHAR(200) NOT NULL,
  `note` TINYINT UNSIGNED DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reservation_id` (`reservation_id`),
  KEY `expert_id` (`expert_id`),
  CONSTRAINT `fk_achievement_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_achievement_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_achievement_client` FOREIGN KEY (`client_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 6. Blog
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `blog_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blog_tags` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(60) NOT NULL,
  `slug` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `author_id` INT UNSIGNED DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `meta_description` VARCHAR(160) DEFAULT NULL,
  `body` LONGTEXT NOT NULL,
  `published_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `published_at` (`published_at`),
  CONSTRAINT `fk_blog_category` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_blog_author` FOREIGN KEY (`author_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blog_post_tags` (
  `post_id` INT UNSIGNED NOT NULL,
  `tag_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`post_id`, `tag_id`),
  CONSTRAINT `fk_bpt_post` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bpt_tag` FOREIGN KEY (`tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample blog category
INSERT INTO `blog_categories` (`name`, `slug`, `description`) VALUES
('Développement', 'developpement', 'Articles sur le développement et la tech')
ON DUPLICATE KEY UPDATE `slug` = `slug`;

INSERT INTO `blog_tags` (`name`, `slug`) VALUES
('Flutter', 'flutter'),
('Recrutement', 'recrutement'),
('Experts', 'experts')
ON DUPLICATE KEY UPDATE `slug` = `slug`;
