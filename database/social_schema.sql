-- ============================================================
-- GLOBALO — Table de suivi des publications sociales IA
-- À exécuter dans phpMyAdmin ou via CLI :
--   mysql -u root globalo < database/social_schema.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS `social_publications` (
    `id`         INT          UNSIGNED NOT NULL AUTO_INCREMENT,
    `sujet`      VARCHAR(300) NOT NULL,
    `contenu`    TEXT         NOT NULL,
    `fb_post_id` VARCHAR(100) DEFAULT NULL COMMENT 'ID du post Facebook publié',
    `li_post_id` VARCHAR(200) DEFAULT NULL COMMENT 'ID du post LinkedIn publié',
    `publie_le`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_publie_le` (`publie_le`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historique des publications IA automatiques sur les réseaux sociaux';

-- ============================================================
-- Paramètres réseaux sociaux (dans la table existante `parametres`)
-- ============================================================
INSERT INTO `parametres` (`cle`, `valeur`) VALUES
    ('social_ai_provider',        'gemini'),
    ('social_ai_api_key',         ''),
    ('social_ton',                'professionnel et engageant'),
    ('social_hashtags',           '#GLOBALO #Freelance #AfriqueOuest #Experts #FCFA'),
    ('social_fb_enabled',         '0'),
    ('social_fb_page_id',         ''),
    ('social_fb_token',           ''),
    ('social_li_enabled',         '0'),
    ('social_li_org_id',          ''),
    ('social_li_token',           ''),
    ('social_jours_actifs',       '["lundi","mercredi","vendredi","samedi"]'),
    ('social_heure_publication',  '9'),
    ('social_planning',           '{}'),
    ('cron_secret',               '')
ON DUPLICATE KEY UPDATE `cle` = `cle`;

-- ============================================================
-- Paramètres chatbot multi-provider
-- ============================================================
INSERT INTO `parametres` (`cle`, `valeur`) VALUES
    ('chatbot_ai_provider', 'openai')
ON DUPLICATE KEY UPDATE `cle` = `cle`;
