<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../../auth/admin_auth.php';
require_once __DIR__ . '/../../helpers/validator.php';

$admin = requireAdminApiAuth();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$androidPageUrl = getAdminRootUrlPath() . '/android-app.php';

if ($method === 'GET') {
    jsonSuccess('Android configuration loaded.', [
        'config' => getAndroidAppConfig(true),
    ]);
}

if ($method !== 'POST') {
    if (expectsJsonResponse()) {
        jsonError('Method not allowed.', 405);
    }
    header('Location: ' . $androidPageUrl, true, 302);
    exit;
}

$payload = getRequestPayload();
$submittedCsrf = extractAdminCsrfToken($payload);

if (!verifyAdminCsrfToken($submittedCsrf)) {
    if (expectsJsonResponse()) {
        jsonError('Security token expired or invalid. Please refresh the page.', 403);
    }
    setAdminFlash('error', 'Security token expired or invalid.');
    header('Location: ' . $androidPageUrl, true, 302);
    exit;
}

$toBoolInt = static function (mixed $val): int {
    return filter_var($val, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
};

$parseOptionalDateTime = static function (mixed $raw): ?string {
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

$isActive = $toBoolInt($payload['is_active'] ?? 1);
$maintenanceMode = $toBoolInt($payload['maintenance_mode'] ?? 0);
$maintenanceMessage = cleanText($payload['maintenance_message'] ?? '');
$restrictedNotice = cleanText($payload['restricted_notice'] ?? '');

$currentVersion = cleanText($payload['current_version'] ?? '');
$latestVersion = cleanText($payload['latest_version'] ?? '');
$minSupportedVersion = cleanText($payload['min_supported_version'] ?? '');
$latestVersionCode = (int) ($payload['latest_version_code'] ?? 1);
$minSupportedVersionCode = (int) ($payload['min_supported_version_code'] ?? 1);

$updateAvailable = $toBoolInt($payload['update_available'] ?? 0);
$mandatoryUpdate = $toBoolInt($payload['mandatory_update'] ?? 0);
$updateTitle = cleanText($payload['update_title'] ?? '');
$updateAnnouncement = cleanText($payload['update_announcement'] ?? '');
$releaseNotes = cleanText($payload['release_notes'] ?? '');
$updateDownloadUrl = cleanText($payload['update_download_url'] ?? '');

$announcementEnabled = $toBoolInt($payload['announcement_enabled'] ?? 0);
$announcementTitle = cleanText($payload['announcement_title'] ?? '');
$announcementContent = cleanText($payload['announcement_content'] ?? '');
$announcementType = cleanText($payload['announcement_type'] ?? 'info');
if (!in_array($announcementType, ['info', 'update', 'warning'], true)) {
    $announcementType = 'info';
}

$startAt = $parseOptionalDateTime($payload['announcement_start_at'] ?? '');
$endAt = $parseOptionalDateTime($payload['announcement_end_at'] ?? '');

$errors = [];
if (!isLengthBetween($maintenanceMessage, 5, 500)) {
    $errors['maintenance_message'] = 'Maintenance message must be between 5 and 500 characters.';
}
if (mb_strlen($restrictedNotice, 'UTF-8') > 500) {
    $errors['restricted_notice'] = 'Temporary restriction notice must not exceed 500 characters.';
}
if (!isLengthBetween($currentVersion, 1, 32)) {
    $errors['current_version'] = 'Current version is required (max 32 characters).';
}
if (!isLengthBetween($latestVersion, 1, 32)) {
    $errors['latest_version'] = 'Latest version is required (max 32 characters).';
}
if (!isLengthBetween($minSupportedVersion, 1, 32)) {
    $errors['min_supported_version'] = 'Minimum supported version is required (max 32 characters).';
}
if ($latestVersionCode < 1 || $latestVersionCode > 9999999) {
    $errors['latest_version_code'] = 'Latest version code must be a positive integer.';
}
if ($minSupportedVersionCode < 1 || $minSupportedVersionCode > 9999999) {
    $errors['min_supported_version_code'] = 'Minimum version code must be a positive integer.';
} elseif ($minSupportedVersionCode > $latestVersionCode) {
    $errors['min_supported_version_code'] = 'Minimum version code cannot be greater than latest version code.';
}
if (mb_strlen($updateTitle, 'UTF-8') > 120) {
    $errors['update_title'] = 'Update title must not exceed 120 characters.';
}
if (mb_strlen($updateAnnouncement, 'UTF-8') > 500) {
    $errors['update_announcement'] = 'Update announcement must not exceed 500 characters.';
}
if (mb_strlen($releaseNotes, 'UTF-8') > 3000) {
    $errors['release_notes'] = 'Release notes must not exceed 3000 characters.';
}
if ($updateDownloadUrl !== '') {
    if (
        mb_strlen($updateDownloadUrl, 'UTF-8') > 500
        || !filter_var($updateDownloadUrl, FILTER_VALIDATE_URL)
        || preg_match('#^https?://#i', $updateDownloadUrl) !== 1
    ) {
        $errors['update_download_url'] = 'Download URL must be a valid http:// or https:// URL (max 500 chars).';
    }
}
if ($announcementEnabled === 1) {
    if (!isLengthBetween($announcementTitle, 2, 150)) {
        $errors['announcement_title'] = 'Announcement title is required (2–150 characters) when enabled.';
    }
    if (!isLengthBetween($announcementContent, 3, 2000)) {
        $errors['announcement_content'] = 'Announcement content is required (3–2000 characters) when enabled.';
    }
} else {
    if (mb_strlen($announcementTitle, 'UTF-8') > 150) {
        $errors['announcement_title'] = 'Announcement title must not exceed 150 characters.';
    }
    if (mb_strlen($announcementContent, 'UTF-8') > 2000) {
        $errors['announcement_content'] = 'Announcement content must not exceed 2000 characters.';
    }
}
if ($startAt === '__INVALID__') {
    $errors['announcement_start_at'] = 'Invalid start date/time format.';
}
if ($endAt === '__INVALID__') {
    $errors['announcement_end_at'] = 'Invalid end date/time format.';
}
if ($startAt !== null && $startAt !== '__INVALID__' && $endAt !== null && $endAt !== '__INVALID__' && strtotime($endAt) < strtotime($startAt)) {
    $errors['announcement_end_at'] = 'End date/time must be after start date/time.';
}

if (!empty($errors)) {
    if (expectsJsonResponse()) {
        jsonError('Please review the highlighted Android configuration fields.', 422, $errors);
    }
    setAdminFlash('error', 'Please review the highlighted Android configuration fields.');
    header('Location: ' . $androidPageUrl, true, 302);
    exit;
}

$previous = getAndroidAppConfig(true);
$wasMaint = ((int) ($previous['maintenance_mode'] ?? 0)) === 1;

try {
    $pdo = getDbConnection();
    $adminId = (int) $admin['id'];

    $stmt = $pdo->prepare(
        'INSERT INTO android_app_config (
            id, is_active, maintenance_mode, maintenance_message, restricted_notice,
            current_version, latest_version, min_supported_version,
            latest_version_code, min_supported_version_code,
            update_available, mandatory_update, update_title, update_announcement,
            release_notes, update_download_url,
            announcement_enabled, announcement_title, announcement_content, announcement_type,
            announcement_start_at, announcement_end_at, updated_by_admin_id, updated_at
        ) VALUES (
            1, :is_active, :maintenance_mode, :maintenance_message, :restricted_notice,
            :current_version, :latest_version, :min_supported_version,
            :latest_version_code, :min_supported_version_code,
            :update_available, :mandatory_update, :update_title, :update_announcement,
            :release_notes, :update_download_url,
            :announcement_enabled, :announcement_title, :announcement_content, :announcement_type,
            :announcement_start_at, :announcement_end_at, :updated_by_admin_id, NOW()
        ) ON DUPLICATE KEY UPDATE
            is_active = VALUES(is_active),
            maintenance_mode = VALUES(maintenance_mode),
            maintenance_message = VALUES(maintenance_message),
            restricted_notice = VALUES(restricted_notice),
            current_version = VALUES(current_version),
            latest_version = VALUES(latest_version),
            min_supported_version = VALUES(min_supported_version),
            latest_version_code = VALUES(latest_version_code),
            min_supported_version_code = VALUES(min_supported_version_code),
            update_available = VALUES(update_available),
            mandatory_update = VALUES(mandatory_update),
            update_title = VALUES(update_title),
            update_announcement = VALUES(update_announcement),
            release_notes = VALUES(release_notes),
            update_download_url = VALUES(update_download_url),
            announcement_enabled = VALUES(announcement_enabled),
            announcement_title = VALUES(announcement_title),
            announcement_content = VALUES(announcement_content),
            announcement_type = VALUES(announcement_type),
            announcement_start_at = VALUES(announcement_start_at),
            announcement_end_at = VALUES(announcement_end_at),
            updated_by_admin_id = VALUES(updated_by_admin_id),
            updated_at = NOW()'
    );

    $stmt->execute([
        'is_active'                  => $isActive,
        'maintenance_mode'           => $maintenanceMode,
        'maintenance_message'        => $maintenanceMessage,
        'restricted_notice'          => $restrictedNotice,
        'current_version'            => $currentVersion,
        'latest_version'             => $latestVersion,
        'min_supported_version'      => $minSupportedVersion,
        'latest_version_code'        => $latestVersionCode,
        'min_supported_version_code' => $minSupportedVersionCode,
        'update_available'           => $updateAvailable,
        'mandatory_update'           => $mandatoryUpdate,
        'update_title'               => $updateTitle !== '' ? $updateTitle : 'Update Available',
        'update_announcement'        => $updateAnnouncement,
        'release_notes'              => $releaseNotes,
        'update_download_url'        => $updateDownloadUrl,
        'announcement_enabled'       => $announcementEnabled,
        'announcement_title'         => $announcementTitle,
        'announcement_content'       => $announcementContent,
        'announcement_type'          => $announcementType,
        'announcement_start_at'      => $startAt,
        'announcement_end_at'        => $endAt,
        'updated_by_admin_id'        => $adminId,
    ]);

    clearRuntimeConfigCache();

    $actionName = ($wasMaint !== ($maintenanceMode === 1))
        ? 'android.maintenance_toggle'
        : 'android.update';
    $summary = ($wasMaint !== ($maintenanceMode === 1))
        ? ('Android App maintenance mode turned ' . ($maintenanceMode === 1 ? 'ON' : 'OFF'))
        : ('Updated Android App remote configuration (v' . $latestVersion . ')');

    logAdminActivity(
        $pdo,
        $adminId,
        (string) $admin['username'],
        $actionName,
        $summary,
        'android_app_config',
        '1',
        [
            'is_active'                  => $isActive,
            'maintenance_mode'           => $maintenanceMode,
            'latest_version'             => $latestVersion,
            'latest_version_code'        => $latestVersionCode,
            'min_supported_version_code' => $minSupportedVersionCode,
            'update_available'           => $updateAvailable,
            'mandatory_update'           => $mandatoryUpdate,
            'announcement_enabled'       => $announcementEnabled,
        ]
    );

    if (expectsJsonResponse()) {
        jsonSuccess('Android configuration saved successfully.', [
            'config' => getAndroidAppConfig(true),
        ]);
    }

    setAdminFlash('success', 'Android configuration saved successfully.');
    header('Location: ' . $androidPageUrl, true, 302);
    exit;
} catch (Throwable $e) {
    error_log('[ScanPro Admin Android Save Error] ' . $e->getMessage());
    if (expectsJsonResponse()) {
        jsonError('Unable to save Android configuration. Ensure migration 002 is imported.', 500);
    }
    setAdminFlash('error', 'Unable to save Android configuration.');
    header('Location: ' . $androidPageUrl, true, 302);
    exit;
}