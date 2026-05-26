-- ============================================================
-- GLOBALO - Migration : Intégration Jɛmɛnipay
-- Table parametres : colonnes cle, valeur, updated_at UNIQUEMENT
-- ============================================================

-- 1. Provider d'abonnement → Jɛmɛnipay
INSERT INTO parametres (`cle`, `valeur`)
VALUES
    ('abonnement_provider',   'jemenipay'),
    ('paiement_moyen_defaut', 'jemenipay'),
    ('paiement_moyen_libelle','Jɛmɛnipay')
ON DUPLICATE KEY UPDATE
    `valeur`     = VALUES(`valeur`),
    `updated_at` = NOW();

-- 2. Clés API Jɛmɛnipay (sandbox — remplacer par les clés live en production)
INSERT INTO parametres (`cle`, `valeur`)
VALUES
    ('jemeni_api_key',     'pk_test_762828c3ce214281c57e1ceb9257805872f07dc7'),
    ('jemeni_secret_key',  'sk_test_ebc9a9212ce14375ee05acd1bdc58672182b3742'),
    ('jemeni_merchant_id', 'DPi4MMaYNmwU5omG0htF16xdtzy0uM5e'),
    ('jemeni_env',            'sandbox'),
    ('jemeni_webhook_secret', 'YlJfyRCOVj0hR3mqDODaGvxSGAoqUIczeLmHdpJFOoI6EOTowfi0qrWWVcjkMtfo')
ON DUPLICATE KEY UPDATE
    `valeur`     = VALUES(`valeur`),
    `updated_at` = NOW();

-- 3. Codes marchands Mobile Money Mali
INSERT INTO parametres (`cle`, `valeur`)
VALUES
    ('jemeni_orange_money_code', '703150'),
    ('jemeni_moov_africa_code',  '800850')
ON DUPLICATE KEY UPDATE
    `valeur`     = VALUES(`valeur`),
    `updated_at` = NOW();

-- 4. Tarifs abonnements mensuels (plans officiels Jɛmɛnipay)
--    client Abonnement   : 2 500 FCFA/mois
--    Expert Abonnement   : 3 000 FCFA/mois
--    Professeur          : 3 000 FCFA/mois
--    Étudiant Abonnement : 2 000 FCFA/mois
INSERT INTO parametres (`cle`, `valeur`)
VALUES
    ('abonnement_prix_client_xof',     '2500'),
    ('abonnement_prix_expert_xof',     '3000'),
    ('abonnement_prix_professeur_xof', '3000'),
    ('abonnement_prix_etudiant_xof',   '2000'),
    ('abonnement_duree_jours',         '30')
ON DUPLICATE KEY UPDATE
    `valeur`     = VALUES(`valeur`),
    `updated_at` = NOW();

-- 5. Étendre l'ENUM type de la table abonnements (etudiant + professeur manquaient)
--    CRITIQUE : sans ce ALTER, les abonnements étudiant/professeur crashent en SQL
ALTER TABLE `abonnements`
    MODIFY COLUMN `type` ENUM('client','expert','etudiant','professeur') NOT NULL;

-- 6. Réserver les clés des plan_ids récurrents Jɛmɛnipay (vides = paiement unique actif)
INSERT INTO parametres (`cle`, `valeur`)
VALUES
    ('jemeni_plan_id_client',     ''),
    ('jemeni_plan_id_expert',     ''),
    ('jemeni_plan_id_professeur', ''),
    ('jemeni_plan_id_etudiant',   '')
ON DUPLICATE KEY UPDATE
    `cle` = `cle`;   -- ne pas écraser si déjà renseigné manuellement

-- ============================================================
-- VÉRIFICATION :
--   SELECT * FROM parametres WHERE cle LIKE 'jemeni%' OR cle LIKE 'abonnement%';
--   SHOW COLUMNS FROM abonnements LIKE 'type';
-- PASSAGE EN PRODUCTION :
--   UPDATE parametres SET valeur='pk_live_...' WHERE cle='jemeni_api_key';
--   UPDATE parametres SET valeur='sk_live_...' WHERE cle='jemeni_secret_key';
--   UPDATE parametres SET valeur='production'  WHERE cle='jemeni_env';
-- ============================================================
