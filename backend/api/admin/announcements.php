<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../../auth/admin_auth.php';
require_once __DIR__ . '/../../helpers/validator.php';

$admin = requireAdminApiAuth();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$annPageUrl = getAdminRootUrlPath() . '/announcements.php';

if ($method === 'GET') {
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query(
            'SELECT id, title, message, type, platform, is_active, is_dismissible, start_at, end_at, created_at, updated_at
             FROM announcements
             ORDER BY id DESC
             LIMIT 100'
        );
        jsonSuccess('Announcements loaded.', [
            'announcements' => $stmt ? ($stmt->fetchAll() ?: []) : [],
        ]);
    } catch (Throwable $e) {
        error_log('[ScanPro Admin Announcements GET Error] ' . $e->getMessage());
        jsonError('Unable to load announcements.', 500);
    }
}

if ($method !== 'POST') {
    if (expectsJsonResponse()) {
        jsonError('Method not allowed.', 405);
    }
    header('Location: ' . $annPageUrl, true, 302);
    exit;
}

$payload = getRequestPayload();
$submittedCsrf = extractAdminCsrfToken($payload);

if (!verifyAdminCsrfToken($submittedCsrf)) {
    if (expectsJsonResponse()) {
        jsonError('Security token expired or invalid. Please refresh the page.', 403);
    }
    setAdminFlash('error', 'Security token expired or invalid.');
    header('Location: ' . $annPageUrl, true, 302);
    exit;
}

$action = cleanText($payload['action'] ?? 'create');
$annId = (int) ($payload['id'] ?? 0);

$parseDateTime = static function (mixed $raw): ?string {
    $str = cleanText($raw);
    if ($str === '') {
        return null;
    }
    $str = str_replace('T', ' ', $str);
    if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $str) === 1) {
        $str .= ':00';
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $str);
    if (!$dt || $dt->format('Y-m-d H:i:s') !== $str) {
        return '__INVALID__';
    }
    return $str;
};

