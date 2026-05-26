-- Photo de profil et pièce d'identité (sécurité)
-- La colonne avatar existe déjà sur utilisateurs. On ajoute piece_identite.

ALTER TABLE `utilisateurs`
  ADD COLUMN `piece_identite` VARCHAR(255) DEFAULT NULL
  COMMENT 'Chemin relatif du fichier (ex. users/22/piece_identite.pdf)';
