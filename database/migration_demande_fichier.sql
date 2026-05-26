-- Migration : Pièces jointes et lien vidéo sur demandes_assistance
-- Date : 2026-03-11

ALTER TABLE `demandes_assistance`
    ADD COLUMN `fichier` VARCHAR(500) DEFAULT NULL
        COMMENT 'Chemin vers la pièce jointe (PDF, Word, Excel, Access…)',
    ADD COLUMN `lien_video` VARCHAR(1000) DEFAULT NULL
        COMMENT 'Lien externe vers une vidéo explicative (WeTransfer, Google Drive, YouTube…)';
