-- ============================================================
--  GLOBALO — Push Notifications (Web Push API / VAPID)
--  Migration : table push_subscriptions
-- ============================================================

CREATE TABLE IF NOT EXISTS push_subscriptions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    endpoint    TEXT         NOT NULL,
    p256dh      VARCHAR(512) NOT NULL COMMENT 'Clé publique du navigateur (base64url)',
    auth_key    VARCHAR(128) NOT NULL COMMENT 'Secret d\'authentification (base64url)',
    user_agent  VARCHAR(500) DEFAULT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY  uk_endpoint (endpoint(255)),
    KEY         idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
