<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../auth/auth_service.php';

startSecureSession();
applySecurityHeaders();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    if (expectsJsonResponse()) {
        jsonError('Method not allowed.', 405);
    }
    header('Location: ../../frontend/forgot-password.php', true, 302);
    exit;
}

$payload = getRequestPayload();
$submittedCsrf = extractRequestCsrfToken() ?? (isset($payload['csrf_token']) ? (string) $payload['csrf_token'] : null);

if (!verifyCsrfToken($submittedCsrf)) {
    if (expectsJsonResponse()) {
        jsonError('Security token expired or invalid. Please refresh the page.', 403);
    }
    setFlashMessage('error', 'Security token expired or invalid.');
    header('Location: ../../frontend/forgot-password.php', true, 302);
    exit;
}

$email = normalizeEmail($payload['email'] ?? '');

if (!isValidEmailAddress($email)) {
    if (expectsJsonResponse()) {
        jsonError('Please enter a valid email address.', 422, ['email' => 'Please enter a valid email address.']);
    }
    setFlashMessage('error', 'Please enter a valid email address.');
    header('Location: ../../frontend/forgot-password.php', true, 302);
    exit;
}

try {
    $pdo = getDbConnection();
    $ipAddress = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $result = createPasswordResetEntry($pdo, $email, $ipAddress);

    if (expectsJsonResponse()) {
        jsonSuccess($result['message'], ['alertType' => $result['alertType']]);
    }

    setFlashMessage($result['alertType'], $result['message']);
    header('Location: ../../frontend/forgot-password.php', true, 302);
    exit;
} catch (Throwable $e) {
    error_log('[ScanPro API Forgot Password Error] ' . $e->getMessage());
    if (expectsJsonResponse()) {
        jsonError('Password reset service is currently unavailable. Please verify your database connection.', 500);
    }
    setFlashMessage('error', 'Password reset service is currently unavailable.');
    header('Location: ../../frontend/forgot-password.php', true, 302);
    exit;
}