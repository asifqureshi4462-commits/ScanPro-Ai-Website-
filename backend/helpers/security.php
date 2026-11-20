<?php
declare(strict_types=1);

/**
 * ScanPro AI - Security, Escaping & CSRF Helpers
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';

/**
 * Escapes text safely for HTML output.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Sends defensive HTTP security headers for HTML and API responses.
 */
function applySecurityHeaders(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

/**
 * Generates or returns the current session CSRF token.
 */
function getCsrfToken(): string
{
    startSecureSession();

    $token = $_SESSION['_csrf_token'] ?? null;
    $issuedAt = $_SESSION['_csrf_token_time'] ?? 0;

    if (
        !is_string($token)
        || strlen($token) !== 64
        || (time() - (int) $issuedAt) > CSRF_TOKEN_LIFETIME
    ) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token'] = $token;
        $_SESSION['_csrf_token_time'] = time();
    }

    return $token;
}

/**
 * Outputs a hidden HTML input containing the active CSRF token.
 */
function csrfHiddenInput(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(getCsrfToken()) . '">';
}

/**
 * Extracts CSRF token from POST body or X-CSRF-Token HTTP header.
 */
function extractRequestCsrfToken(): ?string
{
    if (!empty($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
        return $_POST['csrf_token'];
    }

    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    return null;
}

/**
 * Verifies a submitted CSRF token in constant time.
 */
function verifyCsrfToken(?string $submittedToken): bool
{
    startSecureSession();

    $sessionToken = $_SESSION['_csrf_token'] ?? null;
    if (!is_string($sessionToken) || !is_string($submittedToken) || $submittedToken === '') {
        return false;
    }

    return hash_equals($sessionToken, $submittedToken);
}

/**
 * Generates a cryptographically secure random hex token.
 */
function generateSecureToken(int $byteLength = 32): string
{
    return bin2hex(random_bytes($byteLength));
}

/**
 * Hashes a secret token using SHA-256 before database storage.
 */
function hashToken(string $plainToken): string
{
    return hash('sha256', $plainToken);
}