try {
    $pdo = getDbConnection();
    $adminId = (int) $admin['id'];
    $adminUser = (string) $admin['username'];

    if ($action === 'toggle') {
        if ($annId <= 0) {
            jsonError('Invalid announcement ID.', 422);
        }
        $rowStmt = $pdo->prepare('SELECT id, title, is_active FROM announcements WHERE id = :id LIMIT 1');
        $rowStmt->execute(['id' => $annId]);
        $existing = $rowStmt->fetch();
        if (!is_array($existing)) {
            jsonError('Announcement not found.', 404);
        }

        $nextActive = ((int) $existing['is_active']) === 1 ? 0 : 1;
        $upd = $pdo->prepare('UPDATE announcements SET is_active = :act, updated_at = NOW() WHERE id = :id');
        $upd->execute(['act' => $nextActive, 'id' => $annId]);
        clearRuntimeConfigCache();

        logAdminActivity(
            $pdo,
            $adminId,
            $adminUser,
            'announcement.toggle',
            ($nextActive === 1 ? 'Activated' : 'Deactivated') . ' announcement #' . $annId . ' ("' . (string) $existing['title'] . '")',
            'announcements',
            (string) $annId
        );

        jsonSuccess(
            $nextActive === 1 ? 'Announcement activated.' : 'Announcement deactivated.',
            ['id' => $annId, 'is_active' => $nextActive]
        );
    }

    if ($action === 'delete') {
        if ($annId <= 0) {
            jsonError('Invalid announcement ID.', 422);
        }
        $rowStmt = $pdo->prepare('SELECT id, title FROM announcements WHERE id = :id LIMIT 1');
        $rowStmt->execute(['id' => $annId]);
        $existing = $rowStmt->fetch();
        if (!is_array($existing)) {
            jsonError('Announcement not found.', 404);
        }

        $del = $pdo->prepare('DELETE FROM announcements WHERE id = :id');
        $del->execute(['id' => $annId]);
        clearRuntimeConfigCache();

        logAdminActivity(
            $pdo,
            $adminId,
            $adminUser,
            'announcement.delete',
            'Deleted announcement #' . $annId . ' ("' . (string) $existing['title'] . '")',
            'announcements',
            (string) $annId
        );

        jsonSuccess('Announcement deleted.', ['id' => $annId]);
    }

    if (!in_array($action, ['create', 'update'], true)) {
        jsonError('Unsupported announcement action.', 400);
    }

    $title = cleanText($payload['title'] ?? '');
    $message = cleanText($payload['message'] ?? '');
    $type = cleanText($payload['type'] ?? 'info');
    $platform = cleanText($payload['platform'] ?? 'both');
    $isActive = filter_var($payload['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    $isDismissible = filter_var($payload['is_dismissible'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    $startAt = $parseDateTime($payload['start_at'] ?? '');
    $endAt = $parseDateTime($payload['end_at'] ?? '');

    $errors = [];
    if (!isLengthBetween($title, 2, 150)) {
        $errors['title'] = 'Title must be between 2 and 150 characters.';
    }
    if (!isLengthBetween($message, 3, 2000)) {
        $errors['message'] = 'Message must be between 3 and 2000 characters.';
    }
    if (!in_array($type, ['info', 'update', 'warning'], true)) {
        $errors['type'] = 'Invalid announcement type.';
    }
    if (!in_array($platform, ['website', 'android', 'both'], true)) {
        $errors['platform'] = 'Invalid target platform.';
    }
    if ($startAt === '__INVALID__') {
        $errors['start_at'] = 'Invalid start date/time.';
    }
    if ($endAt === '__INVALID__') {
        $errors['end_at'] = 'Invalid end date/time.';
    }
    if ($startAt !== null && $startAt !== '__INVALID__' && $endAt !== null && $endAt !== '__INVALID__' && strtotime($endAt) < strtotime($startAt)) {
        $errors['end_at'] = 'End date/time must be after start date/time.';
    }
    if ($action === 'update' && $annId <= 0) {
        $errors['id'] = 'Missing announcement ID for update.';
    }

    if (!empty($errors)) {
        jsonError('Please review the highlighted announcement fields.', 422, $errors);
    }

    if ($action === 'create') {
        $ins = $pdo->prepare(
            'INSERT INTO announcements (title, message, type, platform, is_active, is_dismissible, start_at, end_at, created_by_admin_id, created_at, updated_at)
             VALUES (:title, :message, :type, :platform, :is_active, :is_dismissible, :start_at, :end_at, :aid, NOW(), NOW())'
        );
        $ins->execute([
            'title'          => $title,
            'message'        => $message,
            'type'           => $type,
            'platform'       => $platform,
            'is_active'      => $isActive,
            'is_dismissible' => $isDismissible,
            'start_at'       => $startAt,
            'end_at'         => $endAt,
            'aid'            => $adminId,
        ]);
        $newId = (int) $pdo->lastInsertId();
        clearRuntimeConfigCache();

        logAdminActivity(
            $pdo,
            $adminId,
            $adminUser,
            'announcement.publish',
            'Published announcement #' . $newId . ' ("' . $title . '") for ' . $platform,
            'announcements',
            (string) $newId,
            ['type' => $type, 'platform' => $platform, 'is_active' => $isActive]
        );

        jsonSuccess('Announcement published successfully.', ['id' => $newId], 201);
    }

    $upd = $pdo->prepare(
        'UPDATE announcements
         SET title = :title,
             message = :message,
             type = :type,
             platform = :platform,
             is_active = :is_active,
             is_dismissible = :is_dismissible,
             start_at = :start_at,
             end_at = :end_at,
             updated_at = NOW()
         WHERE id = :id'
    );
    $upd->execute([
        'title'          => $title,
        'message'        => $message,
        'type'           => $type,
        'platform'       => $platform,
        'is_active'      => $isActive,
        'is_dismissible' => $isDismissible,
        'start_at'       => $startAt,
        'end_at'         => $endAt,
        'id'             => $annId,
    ]);
    clearRuntimeConfigCache();

    logAdminActivity(
        $pdo,
        $adminId,
        $adminUser,
        'announcement.update',
        'Updated announcement #' . $annId . ' ("' . $title . '")',
        'announcements',
        (string) $annId,
        ['type' => $type, 'platform' => $platform, 'is_active' => $isActive]
    );

    jsonSuccess('Announcement updated successfully.', ['id' => $annId]);
} catch (Throwable $e) {
    error_log('[ScanPro Admin Announcements POST Error] ' . $e->getMessage());
    jsonError('Unable to save announcement right now.', 500);
}