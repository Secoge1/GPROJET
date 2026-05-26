-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 09 mars 2026 à 04:04
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : cp2640311p29_globalo
--

-- --------------------------------------------------------

--
-- Structure de la table `admin_logs`
--

DROP TABLE IF EXISTS `admin_logs`;
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` int UNSIGNED NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cible_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cible_id` int UNSIGNED DEFAULT NULL,
  `details` json DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `avis_notes`
--

DROP TABLE IF EXISTS `avis_notes`;
CREATE TABLE IF NOT EXISTS `avis_notes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` int UNSIGNED NOT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `expert_id` int UNSIGNED NOT NULL,
  `note` tinyint UNSIGNED NOT NULL,
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reservation_id` (`reservation_id`),
  KEY `expert_id` (`expert_id`),
  KEY `client_id` (`client_id`)
) ;

-- --------------------------------------------------------

--
-- Structure de la table `blog_categories`
--

DROP TABLE IF EXISTS `blog_categories`;
CREATE TABLE IF NOT EXISTS `blog_categories` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `blog_categories`
--

INSERT INTO `blog_categories` (`id`, `name`, `slug`, `description`, `created_at`) VALUES
(1, 'Développement', 'developpement', 'Articles sur le développement et la tech', '2026-03-09 03:37:56'),
(2, 'Recrutement', 'recrutement', 'Conseils pour recruter des talents à distance', '2026-03-09 03:37:56'),
(3, 'Productivité', 'productivite', 'Astuces productivité et travail à distance', '2026-03-09 03:37:56');

-- --------------------------------------------------------

--
-- Structure de la table `blog_posts`
--

DROP TABLE IF EXISTS `blog_posts`;
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` int UNSIGNED DEFAULT NULL,
  `author_id` int UNSIGNED DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta_description` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `published_at` (`published_at`),
  KEY `fk_blog_author` (`author_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `category_id`, `author_id`, `title`, `slug`, `meta_description`, `body`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 1, 13, 'Comment corriger un bug Flutter rapidement', 'comment-corriger-bug-flutter', 'Découvrez les étapes pour débugger une app Flutter avec l\'aide d\'un expert sur Globalo.', '<p>Les bugs Flutter sont courants lors du développement. Voici comment un expert peut vous aider à les résoudre rapidement via une session en direct sur Globalo.</p><p>Chat, visio et partage d\'écran permettent de cibler le problème en quelques minutes.</p>', '2026-03-09 03:37:56', '2026-03-09 03:37:56', '2026-03-09 03:37:56'),
(2, 1, 14, 'Où trouver des développeurs en ligne', 'ou-trouver-developpeurs-en-ligne', 'Les meilleures plateformes pour recruter des développeurs à la demande.', '<p>Globalo, Malt, Codeur.com... Comparatif des plateformes qui connectent clients et développeurs pour du travail à la demande.</p>', '2026-03-09 03:37:56', '2026-03-09 03:37:56', '2026-03-09 03:37:56'),
(3, 2, 15, 'Réserver un expert en quelques clics', 'reserver-expert-instant', 'Comment réserver une session avec un expert sur Globalo en moins de 2 minutes.', '<p>Créez une demande, choisissez un expert disponible et réservez un créneau. Paiement sécurisé, session en visio ou chat.</p>', '2026-03-09 03:37:56', '2026-03-09 03:37:56', '2026-03-09 03:37:56');

-- --------------------------------------------------------

--
-- Structure de la table `blog_post_tags`
--

