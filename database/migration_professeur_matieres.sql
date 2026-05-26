-- =============================================================================
-- GLOBALO — Table professeur_matieres (matières enseignées par profil professeur)
-- =============================================================================
--
-- Si vous voyez l’erreur applicative :
--   « table professeur_matieres introuvable »
-- exécutez CE FICHIER sur la même base que l’application.
--
-- WAMP — phpMyAdmin :
--   1. Sélectionnez la base (ex. globalo)
--   2. Onglet « SQL » ou « Importer » → choisir ce fichier → Exécuter
--
-- Ligne de commande (adapter user, base) :
--   mysql -u root -p VOTRE_BASE < database/migration_professeur_matieres.sql
--
-- Prérequis : tables `profils_professeurs` et `matieres_universitaires` déjà créées
-- (migrations migration_professeurs_public.sql et migration_etudiant.sql).
-- =============================================================================

SET NAMES utf8mb4;

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

-- Reprise : compte à la fois professeur + étudiant avec matières renseignées
INSERT IGNORE INTO `professeur_matieres` (`profil_professeur_id`, `matiere_id`)
SELECT pp.id, em.matiere_id
FROM `profils_professeurs` pp
INNER JOIN `profils_etudiants` pe ON pe.utilisateur_id = pp.utilisateur_id
INNER JOIN `etudiant_matieres` em ON em.profil_etudiant_id = pe.id;
