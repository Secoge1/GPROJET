-- ============================================================
-- Migration : tables transactions + transaction_logs
-- Utilisees par WavePaymentService / TransactionModel
-- Sure a rejouer : CREATE TABLE IF NOT EXISTS
-- ============================================================

-- Table principale des paiements Wave (semi-manuels)
CREATE TABLE IF NOT EXISTS `transactions` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `payment_id`       VARCHAR(60)      NOT NULL COMMENT 'WAV-xxxxxxxx-xxxxxxxx (unique, genere)',
  `user_id`          INT UNSIGNED     NOT NULL COMMENT 'Payeur FK utilisateurs.id',
  `amount`           DECIMAL(12,2)    NOT NULL COMMENT 'Montant du service ou depot XOF',
  `platform_fee`     DECIMAL(12,2)    NOT NULL DEFAULT 0.00 COMMENT 'Commission plateforme XOF',
  `total_amount`     DECIMAL(12,2)    NOT NULL COMMENT 'amount + platform_fee',
  `currency`         VARCHAR(10)      NOT NULL DEFAULT 'XOF',
  `phone`            VARCHAR(34)      NOT NULL COMMENT 'Numero Wave du payeur +223XXXXXXXX',
  `provider`         VARCHAR(20)      NOT NULL DEFAULT 'wave',
  `status`           ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
  `type`             VARCHAR(50)      NOT NULL COMMENT 'depot_portefeuille ou abonnement_client ou abonnement_expert',
  `abonnement_type`  VARCHAR(30)      NOT NULL DEFAULT 'client',
  `transaction_code` VARCHAR(50)      DEFAULT NULL COMMENT 'Code SMS Wave soumis par utilisateur',
  `validated_by`     INT UNSIGNED     DEFAULT NULL COMMENT 'Admin validateur FK utilisateurs.id',
  `validated_at`     DATETIME         DEFAULT NULL,
  `notes`            TEXT             DEFAULT NULL COMMENT 'Notes admin',
  `ip_address`       VARCHAR(45)      DEFAULT NULL,
  `user_agent`       VARCHAR(255)     DEFAULT NULL,
  `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME         DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_id`       (`payment_id`),
  UNIQUE KEY `uk_transaction_code` (`transaction_code`),
  KEY `idx_user_id`    (`user_id`),
  KEY `idx_status`     (`status`),
  KEY `idx_type`       (`type`),
  KEY `idx_created_at` (`created_at`),

  CONSTRAINT `fk_tx_user`   FOREIGN KEY (`user_id`)      REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tx_admin`  FOREIGN KEY (`validated_by`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Table de journalisation immuable (audit trail)
CREATE TABLE IF NOT EXISTS `transaction_logs` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `transaction_id` INT UNSIGNED  NOT NULL,
  `action`         VARCHAR(50)   NOT NULL COMMENT 'created code_submitted validated refused expired',
  `actor_id`       INT UNSIGNED  DEFAULT NULL,
  `actor_type`     VARCHAR(20)   NOT NULL DEFAULT 'system' COMMENT 'user admin system',
  `meta`           JSON          DEFAULT NULL,
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_log_transaction` (`transaction_id`),
  KEY `idx_log_actor`       (`actor_id`),

  CONSTRAINT `fk_log_tx` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
