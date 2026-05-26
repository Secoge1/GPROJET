-- Ajoute la colonne referral_code à parrainages (corrige l'erreur "Champ 'referral_code' inconnu")
-- Exécuter une seule fois sur la base globalo (phpMyAdmin ou ligne de commande MySQL).

SET NAMES utf8mb4;

-- Colonne referral_code (format GLOBALO-00001)
ALTER TABLE `parrainages` ADD COLUMN `referral_code` VARCHAR(20) DEFAULT NULL AFTER `code`;
UPDATE `parrainages` SET referral_code = CONCAT('GLOBALO-', LPAD(id, 5, '0')) WHERE referral_code IS NULL;
ALTER TABLE `parrainages` ADD UNIQUE KEY `referral_code` (`referral_code`);
