<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../../auth/admin_auth.php';
require_once __DIR__ . '/../../helpers/validator.php';

$admin = requireAdminApiAuth();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$websitePageUrl = getAdminRootUrlPath() . '/website.php';

if ($method === 'GET') {
    jsonSuccess('Website settings loaded.', [
        'settings' => getSiteSettings(true),
    ]);
}

if ($method !== 'POST') {
    if (expectsJsonResponse()) {
        jsonError('Method not allowed.', 405);
    }
    header('Location: ' . $websitePageUrl, true, 302);
    exit;
}

$payload = getRequestPayload();
$submittedCsrf = extractAdminCsrfToken($payload);

if (!verifyAdminCsrfToken($submittedCsrf)) {
    if (expectsJsonResponse()) {
        jsonError('Security token expired or invalid. Please refresh the page.', 403);
    }
    setAdminFlash('error', 'Security token expired or invalid.');
    header('Location: ' . $websitePageUrl, true, 302);
    exit;
}

$toBoolString = static function (mixed $val): string {
    return filter_var($val, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
};

$siteName = cleanText($payload['site_name'] ?? '');
$siteTagline = cleanText($payload['site_tagline'] ?? '');
$siteDescription = cleanText($payload['site_description'] ?? '');
$maintenanceMode = $toBoolString($payload['maintenance_mode'] ?? '0');
$siteStatus = $maintenanceMode === '1' ? 'maintenance' : 'active';
$maintenanceMessage = cleanText($payload['maintenance_message'] ?? '');

$announcementEnabled = $toBoolString($payload['website_announcement_enabled'] ?? '0');
$announcementText = cleanText($payload['website_announcement_text'] ?? '');
$homepageNoticeEnabled = $toBoolString($payload['homepage_notice_enabled'] ?? '0');
$homepageNoticeText = cleanText($payload['homepage_notice_text'] ?? '');

$contactEmail = normalizeEmail($payload['contact_email'] ?? '');
$contactReceiverEmail = normalizeEmail($payload['contact_receiver_email'] ?? '');

$showWebWorkspace = $toBoolString($payload['show_web_workspace'] ?? '1');
$showPricingSection = $toBoolString($payload['show_pricing_section'] ?? '1');
$showAndroidDownload = $toBoolString($payload['show_android_download'] ?? '1');

$errors = [];
if (!isLengthBetween($siteName, 2, 80)) {
    $errors['site_name'] = 'Website name must be between 2 and 80 characters.';
}
if (mb_strlen($siteTagline, 'UTF-8') > 160) {
    $errors['site_tagline'] = 'Tagline must not exceed 160 characters.';
}
if (mb_strlen($siteDescription, 'UTF-8') > 400) {
    $errors['site_description'] = 'Description must not exceed 400 characters.';
}
if (!isLengthBetween($maintenanceMessage, 5, 500)) {
    $errors['maintenance_message'] = 'Maintenance message must be between 5 and 500 characters.';
}
if ($announcementEnabled === '1' && !isLengthBetween($announcementText, 3, 500)) {
    $errors['website_announcement_text'] = 'Enter an announcement message (3–500 characters) or turn the announcement OFF.';
} elseif (mb_strlen($announcementText, 'UTF-8') > 500) {
    $errors['website_announcement_text'] = 'Announcement message must not exceed 500 characters.';
}
if ($homepageNoticeEnabled === '1' && !isLengthBetween($homepageNoticeText, 3, 500)) {
    $errors['homepage_notice_text'] = 'Enter a homepage notice (3–500 characters) or turn the homepage notice OFF.';
} elseif (mb_strlen($homepageNoticeText, 'UTF-8') > 500) {
    $errors['homepage_notice_text'] = 'Homepage notice must not exceed 500 characters.';
}
if (!isValidEmailAddress($contactEmail)) {
    $errors['contact_email'] = 'Please enter a valid public contact email address.';
}
if (!isValidEmailAddress($contactReceiverEmail)) {
    $errors['contact_receiver_email'] = 'Please enter a valid contact receiver email address.';
}

if (!empty($errors)) {
    if (expectsJsonResponse()) {
        jsonError('Please review the highlighted website configuration fields.', 422, $errors);
    }
    setAdminFlash('error', 'Please review all required website fields.');
    header('Location: ' . $websitePageUrl, true, 302);
    exit;
}

$previous = getSiteSettings(true);
$wasMaintenance = ($previous['maintenance_mode'] ?? '0') === '1';

$updates = [
    'site_name'                    => $siteName,
    'site_tagline'                 => $siteTagline,
    'site_description'             => $siteDescription,
    'site_status'                  => $siteStatus,
    'maintenance_mode'             => $maintenanceMode,
    'maintenance_message'          => $maintenanceMessage,
    'website_announcement_enabled' => $announcementEnabled,
    'website_announcement_text'    => $announcementText,
    'homepage_notice_enabled'      => $homepageNoticeEnabled,
    'homepage_notice_text'         => $homepageNoticeText,
    'contact_email'                => $contactEmail,
    'contact_receiver_email'       => $contactReceiverEmail,
    'show_web_workspace'           => $showWebWorkspace,
    'show_pricing_section'         => $showPricingSection,
    'show_android_download'        => $showAndroidDownload,
];

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO site_settings (setting_key, setting_value, setting_group, updated_by_admin_id, updated_at)
         VALUES (:k, :v, 'website', :aid, NOW())
         ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            updated_by_admin_id = VALUES(updated_by_admin_id),
            updated_at = NOW()"
    );

    $adminId = (int) $admin['id'];
    foreach ($updates as $key => $val) {
        $stmt->execute([
            'k'   => $key,
            'v'   => $val,
            'aid' => $adminId,
        ]);
    }

    $pdo->commit();
    clearRuntimeConfigCache();

    $actionName = ($wasMaintenance !== ($maintenanceMode === '1'))
        ? 'website.maintenance_toggle'
        : 'website.update';
    $summary = ($wasMaintenance !== ($maintenanceMode === '1'))
        ? ('Website maintenance mode turned ' . ($maintenanceMode === '1' ? 'ON' : 'OFF'))
        : 'Updated Website Management settings';

    logAdminActivity(
        $pdo,
        $adminId,
        (string) $admin['username'],
        $actionName,
        $summary,
        'site_settings',
        'website',
        [
            'maintenance_mode'             => $maintenanceMode,
            'website_announcement_enabled' => $announcementEnabled,
            'homepage_notice_enabled'      => $homepageNoticeEnabled,
            'show_web_workspace'           => $showWebWorkspace,
            'show_pricing_section'         => $showPricingSection,
            'show_android_download'        => $showAndroidDownload,
        ]
    );

    if (expectsJsonResponse()) {
        jsonSuccess('Website settings saved successfully.', [
            'settings' => getSiteSettings(true),
        ]);
    }

    setAdminFlash('success', 'Website settings saved successfully.');
    header('Location: ' . $websitePageUrl, true, 302);
    exit;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[ScanPro Admin Website Save Error] ' . $e->getMessage());
    if (expectsJsonResponse()) {
        jsonError('Unable to save website settings. Ensure migration 002 is imported.', 500);
    }
    setAdminFlash('error', 'Unable to save website settings.');
    header('Location: ' . $websitePageUrl, true, 302);
    exit;
}