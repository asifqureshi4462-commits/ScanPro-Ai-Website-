<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../auth/auth_service.php';

startSecureSession();
applySecurityHeaders();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    if (expectsJsonResponse()) {
        jsonError('Method not allowed. Use POST to log out.', 405);
    }
    header('Location: ../../frontend/index.php', true, 302);
    exit;
}

$payload = getRequestPayload();
$submittedCsrf = extractRequestCsrfToken() ?? (isset($payload['csrf_token']) ? (string) $payload['csrf_token'] : null);

if (!verifyCsrfToken($submittedCsrf)) {
    if (expectsJsonResponse()) {
        jsonError('Invalid security token.', 403);
    }
    setFlashMessage('error', 'Invalid security token. Please try logging out again.');
    header('Location: ../../frontend/dashboard.php', true, 302);
    exit;
}

performUserLogout();

if (expectsJsonResponse()) {
    jsonSuccess('You have been logged out.', ['redirect' => 'login.php']);
}

startSecureSession();
setFlashMessage('success', 'You have been signed out safely.');
header('Location: ../../frontend/login.php', true, 302);
exit;