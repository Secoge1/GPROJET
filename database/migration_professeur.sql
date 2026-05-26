-- =============================================================================
-- GLOBALO - Migration : Ajout du rôle 'professeur'
-- Exécuter après migration_etudiant.sql
-- =============================================================================

-- Ajouter le rôle 'professeur' dans la table utilisateurs
ALTER TABLE `utilisateurs`
    MODIFY COLUMN `role` ENUM('client','expert','admin','etudiant','professeur') NOT NULL DEFAULT 'client';
