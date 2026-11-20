-- ============================================================================
-- ScanPro AI - Migration 001: Firebase Google sign-in
-- BACK UP YOUR DATABASE FIRST: phpMyAdmin > Export > Quick > SQL > Go.
-- Safe to run more than once (MariaDB "IF NOT EXISTS").
-- ============================================================================

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `firebase_uid` VARCHAR(128) NULL DEFAULT NULL AFTER `password_hash`,
    ADD COLUMN IF NOT EXISTS `auth_provider` ENUM('password', 'google') NOT NULL DEFAULT 'password' AFTER `firebase_uid`,
    ADD COLUMN IF NOT EXISTS `avatar_url` VARCHAR(500) NULL DEFAULT NULL AFTER `auth_provider`,
    ADD COLUMN IF NOT EXISTS `email_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `avatar_url`;

ALTER TABLE `users`
    ADD UNIQUE INDEX IF NOT EXISTS `uq_users_firebase_uid` (`firebase_uid`);

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `action` VARCHAR(30) NOT NULL,
    `ip_hash` CHAR(64) NOT NULL,
    `identifier_hash` CHAR(64) NULL DEFAULT NULL,
    `success` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_attempts_ip` (`action`, `ip_hash`, `created_at`),
    KEY `idx_attempts_identifier` (`action`, `identifier_hash`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
