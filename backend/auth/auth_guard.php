<?php
declare(strict_types=1);

/**
 * ScanPro AI - Authentication & Route Guard
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../helpers/response.php';

const REMEMBER_COOKIE_NAME = 'SCANPRO_REMEMBER';

/**
 * Clears the Remember-Me cookie from the browser.
 */
function clearRememberMeCookie(): void
{
    if (headers_sent()) {
        return;
    }

    setcookie(
        REMEMBER_COOKIE_NAME,
        '',
        [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => '',
            'secure'   => isHttpsRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

/**
 * Attempts to restore a session from a valid Remember-Me cookie if not yet logged in.
 */
function restoreSessionFromRememberCookie(): void
{
    startSecureSession();

    if (!empty($_SESSION['user_id'])) {
        return;
    }

    $cookieValue = $_COOKIE[REMEMBER_COOKIE_NAME] ?? '';
    if (!is_string($cookieValue) || !str_contains($cookieValue, ':')) {
        return;
    }

    [$selector, $validator] = explode(':', $cookieValue, 2);
    if (
        !preg_match('/^[a-f0-9]{16,32}$/', $selector)
        || !preg_match('/^[a-f0-9]{32,64}$/', $validator)
    ) {
        clearRememberMeCookie();
        return;
    }

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare(
            'SELECT id, full_name, email, role, status, remember_token_hash, remember_expires_at
             FROM users
             WHERE remember_selector = :selector
             LIMIT 1'
        );
        $stmt->execute(['selector' => $selector]);
        $user = $stmt->fetch();

        if (
            !$user
            || ($user['status'] ?? '') !== 'active'
            || empty($user['remember_token_hash'])
            || empty($user['remember_expires_at'])
            || strtotime((string) $user['remember_expires_at']) < time()
        ) {
            clearRememberMeCookie();
            return;
        }

        $expectedHash = (string) $user['remember_token_hash'];
        $providedHash = hashToken($validator);

        if (!hash_equals($expectedHash, $providedHash)) {
            $clearStmt = $pdo->prepare(
                'UPDATE users SET remember_selector = NULL, remember_token_hash = NULL, remember_expires_at = NULL WHERE id = :id'
            );
            $clearStmt->execute(['id' => (int) $user['id']]);
            clearRememberMeCookie();
            return;
        }

        regenerateUserSession();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = (string) $user['full_name'];
        $_SESSION['user_email'] = (string) $user['email'];
        $_SESSION['user_role'] = (string) $user['role'];
    } catch (Throwable $e) {
        error_log('[ScanPro Remember Cookie Error] ' . $e->getMessage());
    }
}

/**
 * Checks if a user is currently authenticated.
 */
function isAuthenticated(): bool
{
    restoreSessionFromRememberCookie();
    return !empty($_SESSION['user_id']);
}

/**
 * Protects a frontend page; redirects unauthenticated visitors to login.php.
 */
function requireWebAuthentication(): int
{
    if (!isAuthenticated()) {
        setFlashMessage('warning', 'Please sign in to access your dashboard.');
        header('Location: login.php', true, 302);
        exit;
    }

    return (int) $_SESSION['user_id'];
}

/**
 * Protects an API endpoint; returns 401 JSON if unauthenticated.
 */
function requireApiAuthentication(): int
{
    if (!isAuthenticated()) {
        jsonError('Authentication required. Please sign in first.', 401);
    }

    return (int) $_SESSION['user_id'];
}

/**
 * Redirects already authenticated users away from guest-only pages.
 */
function redirectIfAuthenticated(string $target = 'dashboard.php'): void
{
    if (isAuthenticated()) {
        header('Location: ' . $target, true, 302);
        exit;
    }
}