-- =============================================================================
-- GLOBALO — Migration complète : Rôles Étudiant & Professeur
-- =============================================================================
-- À exécuter dans phpMyAdmin (onglet SQL ou Importer) sur la base globalo.
-- Ce fichier est idempotent : sûr à rejouer si partiellement exécuté.
--
-- Ordre d'exécution :
--   1. Modifier l'ENUM role (ajoute etudiant + professeur)
--   2. Tables matières, profils étudiants, exercices
--   3. Profils professeurs + sessions
--   4. Table de liaison professeur_matieres
--   5. Table demandes_retrait_prof
--   6. Données de référence (matières universitaires)
--   7. Création automatique des profils manquants
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. Modifier le champ role pour inclure etudiant + professeur
-- -----------------------------------------------------------------------------
ALTER TABLE `utilisateurs`
    MODIFY COLUMN `role`
        ENUM('client','expert','admin','etudiant','professeur')
        NOT NULL DEFAULT 'client';

-- -----------------------------------------------------------------------------
-- 2. Table des matières universitaires
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `matieres_universitaires` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom`         VARCHAR(150) NOT NULL,
    `code`        VARCHAR(20)  DEFAULT NULL,
    `filiere`     VARCHAR(100) NOT NULL,
    `categorie`   ENUM(
        'Sciences exactes',
        'Sciences de la vie',
        'Sciences humaines',
        'Sciences juridiques',
        'Sciences économiques',
        'Informatique & Numérique',
        'Lettres & Langues',
        'Santé & Médecine',
        'Agriculture & Environnement',
        'Architecture & BTP',
        'Autres'
    ) NOT NULL DEFAULT 'Autres',
    `slug`        VARCHAR(160) NOT NULL,
    `description` TEXT         DEFAULT NULL,
    `actif`       TINYINT(1)   NOT NULL DEFAULT 1,
    `ordre`       SMALLINT     NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_matiere_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Profils étudiants
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `profils_etudiants` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `utilisateur_id`    INT UNSIGNED NOT NULL,
    `universite`        VARCHAR(200) DEFAULT NULL,
    `pays`              VARCHAR(80)  DEFAULT NULL,
    `ville`             VARCHAR(100) DEFAULT NULL,
    `filiere`           VARCHAR(150) DEFAULT NULL,
    `niveau_etude`      ENUM('Licence 1','Licence 2','Licence 3','Master 1','Master 2','Doctorat','BTS','DUT','Autre') DEFAULT 'Licence 1',
    `annee_inscription` YEAR         DEFAULT NULL,
    `numero_etudiant`   VARCHAR(50)  DEFAULT NULL,
    `bio`               TEXT         DEFAULT NULL,
    `disponible`        TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`        DATETIME     NOT NULL,
    `updated_at`        DATETIME     NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_profil_etudiant` (`utilisateur_id`),
    CONSTRAINT `fk_profil_etudiant_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Matières maîtrisées par étudiant (many-to-many)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `etudiant_matieres` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `profil_etudiant_id` INT UNSIGNED NOT NULL,
    `matiere_id`         INT UNSIGNED NOT NULL,
    `niveau_maitrise`    ENUM('debutant','intermediaire','avance','expert') NOT NULL DEFAULT 'intermediaire',
    `note_obtenue`       DECIMAL(4,2) DEFAULT NULL,
    `created_at`         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_etud_matiere` (`profil_etudiant_id`, `matiere_id`),
    CONSTRAINT `fk_em_profil`  FOREIGN KEY (`profil_etudiant_id`) REFERENCES `profils_etudiants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_em_matiere` FOREIGN KEY (`matiere_id`)         REFERENCES `matieres_universitaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Exercices soumis par les étudiants
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exercices` (
    `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `etudiant_id`        INT UNSIGNED NOT NULL,
    `matiere_id`         INT UNSIGNED DEFAULT NULL,
    `titre`              VARCHAR(250) NOT NULL,
    `description`        TEXT         NOT NULL,
    `type_exercice`      ENUM('devoir','examen','tp','projet','dissertation','qcm','oral','autre') NOT NULL DEFAULT 'devoir',
    `niveau_difficulte`  ENUM('facile','moyen','difficile','tres_difficile') NOT NULL DEFAULT 'moyen',
    `urgence`            ENUM('normale','urgent','tres_urgent') NOT NULL DEFAULT 'normale',
    `statut`             ENUM('ouvert','en_cours','correction_livree','resolu','annule') NOT NULL DEFAULT 'ouvert',
    `fichier`            VARCHAR(500)  DEFAULT NULL,
    `lien_ressource`     VARCHAR(1000) DEFAULT NULL,
    `date_limite`        DATETIME     DEFAULT NULL,
    `solution`           TEXT         DEFAULT NULL,
    `expert_id`          INT UNSIGNED DEFAULT NULL,
    `note_finale`        DECIMAL(4,2) DEFAULT NULL,
    `commentaire_expert` TEXT         DEFAULT NULL,
    `prix_correction`    DECIMAL(10,2) DEFAULT NULL,
    `paiement_statut`    ENUM('gratuit','en_attente','paye') NOT NULL DEFAULT 'gratuit',
    `paiement_reference` VARCHAR(100)  DEFAULT NULL,
    `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_exercice_etudiant` (`etudiant_id`),
    KEY `idx_exercice_matiere`  (`matiere_id`),
    KEY `idx_exercice_statut`   (`statut`),
    CONSTRAINT `fk_exercice_etudiant` FOREIGN KEY (`etudiant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_exercice_matiere`  FOREIGN KEY (`matiere_id`)  REFERENCES `matieres_universitaires` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. Propositions de correction (professeurs)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exercice_propositions` (
    `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `exercice_id`           INT UNSIGNED NOT NULL,
    `profil_professeur_id`  INT UNSIGNED NOT NULL,
    `presentation`          TEXT         DEFAULT NULL,
    `message`               TEXT         DEFAULT NULL,
    `tarif_propose`         DECIMAL(10,2) NOT NULL DEFAULT 0,
    `delai_jours`           TINYINT UNSIGNED NOT NULL DEFAULT 3,
    `competences_cles`      VARCHAR(500) DEFAULT NULL,
    `statut`                ENUM('en_attente','acceptee','refusee') NOT NULL DEFAULT 'en_attente',
    `created_at`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_prop_exercice_prof` (`exercice_id`, `profil_professeur_id`),
    KEY `idx_prop_prof` (`profil_professeur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. Profils publics professeurs
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `profils_professeurs` (
    `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `utilisateur_id`   INT UNSIGNED NOT NULL,
    `titre`            VARCHAR(150) NOT NULL,
    `description`      TEXT DEFAULT NULL,
    `tarif_horaire`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `disponible`       TINYINT(1) NOT NULL DEFAULT 0,
    `valide_par_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `note_moyenne`     DECIMAL(3,2) DEFAULT NULL,
    `nombre_avis`      INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_profil_professeur_user` (`utilisateur_id`),
    KEY `idx_disponible` (`disponible`),
    KEY `idx_valide` (`valide_par_admin`),
    CONSTRAINT `fk_profil_professeur_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. Sessions réservables étudiant ↔ professeur
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sessions_professeurs` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `etudiant_id`       INT UNSIGNED NOT NULL,
    `professeur_id`     INT UNSIGNED NOT NULL,
    `matiere_id`        INT UNSIGNED DEFAULT NULL,
    `date_debut_prevue` DATETIME NOT NULL,
    `duree_heures`      DECIMAL(3,2) NOT NULL,
    `tarif_horaire`     DECIMAL(10,2) NOT NULL,
    `montant_total`     DECIMAL(10,2) NOT NULL,
    `statut`            ENUM('en_attente','acceptee','en_cours','terminee','annulee') NOT NULL DEFAULT 'en_attente',
    `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_etudiant`  (`etudiant_id`),
    KEY `idx_professeur`(`professeur_id`),
    KEY `idx_statut`    (`statut`),
    CONSTRAINT `fk_session_prof_etudiant`   FOREIGN KEY (`etudiant_id`)  REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_session_prof_professeur` FOREIGN KEY (`professeur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_session_prof_matiere`    FOREIGN KEY (`matiere_id`)   REFERENCES `matieres_universitaires` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. Table de liaison professeur ↔ matières enseignées
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `professeur_matieres` (
    `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `profil_professeur_id` INT UNSIGNED NOT NULL,
    `matiere_id`           INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_professeur_matiere` (`profil_professeur_id`, `matiere_id`),
    KEY `idx_pm_matiere` (`matiere_id`),
    CONSTRAINT `fk_pm_profil_prof` FOREIGN KEY (`profil_professeur_id`) REFERENCES `profils_professeurs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pm_matiere`     FOREIGN KEY (`matiere_id`)          REFERENCES `matieres_universitaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 10. Demandes de retrait Mobile Money (professeurs)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `demandes_retrait_prof` (
    `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `utilisateur_id` INT UNSIGNED  NOT NULL,
    `montant`        DECIMAL(12,2) NOT NULL,
    `statut`         ENUM('en_attente','traitee','refusee') NOT NULL DEFAULT 'en_attente',
    `numero_wave`    VARCHAR(34)   DEFAULT NULL,
    `reference`      VARCHAR(100)  DEFAULT NULL,
    `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `traite_at`      DATETIME      DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_retrait_prof_user`   (`utilisateur_id`),
    KEY `idx_retrait_prof_statut` (`statut`),
    CONSTRAINT `fk_retrait_prof_user` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. Données de référence : Matières universitaires (Afrique de l'Ouest)
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `matieres_universitaires` (`nom`, `code`, `filiere`, `categorie`, `slug`, `ordre`) VALUES
('Mathématiques',                   'MATH',   'Sciences',                   'Sciences exactes',          'mathematiques',              1),
('Statistiques & Probabilités',     'STAT',   'Sciences',                   'Sciences exactes',          'statistiques-probabilites',  2),
('Physique générale',               'PHY',    'Sciences',                   'Sciences exactes',          'physique-generale',          3),
('Chimie générale',                 'CHIM',   'Sciences',                   'Sciences exactes',          'chimie-generale',            4),
('Algèbre linéaire',                'ALG',    'Sciences',                   'Sciences exactes',          'algebre-lineaire',           5),
('Analyse mathématique',            'ANA',    'Sciences',                   'Sciences exactes',          'analyse-mathematique',       6),
('Biologie cellulaire',             'BIO',    'Sciences naturelles',        'Sciences de la vie',        'biologie-cellulaire',        10),
('Écologie & Environnement',        'ECO',    'Sciences naturelles',        'Sciences de la vie',        'ecologie-environnement',     11),
('Génétique',                       'GEN',    'Sciences naturelles',        'Sciences de la vie',        'genetique',                  12),
('Microbiologie',                   'MICR',   'Sciences naturelles',        'Sciences de la vie',        'microbiologie',              13),
('Philosophie',                     'PHIL',   'Lettres & Sciences humaines','Sciences humaines',         'philosophie',                20),
('Sociologie',                      'SOC',    'Sciences humaines',          'Sciences humaines',         'sociologie',                 21),
('Histoire',                        'HIST',   'Sciences humaines',          'Sciences humaines',         'histoire',                   22),
('Géographie',                      'GEO',    'Sciences humaines',          'Sciences humaines',         'geographie',                 23),
('Psychologie',                     'PSY',    'Sciences humaines',          'Sciences humaines',         'psychologie',                24),
('Sciences de l\'éducation',        'EDUC',   'Sciences humaines',          'Sciences humaines',         'sciences-education',         25),
('Droit civil',                     'DC',     'Droit',                      'Sciences juridiques',       'droit-civil',                30),
('Droit des affaires',              'DA',     'Droit',                      'Sciences juridiques',       'droit-affaires',             31),
('Droit public',                    'DP',     'Droit',                      'Sciences juridiques',       'droit-public',               32),
('Droit international',             'DI',     'Droit',                      'Sciences juridiques',       'droit-international',        33),
('Sciences politiques',             'SCP',    'Droit & Science Po',         'Sciences juridiques',       'sciences-politiques',        34),
('Économie générale',               'ECN',    'Économie & Gestion',         'Sciences économiques',      'economie-generale',          40),
('Microéconomie',                   'MECN',   'Économie',                   'Sciences économiques',      'microeconomie',              41),
('Macroéconomie',                   'MACR',   'Économie',                   'Sciences économiques',      'macroeconomie',              42),
('Comptabilité générale',           'CMPT',   'Gestion',                    'Sciences économiques',      'comptabilite-generale',      43),
('Finance d\'entreprise',           'FIN',    'Gestion / Finance',          'Sciences économiques',      'finance-entreprise',         44),
('Marketing & Commerce',            'MKT',    'Gestion',                    'Sciences économiques',      'marketing-commerce',         45),
('Management des organisations',    'MGT',    'Gestion',                    'Sciences économiques',      'management-organisations',   46),
('Fiscalité & Droit fiscal',        'FISC',   'Droit / Gestion',            'Sciences économiques',      'fiscalite-droit-fiscal',     47),
('Algorithmique & Programmation',   'ALGO',   'Informatique',               'Informatique & Numérique',  'algorithmique-programmation', 50),
('Développement web',               'WEB',    'Informatique',               'Informatique & Numérique',  'developpement-web',          51),
('Bases de données',                'BD',     'Informatique',               'Informatique & Numérique',  'bases-donnees',              52),
('Réseaux & Télécommunications',    'NET',    'Informatique / Réseaux',     'Informatique & Numérique',  'reseaux-telecommunications', 53),
('Intelligence artificielle',       'IA',     'Informatique',               'Informatique & Numérique',  'intelligence-artificielle',  54),
('Systèmes d\'exploitation',        'SYS',    'Informatique',               'Informatique & Numérique',  'systemes-exploitation',      55),
('Littérature française',           'LITF',   'Lettres',                    'Lettres & Langues',         'litterature-francaise',      60),
('Linguistique',                    'LING',   'Lettres',                    'Lettres & Langues',         'linguistique',               61),
('Anglais',                         'ANG',    'Langues',                    'Lettres & Langues',         'anglais',                    62),
('Arabe',                           'ARB',    'Langues',                    'Lettres & Langues',         'arabe',                      63),
('Communication & Expression',      'COM',    'Lettres',                    'Lettres & Langues',         'communication-expression',   64),
('Anatomie',                        'ANAT',   'Médecine',                   'Santé & Médecine',          'anatomie',                   70),
('Pharmacologie',                   'PHARM',  'Pharmacie',                  'Santé & Médecine',          'pharmacologie',              71),
('Santé publique',                  'SPUB',   'Santé publique',             'Santé & Médecine',          'sante-publique',             72),
('Nutrition',                       'NUT',    'Santé',                      'Santé & Médecine',          'nutrition',                  73),
('Agronomie',                       'AGRO',   'Agriculture',                'Agriculture & Environnement','agronomie',                 80),
('Zootechnie',                      'ZOO',    'Élevage',                    'Agriculture & Environnement','zootechnie',                81),
('Gestion des ressources naturelles','GRN',   'Environnement',              'Agriculture & Environnement','gestion-ressources-naturelles',82),
('Hydraulique agricole',            'HYD',    'Agriculture / Génie rural',  'Agriculture & Environnement','hydraulique-agricole',      83),
('Architecture',                    'ARCH',   'Architecture',               'Architecture & BTP',        'architecture',               90),
('Génie civil',                     'GC',     'BTP',                        'Architecture & BTP',        'genie-civil',                91),
('Urbanisme & Aménagement',         'URB',    'Urbanisme',                  'Architecture & BTP',        'urbanisme-amenagement',      92),
('Autres matières',                 'AUTRE',  'Divers',                     'Autres',                    'autres-matieres',            99);

-- -----------------------------------------------------------------------------
-- 12. Créer profil étudiant pour les utilisateurs etudiant/professeur existants
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `profils_etudiants` (`utilisateur_id`, `created_at`, `updated_at`)
SELECT u.id, NOW(), NOW()
FROM utilisateurs u
WHERE u.role IN ('etudiant', 'professeur')
  AND NOT EXISTS (SELECT 1 FROM profils_etudiants pe WHERE pe.utilisateur_id = u.id);

-- -----------------------------------------------------------------------------
-- 13. Créer profil professeur pour les utilisateurs professeur existants
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `profils_professeurs` (`utilisateur_id`, `titre`, `tarif_horaire`, `disponible`, `valide_par_admin`, `created_at`, `updated_at`)
SELECT u.id, CONCAT('Professeur - ', u.prenom, ' ', u.nom), 0, 0, 0, NOW(), NOW()
FROM utilisateurs u
WHERE u.role = 'professeur'
  AND NOT EXISTS (SELECT 1 FROM profils_professeurs pp WHERE pp.utilisateur_id = u.id);

-- -----------------------------------------------------------------------------
-- 14. Corriger le rôle des utilisateurs qui ont un profil professeur
--     mais dont le role est encore 'client' ou '' (migration partielle)
-- -----------------------------------------------------------------------------
UPDATE `utilisateurs` u
INNER JOIN `profils_professeurs` pp ON pp.utilisateur_id = u.id
SET u.role = 'professeur'
WHERE u.role != 'professeur';

-- -----------------------------------------------------------------------------
-- 15. Corriger le rôle des utilisateurs qui ont un profil étudiant
--     mais dont le role est encore 'client' ou '' (migration partielle)
-- -----------------------------------------------------------------------------
UPDATE `utilisateurs` u
INNER JOIN `profils_etudiants` pe ON pe.utilisateur_id = u.id
SET u.role = 'etudiant'
WHERE u.role NOT IN ('etudiant', 'professeur');

-- -----------------------------------------------------------------------------
-- 16. Synchroniser professeur_matieres depuis etudiant_matieres
--     pour les professeurs qui ont mis à jour leur profil avant ce correctif
--     (corrige l'invisibilité des exercices étudiants côté professeur)
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `professeur_matieres` (`profil_professeur_id`, `matiere_id`)
SELECT pp.id, em.matiere_id
FROM `profils_professeurs` pp
INNER JOIN `utilisateurs` u  ON u.id = pp.utilisateur_id AND u.role = 'professeur'
INNER JOIN `profils_etudiants` pe ON pe.utilisateur_id = pp.utilisateur_id
INNER JOIN `etudiant_matieres` em ON em.profil_etudiant_id = pe.id
WHERE NOT EXISTS (
    SELECT 1 FROM `professeur_matieres` pm2
    WHERE pm2.profil_professeur_id = pp.id AND pm2.matiere_id = em.matiere_id
);

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- FIN — Migration complète professeur/étudiant appliquée avec succès.
-- =============================================================================
