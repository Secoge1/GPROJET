-- GLOBALO - Schéma de base de données
-- Plateforme d'assistance professionnelle à la demande
-- Charset: utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table: utilisateurs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `role` ENUM('client', 'expert', 'admin') NOT NULL DEFAULT 'client',
  `nom` VARCHAR(100) NOT NULL,
  `prenom` VARCHAR(100) NOT NULL,
  `telephone` VARCHAR(20) DEFAULT NULL,
  `pays` VARCHAR(50) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `piece_identite` VARCHAR(255) DEFAULT NULL COMMENT 'Chemin fichier pièce d''identité',
  `email_verifie` TINYINT(1) NOT NULL DEFAULT 0,
  `token_verification` VARCHAR(64) DEFAULT NULL,
  `token_verification_expire` DATETIME DEFAULT NULL,
  `token_reinitialisation` VARCHAR(64) DEFAULT NULL,
  `token_reinit_expire` DATETIME DEFAULT NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `derniere_connexion` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `actif` (`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: profils_experts
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `profils_experts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `titre` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `tarif_horaire` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `disponible` TINYINT(1) NOT NULL DEFAULT 0,
  `certifie` TINYINT(1) NOT NULL DEFAULT 0,
  `valide_par_admin` TINYINT(1) NOT NULL DEFAULT 0,
  `niveau_experience` ENUM('debutant', 'intermediaire', 'confirme', 'expert') DEFAULT 'intermediaire',
  `note_moyenne` DECIMAL(3,2) DEFAULT NULL,
  `nombre_avis` INT UNSIGNED NOT NULL DEFAULT 0,
  `competences_autres` VARCHAR(255) DEFAULT NULL COMMENT 'Précision quand compétence Autres est cochée',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `utilisateur_id` (`utilisateur_id`),
  KEY `disponible` (`disponible`),
  KEY `valide_par_admin` (`valide_par_admin`),
  CONSTRAINT `fk_profil_expert_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: competences
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `competences` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` VARCHAR(100) NOT NULL,
  `categorie` VARCHAR(80) DEFAULT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `categorie` (`categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: expert_competences (liaison expert <-> compétences)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `expert_competences` (
  `expert_id` INT UNSIGNED NOT NULL,
  `competence_id` INT UNSIGNED NOT NULL,
  `niveau` ENUM('debutant', 'intermediaire', 'avance', 'expert') DEFAULT 'intermediaire',
  PRIMARY KEY (`expert_id`, `competence_id`),
  KEY `competence_id` (`competence_id`),
  CONSTRAINT `fk_ec_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ec_competence` FOREIGN KEY (`competence_id`) REFERENCES `competences` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: demandes_assistance
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `demandes_assistance` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `client_id` INT UNSIGNED NOT NULL,
  `titre` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `competence_id` INT UNSIGNED DEFAULT NULL,
  `duree_estimee_heures` DECIMAL(3,2) NOT NULL,
  `urgence` ENUM('normale', 'urgent', 'tres_urgent') DEFAULT 'normale',
  `statut` ENUM('ouverte', 'en_cours', 'terminee', 'annulee') NOT NULL DEFAULT 'ouverte',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `competence_id` (`competence_id`),
  KEY `statut` (`statut`),
  CONSTRAINT `fk_demande_client` FOREIGN KEY (`client_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_demande_competence` FOREIGN KEY (`competence_id`) REFERENCES `competences` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: reservations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reservations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `demande_id` INT UNSIGNED NOT NULL,
  `expert_id` INT UNSIGNED NOT NULL,
  `client_id` INT UNSIGNED NOT NULL,
  `date_debut_prevue` DATETIME NOT NULL,
  `duree_heures` DECIMAL(3,2) NOT NULL,
  `tarif_horaire` DECIMAL(10,2) NOT NULL,
  `montant_total` DECIMAL(10,2) NOT NULL,
  `statut` ENUM('en_attente', 'acceptee', 'en_cours', 'terminee', 'annulee', 'payee') NOT NULL DEFAULT 'en_attente',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `demande_id` (`demande_id`),
  KEY `expert_id` (`expert_id`),
  KEY `client_id` (`client_id`),
  KEY `statut` (`statut`),
  CONSTRAINT `fk_reservation_demande` FOREIGN KEY (`demande_id`) REFERENCES `demandes_assistance` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reservation_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reservation_client` FOREIGN KEY (`client_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: sessions (sessions de travail / créneaux)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` INT UNSIGNED NOT NULL,
  `debut_reel` DATETIME DEFAULT NULL,
  `fin_reel` DATETIME DEFAULT NULL,
  `duree_minutes` INT UNSIGNED DEFAULT NULL,
  `statut` ENUM('planifiee', 'en_cours', 'terminee', 'annulee') NOT NULL DEFAULT 'planifiee',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reservation_id` (`reservation_id`),
  CONSTRAINT `fk_session_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: portefeuilles
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `portefeuilles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `solde` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `devise` VARCHAR(3) NOT NULL DEFAULT 'XOF',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `utilisateur_id` (`utilisateur_id`),
  CONSTRAINT `fk_portefeuille_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: paiements
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `paiements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` INT UNSIGNED DEFAULT NULL,
  `client_id` INT UNSIGNED NOT NULL,
  `expert_id` INT UNSIGNED DEFAULT NULL,
  `type` ENUM('depot', 'paiement_session', 'commission', 'retrait', 'remboursement') NOT NULL,
  `montant` DECIMAL(12,2) NOT NULL,
  `commission_plateforme` DECIMAL(12,2) DEFAULT 0.00,
  `montant_net_expert` DECIMAL(12,2) DEFAULT NULL,
  `statut` ENUM('en_attente', 'effectue', 'echoue', 'annule', 'rembourse') NOT NULL DEFAULT 'en_attente',
  `reference_externe` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `client_id` (`client_id`),
  KEY `expert_id` (`expert_id`),
  KEY `type` (`type`),
  KEY `statut` (`statut`),
  CONSTRAINT `fk_paiement_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_paiement_client` FOREIGN KEY (`client_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_paiement_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: demandes_retrait (experts)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `demandes_retrait` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `expert_id` INT UNSIGNED NOT NULL,
  `montant` DECIMAL(12,2) NOT NULL,
  `statut` ENUM('en_attente', 'traitee', 'refusee') NOT NULL DEFAULT 'en_attente',
  `iban` VARCHAR(34) DEFAULT NULL,
  `reference` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `traite_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expert_id` (`expert_id`),
  KEY `statut` (`statut`),
  CONSTRAINT `fk_retrait_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: avis_notes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `avis_notes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` INT UNSIGNED NOT NULL,
  `client_id` INT UNSIGNED NOT NULL,
  `expert_id` INT UNSIGNED NOT NULL,
  `note` TINYINT UNSIGNED NOT NULL CHECK (`note` BETWEEN 1 AND 5),
  `commentaire` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reservation_id` (`reservation_id`),
  KEY `expert_id` (`expert_id`),
  KEY `client_id` (`client_id`),
  CONSTRAINT `fk_avis_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_avis_client` FOREIGN KEY (`client_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_avis_expert` FOREIGN KEY (`expert_id`) REFERENCES `profils_experts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: messages
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `reservation_id` INT UNSIGNED NOT NULL,
  `expediteur_id` INT UNSIGNED NOT NULL,
  `contenu` TEXT NOT NULL,
  `lu` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reservation_id` (`reservation_id`),
  KEY `expediteur_id` (`expediteur_id`),
  KEY `lu` (`lu`),
  CONSTRAINT `fk_message_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_message_expediteur` FOREIGN KEY (`expediteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: pieces_jointes (fichiers dans les messages)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pieces_jointes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `message_id` INT UNSIGNED NOT NULL,
  `nom_fichier` VARCHAR(255) NOT NULL,
  `chemin` VARCHAR(500) NOT NULL,
  `taille` INT UNSIGNED NOT NULL,
  `type_mime` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`),
  CONSTRAINT `fk_pj_message` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: notifications
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `titre` VARCHAR(200) NOT NULL,
  `contenu` TEXT,
  `lien` VARCHAR(500) DEFAULT NULL,
  `lu` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `lu` (`lu`),
  CONSTRAINT `fk_notif_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: admin_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `cible_type` VARCHAR(50) DEFAULT NULL,
  `cible_id` INT UNSIGNED DEFAULT NULL,
  `details` JSON DEFAULT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `action` (`action`),
  CONSTRAINT `fk_admin_log_utilisateur` FOREIGN KEY (`admin_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: parametres (config plateforme)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `parametres` (
  `cle` VARCHAR(100) NOT NULL,
  `valeur` TEXT,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: signalements
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `signalements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `signaleur_id` INT UNSIGNED NOT NULL,
  `cible_type` ENUM('utilisateur', 'reservation', 'message', 'avis') NOT NULL,
  `cible_id` INT UNSIGNED NOT NULL,
  `motif` TEXT,
  `statut` ENUM('nouveau', 'en_cours', 'traite', 'rejete') NOT NULL DEFAULT 'nouveau',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `traite_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `signaleur_id` (`signaleur_id`),
  KEY `statut` (`statut`),
  CONSTRAINT `fk_signalement_utilisateur` FOREIGN KEY (`signaleur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données initiales: compétences
INSERT INTO `competences` (`nom`, `categorie`, `slug`, `actif`) VALUES
('Excel', 'Bureautique', 'excel', 1),
('Word', 'Bureautique', 'word', 1),
('Access', 'Bureautique', 'access', 1),
('Rédaction professionnelle', 'Rédaction', 'redaction-professionnelle', 1),
('Présentations', 'Bureautique', 'presentations', 1),
('Développement web', 'Tech', 'developpement-web', 1),
('Design graphique', 'Design', 'design-graphique', 1),
('Comptabilité', 'Finance', 'comptabilite', 1),
('Traduction', 'Langues', 'traduction', 1),
('Rapports et analyses', 'Bureautique', 'rapports-analyses', 1),
('Autres', 'Autres', 'autres', 1);

-- Paramètres par défaut
INSERT INTO `parametres` (`cle`, `valeur`) VALUES
('commission_pourcent', '15'),
('plateforme_nom', 'GLOBALO'),
('plateforme_email', 'contact@globalo.fr'),
('maintenance', '0');

SET FOREIGN_KEY_CHECKS = 1;
