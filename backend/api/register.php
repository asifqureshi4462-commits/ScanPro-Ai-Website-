<?php
ob_start();

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
    header('Location: ../../frontend/register.php', true, 302);
    exit;
}

$payload = getRequestPayload();
$submittedCsrf = extractRequestCsrfToken() ?? (isset($payload['csrf_token']) ? (string) $payload['csrf_token'] : null);

if (!verifyCsrfToken($submittedCsrf)) {
    if (expectsJsonResponse()) {
        jsonError('Security token expired or invalid. Please refresh the page and try again.', 403);
    }
    setFlashMessage('error', 'Security token expired or invalid. Please try again.');
    header('Location: ../../frontend/register.php', true, 302);
    exit;
}

$fullName = cleanText($payload['full_name'] ?? '');
$email = normalizeEmail($payload['email'] ?? '');
$password = is_string($payload['password'] ?? null) ? (string) $payload['password'] : '';
$confirmPassword = is_string($payload['confirm_password'] ?? null) ? (string) $payload['confirm_password'] : '';

$errors = [];
if (!isLengthBetween($fullName, 2, 100)) {
    $errors['full_name'] = 'Full name must be between 2 and 100 characters.';
}
if (!isValidEmailAddress($email)) {
    $errors['email'] = 'Please enter a valid email address.';
}
$passwordError = validatePasswordPolicy($password);
if ($passwordError !== null) {
    $errors['password'] = $passwordError;
}
if ($confirmPassword !== $password) {
    $errors['confirm_password'] = 'Passwords do not match.';
}

if (!empty($errors)) {
    if (expectsJsonResponse()) {
        jsonError('Please review the highlighted registration fields.', 422, $errors);
    }
    setFlashMessage('error', 'Please review all required registration fields.');
    header('Location: ../../frontend/register.php', true, 302);
    exit;
}

try {
    $pdo = getDbConnection();
    $result = registerNewUser($pdo, $fullName, $email, $password);

    if (!$result['ok']) {
        $status = !empty($result['errors']['email']) ? 409 : 500;
        if (expectsJsonResponse()) {
            jsonError($result['message'], $status, $result['errors'] ?? []);
        }
        setFlashMessage('error', $result['message']);
        header('Location: ../../frontend/register.php', true, 302);
        exit;
    }

    if (expectsJsonResponse()) {
        jsonSuccess($result['message'], ['redirect' => 'dashboard.php'], 201);
    }

    setFlashMessage('success', 'Welcome to ScanPro AI! Your free account is ready.');
    header('Location: ../../frontend/dashboard.php', true, 302);
    exit;
} catch (Throwable $e) {
    error_log('[ScanPro API Register Error] ' . $e->getMessage());
    if (expectsJsonResponse()) {
        jsonError('Registration service is currently unavailable. Please verify your database connection.', 500);
    }
    setFlashMessage('error', 'Registration service is currently unavailable.');
    header('Location: ../../frontend/register.php', true, 302);
    exit;
}