<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/rate_limiter.php';
require_once __DIR__ . '/../auth/auth_service.php';

startSecureSession();
applySecurityHeaders();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    if (expectsJsonResponse()) {
        jsonError('Method not allowed.', 405);
    }
    header('Location: ../../frontend/login.php', true, 302);
    exit;
}

$payload = getRequestPayload();
$submittedCsrf = extractRequestCsrfToken() ?? (isset($payload['csrf_token']) ? (string) $payload['csrf_token'] : null);

if (!verifyCsrfToken($submittedCsrf)) {
    if (expectsJsonResponse()) {
        jsonError('Security token expired or invalid. Please refresh the page and try again.', 403);
    }
    setFlashMessage('error', 'Security token expired or invalid. Please try again.');
    header('Location: ../../frontend/login.php', true, 302);
    exit;
}

$email = normalizeEmail($payload['email'] ?? '');
$password = is_string($payload['password'] ?? null) ? (string) $payload['password'] : '';
$rememberMe = filter_var($payload['remember_me'] ?? false, FILTER_VALIDATE_BOOLEAN);

$errors = [];
if (!isValidEmailAddress($email)) {
    $errors['email'] = 'Please enter a valid email address.';
}
if ($password === '') {
    $errors['password'] = 'Please enter your password.';
}

if (!empty($errors)) {
    if (expectsJsonResponse()) {
        jsonError('Please correct the highlighted fields.', 422, $errors);
    }
    setFlashMessage('error', 'Please enter a valid email address and password.');
    header('Location: ../../frontend/login.php', true, 302);
    exit;
}

try {
    $pdo = getDbConnection();

    // Rate limit: 8 failures per email+IP and 40 per IP within 15 minutes.
    $limitIdentity = $email . '|' . rateLimitClientIp();
    if (rateLimitIsBlocked($pdo, 'password_login', $limitIdentity, 8, 40, 900)) {
        $tooMany = 'Too many sign-in attempts. Please wait 15 minutes and try again.';
        if (expectsJsonResponse()) {
            jsonError($tooMany, 429);
        }
        setFlashMessage('error', $tooMany);
        header('Location: ../../frontend/login.php', true, 302);
        exit;
    }

    $result = loginUserWithCredentials($pdo, $email, $password, $rememberMe);

    if (!$result['ok']) {
        rateLimitRecordFailure($pdo, 'password_login', $limitIdentity);
        if (expectsJsonResponse()) {
            jsonError($result['message'], 401);
        }
        setFlashMessage('error', $result['message']);
        header('Location: ../../frontend/login.php', true, 302);
        exit;
    }

    if (expectsJsonResponse()) {
        jsonSuccess($result['message'], ['redirect' => 'dashboard.php']);
    }

    setFlashMessage('success', $result['message']);
    header('Location: ../../frontend/dashboard.php', true, 302);
    exit;
} catch (Throwable $e) {
    error_log('[ScanPro API Login Error] ' . $e->getMessage());
    if (expectsJsonResponse()) {
        jsonError('Authentication service is currently unavailable. Please verify your database connection.', 500);
    }
    setFlashMessage('error', 'Authentication service is currently unavailable.');
    header('Location: ../../frontend/login.php', true, 302);
    exit;
}