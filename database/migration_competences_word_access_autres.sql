-- Compétences : Word, Access, Autres (avec précision à saisir)
-- À exécuter une seule fois.

-- 1. Ajouter Word, Access et Autres dans la table competences
INSERT INTO `competences` (`nom`, `categorie`, `slug`, `actif`) VALUES
('Word', 'Bureautique', 'word', 1),
('Access', 'Bureautique', 'access', 1),
('Autres', 'Autres', 'autres', 1)
ON DUPLICATE KEY UPDATE actif = 1;

-- 2. Colonne pour la précision quand l'expert choisit "Autres"
-- (Si la colonne existe déjà, ignorer l'erreur ou commenter cette ligne.)
ALTER TABLE `profils_experts`
  ADD COLUMN `competences_autres` VARCHAR(255) DEFAULT NULL
  COMMENT 'Précision (ex. Power BI, Python) quand compétence Autres est cochée';
