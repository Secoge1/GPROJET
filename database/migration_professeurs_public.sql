-- =============================================================================
-- GLOBALO - Migration : Profils publics des professeurs et sessions réservables
-- Exécuter après migration_professeur.sql
-- Permet aux étudiants de voir les professeurs inscrits et de réserver des sessions
-- =============================================================================

-- Table profils_professeurs : profil public pour les professeurs (comme profils_experts)
CREATE TABLE IF NOT EXISTS `profils_professeurs` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `utilisateur_id`    INT UNSIGNED NOT NULL,
    `titre`             VARCHAR(150) NOT NULL COMMENT 'Ex: Professeur de Mathématiques',
    `description`       TEXT DEFAULT NULL,
    `tarif_horaire`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `disponible`        TINYINT(1) NOT NULL DEFAULT 1,
    `valide_par_admin`  TINYINT(1) NOT NULL DEFAULT 0,
    `note_moyenne`      DECIMAL(3,2) DEFAULT NULL,
    `nombre_avis`       INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_profil_professeur_user` (`utilisateur_id`),
    KEY `idx_disponible` (`disponible`),
    KEY `idx_valide` (`valide_par_admin`),
    CONSTRAINT `fk_profil_professeur_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table sessions_professeurs : réservations étudiant ↔ professeur
CREATE TABLE IF NOT EXISTS `sessions_professeurs` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `etudiant_id`       INT UNSIGNED NOT NULL,
    `professeur_id`     INT UNSIGNED NOT NULL COMMENT 'utilisateur_id du professeur',
    `matiere_id`        INT UNSIGNED DEFAULT NULL,
    `date_debut_prevue` DATETIME NOT NULL,
    `duree_heures`      DECIMAL(3,2) NOT NULL,
    `tarif_horaire`     DECIMAL(10,2) NOT NULL,
    `montant_total`     DECIMAL(10,2) NOT NULL,
    `statut`            ENUM('en_attente','acceptee','en_cours','terminee','annulee') NOT NULL DEFAULT 'en_attente',
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_etudiant` (`etudiant_id`),
    KEY `idx_professeur` (`professeur_id`),
    KEY `idx_statut` (`statut`),
    CONSTRAINT `fk_session_prof_etudiant` FOREIGN KEY (`etudiant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_session_prof_professeur` FOREIGN KEY (`professeur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_session_prof_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres_universitaires` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Créer automatiquement un profil_professeur pour chaque professeur existant (disponible=0, valide=0 par défaut)
INSERT IGNORE INTO `profils_professeurs` (`utilisateur_id`, `titre`, `tarif_horaire`, `disponible`, `valide_par_admin`, `created_at`, `updated_at`)
SELECT u.id, CONCAT('Professeur - ', u.prenom, ' ', u.nom), 0, 0, 0, NOW(), NOW()
FROM utilisateurs u
WHERE u.role = 'professeur'
  AND NOT EXISTS (SELECT 1 FROM profils_professeurs pp WHERE pp.utilisateur_id = u.id);

-- -----------------------------------------------------------------------------
-- Matières par profil (filtre public + admin + fiche)
-- Si vous aviez déjà exécuté ce fichier avant cette section : exécutez
-- database/migration_professeur_matieres.sql seul (CREATE TABLE identique).
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `professeur_matieres` (
    `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `profil_professeur_id`  INT UNSIGNED NOT NULL,
    `matiere_id`            INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_professeur_matiere` (`profil_professeur_id`, `matiere_id`),
    KEY `idx_pm_matiere` (`matiere_id`),
    CONSTRAINT `fk_pm_profil_prof` FOREIGN KEY (`profil_professeur_id`) REFERENCES `profils_professeurs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pm_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres_universitaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `professeur_matieres` (`profil_professeur_id`, `matiere_id`)
SELECT pp.id, em.matiere_id
FROM `profils_professeurs` pp
INNER JOIN `profils_etudiants` pe ON pe.utilisateur_id = pp.utilisateur_id
INNER JOIN `etudiant_matieres` em ON em.profil_etudiant_id = pe.id;
