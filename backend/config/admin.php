<?php
declare(strict_types=1);

/**
 * ScanPro AI - Isolated Admin Control Center Configuration
 */

require_once __DIR__ . '/app.php';

if (defined('SCANPRO_ADMIN_CONFIG_LOADED')) {
    return;
}
define('SCANPRO_ADMIN_CONFIG_LOADED', true);

// Dedicated session cookie completely separate from user website session (SCANPRO_SESSID)
define('ADMIN_SESSION_NAME', 'SCANPRO_ADM_SESSID');
define('ADMIN_SESSION_IDLE_TIMEOUT', 1800);        // 30 minutes default idle timeout
define('ADMIN_SESSION_REGENERATE_INTERVAL', 900);  // 15 minutes
define('ADMIN_CSRF_TOKEN_LIFETIME', 7200);         // 2 hours

// Brute-force protection thresholds for admin sign-in
define('ADMIN_LOGIN_MAX_ATTEMPTS_IDENTITY', 5);    // 5 failed attempts per username+IP
define('ADMIN_LOGIN_MAX_ATTEMPTS_IP', 15);         // 15 failed attempts per IP
define('ADMIN_LOGIN_WINDOW_SECONDS', 900);         // 15-minute window
define('ADMIN_PASSWORD_MIN_LENGTH', 10);

// Lock file created once the initial super admin account is created
define('ADMIN_SETUP_LOCK_FILE', dirname(__DIR__) . '/storage/admin_setup.lock');

// Auto-generated server-only salt used for daily anonymous visitor hashing
define('ADMIN_ANALYTICS_SALT_FILE', dirname(__DIR__) . '/storage/analytics_salt.key');

/**
 * Computes the base URL path to /admin safely across localhost subfolders & live root.
 */
function getAdminBasePath(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    foreach (['/admin/', '/backend/', '/frontend/'] as $marker) {
        $pos = strpos($scriptName, $marker);
        if ($pos !== false) {
            return substr($scriptName, 0, $pos) . '/admin';
        }
    }
    return '/admin';
}

/**
 * Checks whether initial admin setup has been locked on disk.
 */
function isAdminSetupLockedOnDisk(): bool
{
    return is_file(ADMIN_SETUP_LOCK_FILE);
}

/**
 * Returns or creates a private local salt for daily anonymous visitor hashing (never exposed).
 */
function getPrivacyAnalyticsSalt(): string
{
    $file = ADMIN_ANALYTICS_SALT_FILE;
    if (is_file($file) && is_readable($file)) {
        $existing = trim((string) @file_get_contents($file));
        if (strlen($existing) >= 32) {
            return $existing;
        }
    }

    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }

    $salt = bin2hex(random_bytes(32));
    @file_put_contents($file, $salt, LOCK_EX);
    return $salt;
}