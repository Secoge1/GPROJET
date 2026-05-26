-- GLOBALO - Données de test (3 enregistrements par module) - Growth Loop System
-- Exécuter après : schema.sql, migration_growth_loop.sql (et migration_referral si parrainages manquants)
-- Mot de passe pour tous les utilisateurs test : password
--
-- Contenu :
-- 1. Utilisateurs : 3 clients + 3 experts (emails growth-*@test.globalo)
-- 2. Profils experts : 3 (slugs : amadou-flutter-developer, fatou-designer-ui-ux, ibrahima-consultant-it)
-- 3. Demandes / jobs : 3 (slugs : correction-bug-flutter-liste, maquette-landing-page-startup, migration-excel-vers-base)
-- 4. Réservations : 3 terminées
-- 5. Parrainages : 3 (codes GLOBALO-SEED1, GLOBALO-SEED2, GLOBALO-SEED3)
-- 6. Growth page views : 3 expert + 3 job + 3 blog
-- 7. Session achievements : 3 cartes partageables
-- 8. Blog : 2 catégories + 3 articles + 3 liaisons post-tag

SET NAMES utf8mb4;

-- Création des tables growth si absentes (sinon exécuter migration_growth_loop.sql avant)
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

-- Données blog de base (catégorie + tags) si vides
INSERT IGNORE INTO `blog_categories` (`id`, `name`, `slug`, `description`) VALUES (1, 'Développement', 'developpement', 'Articles sur le développement et la tech');
INSERT IGNORE INTO `blog_tags` (`id`, `name`, `slug`) VALUES (1, 'Flutter', 'flutter'), (2, 'Recrutement', 'recrutement'), (3, 'Experts', 'experts');

-- Hash bcrypt pour "password"
SET @pwd = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- =============================================================================
-- 1. UTILISATEURS (3 clients + 3 experts)
-- =============================================================================
-- Nettoyage si re-exécution (optionnel)
DELETE FROM `utilisateurs` WHERE email LIKE 'growth-%@test.globalo';

INSERT INTO `utilisateurs` (`email`, `mot_de_passe`, `role`, `nom`, `prenom`, `email_verifie`, `actif`) VALUES
('growth-client1@test.globalo', @pwd, 'client', 'Dupont', 'Marie', 1, 1);
SET @client1 = LAST_INSERT_ID();
INSERT INTO `utilisateurs` (`email`, `mot_de_passe`, `role`, `nom`, `prenom`, `email_verifie`, `actif`) VALUES
('growth-client2@test.globalo', @pwd, 'client', 'Martin', 'Luc', 1, 1);
SET @client2 = LAST_INSERT_ID();
INSERT INTO `utilisateurs` (`email`, `mot_de_passe`, `role`, `nom`, `prenom`, `email_verifie`, `actif`) VALUES
('growth-client3@test.globalo', @pwd, 'client', 'Bernard', 'Sophie', 1, 1);
SET @client3 = LAST_INSERT_ID();

INSERT INTO `utilisateurs` (`email`, `mot_de_passe`, `role`, `nom`, `prenom`, `email_verifie`, `actif`) VALUES
('growth-expert1@test.globalo', @pwd, 'expert', 'Diallo', 'Amadou', 1, 1);
SET @expert_u1 = LAST_INSERT_ID();
INSERT INTO `utilisateurs` (`email`, `mot_de_passe`, `role`, `nom`, `prenom`, `email_verifie`, `actif`) VALUES
('growth-expert2@test.globalo', @pwd, 'expert', 'Traoré', 'Fatou', 1, 1);
SET @expert_u2 = LAST_INSERT_ID();
INSERT INTO `utilisateurs` (`email`, `mot_de_passe`, `role`, `nom`, `prenom`, `email_verifie`, `actif`) VALUES
('growth-expert3@test.globalo', @pwd, 'expert', 'Sow', 'Ibrahima', 1, 1);
SET @expert_u3 = LAST_INSERT_ID();

-- =============================================================================
-- 2. PROFILS EXPERTS (3) avec slug SEO
-- =============================================================================
INSERT INTO `profils_experts` (`utilisateur_id`, `titre`, `description`, `tarif_horaire`, `disponible`, `valide_par_admin`, `niveau_experience`, `slug`) VALUES
(@expert_u1, 'Développeur Flutter senior', 'Expert Flutter et Dart, 5 ans d''expérience. Apps mobile iOS/Android.', 45.00, 1, 1, 'expert', 'amadou-flutter-developer');
SET @profil1 = LAST_INSERT_ID();
INSERT INTO `profils_experts` (`utilisateur_id`, `titre`, `description`, `tarif_horaire`, `disponible`, `valide_par_admin`, `niveau_experience`, `slug`) VALUES
(@expert_u2, 'Designer UI/UX', 'Design d''interfaces et expérience utilisateur. Figma, Adobe XD.', 40.00, 1, 1, 'confirme', 'fatou-designer-ui-ux');
SET @profil2 = LAST_INSERT_ID();
INSERT INTO `profils_experts` (`utilisateur_id`, `titre`, `description`, `tarif_horaire`, `disponible`, `valide_par_admin`, `niveau_experience`, `slug`) VALUES
(@expert_u3, 'Consultant IT & Support', 'Infrastructure, dépannage, migration cloud.', 35.00, 1, 1, 'intermediaire', 'ibrahima-consultant-it');
SET @profil3 = LAST_INSERT_ID();

