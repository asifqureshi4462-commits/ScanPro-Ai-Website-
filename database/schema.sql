-- ============================================================================
-- ScanPro AI - Complete MySQL / MariaDB Database Schema
-- Compatible with MySQL 8+ and MariaDB (AWebServer / phpMyAdmin)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `scanpro_ai`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `scanpro_ai`;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

-- ----------------------------------------------------------------------------
-- 1. Users Table
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `firebase_uid` VARCHAR(128) NULL DEFAULT NULL,
    `auth_provider` ENUM('password', 'google') NOT NULL DEFAULT 'password',
    `avatar_url` VARCHAR(500) NULL DEFAULT NULL,
    `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    `remember_selector` VARCHAR(64) NULL DEFAULT NULL,
    `remember_token_hash` CHAR(64) NULL DEFAULT NULL,
    `remember_expires_at` DATETIME NULL DEFAULT NULL,
    `last_login_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    UNIQUE KEY `uq_users_firebase_uid` (`firebase_uid`),
    KEY `idx_users_remember_selector` (`remember_selector`),
    KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. User Profiles Table (1-to-1 extension of users)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_profiles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `phone` VARCHAR(30) NULL DEFAULT NULL,
    `organization` VARCHAR(120) NULL DEFAULT NULL,
    `bio` VARCHAR(500) NULL DEFAULT NULL,
    `preferred_language` VARCHAR(50) NOT NULL DEFAULT 'English',
    `preferred_theme` ENUM('dark', 'light', 'system') NOT NULL DEFAULT 'dark',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_profiles_user_id` (`user_id`),
    CONSTRAINT `fk_user_profiles_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Subscription Plans Table
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `plans` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(50) NOT NULL,
    `name` VARCHAR(80) NOT NULL,
    `tagline` VARCHAR(190) NULL DEFAULT NULL,
    `price_monthly` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `price_yearly` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `storage_label` VARCHAR(80) NOT NULL,
    `storage_limit_mb` INT UNSIGNED NOT NULL DEFAULT 5120,
    `features_json` TEXT NOT NULL,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_plans_slug` (`slug`),
    KEY `idx_plans_active_order` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. User Subscriptions Table
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_subscriptions` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `plan_id` INT UNSIGNED NOT NULL,
    `billing_cycle` ENUM('monthly', 'yearly') NOT NULL DEFAULT 'monthly',
    `status` ENUM('active', 'cancelled', 'expired', 'pending') NOT NULL DEFAULT 'active',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_subscriptions_user_status` (`user_id`, `status`),
    KEY `idx_user_subscriptions_plan` (`plan_id`),
    CONSTRAINT `fk_user_subscriptions_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_user_subscriptions_plan`
        FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Contact Messages Table
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NULL DEFAULT NULL,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `subject` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `status` ENUM('new', 'read', 'replied', 'archived') NOT NULL DEFAULT 'new',
    `ip_address` VARCHAR(45) NULL DEFAULT NULL,
    `user_agent` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_contact_email` (`email`),
    KEY `idx_contact_status` (`status`),
    KEY `idx_contact_created_at` (`created_at`),
    KEY `idx_contact_user_id` (`user_id`),
    CONSTRAINT `fk_contact_messages_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. Password Resets Table
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `token_hash` CHAR(64) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL DEFAULT NULL,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_password_resets_token_hash` (`token_hash`),
    KEY `idx_password_resets_user_id` (`user_id`),
    KEY `idx_password_resets_expires_at` (`expires_at`),
    CONSTRAINT `fk_password_resets_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- Seed Default Plans (Free, Pro, Ultra)
-- ----------------------------------------------------------------------------
INSERT INTO `plans` (
    `slug`,
    `name`,
    `tagline`,
    `price_monthly`,
    `price_yearly`,
    `storage_label`,
    `storage_limit_mb`,
    `features_json`,
    `is_featured`,
    `is_active`,
    `sort_order`
) VALUES
(
    'free',
    'Free',
    'Essential document scanning & offline vault',
    0.00,
    0.00,
    '5 GB Vault Storage',
    5120,
    '["Smart Camera Scanner & Edge Crop","11 Real-Time Enhancement Filters","Local Offline Document Vault","Basic PDF Viewing & Organization","Email & Password Web Account"]',
    0,
    1,
    1
),
(
    'pro',
    'Pro',
    'Full Gemini AI OCR, translation & PDF toolkit',
    9.99,
    95.88,
    '100 GB Cloud Vault',
    102400,
    '["Everything in Free","Gemini AI OCR Text Extraction","Executive Summaries & Document Q&A","50+ Language Document Translation","AES-128 PDF Encryption & Watermarking"]',
    1,
    1,
    2
),
(
    'ultra',
    'Ultra',
    'High-volume AI workflows & expanded vault',
    19.99,
    191.88,
    '1 TB Cloud Vault',
    1048576,
    '["Everything in Pro","Unlimited AI Table-to-CSV Extraction","Exam Question Set Parsing Studio","Priority Multi-Format Office Sync","Priority Customer Support"]',
    0,
    1,
    3
)
ON DUPLICATE KEY UPDATE
    `name` = VALUES(`name`),
    `tagline` = VALUES(`tagline`),
    `price_monthly` = VALUES(`price_monthly`),
    `price_yearly` = VALUES(`price_yearly`),
    `storage_label` = VALUES(`storage_label`),
    `storage_limit_mb` = VALUES(`storage_limit_mb`),
    `features_json` = VALUES(`features_json`),
    `is_featured` = VALUES(`is_featured`),
    `is_active` = VALUES(`is_active`),
    `sort_order` = VALUES(`sort_order`);

-- ----------------------------------------------------------------------------
-- Login attempts (rate limiting for password and Google sign-in)
-- ----------------------------------------------------------------------------
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
