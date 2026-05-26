-- Exercice : statut intermédiaire après envoi de la correction par le professeur
-- (l'étudiant confirme ensuite la résolution → 'resolu').
-- Exécuter une fois sur chaque base (WAMP / prod).

ALTER TABLE `exercices`
MODIFY COLUMN `statut` ENUM('ouvert','en_cours','correction_livree','resolu','annule') NOT NULL DEFAULT 'ouvert';
