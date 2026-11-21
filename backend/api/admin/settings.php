<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../../auth/admin_auth.php';
require_once __DIR__ . '/../../helpers/validator.php';

$admin = requireAdminApiAuth();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$settingsPageUrl = getAdminRootUrlPath() . '/settings.php';

if ($method === 'GET') {
    jsonSuccess('Admin settings loaded.', [
        'admin' => [
            'id'            => (int) $admin['id'],
            'username'      => (string) $admin['username'],
            'email'         => (string) $admin['email'],
            'full_name'     => (string) $admin['full_name'],
            'role'          => (string) $admin['role'],
            'last_login_at' => $admin['last_login_at'] ?? null,
        ],
        'admin_session_timeout' => getConfiguredAdminIdleTimeout(),
    ]);
}

if ($method !== 'POST') {
    if (expectsJsonResponse()) {
        jsonError('Method not allowed.', 405);
    }
    header('Location: ' . $settingsPageUrl, true, 302);
    exit;
}

$payload = getRequestPayload();
$submittedCsrf = extractAdminCsrfToken($payload);

if (!verifyAdminCsrfToken($submittedCsrf)) {
    if (expectsJsonResponse()) {
        jsonError('Security token expired or invalid. Please refresh the page.', 403);
    }
    setAdminFlash('error', 'Security token expired or invalid.');
    header('Location: ' . $settingsPageUrl, true, 302);
    exit;
}

$action = cleanText($payload['action'] ?? '');
$adminId = (int) $admin['id'];
$adminUsername = (string) $admin['username'];

$fail = static function (string $msg, int $status = 422, array $errors = []) use ($settingsPageUrl): void {
    if (expectsJsonResponse()) {
        jsonError($msg, $status, $errors);
    }
    setAdminFlash('error', $msg);
    header('Location: ' . $settingsPageUrl, true, 302);
    exit;
};

try {
    $pdo = getDbConnection();

    if ($action === 'update_profile') {
        $fullName = cleanText($payload['full_name'] ?? '');
        $email = normalizeEmail($payload['email'] ?? '');

        $errors = [];
        if (!isLengthBetween($fullName, 2, 100)) {
            $errors['full_name'] = 'Full name must be between 2 and 100 characters.';
        }
        if (!isValidEmailAddress($email)) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if (!empty($errors)) {
            $fail('Please fix the highlighted profile fields.', 422, $errors);
        }

        $dupStmt = $pdo->prepare('SELECT id FROM admins WHERE email = :email AND id <> :id LIMIT 1');
        $dupStmt->execute(['email' => $email, 'id' => $adminId]);
        if ($dupStmt->fetch()) {
            $fail('Another admin account already uses this email address.', 409, ['email' => 'Email already in use.']);
        }

        $upd = $pdo->prepare('UPDATE admins SET full_name = :name, email = :email, updated_at = NOW() WHERE id = :id');
        $upd->execute([
            'name'  => $fullName,
            'email' => $email,
            'id'    => $adminId,
        ]);

        $_SESSION['admin_name'] = $fullName;
        $_SESSION['admin_email'] = $email;

        logAdminActivity(
            $pdo,
            $adminId,
            $adminUsername,
            'admin.profile_update',
            'Updated administrator profile details',
            'admin',
            (string) $adminId
        );

        if (expectsJsonResponse()) {
            jsonSuccess('Admin profile updated successfully.', [
                'full_name' => $fullName,
                'email'     => $email,
            ]);
        }
        setAdminFlash('success', 'Admin profile updated successfully.');
        header('Location: ' . $settingsPageUrl, true, 302);
        exit;
    }

    if ($action === 'change_password') {
        $currentPassword = is_string($payload['current_password'] ?? null) ? (string) $payload['current_password'] : '';
        $newPassword = is_string($payload['new_password'] ?? null) ? (string) $payload['new_password'] : '';
        $confirmPassword = is_string($payload['confirm_password'] ?? null) ? (string) $payload['confirm_password'] : '';

        $errors = [];
        if ($currentPassword === '') {
            $errors['current_password'] = 'Current password is required.';
        }
        $newLen = mb_strlen($newPassword, 'UTF-8');
        if ($newLen < ADMIN_PASSWORD_MIN_LENGTH || $newLen > 128) {
            $errors['new_password'] = 'New password must be between ' . ADMIN_PASSWORD_MIN_LENGTH . ' and 128 characters.';
        } elseif (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/\d/', $newPassword)) {
            $errors['new_password'] = 'New password must include both letters and numbers.';
        }
        if ($confirmPassword !== $newPassword) {
            $errors['confirm_password'] = 'New passwords do not match.';
        }

        if (!empty($errors)) {
            $fail('Please fix the password fields.', 422, $errors);
        }

        $stmt = $pdo->prepare('SELECT password_hash FROM admins WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $adminId]);
        $row = $stmt->fetch();

        if (!is_array($row) || !password_verify($currentPassword, (string) $row['password_hash'])) {
            $fail('Current password is incorrect.', 400, ['current_password' => 'Current password does not match.']);
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE admins SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
        $upd->execute([
            'hash' => $newHash,
            'id'   => $adminId,
        ]);

        regenerateAdminSession();

        logAdminActivity(
            $pdo,
            $adminId,
            $adminUsername,
            'admin.password_change',
            'Administrator changed account password',
            'admin',
            (string) $adminId
        );

        if (expectsJsonResponse()) {
            jsonSuccess('Admin password updated successfully.');
        }
        setAdminFlash('success', 'Admin password updated successfully.');
        header('Location: ' . $settingsPageUrl, true, 302);
        exit;
    }

    if ($action === 'update_security') {
        $timeout = (int) ($payload['admin_session_timeout'] ?? ADMIN_SESSION_IDLE_TIMEOUT);
        if ($timeout < 300 || $timeout > 86400) {
            $fail('Session idle timeout must be between 300 and 86400 seconds.', 422);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO site_settings (setting_key, setting_value, setting_group, updated_by_admin_id, updated_at)
             VALUES ('admin_session_timeout', :val, 'security', :aid, NOW())
             ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                setting_group = 'security',
                updated_by_admin_id = VALUES(updated_by_admin_id),
                updated_at = NOW()"
        );
        $stmt->execute([
            'val' => (string) $timeout,
            'aid' => $adminId,
        ]);

        clearRuntimeConfigCache();

        logAdminActivity(
            $pdo,
            $adminId,
            $adminUsername,
            'admin.security_update',
            'Updated admin session idle timeout to ' . $timeout . 's',
            'site_settings',
            'admin_session_timeout',
            ['admin_session_timeout' => $timeout]
        );

        if (expectsJsonResponse()) {
            jsonSuccess('Security settings saved.', ['admin_session_timeout' => $timeout]);
        }
        setAdminFlash('success', 'Security settings saved.');
        header('Location: ' . $settingsPageUrl, true, 302);
        exit;
    }

    $fail('Unsupported settings action.', 400);
} catch (Throwable $e) {
    error_log('[ScanPro Admin Settings API Error] ' . $e->getMessage());
    $fail('Unable to update admin settings right now.', 500);
}