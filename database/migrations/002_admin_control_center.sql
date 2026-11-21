-- ============================================================================
-- ScanPro AI - Unified Admin Control Center Migration (002)
-- Safe & Non-Destructive: Reuses existing users, plans, contact_messages,
-- and login_attempts tables without altering or dropping them.
-- Compatible with InfinityFree MySQL / MariaDB (phpMyAdmin import).
-- ============================================================================

-- 1. Separate Admin Accounts Table (Isolated from normal website users)
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(60) NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'admin') NOT NULL DEFAULT 'super_admin',
    `status` ENUM('active', 'disabled') NOT NULL DEFAULT 'active',
    `last_login_at` DATETIME NULL DEFAULT NULL,
    `last_login_ip` VARCHAR(45) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_admins_username` (`username`),
    UNIQUE KEY `uq_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Admin Activity Audit Log
CREATE TABLE IF NOT EXISTS `admin_activity_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `admin_id` INT UNSIGNED NULL DEFAULT NULL,
    `admin_username` VARCHAR(60) NULL DEFAULT NULL,
    `action` VARCHAR(80) NOT NULL,
    `target_type` VARCHAR(50) NULL DEFAULT NULL,
    `target_id` VARCHAR(80) NULL DEFAULT NULL,
    `summary` VARCHAR(255) NOT NULL,
    `details_json` TEXT NULL DEFAULT NULL,
    `ip_hash` CHAR(64) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_admin_logs_admin_date` (`admin_id`, `created_at`),
    KEY `idx_admin_logs_action_date` (`action`, `created_at`),
    KEY `idx_admin_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Website & Ads Runtime Settings (Key-Value Store)
CREATE TABLE IF NOT EXISTS `site_settings` (
    `setting_key` VARCHAR(80) NOT NULL,
    `setting_value` TEXT NULL,
    `setting_group` ENUM('website', 'ads', 'security', 'general') NOT NULL DEFAULT 'general',
    `updated_by_admin_id` INT UNSIGNED NULL DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_key`),
    KEY `idx_site_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default Website, Ads, and Security settings safely (does not overwrite existing values)
INSERT IGNORE INTO `site_settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
    ('site_name', 'ScanPro AI', 'website'),
    ('site_tagline', 'Next-Generation AI Document Scanner, PDF Suite & Cloud Vault', 'website'),
    ('site_description', 'ScanPro AI is an all-in-one AI document scanner, OCR engine, PDF studio, and secure cloud vault for mobile and web productivity.', 'website'),
    ('site_status', 'active', 'website'),
    ('maintenance_mode', '0', 'website'),
    ('maintenance_message', 'ScanPro AI is undergoing scheduled maintenance. Please check back shortly.', 'website'),
    ('website_announcement_enabled', '0', 'website'),
    ('website_announcement_text', '', 'website'),
    ('homepage_notice_enabled', '0', 'website'),
    ('homepage_notice_text', '', 'website'),
    ('contact_email', 'asifqureshi.dev143@gmail.com', 'website'),
    ('contact_receiver_email', 'asifqureshi.dev143@gmail.com', 'website'),
    ('show_web_workspace', '1', 'website'),
    ('show_pricing_section', '1', 'website'),
    ('show_android_download', '1', 'website'),
    ('ads_enabled', '1', 'ads'),
    ('ads_guest_enabled', '1', 'ads'),
    ('ads_logged_in_enabled', '0', 'ads'),
    ('ads_home_enabled', '1', 'ads'),
    ('ads_features_enabled', '1', 'ads'),
    ('ads_pdf_tools_enabled', '1', 'ads'),
    ('ads_tool_pages_enabled', '1', 'ads'),
    ('ads_native_script_src', 'https://bauval.org/21/bc212c0c99169247b033ff18c20a0ee1', 'ads'),
    ('ads_native_container_id', 'container-bc212c0c99169247b033ff18c20a0ee1', 'ads'),
    ('ads_android_enabled', '0', 'ads'),
    ('ads_android_banner_id', '', 'ads'),
    ('admin_session_timeout', '1800', 'security');

