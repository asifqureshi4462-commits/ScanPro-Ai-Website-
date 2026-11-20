<?php
declare(strict_types=1);

ob_start();

/**
 * ScanPro AI - Google (Firebase) sign-in endpoint.
 * Receives a Firebase ID token, verifies it on the server, syncs the MySQL user and starts the PHP session.
 * The browser never tells us who the user is: only the verified token is trusted.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/rate_limiter.php';
require_once __DIR__ . '/../auth/auth_service.php';
require_once __DIR__ . '/../auth/firebase_verifier.php';

startSecureSession();
applySecurityHeaders();

function firebaseLoginFail(string $message, int $status): void
{
    jsonError($message, $status);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    firebaseLoginFail('Method not allowed.', 405);
}

if (!FIREBASE_LOGIN_ENABLED) {
    firebaseLoginFail('Google sign-in is currently disabled.', 503);
}

if (APP_ENV === 'production' && !isHttpsRequest() && !isLocalhostRequest()) {
    firebaseLoginFail('A secure HTTPS connection is required.', 400);
}

$payload = getRequestPayload();
$submittedCsrf = extractRequestCsrfToken() ?? (isset($payload['csrf_token']) && is_string($payload['csrf_token']) ? $payload['csrf_token'] : null);
if (!verifyCsrfToken($submittedCsrf)) {
    firebaseLoginFail('Security token expired or invalid. Please refresh the page and try again.', 403);
}

$idToken = isset($payload['id_token']) && is_string($payload['id_token']) ? $payload['id_token'] : '';

try {
    $pdo = getDbConnection();

    if (rateLimitIsBlocked($pdo, 'google_login', null, 0, 15, 600)) {
        firebaseLoginFail('Too many attempts. Please wait a few minutes and try again.', 429);
    }

    try {
        $identity = verifyFirebaseIdToken($idToken);
    } catch (FirebaseTokenException $e) {
        error_log('[ScanPro Firebase Verify] ' . $e->getMessage());
        if (!$e->isServiceError()) {
            rateLimitRecordFailure($pdo, 'google_login', null);
        }
        firebaseLoginFail($e->userMessage(), $e->isServiceError() ? 503 : 401);
    }

    $result = syncFirebaseUser($pdo, $identity);
    if (!$result['ok'] || !isset($result['user'])) {
        firebaseLoginFail($result['message'], 403);
    }

    establishLoginSession($result['user']);

    jsonSuccess('Signed in successfully.', [
        'redirect' => 'dashboard.php',
        'is_new'   => !empty($result['is_new']),
    ]);
} catch (Throwable $e) {
    error_log('[ScanPro Firebase Login Error] ' . $e->getMessage());
    firebaseLoginFail('Google sign-in is temporarily unavailable. Please try again.', 500);
}