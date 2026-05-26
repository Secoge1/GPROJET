-- GLOBALO — Migration Google OAuth 2.0
-- Exécuter UNE SEULE FOIS en base de données
-- Date : 2026-03-18

-- 1. Identifiant Google unique par utilisateur (sub = ID Google)
ALTER TABLE utilisateurs
    ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) NULL UNIQUE COMMENT 'ID Google OAuth (sub)' AFTER email;

-- 2. Fournisseur d'authentification : email standard ou Google
ALTER TABLE utilisateurs
    ADD COLUMN IF NOT EXISTS auth_provider ENUM('email','google') NOT NULL DEFAULT 'email'
        COMMENT 'Méthode d''inscription (email/google)' AFTER google_id;

-- 3. Le mot de passe devient optionnel (NULL pour les comptes Google purs)
--    Vérifier le vrai nom de colonne dans votre BDD (ici "mot_de_passe")
ALTER TABLE utilisateurs
    MODIFY COLUMN mot_de_passe VARCHAR(255) NULL COMMENT 'Hash bcrypt (NULL pour comptes Google purs)';

-- 4. Index sur google_id pour lookup rapide
ALTER TABLE utilisateurs
    ADD INDEX IF NOT EXISTS idx_google_id (google_id);

-- Vérification
SELECT 'Migration Google OAuth OK' AS status;
