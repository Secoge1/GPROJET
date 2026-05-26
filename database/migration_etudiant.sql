-- =============================================================================
-- GLOBALO - Migration : Statut Étudiant (Afrique de l'Ouest)
-- Exécuter après schema.sql
-- =============================================================================

-- 1. Ajouter le rôle 'etudiant' dans la table utilisateurs
ALTER TABLE `utilisateurs`
    MODIFY COLUMN `role` ENUM('client','expert','admin','etudiant') NOT NULL DEFAULT 'client';

-- 2. Table des matières universitaires (adaptée aux universités d'Afrique de l'Ouest)
CREATE TABLE IF NOT EXISTS `matieres_universitaires` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom`         VARCHAR(150) NOT NULL,
    `code`        VARCHAR(20)  DEFAULT NULL COMMENT 'Code matière (ex: MATH101)',
    `filiere`     VARCHAR(100) NOT NULL COMMENT 'Filière / département',
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

-- 3. Profils étudiants
CREATE TABLE IF NOT EXISTS `profils_etudiants` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `utilisateur_id`    INT UNSIGNED NOT NULL,
    `universite`        VARCHAR(200) DEFAULT NULL COMMENT 'Nom de l\'université ou école',
    `pays`              VARCHAR(80)  DEFAULT NULL COMMENT 'Pays (Afrique de l\'Ouest)',
    `ville`             VARCHAR(100) DEFAULT NULL,
    `filiere`           VARCHAR(150) DEFAULT NULL COMMENT 'Filière d\'études',
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

-- 4. Matières maîtrisées par l'étudiant (many-to-many)
CREATE TABLE IF NOT EXISTS `etudiant_matieres` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `profil_etudiant_id` INT UNSIGNED NOT NULL,
    `matiere_id`        INT UNSIGNED NOT NULL,
    `niveau_maitrise`   ENUM('debutant','intermediaire','avance','expert') NOT NULL DEFAULT 'intermediaire',
    `note_obtenue`      DECIMAL(4,2) DEFAULT NULL COMMENT 'Note sur 20 si connue',
    `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_etud_matiere` (`profil_etudiant_id`, `matiere_id`),
    CONSTRAINT `fk_em_profil` FOREIGN KEY (`profil_etudiant_id`) REFERENCES `profils_etudiants` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_em_matiere` FOREIGN KEY (`matiere_id`) REFERENCES `matieres_universitaires` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Exercices soumis par les étudiants
CREATE TABLE IF NOT EXISTS `exercices` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `etudiant_id`       INT UNSIGNED NOT NULL COMMENT 'utilisateur_id de l\'étudiant',
    `matiere_id`        INT UNSIGNED DEFAULT NULL,
    `titre`             VARCHAR(250) NOT NULL,
    `description`       TEXT         NOT NULL,
    `type_exercice`     ENUM('devoir','examen','tp','projet','dissertation','qcm','oral','autre') NOT NULL DEFAULT 'devoir',
    `niveau_difficulte` ENUM('facile','moyen','difficile','tres_difficile') NOT NULL DEFAULT 'moyen',
    `urgence`           ENUM('normale','urgent','tres_urgent') NOT NULL DEFAULT 'normale',
    `statut`            ENUM('ouvert','en_cours','correction_livree','resolu','annule') NOT NULL DEFAULT 'ouvert',
    `fichier`           VARCHAR(500) DEFAULT NULL COMMENT 'Chemin vers le fichier joint',
    `lien_ressource`    VARCHAR(1000) DEFAULT NULL COMMENT 'Lien externe (Drive, Moodle, etc.)',
    `date_limite`       DATETIME     DEFAULT NULL COMMENT 'Deadline de rendu',
    `solution`          TEXT         DEFAULT NULL COMMENT 'Solution apportée par l\'expert',
    `expert_id`         INT UNSIGNED DEFAULT NULL COMMENT 'Expert/tuteur qui traite l\'exercice',
    `note_finale`       DECIMAL(4,2) DEFAULT NULL COMMENT 'Note obtenue sur 20',
    `commentaire_expert` TEXT        DEFAULT NULL,
    `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_exercice_etudiant` (`etudiant_id`),
    KEY `idx_exercice_matiere`  (`matiere_id`),
    KEY `idx_exercice_statut`   (`statut`),
    CONSTRAINT `fk_exercice_etudiant` FOREIGN KEY (`etudiant_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_exercice_matiere`  FOREIGN KEY (`matiere_id`)  REFERENCES `matieres_universitaires` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Données de référence : Matières universitaires (Afrique de l'Ouest - UEMOA/CEDEAO)
-- =============================================================================

INSERT INTO `matieres_universitaires` (`nom`, `code`, `filiere`, `categorie`, `slug`, `ordre`) VALUES
-- Sciences exactes
('Mathématiques',                   'MATH',   'Sciences',                  'Sciences exactes',        'mathematiques',            1),
('Statistiques & Probabilités',     'STAT',   'Sciences',                  'Sciences exactes',        'statistiques-probabilites', 2),
('Physique générale',               'PHY',    'Sciences',                  'Sciences exactes',        'physique-generale',        3),
('Chimie générale',                 'CHIM',   'Sciences',                  'Sciences exactes',        'chimie-generale',          4),
('Algèbre linéaire',                'ALG',    'Sciences',                  'Sciences exactes',        'algebre-lineaire',         5),
('Analyse mathématique',            'ANA',    'Sciences',                  'Sciences exactes',        'analyse-mathematique',     6),
-- Sciences de la vie
('Biologie cellulaire',             'BIO',    'Sciences naturelles',       'Sciences de la vie',      'biologie-cellulaire',      10),
('Écologie & Environnement',        'ECO',    'Sciences naturelles',       'Sciences de la vie',      'ecologie-environnement',   11),
('Génétique',                       'GEN',    'Sciences naturelles',       'Sciences de la vie',      'genetique',                12),
('Microbiologie',                   'MICR',   'Sciences naturelles',       'Sciences de la vie',      'microbiologie',            13),
-- Sciences humaines
('Philosophie',                     'PHIL',   'Lettres & Sciences humaines','Sciences humaines',      'philosophie',              20),
('Sociologie',                      'SOC',    'Sciences humaines',          'Sciences humaines',      'sociologie',               21),
('Histoire',                        'HIST',   'Sciences humaines',          'Sciences humaines',      'histoire',                 22),
('Géographie',                      'GEO',    'Sciences humaines',          'Sciences humaines',      'geographie',               23),
('Psychologie',                     'PSY',    'Sciences humaines',          'Sciences humaines',      'psychologie',              24),
('Sciences de l\'éducation',        'EDUC',   'Sciences humaines',          'Sciences humaines',      'sciences-education',       25),
-- Sciences juridiques
('Droit civil',                     'DC',     'Droit',                     'Sciences juridiques',     'droit-civil',              30),
('Droit des affaires',              'DA',     'Droit',                     'Sciences juridiques',     'droit-affaires',           31),
('Droit public',                    'DP',     'Droit',                     'Sciences juridiques',     'droit-public',             32),
('Droit international',             'DI',     'Droit',                     'Sciences juridiques',     'droit-international',      33),
('Sciences politiques',             'SCP',    'Droit & Science Po',        'Sciences juridiques',     'sciences-politiques',      34),
-- Sciences économiques et gestion
('Économie générale',               'ECN',    'Économie & Gestion',        'Sciences économiques',    'economie-generale',        40),
('Microéconomie',                   'MECN',   'Économie',                  'Sciences économiques',    'microeconomie',            41),
('Macroéconomie',                   'MACR',   'Économie',                  'Sciences économiques',    'macroeconomie',            42),
('Comptabilité générale',           'CMPT',   'Gestion',                   'Sciences économiques',    'comptabilite-generale',    43),
('Finance d\'entreprise',           'FIN',    'Gestion / Finance',         'Sciences économiques',    'finance-entreprise',       44),
('Marketing & Commerce',            'MKT',    'Gestion',                   'Sciences économiques',    'marketing-commerce',       45),
('Management des organisations',    'MGT',    'Gestion',                   'Sciences économiques',    'management-organisations', 46),
('Fiscalité & Droit fiscal',        'FISC',   'Droit / Gestion',           'Sciences économiques',    'fiscalite-droit-fiscal',   47),
-- Informatique & Numérique
('Algorithmique & Programmation',   'ALGO',   'Informatique',              'Informatique & Numérique','algorithmique-programmation',50),
('Développement web',               'WEB',    'Informatique',              'Informatique & Numérique','developpement-web',        51),
('Bases de données',                'BD',     'Informatique',              'Informatique & Numérique','bases-donnees',            52),
('Réseaux & Télécommunications',    'NET',    'Informatique / Réseaux',    'Informatique & Numérique','reseaux-telecommunications',53),
('Intelligence artificielle',       'IA',     'Informatique',              'Informatique & Numérique','intelligence-artificielle', 54),
('Systèmes d\'exploitation',        'SYS',    'Informatique',              'Informatique & Numérique','systemes-exploitation',    55),
-- Lettres & Langues
('Littérature française',           'LITF',   'Lettres',                   'Lettres & Langues',       'litterature-francaise',    60),
('Linguistique',                    'LING',   'Lettres',                   'Lettres & Langues',       'linguistique',             61),
('Anglais',                         'ANG',    'Langues',                   'Lettres & Langues',       'anglais',                  62),
('Arabe',                           'ARB',    'Langues',                   'Lettres & Langues',       'arabe',                    63),
('Communication & Expression',      'COM',    'Lettres',                   'Lettres & Langues',       'communication-expression', 64),
-- Santé & Médecine
('Anatomie',                        'ANAT',   'Médecine',                  'Santé & Médecine',        'anatomie',                 70),
('Pharmacologie',                   'PHARM',  'Pharmacie',                 'Santé & Médecine',        'pharmacologie',            71),
('Santé publique',                  'SPUB',   'Santé publique',            'Santé & Médecine',        'sante-publique',           72),
('Nutrition',                       'NUT',    'Santé',                     'Santé & Médecine',        'nutrition',                73),
-- Agriculture & Environnement
('Agronomie',                       'AGRO',   'Agriculture',               'Agriculture & Environnement','agronomie',             80),
('Zootechnie',                      'ZOO',    'Élevage',                   'Agriculture & Environnement','zootechnie',            81),
('Gestion des ressources naturelles','GRN',   'Environnement',             'Agriculture & Environnement','gestion-ressources-naturelles',82),
('Hydraulique agricole',            'HYD',    'Agriculture / Génie rural', 'Agriculture & Environnement','hydraulique-agricole',  83),
-- Architecture & BTP
('Architecture',                    'ARCH',   'Architecture',              'Architecture & BTP',      'architecture',             90),
('Génie civil',                     'GC',     'BTP',                       'Architecture & BTP',      'genie-civil',              91),
('Urbanisme & Aménagement',         'URB',    'Urbanisme',                 'Architecture & BTP',      'urbanisme-amenagement',    92),
-- Autres
('Autres matières',                 'AUTRE',  'Divers',                    'Autres',                  'autres-matieres',          99);