-- Liaison expert <-> compétence (id 5 = Développement web, 6 = Design, 1 = Excel pour IT)
INSERT INTO `expert_competences` (`expert_id`, `competence_id`, `niveau`) VALUES
(@profil1, 5, 'avance'),
(@profil2, 6, 'expert'),
(@profil3, 1, 'intermediaire');

-- =============================================================================
-- 3. DEMANDES ASSISTANCE / JOBS (3) avec slug SEO
-- =============================================================================
INSERT INTO `demandes_assistance` (`client_id`, `titre`, `description`, `competence_id`, `duree_estimee_heures`, `urgence`, `statut`, `slug`) VALUES
(@client1, 'Correction bug Flutter sur liste déroulante', 'Une liste déroulante ne s''affiche pas correctement sur Android. Besoin d''un dev Flutter.', 5, 2.00, 'urgent', 'ouverte', 'correction-bug-flutter-liste');
SET @demande1 = LAST_INSERT_ID();
INSERT INTO `demandes_assistance` (`client_id`, `titre`, `description`, `competence_id`, `duree_estimee_heures`, `urgence`, `statut`, `slug`) VALUES
(@client2, 'Maquette landing page startup', 'Création d''une maquette Figma pour une landing page moderne.', 6, 3.00, 'normale', 'ouverte', 'maquette-landing-page-startup');
SET @demande2 = LAST_INSERT_ID();
INSERT INTO `demandes_assistance` (`client_id`, `titre`, `description`, `competence_id`, `duree_estimee_heures`, `urgence`, `statut`, `slug`) VALUES
(@client3, 'Migration données Excel vers base', 'Aide pour structurer et migrer des données Excel vers une base.', 1, 1.50, 'normale', 'ouverte', 'migration-excel-vers-base');
SET @demande3 = LAST_INSERT_ID();

-- =============================================================================
-- 4. RÉSERVATIONS (3) terminées (pour session_achievements)
-- =============================================================================
INSERT INTO `reservations` (`demande_id`, `expert_id`, `client_id`, `date_debut_prevue`, `duree_heures`, `tarif_horaire`, `montant_total`, `statut`) VALUES
(@demande1, @profil1, @client1, NOW(), 2.00, 45.00, 90.00, 'terminee');
SET @reserv1 = LAST_INSERT_ID();
INSERT INTO `reservations` (`demande_id`, `expert_id`, `client_id`, `date_debut_prevue`, `duree_heures`, `tarif_horaire`, `montant_total`, `statut`) VALUES
(@demande2, @profil2, @client2, NOW(), 3.00, 40.00, 120.00, 'terminee');
SET @reserv2 = LAST_INSERT_ID();
INSERT INTO `reservations` (`demande_id`, `expert_id`, `client_id`, `date_debut_prevue`, `duree_heures`, `tarif_horaire`, `montant_total`, `statut`) VALUES
(@demande3, @profil3, @client3, NOW(), 1.50, 35.00, 52.50, 'terminee');
SET @reserv3 = LAST_INSERT_ID();