DROP TABLE IF EXISTS `blog_post_tags`;
CREATE TABLE IF NOT EXISTS `blog_post_tags` (
  `post_id` int UNSIGNED NOT NULL,
  `tag_id` int UNSIGNED NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`),
  KEY `fk_bpt_tag` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `blog_post_tags`
--

INSERT INTO `blog_post_tags` (`post_id`, `tag_id`) VALUES
(1, 1),
(2, 2),
(3, 3);

-- --------------------------------------------------------

--
-- Structure de la table `blog_tags`
--

DROP TABLE IF EXISTS `blog_tags`;
CREATE TABLE IF NOT EXISTS `blog_tags` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `blog_tags`
--

INSERT INTO `blog_tags` (`id`, `name`, `slug`) VALUES
(1, 'Flutter', 'flutter'),
(2, 'Recrutement', 'recrutement'),
(3, 'Experts', 'experts');

-- --------------------------------------------------------

--
-- Structure de la table `chatbot_config`
--

DROP TABLE IF EXISTS `chatbot_config`;
CREATE TABLE IF NOT EXISTS `chatbot_config` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `cle` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cle` (`cle`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `chatbot_config`
--

INSERT INTO `chatbot_config` (`id`, `cle`, `valeur`, `updated_at`) VALUES
(1, 'system_prompt', 'Tu es l\'assistant virtuel de GLOBALO, une plateforme qui met en relation des clients avec des experts (développement, design, conseil, etc.). Tu réponds en français de manière courtoise et professionnelle. Tu peux : aider à trouver un expert, expliquer comment réserver, créer une demande d\'assistance, expliquer les paiements et les retraits.', '2026-03-09 02:25:43'),
(2, 'default_find_expert', 'Je peux vous aider à trouver un expert. Voici des profils disponibles.', '2026-03-09 02:25:43'),
(3, 'default_create_task', 'Je peux créer une demande d\'assistance pour vous. Indiquez la durée estimée (en heures) et votre budget si vous en avez un.', '2026-03-09 02:25:43'),
(4, 'help_payment', 'Sur GLOBALO, vous payez à la réservation. Le montant est débité de votre portefeuille. La plateforme prélève une commission (voir Paramètres).', '2026-03-09 02:25:43'),
(5, 'help_withdrawal', 'Les experts peuvent demander un retrait depuis leur tableau de bord (Revenus > Demander un retrait). Le virement est traité sous quelques jours ouvrés.', '2026-03-09 02:25:43'),
(6, 'help_booking', 'Pour réserver : 1) Trouvez un expert, 2) Choisissez un créneau, 3) Validez la réservation. Le paiement est débité à la réservation.', '2026-03-09 02:25:43'),
(7, 'help_commission', 'La plateforme prélève une commission sur chaque session (configurable par l\'administrateur, par défaut 15%).', '2026-03-09 02:25:43');

-- --------------------------------------------------------

--
-- Structure de la table `chatbot_conversations`
--

DROP TABLE IF EXISTS `chatbot_conversations`;
CREATE TABLE IF NOT EXISTS `chatbot_conversations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int UNSIGNED DEFAULT NULL,
  `session_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Pour utilisateurs non connectés',
  `conversation_uid` varchar(36) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'UUID côté client',
  `context` json DEFAULT NULL COMMENT 'Intent en cours, paramètres create_task, etc.',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversation_uid` (`conversation_uid`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `session_id` (`session_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `chatbot_conversations`
--

INSERT INTO `chatbot_conversations` (`id`, `utilisateur_id`, `session_id`, `conversation_uid`, `context`, `created_at`, `updated_at`) VALUES
(1, NULL, 've7n7dk7btq38f3o6duqdj81mk', '17aed45a-d61e-df22-003b-68c0a3d53bdd', '{}', '2026-03-09 02:29:42', '2026-03-09 02:29:42'),
(2, NULL, '0hs7bk9f4he1qnl1cqfe625066', 'bc86dc4e-1e96-b126-1403-f46b2a288900', '{}', '2026-03-09 02:41:08', '2026-03-09 02:41:08'),
(3, NULL, '6ilv63jb49b7102540t50p2ckv', '627a290b-1de0-6542-065f-f54e03f09095', '{}', '2026-03-09 03:15:56', '2026-03-09 03:15:56');

-- --------------------------------------------------------

--
-- Structure de la table `chatbot_messages`
--

DROP TABLE IF EXISTS `chatbot_messages`;
CREATE TABLE IF NOT EXISTS `chatbot_messages` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `conversation_id` int UNSIGNED NOT NULL,
  `role` enum('user','assistant','system') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `intent` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json DEFAULT NULL COMMENT 'Experts list, quick_actions, etc.',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `conversation_id` (`conversation_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `chatbot_messages`
--

INSERT INTO `chatbot_messages` (`id`, `conversation_id`, `role`, `content`, `intent`, `payload`, `created_at`) VALUES
(1, 1, 'user', 'Je cherche un expert', NULL, NULL, '2026-03-09 02:29:42'),
(2, 1, 'assistant', 'Je peux vous aider à trouver un expert. Voici des profils disponibles.', 'general_question', '{\"experts\": [], \"quick_actions\": [\"find_expert\", \"post_request\", \"my_sessions\", \"support\"]}', '2026-03-09 02:29:42'),
(3, 1, 'user', 'Je veux créer une demande d\'assistance', NULL, NULL, '2026-03-09 02:29:47'),
(4, 1, 'assistant', 'Le chatbot n\'est pas configuré (clé API manquante).', 'general_question', '{\"experts\": [], \"quick_actions\": [\"find_expert\", \"post_request\", \"my_sessions\", \"support\"]}', '2026-03-09 02:29:47'),
(5, 1, 'user', 'Je cherche un expert', NULL, NULL, '2026-03-09 02:29:58'),
(6, 1, 'assistant', 'Je peux vous aider à trouver un expert. Voici des profils disponibles.', 'general_question', '{\"experts\": [], \"quick_actions\": [\"find_expert\", \"post_request\", \"my_sessions\", \"support\"]}', '2026-03-09 02:29:58'),
(7, 1, 'user', 'Je veux créer une demande d\'assistance', NULL, NULL, '2026-03-09 02:29:59'),
(8, 1, 'assistant', 'Le chatbot n\'est pas configuré (clé API manquante).', 'general_question', '{\"experts\": [], \"quick_actions\": [\"find_expert\", \"post_request\", \"my_sessions\", \"support\"]}', '2026-03-09 02:29:59'),
(9, 1, 'user', 'Je cherche un expert', NULL, NULL, '2026-03-09 02:29:59'),
(10, 1, 'assistant', 'Je peux vous aider à trouver un expert. Voici des profils disponibles.', 'general_question', '{\"experts\": [], \"quick_actions\": [\"find_expert\", \"post_request\", \"my_sessions\", \"support\"]}', '2026-03-09 02:30:00'),
(11, 2, 'user', 'Je cherche un expert', NULL, NULL, '2026-03-09 02:41:08'),
(12, 2, 'assistant', 'Je peux vous aider à trouver un expert. Voici des profils disponibles.', 'general_question', '{\"experts\": [], \"quick_actions\": [\"find_expert\", \"post_request\", \"my_sessions\", \"support\"]}', '2026-03-09 02:41:08'),
(13, 2, 'user', 'Je veux créer une demande d\'assistance', NULL, NULL, '2026-03-09 02:41:11'),
(14, 2, 'assistant', 'Le chatbot n\'est pas configuré (clé API manquante).', 'general_question', '{\"experts\": [], \"quick_actions\": [\"find_expert\", \"post_request\", \"my_sessions\", \"support\"]}', '2026-03-09 02:41:11'),
(15, 2, 'user', 'Je cherche un expert', NULL, NULL, '2026-03-09 02:41:15'),
(16, 2, 'assistant', 'Je peux vous aider à trouver un expert. Voici des profils disponibles.', 'general_question', '{\"experts\": [], \"quick_actions\": [\"find_expert\", \"post_request\", \"my_sessions\", \"support\"]}', '2026-03-09 02:41:15'),
(17, 2, 'user', 'Je cherche un expert', NULL, NULL, '2026-03-09 02:41:17'),
(18, 2, 'assistant', 'Je peux vous aider à trouver un expert. Voici des profils disponibles.', 'general_question', '{\"experts\": [], \"quick_actions\": [\"find_expert\", \"post_request\", \"my_sessions\", \"support\"]}', '2026-03-09 02:41:17'),
(19, 2, 'user', 'Je cherche un expert', NULL, NULL, '2026-03-09 02:42:37'),
(20, 2, 'assistant', 'Je peux vous aider à trouver un expert. Voici des profils disponibles.', 'general_question', '{\"experts\": [], \"quick_actions\": [\"find_expert\", \"post_request\", \"my_sessions\", \"support\"]}', '2026-03-09 02:42:37'),
(21, 3, 'user', 'Je cherche un expert', NULL, NULL, '2026-03-09 03:15:56'),
(22, 3, 'assistant', 'Je peux vous aider à trouver un expert. Voici des profils disponibles.', 'general_question', '{\"experts\": [], \"quick_actions\": [\"find_expert\", \"post_request\", \"my_sessions\", \"support\"]}', '2026-03-09 03:15:56');

-- --------------------------------------------------------

--
-- Structure de la table `commission_config`
--

DROP TABLE IF EXISTS `commission_config`;
CREATE TABLE IF NOT EXISTS `commission_config` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` enum('defaut','premium','pays') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'defaut',
  `valeur_pourcent` decimal(5,2) NOT NULL,
  `pays_code` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ISO 3166-1 alpha-2 (ex: SN, FR)',
  `expert_profil_id` int UNSIGNED DEFAULT NULL COMMENT 'Pour type premium sur un expert',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_premium_expert` (`type`,`expert_profil_id`),
  UNIQUE KEY `uq_pays` (`type`,`pays_code`),
  KEY `type` (`type`),
  KEY `fk_commission_expert` (`expert_profil_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commission_config`
--

INSERT INTO `commission_config` (`id`, `type`, `valeur_pourcent`, `pays_code`, `expert_profil_id`, `actif`, `created_at`) VALUES
(1, 'defaut', 20.00, NULL, NULL, 1, '2026-03-09 02:09:47');

-- --------------------------------------------------------

--
-- Structure de la table `competences`
--

DROP TABLE IF EXISTS `competences`;
CREATE TABLE IF NOT EXISTS `competences` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `categorie` (`categorie`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `competences`
--

INSERT INTO `competences` (`id`, `nom`, `categorie`, `slug`, `actif`) VALUES
(1, 'Excel', 'Bureautique', 'excel', 1),
(2, 'Rédaction professionnelle', 'Rédaction', 'redaction-professionnelle', 1),
(3, 'Présentations', 'Bureautique', 'presentations', 1),
(5, 'Développement web', 'Tech', 'developpement-web', 1),
(6, 'Design graphique', 'Design', 'design-graphique', 1),
(7, 'Comptabilité', 'Finance', 'comptabilite', 1),
(8, 'Traduction', 'Langues', 'traduction', 1),
(9, 'Rapports et analyses', 'Bureautique', 'rapports-analyses', 1);

-- --------------------------------------------------------

--
-- Structure de la table `demandes_assistance`
--

DROP TABLE IF EXISTS `demandes_assistance`;
CREATE TABLE IF NOT EXISTS `demandes_assistance` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` int UNSIGNED NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `competence_id` int UNSIGNED DEFAULT NULL,
  `duree_estimee_heures` decimal(3,2) NOT NULL,
  `urgence` enum('normale','urgent','tres_urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'normale',
  `statut` enum('ouverte','en_cours','terminee','annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ouverte',
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `client_id` (`client_id`),
  KEY `competence_id` (`competence_id`),
  KEY `statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `demandes_assistance`
--

INSERT INTO `demandes_assistance` (`id`, `client_id`, `titre`, `description`, `competence_id`, `duree_estimee_heures`, `urgence`, `statut`, `slug`, `created_at`, `updated_at`) VALUES
(7, 13, 'Correction bug Flutter sur liste déroulante', 'Une liste déroulante ne s\'affiche pas correctement sur Android. Besoin d\'un dev Flutter.', 5, 2.00, 'urgent', 'ouverte', 'correction-bug-flutter-liste', '2026-03-09 03:37:56', '2026-03-09 03:37:56'),
(8, 14, 'Maquette landing page startup', 'Création d\'une maquette Figma pour une landing page moderne.', 6, 3.00, 'normale', 'ouverte', 'maquette-landing-page-startup', '2026-03-09 03:37:56', '2026-03-09 03:37:56'),
(9, 15, 'Migration données Excel vers base', 'Aide pour structurer et migrer des données Excel vers une base.', 1, 1.50, 'normale', 'ouverte', 'migration-excel-vers-base', '2026-03-09 03:37:56', '2026-03-09 03:37:56');

-- --------------------------------------------------------

--
-- Structure de la table `demandes_retrait`
--

DROP TABLE IF EXISTS `demandes_retrait`;
CREATE TABLE IF NOT EXISTS `demandes_retrait` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `expert_id` int UNSIGNED NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `statut` enum('en_attente','traitee','refusee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `iban` varchar(34) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `traite_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expert_id` (`expert_id`),
  KEY `statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `expert_competences`
--

DROP TABLE IF EXISTS `expert_competences`;
CREATE TABLE IF NOT EXISTS `expert_competences` (
  `expert_id` int UNSIGNED NOT NULL,
  `competence_id` int UNSIGNED NOT NULL,
  `niveau` enum('debutant','intermediaire','avance','expert') COLLATE utf8mb4_unicode_ci DEFAULT 'intermediaire',
  PRIMARY KEY (`expert_id`,`competence_id`),
  KEY `competence_id` (`competence_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `expert_competences`
--

INSERT INTO `expert_competences` (`expert_id`, `competence_id`, `niveau`) VALUES
(7, 5, 'avance'),
(8, 6, 'expert'),
(9, 1, 'intermediaire');

-- --------------------------------------------------------

--
-- Structure de la table `growth_page_views`
--

DROP TABLE IF EXISTS `growth_page_views`;
CREATE TABLE IF NOT EXISTS `growth_page_views` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_type` enum('expert','job','blog') COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int UNSIGNED NOT NULL,
  `viewed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `session_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referer` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `page_type_entity` (`page_type`,`entity_id`),
  KEY `viewed_at` (`viewed_at`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `growth_page_views`
--

INSERT INTO `growth_page_views` (`id`, `page_type`, `entity_id`, `viewed_at`, `session_id`, `referer`) VALUES
(1, 'expert', 7, '2026-03-09 03:37:56', NULL, 'https://www.google.com/'),
(2, 'expert', 8, '2026-03-09 03:37:56', NULL, 'https://www.google.com/'),
(3, 'expert', 9, '2026-03-09 03:37:56', NULL, 'https://twitter.com/'),
(4, 'job', 7, '2026-03-09 03:37:56', NULL, 'https://www.google.com/'),
(5, 'job', 8, '2026-03-09 03:37:56', NULL, 'https://www.linkedin.com/'),
(6, 'job', 9, '2026-03-09 03:37:56', NULL, NULL),
(7, 'blog', 1, '2026-03-09 03:37:56', NULL, 'https://www.google.com/'),
(8, 'blog', 2, '2026-03-09 03:37:56', NULL, 'https://www.facebook.com/'),
(9, 'blog', 3, '2026-03-09 03:37:56', NULL, NULL),
(10, 'expert', 7, '2026-03-09 03:38:36', NULL, 'http://localhost/globalo/public/experts'),
(11, 'expert', 8, '2026-03-09 03:40:06', NULL, 'http://localhost/globalo/public/experts');

-- --------------------------------------------------------

--
-- Structure de la table `litiges`
--

DROP TABLE IF EXISTS `litiges`;
CREATE TABLE IF NOT EXISTS `litiges` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` int UNSIGNED NOT NULL,
  `ouvert_par` int UNSIGNED NOT NULL COMMENT 'utilisateur_id (client ou expert)',
  `statut` enum('ouvert','rembourse_client','libere_expert','clos') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ouvert',
  `motif` text COLLATE utf8mb4_unicode_ci,
  `decision_admin` text COLLATE utf8mb4_unicode_ci,
  `decide_par` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `traite_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reservation_id` (`reservation_id`),
  KEY `statut` (`statut`),
  KEY `fk_litige_ouvert` (`ouvert_par`),
  KEY `fk_litige_decide` (`decide_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` int UNSIGNED NOT NULL,
  `expediteur_id` int UNSIGNED NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `expediteur_id` (`expediteur_id`),
  KEY `lu` (`lu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mission_urgence`
--

DROP TABLE IF EXISTS `mission_urgence`;
CREATE TABLE IF NOT EXISTS `mission_urgence` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `demande_id` int UNSIGNED NOT NULL,
  `statut` enum('en_attente','acceptee','expiree') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `expert_id` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `accepte_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `demande_id` (`demande_id`),
  KEY `statut` (`statut`),
  KEY `fk_mission_urgence_expert` (`expert_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int UNSIGNED NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titre` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenu` text COLLATE utf8mb4_unicode_ci,
  `lien` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lu` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `lu` (`lu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

DROP TABLE IF EXISTS `paiements`;
CREATE TABLE IF NOT EXISTS `paiements` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` int UNSIGNED DEFAULT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `expert_id` int UNSIGNED DEFAULT NULL,
  `type` enum('depot','paiement_session','commission','retrait','remboursement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `commission_plateforme` decimal(12,2) DEFAULT '0.00',
  `montant_net_expert` decimal(12,2) DEFAULT NULL,
  `statut` enum('en_attente','effectue','echoue','annule','rembourse') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `statut_escrow` enum('bloque','libere','rembourse') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `libere_at` datetime DEFAULT NULL,
  `reference_externe` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `client_id` (`client_id`),
  KEY `expert_id` (`expert_id`),
  KEY `type` (`type`),
  KEY `statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parametres`
--

DROP TABLE IF EXISTS `parametres`;
CREATE TABLE IF NOT EXISTS `parametres` (
  `cle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres`
--

INSERT INTO `parametres` (`cle`, `valeur`, `updated_at`) VALUES
('chatbot_enabled', '1', '2026-03-09 02:25:43'),
('chatbot_max_history_messages', '20', '2026-03-09 02:25:43'),
('chatbot_openai_api_key', '', '2026-03-09 02:25:43'),
('commission_pourcent', '15', '2026-03-09 01:14:49'),
('commission_premium_pourcent', '15', '2026-03-09 02:09:47'),
('devise_plateforme', 'XOF', '2026-03-09 02:09:47'),
('maintenance', '0', '2026-03-09 01:14:49'),
('paiement_moyens', 'orange_money,tmoney,cb_portefeuille', '2026-03-09 02:09:47'),
('plateforme_email', 'contact@globalo.fr', '2026-03-09 01:14:49'),
('plateforme_nom', 'GLOBALO', '2026-03-09 01:14:49'),
('referral_reward_after', 'email_verified', '2026-03-09 03:26:24'),
('referral_reward_filleul', '500', '2026-03-09 03:26:24'),
('referral_reward_parrain', '500', '2026-03-09 03:26:24');

-- --------------------------------------------------------

--
-- Structure de la table `parrainages`
--

DROP TABLE IF EXISTS `parrainages`;
CREATE TABLE IF NOT EXISTS `parrainages` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `parrain_id` int UNSIGNED NOT NULL COMMENT 'Utilisateur qui invite',
  `filleul_id` int UNSIGNED DEFAULT NULL COMMENT 'Invitée (rempli à l''inscription)',
  `code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_invite` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('envoye','inscrit','recompense_parrain','recompense_filleul') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'envoye',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `inscrit_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `parrain_id` (`parrain_id`),
  KEY `filleul_id` (`filleul_id`),
  KEY `statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parrainages`
--

INSERT INTO `parrainages` (`id`, `parrain_id`, `filleul_id`, `code`, `email_invite`, `statut`, `created_at`, `inscrit_at`) VALUES
(4, 13, NULL, 'seedref13a', NULL, 'envoye', '2026-03-09 03:37:56', NULL),
(5, 14, NULL, 'seedref14b', NULL, 'inscrit', '2026-03-09 03:37:56', NULL),
(6, 15, NULL, 'seedref15c', NULL, 'envoye', '2026-03-09 03:37:56', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `pieces_jointes`
--

DROP TABLE IF EXISTS `pieces_jointes`;
CREATE TABLE IF NOT EXISTS `pieces_jointes` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` int UNSIGNED NOT NULL,
  `nom_fichier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chemin` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `taille` int UNSIGNED NOT NULL,
  `type_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `portefeuilles`
--

DROP TABLE IF EXISTS `portefeuilles`;
CREATE TABLE IF NOT EXISTS `portefeuilles` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int UNSIGNED NOT NULL,
  `solde` decimal(12,2) NOT NULL DEFAULT '0.00',
  `devise` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `profils_experts`
--

DROP TABLE IF EXISTS `profils_experts`;
CREATE TABLE IF NOT EXISTS `profils_experts` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int UNSIGNED NOT NULL,
  `titre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `tarif_horaire` decimal(10,2) NOT NULL DEFAULT '0.00',
  `disponible` tinyint(1) NOT NULL DEFAULT '0',
  `certifie` tinyint(1) NOT NULL DEFAULT '0',
  `valide_par_admin` tinyint(1) NOT NULL DEFAULT '0',
  `niveau_experience` enum('debutant','intermediaire','confirme','expert') COLLATE utf8mb4_unicode_ci DEFAULT 'intermediaire',
  `note_moyenne` decimal(3,2) DEFAULT NULL,
  `nombre_avis` int UNSIGNED NOT NULL DEFAULT '0',
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `utilisateur_id` (`utilisateur_id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `disponible` (`disponible`),
  KEY `valide_par_admin` (`valide_par_admin`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `profils_experts`
--

INSERT INTO `profils_experts` (`id`, `utilisateur_id`, `titre`, `description`, `tarif_horaire`, `disponible`, `certifie`, `valide_par_admin`, `niveau_experience`, `note_moyenne`, `nombre_avis`, `slug`, `created_at`, `updated_at`) VALUES
(7, 16, 'Développeur Flutter senior', 'Expert Flutter et Dart, 5 ans d\'expérience. Apps mobile iOS/Android.', 45.00, 1, 0, 1, 'expert', NULL, 0, 'amadou-flutter-developer', '2026-03-09 03:37:56', '2026-03-09 03:37:56'),
(8, 17, 'Designer UI/UX', 'Design d\'interfaces et expérience utilisateur. Figma, Adobe XD.', 40.00, 1, 0, 1, 'confirme', NULL, 0, 'fatou-designer-ui-ux', '2026-03-09 03:37:56', '2026-03-09 03:37:56'),
(9, 18, 'Consultant IT & Support', 'Infrastructure, dépannage, migration cloud.', 35.00, 1, 0, 1, 'intermediaire', NULL, 0, 'ibrahima-consultant-it', '2026-03-09 03:37:56', '2026-03-09 03:37:56');

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

DROP TABLE IF EXISTS `reservations`;
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `demande_id` int UNSIGNED NOT NULL,
  `expert_id` int UNSIGNED NOT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `date_debut_prevue` datetime NOT NULL,
  `duree_heures` decimal(3,2) NOT NULL,
  `tarif_horaire` decimal(10,2) NOT NULL,
  `montant_total` decimal(10,2) NOT NULL,
  `statut` enum('en_attente','acceptee','en_cours','terminee','annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en_attente',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `demande_id` (`demande_id`),
  KEY `expert_id` (`expert_id`),
  KEY `client_id` (`client_id`),
  KEY `statut` (`statut`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`id`, `demande_id`, `expert_id`, `client_id`, `date_debut_prevue`, `duree_heures`, `tarif_horaire`, `montant_total`, `statut`, `created_at`, `updated_at`) VALUES
(7, 7, 7, 13, '2026-03-09 03:37:56', 2.00, 45.00, 90.00, 'terminee', '2026-03-09 03:37:56', '2026-03-09 03:37:56'),
(8, 8, 8, 14, '2026-03-09 03:37:56', 3.00, 40.00, 120.00, 'terminee', '2026-03-09 03:37:56', '2026-03-09 03:37:56'),
(9, 9, 9, 15, '2026-03-09 03:37:56', 1.50, 35.00, 52.50, 'terminee', '2026-03-09 03:37:56', '2026-03-09 03:37:56');

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` int UNSIGNED NOT NULL,
  `debut_reel` datetime DEFAULT NULL,
  `fin_reel` datetime DEFAULT NULL,
  `duree_minutes` int UNSIGNED DEFAULT NULL,
  `statut` enum('planifiee','en_cours','terminee','annulee') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'planifiee',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reservation_id` (`reservation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `session_achievements`
--

DROP TABLE IF EXISTS `session_achievements`;
CREATE TABLE IF NOT EXISTS `session_achievements` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` int UNSIGNED NOT NULL,
  `expert_id` int UNSIGNED NOT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `titre_session` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` tinyint UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reservation_id` (`reservation_id`),
  KEY `expert_id` (`expert_id`),
  KEY `fk_achievement_client` (`client_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `session_achievements`
--

INSERT INTO `session_achievements` (`id`, `reservation_id`, `expert_id`, `client_id`, `titre_session`, `note`, `created_at`) VALUES
(1, 7, 7, 13, 'Correction bug Flutter liste déroulante', 5, '2026-03-09 03:37:56'),
(2, 8, 8, 14, 'Maquette landing page startup', 4, '2026-03-09 03:37:56'),
(3, 9, 9, 15, 'Migration Excel vers base', 5, '2026-03-09 03:37:56');

-- --------------------------------------------------------

--
-- Structure de la table `signalements`
--

DROP TABLE IF EXISTS `signalements`;
CREATE TABLE IF NOT EXISTS `signalements` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `signaleur_id` int UNSIGNED NOT NULL,
  `cible_type` enum('utilisateur','reservation','message','avis') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cible_id` int UNSIGNED NOT NULL,
  `motif` text COLLATE utf8mb4_unicode_ci,
  `statut` enum('nouveau','en_cours','traite','rejete') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nouveau',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `traite_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `signaleur_id` (`signaleur_id`),
  KEY `statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `solde_plateforme`
--

DROP TABLE IF EXISTS `solde_plateforme`;
CREATE TABLE IF NOT EXISTS `solde_plateforme` (
  `id` tinyint UNSIGNED NOT NULL DEFAULT '1',
  `solde` decimal(14,2) NOT NULL DEFAULT '0.00',
  `devise` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'XOF',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `solde_plateforme`
--

INSERT INTO `solde_plateforme` (`id`, `solde`, `devise`, `updated_at`) VALUES
(1, 0.00, 'XOF', '2026-03-09 02:09:47');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('client','expert','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'client',
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verifie` tinyint(1) NOT NULL DEFAULT '0',
  `token_verification` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_verification_expire` datetime DEFAULT NULL,
  `token_reinitialisation` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_reinit_expire` datetime DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `derniere_connexion` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `actif` (`actif`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `email`, `mot_de_passe`, `role`, `nom`, `prenom`, `telephone`, `avatar`, `email_verifie`, `token_verification`, `token_verification_expire`, `token_reinitialisation`, `token_reinit_expire`, `actif`, `derniere_connexion`, `created_at`, `updated_at`) VALUES
(13, 'growth-client1@test.globalo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'Dupont', 'Marie', NULL, NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, '2026-03-09 03:37:56', '2026-03-09 03:37:56'),
(14, 'growth-client2@test.globalo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'Martin', 'Luc', NULL, NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, '2026-03-09 03:37:56', '2026-03-09 03:37:56'),
(15, 'growth-client3@test.globalo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'Bernard', 'Sophie', NULL, NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, '2026-03-09 03:37:56', '2026-03-09 03:37:56'),
(16, 'growth-expert1@test.globalo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'expert', 'Diallo', 'Amadou', NULL, NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, '2026-03-09 03:37:56', '2026-03-09 03:37:56'),
(17, 'growth-expert2@test.globalo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'expert', 'Traoré', 'Fatou', NULL, NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, '2026-03-09 03:37:56', '2026-03-09 03:37:56'),
(18, 'growth-expert3@test.globalo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'expert', 'Sow', 'Ibrahima', NULL, NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, '2026-03-09 03:37:56', '2026-03-09 03:37:56'),
(19, 'admin@globalo.local', 'TON_HASH_ICI', 'admin', 'Admin', 'Globalo', NULL, NULL, 1, NULL, NULL, NULL, NULL, 1, NULL, '2026-03-09 03:47:27', '2026-03-09 03:47:27');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `fk_admin_log_utilisateur` FOREIGN KEY (`admin_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `avis_notes`
--
ALTER TABLE `avis_notes`
  ADD CONSTRAINT `fk_avis_client` FOREIGN KEY (`client_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_avis_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_avis_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `fk_blog_author` FOREIGN KEY (`author_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_blog_category` FOREIGN KEY (`category_id`) REFERENCES `blog_categories` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `blog_post_tags`
--
ALTER TABLE `blog_post_tags`
  ADD CONSTRAINT `fk_bpt_post` FOREIGN KEY (`post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bpt_tag` FOREIGN KEY (`tag_id`) REFERENCES `blog_tags` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `chatbot_conversations`
--
ALTER TABLE `chatbot_conversations`
  ADD CONSTRAINT `fk_chatbot_conv_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `chatbot_messages`
--
ALTER TABLE `chatbot_messages`
  ADD CONSTRAINT `fk_chatbot_msg_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `chatbot_conversations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commission_config`
--
ALTER TABLE `commission_config`
  ADD CONSTRAINT `fk_commission_expert` FOREIGN KEY (`expert_profil_id`) REFERENCES `profils_experts` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `demandes_assistance`
--
ALTER TABLE `demandes_assistance`
  ADD CONSTRAINT `fk_demande_client` FOREIGN KEY (`client_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_demande_competence` FOREIGN KEY (`competence_id`) REFERENCES `competences` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `demandes_retrait`
--
ALTER TABLE `demandes_retrait`
  ADD CONSTRAINT `fk_retrait_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `expert_competences`
--
ALTER TABLE `expert_competences`
  ADD CONSTRAINT `fk_ec_competence` FOREIGN KEY (`competence_id`) REFERENCES `competences` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ec_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `litiges`
--
ALTER TABLE `litiges`
  ADD CONSTRAINT `fk_litige_decide` FOREIGN KEY (`decide_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_litige_ouvert` FOREIGN KEY (`ouvert_par`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_litige_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_message_expediteur` FOREIGN KEY (`expediteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_message_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `mission_urgence`
--
ALTER TABLE `mission_urgence`
  ADD CONSTRAINT `fk_mission_urgence_demande` FOREIGN KEY (`demande_id`) REFERENCES `demandes_assistance` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mission_urgence_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `fk_paiement_client` FOREIGN KEY (`client_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_paiement_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_paiement_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `parrainages`
--
ALTER TABLE `parrainages`
  ADD CONSTRAINT `fk_parrain_filleul` FOREIGN KEY (`filleul_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_parrain_parrain` FOREIGN KEY (`parrain_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `pieces_jointes`
--
ALTER TABLE `pieces_jointes`
  ADD CONSTRAINT `fk_pj_message` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `portefeuilles`
--
ALTER TABLE `portefeuilles`
  ADD CONSTRAINT `fk_portefeuille_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `profils_experts`
--
ALTER TABLE `profils_experts`
  ADD CONSTRAINT `fk_profil_expert_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_reservation_client` FOREIGN KEY (`client_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reservation_demande` FOREIGN KEY (`demande_id`) REFERENCES `demandes_assistance` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reservation_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `fk_session_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `session_achievements`
--
ALTER TABLE `session_achievements`
  ADD CONSTRAINT `fk_achievement_client` FOREIGN KEY (`client_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_achievement_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_achievement_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `signalements`
--
ALTER TABLE `signalements`
  ADD CONSTRAINT `fk_signalement_utilisateur` FOREIGN KEY (`signaleur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
