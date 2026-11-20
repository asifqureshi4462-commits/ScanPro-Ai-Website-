<?php
declare(strict_types=1);

/**
 * ScanPro AI - Core Application Configuration
 */

if (defined('SCANPRO_APP_LOADED')) {
    return;
}
define('SCANPRO_APP_LOADED', true);

define('APP_NAME', 'ScanPro AI');
define('APP_TAGLINE', 'Next-Generation AI Document Scanner, PDF Suite & Cloud Vault');
define('APP_VERSION', '1.0.0');

// Set to 'production' when deploying live
define('APP_ENV', 'production');
define('APP_DEBUG', APP_ENV === 'development');

// Default timezone
date_default_timezone_set('UTC');

// Security & token lifetimes (in seconds)
define('CSRF_TOKEN_LIFETIME', 7200);       // 2 hours
define('RESET_TOKEN_EXPIRY', 1800);        // 30 minutes
define('REMEMBER_ME_DAYS', 30);            // 30 days
define('PASSWORD_MIN_LENGTH', 8);

// Support / contact metadata
define('APP_SUPPORT_EMAIL', 'asifqureshi.dev143@gmail.com');

// Contact form messages are emailed to this address (they are also saved in the database)
define('CONTACT_RECEIVER_EMAIL', 'asifqureshi.dev143@gmail.com');

// Password reset mail capability flag (false until SMTP/mailer is configured)
define('MAIL_DELIVERY_ENABLED', false);

/**
 * Check if the current request is running on a local development host.
 */
function isLocalhostRequest(): bool
{
    $rawHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    $hostOnly = preg_replace('/:\d+$/', '', $rawHost);
    $hostOnly = trim((string) $hostOnly, '[]');

    return in_array($hostOnly, ['localhost', '127.0.0.1', '::1'], true);
}

/**
 * Determine whether the current request is served over HTTPS (including reverse proxies / InfinityFree).
 */
function isHttpsRequest(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (!empty($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PORT']) && (int) $_SERVER['HTTP_X_FORWARDED_PORT'] === 443) {
        return true;
    }
    if (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains(strtolower((string) $_SERVER['HTTP_CF_VISITOR']), '"https"')) {
        return true;
    }

    $rawHost = strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
    if ($rawHost === 'scanproai.great-site.net') {
        return true;
    }

    return false;
}

/**
 * Compute the project root URL path dynamically so the app works
 * both at domain root (http://localhost:8080/) and inside subfolders
 * (http://localhost:8080/scanpro-ai/).
 */
function getProjectBasePath(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

    foreach (['/frontend/', '/backend/'] as $marker) {
        $pos = strpos($scriptName, $marker);
        if ($pos !== false) {
            return substr($scriptName, 0, $pos);
        }
    }

    $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    return $dir === '.' ? '' : $dir;
}

// Android Companion App constants (used in index.php, ai-scanner.php, footer.php)
define('ANDROID_APP_VERSION', '1.0');
define('ANDROID_MIN_OS', 'Android 7.0+ (API 24+)');
define('ANDROID_APK_URL', 'https://github.com/asifqureshi4462-commits/ScanPro-Ai-/releases/download/V1.0/app-debug.apk');