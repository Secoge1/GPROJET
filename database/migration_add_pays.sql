-- Migration : ajout de la colonne `pays` dans la table `utilisateurs`
ALTER TABLE `utilisateurs`
    ADD COLUMN `pays` VARCHAR(50) DEFAULT NULL AFTER `telephone`;
