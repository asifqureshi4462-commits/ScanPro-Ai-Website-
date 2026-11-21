<?php
declare(strict_types=1);
/**
 * ScanPro AI - Admin User Management API
 */
require_once __DIR__ . '/../../auth/admin_auth.php';
require_once __DIR__ . '/../../helpers/validator.php';

$admin = requireAdminApiAuth();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$payload = getRequestPayload();
$submittedCsrf = extractAdminCsrfToken($payload);
if (!verifyAdminCsrfToken($submittedCsrf)) {
    jsonError('Security token expired or invalid. Please refresh the page.', 403);
}

$action = cleanText($payload['action'] ?? '');
if ($action !== 'set_status') {
    jsonError('Unsupported user action.', 400);
}

$userId = filter_var($payload['user_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);
$status = cleanText($payload['status'] ?? '');
if ($userId === false || !in_array($status, ['active', 'suspended'], true)) {
    jsonError('Invalid user status request.', 422);
}

try {
    $pdo = getDbConnection();
    $find = $pdo->prepare(
        'SELECT id, email, role, status FROM users WHERE id = :id LIMIT 1'
    );
    $find->execute(['id' => (int) $userId]);
    $user = $find->fetch();
    if (!is_array($user)) {
        jsonError('User not found.', 404);
    }

    if (($user['role'] ?? 'user') === 'admin') {
        jsonError('Administrator accounts must be managed separately.', 403);
    }

    if (($user['status'] ?? '') === $status) {
        jsonSuccess('User status is already ' . $status . '.', ['status' => $status]);
    }

    $update = $pdo->prepare(
        'UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id'
    );
    $update->execute([
        'status' => $status,
        'id'     => (int) $userId,
    ]);

    logAdminActivity(
        $pdo,
        (int) ($admin['id'] ?? 0),
        (string) ($admin['username'] ?? ''),
        'user.status_updated',
        'Updated user account status',
        'user',
        (string) $userId,
        ['status' => $status, 'email' => (string) ($user['email'] ?? '')]
    );

    jsonSuccess('User status updated successfully.', [
        'status'  => $status,
        'user_id' => (int) $userId,
    ]);
} catch (Throwable $e) {
    error_log('[ScanPro Admin User Status Error] ' . $e->getMessage());
    jsonError('Unable to update the user status right now.', 500);
}