-- =============================================================================
-- 5. PARRAINAGES (3) - colonnes de base uniquement (referral_code/reward_status
--    ajoutés par migration_growth_loop.sql ; après migration, exécuter en plus :
--    UPDATE parrainages SET referral_code = CONCAT('GLOBALO-', LPAD(id, 5, '0')), reward_status = 'pending' WHERE referral_code IS NULL;
-- =============================================================================
INSERT INTO `parrainages` (`parrain_id`, `code`, `statut`) VALUES
(@client1, CONCAT('seedref', @client1, 'a'), 'envoye');
SET @parrain1 = LAST_INSERT_ID();
INSERT INTO `parrainages` (`parrain_id`, `code`, `statut`) VALUES
(@client2, CONCAT('seedref', @client2, 'b'), 'inscrit');
SET @parrain2 = LAST_INSERT_ID();
INSERT INTO `parrainages` (`parrain_id`, `code`, `statut`) VALUES
(@client3, CONCAT('seedref', @client3, 'c'), 'envoye');
SET @parrain3 = LAST_INSERT_ID();

-- =============================================================================
-- 6. GROWTH PAGE VIEWS (3 expert + 3 job + 3 blog = 9)
-- =============================================================================
INSERT INTO `growth_page_views` (`page_type`, `entity_id`, `referer`) VALUES
('expert', @profil1, 'https://www.google.com/'),
('expert', @profil2, 'https://www.google.com/'),
('expert', @profil3, 'https://twitter.com/');
INSERT INTO `growth_page_views` (`page_type`, `entity_id`, `referer`) VALUES
('job', @demande1, 'https://www.google.com/'),
('job', @demande2, 'https://www.linkedin.com/'),
('job', @demande3, NULL);

-- =============================================================================
-- 7. SESSION ACHIEVEMENTS (3) cartes partageables
-- =============================================================================
INSERT INTO `session_achievements` (`reservation_id`, `expert_id`, `client_id`, `titre_session`, `note`) VALUES
(@reserv1, @profil1, @client1, 'Correction bug Flutter liste déroulante', 5);
INSERT INTO `session_achievements` (`reservation_id`, `expert_id`, `client_id`, `titre_session`, `note`) VALUES
(@reserv2, @profil2, @client2, 'Maquette landing page startup', 4);
INSERT INTO `session_achievements` (`reservation_id`, `expert_id`, `client_id`, `titre_session`, `note`) VALUES
(@reserv3, @profil3, @client3, 'Migration Excel vers base', 5);

-- =============================================================================
-- 8. BLOG - Catégories (2 supplémentaires si pas déjà 3)
-- =============================================================================
INSERT INTO `blog_categories` (`name`, `slug`, `description`) VALUES
('Recrutement', 'recrutement', 'Conseils pour recruter des talents à distance'),
('Productivité', 'productivite', 'Astuces productivité et travail à distance')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =============================================================================
-- 9. BLOG - Articles (3)
-- =============================================================================
INSERT INTO `blog_posts` (`category_id`, `author_id`, `title`, `slug`, `meta_description`, `body`, `published_at`) VALUES
((SELECT id FROM blog_categories WHERE slug = 'developpement' LIMIT 1), @client1, 'Comment corriger un bug Flutter rapidement', 'comment-corriger-bug-flutter', 'Découvrez les étapes pour débugger une app Flutter avec l''aide d''un expert sur Globalo.', '<p>Les bugs Flutter sont courants lors du développement. Voici comment un expert peut vous aider à les résoudre rapidement via une session en direct sur Globalo.</p><p>Chat, visio et partage d''écran permettent de cibler le problème en quelques minutes.</p>', NOW());
SET @post1 = LAST_INSERT_ID();
INSERT INTO `blog_posts` (`category_id`, `author_id`, `title`, `slug`, `meta_description`, `body`, `published_at`) VALUES
((SELECT id FROM blog_categories WHERE slug = 'developpement' LIMIT 1), @client2, 'Où trouver des développeurs en ligne', 'ou-trouver-developpeurs-en-ligne', 'Les meilleures plateformes pour recruter des développeurs à la demande.', '<p>Globalo, Malt, Codeur.com... Comparatif des plateformes qui connectent clients et développeurs pour du travail à la demande.</p>', NOW());
SET @post2 = LAST_INSERT_ID();
INSERT INTO `blog_posts` (`category_id`, `author_id`, `title`, `slug`, `meta_description`, `body`, `published_at`) VALUES
((SELECT id FROM blog_categories WHERE slug = 'recrutement' LIMIT 1), @client3, 'Réserver un expert en quelques clics', 'reserver-expert-instant', 'Comment réserver une session avec un expert sur Globalo en moins de 2 minutes.', '<p>Créez une demande, choisissez un expert disponible et réservez un créneau. Paiement sécurisé, session en visio ou chat.</p>', NOW());
SET @post3 = LAST_INSERT_ID();

-- =============================================================================
-- 10. BLOG - Tags des articles (3 liaisons post <-> tag)
-- =============================================================================
-- Les tags Flutter (1), Recrutement (2), Experts (3) existent dans migration_growth_loop
INSERT INTO `blog_post_tags` (`post_id`, `tag_id`) VALUES
(@post1, 1),
(@post2, 2),
(@post3, 3)
ON DUPLICATE KEY UPDATE post_id = post_id;

-- =============================================================================
-- 11. GROWTH PAGE VIEWS - Blog (3 vues, après création des articles)
-- =============================================================================
INSERT INTO `growth_page_views` (`page_type`, `entity_id`, `referer`) VALUES
('blog', @post1, 'https://www.google.com/'),
('blog', @post2, 'https://www.facebook.com/'),
('blog', @post3, NULL);
