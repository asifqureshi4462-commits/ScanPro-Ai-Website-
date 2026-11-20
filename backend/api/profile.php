<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../auth/auth_guard.php';
require_once __DIR__ . '/../auth/auth_service.php';

startSecureSession();
applySecurityHeaders();

if (!isAuthenticated()) {
    if (expectsJsonResponse()) {
        jsonError('Authentication required. Please sign in first.', 401);
    }
    setFlashMessage('warning', 'Please sign in to manage your profile.');
    header('Location: ../../frontend/login.php', true, 302);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function profileFail(string $message, int $status = 400, array $errors = []): void
{
    if (expectsJsonResponse()) {
        jsonError($message, $status, $errors);
    }
    setFlashMessage('error', $message);
    header('Location: ../../frontend/dashboard.php', true, 302);
    exit;
}

try {
    $pdo = getDbConnection();

    if ($method === 'GET') {
        $profile = fetchUserDashboardProfile($pdo, $userId);
        if (!$profile) {
            jsonError('Account profile not found.', 404);
        }
        jsonSuccess('Profile loaded.', ['profile' => $profile]);
    }

    if ($method !== 'POST') {
        profileFail('Method not allowed.', 405);
    }

    $payload = getRequestPayload();
    $submittedCsrf = extractRequestCsrfToken() ?? (isset($payload['csrf_token']) ? (string) $payload['csrf_token'] : null);

    if (!verifyCsrfToken($submittedCsrf)) {
        profileFail('Security token expired or invalid. Please refresh the page.', 403);
    }

    $action = cleanText($payload['action'] ?? 'update_profile');

    if ($action === 'change_password') {
        $currentPassword = is_string($payload['current_password'] ?? null) ? (string) $payload['current_password'] : '';
        $newPassword = is_string($payload['new_password'] ?? null) ? (string) $payload['new_password'] : '';
        $confirmNewPassword = is_string($payload['confirm_new_password'] ?? null) ? (string) $payload['confirm_new_password'] : '';

        $errors = [];
        if ($currentPassword === '') {
            $errors['current_password'] = 'Please enter your current password.';
        }
        $policyError = validatePasswordPolicy($newPassword);
        if ($policyError !== null) {
            $errors['new_password'] = $policyError;
        }
        if ($confirmNewPassword !== $newPassword) {
            $errors['confirm_new_password'] = 'New passwords do not match.';
        }

        if (!empty($errors)) {
            profileFail('Please fix the password fields.', 422, $errors);
        }

        $res = changeUserPassword($pdo, $userId, $currentPassword, $newPassword);
        if (!$res['ok']) {
            profileFail($res['message'], 400, $res['errors'] ?? []);
        }

        if (expectsJsonResponse()) {
            jsonSuccess($res['message']);
        }
        setFlashMessage('success', $res['message']);
        header('Location: ../../frontend/dashboard.php', true, 302);
        exit;
    }

    // Default action: update_profile
    $fullName = cleanText($payload['full_name'] ?? '');
    $phone = cleanText($payload['phone'] ?? '');
    $organization = cleanText($payload['organization'] ?? '');
    $bio = cleanText($payload['bio'] ?? '');
    $preferredLanguage = cleanText($payload['preferred_language'] ?? 'English');

    $allowedLanguages = ['English', 'Spanish', 'French', 'German', 'Hindi', 'Urdu', 'Japanese'];
    if (!in_array($preferredLanguage, $allowedLanguages, true)) {
        $preferredLanguage = 'English';
    }

    $errors = [];
    if (!isLengthBetween($fullName, 2, 100)) {
        $errors['full_name'] = 'Full name must be between 2 and 100 characters.';
    }
    if ($phone !== '' && mb_strlen($phone, 'UTF-8') > 30) {
        $errors['phone'] = 'Phone number must not exceed 30 characters.';
    }
    if ($organization !== '' && mb_strlen($organization, 'UTF-8') > 120) {
        $errors['organization'] = 'Organization must not exceed 120 characters.';
    }
    if ($bio !== '' && mb_strlen($bio, 'UTF-8') > 500) {
        $errors['bio'] = 'Bio must not exceed 500 characters.';
    }

    if (!empty($errors)) {
        profileFail('Please correct the highlighted profile fields.', 422, $errors);
    }

    $result = saveUserProfileChanges(
        $pdo,
        $userId,
        $fullName,
        $phone,
        $organization,
        $bio,
        $preferredLanguage
    );

    if (!$result['ok']) {
        profileFail($result['message'], 500);
    }

    if (expectsJsonResponse()) {
        $updatedProfile = fetchUserDashboardProfile($pdo, $userId);
        jsonSuccess($result['message'], ['profile' => $updatedProfile]);
    }

    setFlashMessage('success', $result['message']);
    header('Location: ../../frontend/dashboard.php', true, 302);
    exit;
} catch (Throwable $e) {
    error_log('[ScanPro API Profile Error] ' . $e->getMessage());
    profileFail('Profile service is currently unavailable.', 500);
}