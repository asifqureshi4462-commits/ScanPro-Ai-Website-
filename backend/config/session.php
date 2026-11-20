<?php
declare(strict_types=1);

/**
 * ScanPro AI - Secure Session Management
 */

require_once __DIR__ . '/app.php';

/**
 * Starts a hardened PHP session if one is not already active.
 */
function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = isHttpsRequest();

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_name('SCANPRO_SESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    // Idle expiry: a logged-in session unused for 2 hours is ended (remember-me cookie logins are unaffected).
    $idleLimit = defined('SESSION_IDLE_TIMEOUT') ? (int) SESSION_IDLE_TIMEOUT : 7200;
    if (
        !empty($_SESSION['user_id'])
        && isset($_SESSION['_last_activity'])
        && (time() - (int) $_SESSION['_last_activity']) > $idleLimit
    ) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $_SESSION['_last_activity'] = time();

    if (!isset($_SESSION['_session_created_at'])) {
        $_SESSION['_session_created_at'] = time();
    } elseif (time() - (int) $_SESSION['_session_created_at'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['_session_created_at'] = time();
    }
}

/**
 * Regenerates session ID safely after login or privilege change.
 */
function regenerateUserSession(): void
{
    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['_session_created_at'] = time();
}

/**
 * Completely clears and destroys the active session and its cookie.
 */
function destroyUserSession(): void
{
    startSecureSession();

    $_SESSION = [];
    $sessName = session_name();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        if (!headers_sent()) {
            setcookie(
                $sessName,
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => (bool) $params['secure'],
                    'httponly' => (bool) $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }
    }

    unset($_COOKIE[$sessName]);
    session_destroy();

    if (session_status() === PHP_SESSION_NONE) {
        session_id('');
    }
}

/**
 * Stores a one-time flash message in session.
 */
function setFlashMessage(string $type, string $message): void
{
    startSecureSession();
    $_SESSION['_flash_messages'][] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Retrieves and clears all queued flash messages.
 *
 * @return array<int, array{type: string, message: string}>
 */
function consumeFlashMessages(): array
{
    startSecureSession();
    $messages = $_SESSION['_flash_messages'] ?? [];
    unset($_SESSION['_flash_messages']);
    return is_array($messages) ? $messages : [];
}