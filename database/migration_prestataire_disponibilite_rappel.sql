-- Rappel popup disponibilité (expert / professeur) après validation admin
ALTER TABLE `profils_experts`
    ADD COLUMN `rappel_disponibilite_vu` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = popup rappel disponibilité déjà vu ou ignoré' AFTER `competences_autres`;

ALTER TABLE `profils_professeurs`
    ADD COLUMN `rappel_disponibilite_vu` TINYINT(1) NOT NULL DEFAULT 0
    COMMENT '1 = popup rappel disponibilité déjà vu ou ignoré' AFTER `valide_par_admin`;