-- 4. Android Application Remote Configuration (Singleton row id = 1)
CREATE TABLE IF NOT EXISTS `android_app_config` (
    `id` TINYINT UNSIGNED NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `maintenance_mode` TINYINT(1) NOT NULL DEFAULT 0,
    `maintenance_message` VARCHAR(500) NOT NULL DEFAULT 'ScanPro AI Android app is undergoing scheduled maintenance.',
    `restricted_notice` VARCHAR(500) NOT NULL DEFAULT '',
    `current_version` VARCHAR(32) NOT NULL DEFAULT '1.0',
    `latest_version` VARCHAR(32) NOT NULL DEFAULT '1.0',
    `min_supported_version` VARCHAR(32) NOT NULL DEFAULT '1.0',
    `latest_version_code` INT UNSIGNED NOT NULL DEFAULT 1,
    `min_supported_version_code` INT UNSIGNED NOT NULL DEFAULT 1,
    `update_available` TINYINT(1) NOT NULL DEFAULT 0,
    `mandatory_update` TINYINT(1) NOT NULL DEFAULT 0,
    `update_title` VARCHAR(120) NOT NULL DEFAULT 'Update Available',
    `update_announcement` VARCHAR(500) NOT NULL DEFAULT '',
    `release_notes` TEXT NULL,
    `update_download_url` VARCHAR(500) NOT NULL DEFAULT 'https://github.com/asifqureshi4462-commits/ScanPro-Ai-/releases/download/V1.0/app-debug.apk',
    `announcement_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `announcement_title` VARCHAR(150) NOT NULL DEFAULT '',
    `announcement_content` TEXT NULL,
    `announcement_type` ENUM('info', 'update', 'warning') NOT NULL DEFAULT 'info',
    `announcement_start_at` DATETIME NULL DEFAULT NULL,
    `announcement_end_at` DATETIME NULL DEFAULT NULL,
    `updated_by_admin_id` INT UNSIGNED NULL DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `android_app_config` (`id`, `is_active`, `maintenance_mode`, `current_version`, `latest_version`, `min_supported_version`, `latest_version_code`, `min_supported_version_code`, `update_download_url`)
VALUES (1, 1, 0, '1.0', '1.0', '1.0', 1, 1, 'https://github.com/asifqureshi4462-commits/ScanPro-Ai-/releases/download/V1.0/app-debug.apk');

-- 5. Unified Feature Control Center (Website + Android + files-to-zip + PDF Tools)
CREATE TABLE IF NOT EXISTS `feature_flags` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `feature_key` VARCHAR(64) NOT NULL,
    `feature_name` VARCHAR(100) NOT NULL,
    `category` ENUM('core', 'pdf_tool', 'archive_tool', 'ai_tool') NOT NULL DEFAULT 'core',
    `platform` ENUM('website', 'android', 'both') NOT NULL DEFAULT 'both',
    `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `status` ENUM('active', 'maintenance', 'disabled') NOT NULL DEFAULT 'active',
    `maintenance_message` VARCHAR(300) NOT NULL DEFAULT '',
    `web_tool_slug` VARCHAR(64) NULL DEFAULT NULL,
    `sort_order` INT NOT NULL DEFAULT 100,
    `updated_by_admin_id` INT UNSIGNED NULL DEFAULT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_feature_flags_key` (`feature_key`),
    KEY `idx_feature_flags_slug` (`web_tool_slug`),
    KEY `idx_feature_flags_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `feature_flags` (`feature_key`, `feature_name`, `category`, `platform`, `is_enabled`, `status`, `maintenance_message`, `web_tool_slug`, `sort_order`) VALUES
    ('ai_scanner', 'AI Scanner', 'core', 'both', 1, 'active', '', NULL, 10),
    ('document_scanner', 'Document Scanner', 'core', 'android', 1, 'active', '', NULL, 20),
    ('id_scanner', 'ID Scanner', 'core', 'android', 1, 'active', '', NULL, 30),
    ('smart_eraser', 'Smart Eraser', 'ai_tool', 'android', 1, 'active', '', NULL, 40),
    ('ai_tools', 'AI Tools (Gemini Suite)', 'ai_tool', 'android', 1, 'active', '', NULL, 50),
    ('ocr', 'OCR Text Extraction', 'ai_tool', 'both', 1, 'active', '', NULL, 60),
    ('pdf_editor', 'PDF Editor & Web Workspace', 'core', 'both', 1, 'active', '', 'workspace', 70),
    ('files_to_zip', 'Files to ZIP / 7Z', 'archive_tool', 'website', 1, 'active', '', 'files-to-zip', 80),
    ('merge_pdf', 'Merge PDF', 'pdf_tool', 'both', 1, 'active', '', 'merge-pdf', 90),
    ('split_pdf', 'Split PDF', 'pdf_tool', 'both', 1, 'active', '', 'split-pdf', 100),
    ('compress_pdf', 'Compress PDF', 'pdf_tool', 'both', 1, 'active', '', 'compress-pdf', 110),
    ('image_to_pdf', 'Image to PDF (JPG to PDF)', 'pdf_tool', 'both', 1, 'active', '', 'jpg-to-pdf', 120),
    ('pdf_to_image', 'PDF to Image (PDF to JPG)', 'pdf_tool', 'both', 1, 'active', '', 'pdf-to-jpg', 130),
    ('rotate_pdf', 'Rotate PDF', 'pdf_tool', 'website', 1, 'active', '', 'rotate-pdf', 140),
    ('watermark_pdf', 'Watermark PDF', 'pdf_tool', 'website', 1, 'active', '', 'watermark-pdf', 150),
    ('add_page_numbers', 'Add Page Numbers', 'pdf_tool', 'website', 1, 'active', '', 'add-page-numbers', 160),
    ('extract_pages', 'Extract PDF Pages', 'pdf_tool', 'website', 1, 'active', '', 'extract-pages', 170),
    ('reorder_pages', 'Reorder PDF Pages', 'pdf_tool', 'website', 1, 'active', '', 'reorder-pages', 180),
    ('remove_pages', 'Delete PDF Pages', 'pdf_tool', 'website', 1, 'active', '', 'remove-pages', 190),
    ('crop_pdf', 'Crop PDF', 'pdf_tool', 'website', 1, 'active', '', 'crop-pdf', 200),
    ('pdf_to_text', 'PDF to Text', 'pdf_tool', 'website', 1, 'active', '', 'pdf-to-text', 210),
    ('pdf_to_word', 'PDF to Word', 'pdf_tool', 'website', 1, 'active', '', 'pdf-to-word', 220),
    ('pdf_to_markdown', 'PDF to Markdown', 'pdf_tool', 'website', 1, 'active', '', 'pdf-to-markdown', 230),
    ('repair_pdf', 'Repair PDF', 'pdf_tool', 'website', 1, 'active', '', 'repair-pdf', 240),
    ('compare_pdf', 'Compare PDF', 'pdf_tool', 'website', 1, 'active', '', 'compare-pdf', 250),
    ('sign_pdf', 'Sign PDF', 'pdf_tool', 'website', 1, 'active', '', 'sign-pdf', 260),
    ('redact_pdf', 'Redact PDF', 'pdf_tool', 'website', 1, 'active', '', 'redact-pdf', 270);

-- 6. Unified Multi-Platform Announcements Center
CREATE TABLE IF NOT EXISTS `announcements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(150) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info', 'update', 'warning') NOT NULL DEFAULT 'info',
    `platform` ENUM('website', 'android', 'both') NOT NULL DEFAULT 'both',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `is_dismissible` TINYINT(1) NOT NULL DEFAULT 1,
    `start_at` DATETIME NULL DEFAULT NULL,
    `end_at` DATETIME NULL DEFAULT NULL,
    `created_by_admin_id` INT UNSIGNED NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_announcements_active` (`is_active`, `platform`, `start_at`, `end_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Privacy-Conscious Analytics Events (No raw IPs or personal data stored)
CREATE TABLE IF NOT EXISTS `analytics_events` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `event_type` ENUM('page_view', 'tool_run', 'download', 'app_open') NOT NULL,
    `target_key` VARCHAR(100) NOT NULL,
    `platform` ENUM('website', 'android') NOT NULL DEFAULT 'website',
    `device_category` ENUM('mobile', 'tablet', 'desktop', 'unknown') NOT NULL DEFAULT 'unknown',
    `visitor_hash` CHAR(64) NULL DEFAULT NULL,
    `event_date` DATE NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_analytics_type_date` (`event_type`, `event_date`),
    KEY `idx_analytics_target_date` (`target_key`, `event_date`),
    KEY `idx_analytics_visitor_date` (`visitor_hash`, `event_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;