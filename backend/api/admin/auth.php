<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../../auth/admin_auth.php';
require_once __DIR__ . '/../../helpers/validator.php';

startAdminSession();
applyAdminSecurityHeaders();

$loginPageUrl = getAdminRootUrlPath() . '/login.php';
$dashboardUrl = getAdminRootUrlPath() . '/index.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    if (expectsJsonResponse()) {
        jsonError('Method not allowed.', 405);
    }
    header('Location: ' . $loginPageUrl, true, 302);
    exit;
}

$payload = getRequestPayload();
$submittedCsrf = extractAdminCsrfToken($payload);

if (!verifyAdminCsrfToken($submittedCsrf)) {
    if (expectsJsonResponse()) {
        jsonError('Security token expired or invalid. Please refresh the page.', 403);
    }
    setAdminFlash('error', 'Security token expired or invalid. Please try again.');
    header('Location: ' . $loginPageUrl, true, 302);
    exit;
}

$action = cleanText($payload['action'] ?? 'login');

if ($action === 'logout') {
    performAdminLogout();
    if (expectsJsonResponse()) {
        jsonSuccess('Signed out successfully.', ['redirect' => 'login.php']);
    }
    startAdminSession();
    setAdminFlash('success', 'You have signed out of the Control Center.');
    header('Location: ' . $loginPageUrl, true, 302);
    exit;
}

if ($action !== 'login') {
    if (expectsJsonResponse()) {
        jsonError('Unsupported authentication action.', 400);
    }
    header('Location: ' . $loginPageUrl, true, 302);
    exit;
}

$identity = cleanText($payload['identity'] ?? '');
$password = is_string($payload['password'] ?? null) ? (string) $payload['password'] : '';

$errors = [];
if ($identity === '' || mb_strlen($identity, 'UTF-8') > 190) {
    $errors['identity'] = 'Please enter your admin username or email.';
}
if ($password === '') {
    $errors['password'] = 'Please enter your password.';
}

if (!empty($errors)) {
    if (expectsJsonResponse()) {
        jsonError('Please enter your credentials.', 422, $errors);
    }
    setAdminFlash('error', 'Please enter your username/email and password.');
    header('Location: ' . $loginPageUrl, true, 302);
    exit;
}

try {
    $pdo = getDbConnection();
    $limitIdentity = strtolower($identity) . '|' . rateLimitClientIp();

    if (rateLimitIsBlocked(
        $pdo,
        'admin_login',
        $limitIdentity,
        ADMIN_LOGIN_MAX_ATTEMPTS_IDENTITY,
        ADMIN_LOGIN_MAX_ATTEMPTS_IP,
        ADMIN_LOGIN_WINDOW_SECONDS
    )) {
        $blockedMsg = 'Too many failed admin sign-in attempts. Please wait 15 minutes and try again.';
        if (expectsJsonResponse()) {
            jsonError($blockedMsg, 429);
        }
        setAdminFlash('error', $blockedMsg);
        header('Location: ' . $loginPageUrl, true, 302);
        exit;
    }

    $result = loginAdminWithCredentials($pdo, $identity, $password);

    if (!$result['ok']) {
        rateLimitRecordFailure($pdo, 'admin_login', $limitIdentity);
        if (expectsJsonResponse()) {
            jsonError($result['message'], 401);
        }
        setAdminFlash('error', $result['message']);
        header('Location: ' . $loginPageUrl, true, 302);
        exit;
    }

    if (expectsJsonResponse()) {
        jsonSuccess($result['message'], ['redirect' => 'index.php']);
    }

    header('Location: ' . $dashboardUrl, true, 302);
    exit;
} catch (Throwable $e) {
    error_log('[ScanPro Admin Auth API Error] ' . $e->getMessage());
    if (expectsJsonResponse()) {
        jsonError('Admin authentication service is currently unavailable.', 500);
    }
    setAdminFlash('error', 'Admin authentication service is currently unavailable.');
    header('Location: ' . $loginPageUrl, true, 302);
    exit;
}