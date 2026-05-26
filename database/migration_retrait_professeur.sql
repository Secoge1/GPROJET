-- ============================================================
-- Migration : table de retraits Wave pour les professeurs
-- Séparée de demandes_retrait (qui est liée à profils_experts)
-- Sûre à rejouer : CREATE TABLE IF NOT EXISTS
-- ============================================================

CREATE TABLE IF NOT EXISTS `demandes_retrait_prof` (
  `id`             INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT UNSIGNED    NOT NULL COMMENT 'FK vers utilisateurs.id (role=professeur)',
  `montant`        DECIMAL(12,2)   NOT NULL,
  `statut`         ENUM('en_attente','traitee','refusee') NOT NULL DEFAULT 'en_attente',
  `numero_wave`    VARCHAR(34)     DEFAULT NULL COMMENT 'Numéro Wave Money du professeur',
  `reference`      VARCHAR(100)    DEFAULT NULL COMMENT 'Référence transfert / opérateur si payout',
  `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `traite_at`      DATETIME        DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_retrait_prof_user`   (`utilisateur_id`),
  KEY `idx_retrait_prof_statut` (`statut`),
  CONSTRAINT `fk_retrait_prof_user`
      FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
