<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../../auth/admin_auth.php';
require_once __DIR__ . '/../../helpers/validator.php';

$admin = requireAdminApiAuth();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$adsPageUrl = getAdminRootUrlPath() . '/ads.php';

if ($method === 'GET') {
    jsonSuccess('Ads configuration loaded.', [
        'ads' => getAdRuntimeConfig(true),
    ]);
}

if ($method !== 'POST') {
    if (expectsJsonResponse()) {
        jsonError('Method not allowed.', 405);
    }
    header('Location: ' . $adsPageUrl, true, 302);
    exit;
}

$payload = getRequestPayload();
$submittedCsrf = extractAdminCsrfToken($payload);

if (!verifyAdminCsrfToken($submittedCsrf)) {
    if (expectsJsonResponse()) {
        jsonError('Security token expired or invalid. Please refresh the page.', 403);
    }
    setAdminFlash('error', 'Security token expired or invalid.');
    header('Location: ' . $adsPageUrl, true, 302);
    exit;
}

$toBoolString = static function (mixed $val): string {
    return filter_var($val, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
};

$adsEnabled = $toBoolString($payload['ads_enabled'] ?? '1');
$guestEnabled = $toBoolString($payload['ads_guest_enabled'] ?? '1');
$loggedInEnabled = $toBoolString($payload['ads_logged_in_enabled'] ?? '0');

$homeEnabled = $toBoolString($payload['ads_home_enabled'] ?? '1');
$featuresEnabled = $toBoolString($payload['ads_features_enabled'] ?? '1');
$pdfToolsEnabled = $toBoolString($payload['ads_pdf_tools_enabled'] ?? '1');
$toolPagesEnabled = $toBoolString($payload['ads_tool_pages_enabled'] ?? '1');

$scriptSrc = cleanText($payload['ads_native_script_src'] ?? '');
$containerId = cleanText($payload['ads_native_container_id'] ?? '');

$androidAdsEnabled = $toBoolString($payload['ads_android_enabled'] ?? '0');
$androidBannerId = cleanText($payload['ads_android_banner_id'] ?? '');

$errors = [];
if ($scriptSrc !== '') {
    if (
        mb_strlen($scriptSrc, 'UTF-8') > 500
        || !filter_var($scriptSrc, FILTER_VALIDATE_URL)
        || preg_match('#^https?://#i', $scriptSrc) !== 1
    ) {
        $errors['ads_native_script_src'] = 'Native ad script URL must be a valid http:// or https:// URL (max 500 chars).';
    }
}
if ($containerId !== '' && preg_match('/^[a-zA-Z0-9_-]{3,120}$/', $containerId) !== 1) {
    $errors['ads_native_container_id'] = 'Container ID may contain only letters, numbers, hyphens, and underscores (3–120 chars).';
}
if (mb_strlen($androidBannerId, 'UTF-8') > 150) {
    $errors['ads_android_banner_id'] = 'Android Ad Unit / Banner ID must not exceed 150 characters.';
}

if (!empty($errors)) {
    if (expectsJsonResponse()) {
        jsonError('Please review the highlighted ads configuration fields.', 422, $errors);
    }
    setAdminFlash('error', 'Please review the highlighted ads configuration fields.');
    header('Location: ' . $adsPageUrl, true, 302);
    exit;
}

$prev = getAdRuntimeConfig(true);
$wasEnabled = !empty($prev['ads_enabled']);

$updates = [
    'ads_enabled'             => $adsEnabled,
    'ads_guest_enabled'       => $guestEnabled,
    'ads_logged_in_enabled'   => $loggedInEnabled,
    'ads_home_enabled'        => $homeEnabled,
    'ads_features_enabled'    => $featuresEnabled,
    'ads_pdf_tools_enabled'   => $pdfToolsEnabled,
    'ads_tool_pages_enabled'  => $toolPagesEnabled,
    'ads_native_script_src'   => $scriptSrc,
    'ads_native_container_id' => $containerId,
    'ads_android_enabled'     => $androidAdsEnabled,
    'ads_android_banner_id'   => $androidBannerId,
];

try {
    $pdo = getDbConnection();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO site_settings (setting_key, setting_value, setting_group, updated_by_admin_id, updated_at)
         VALUES (:k, :v, 'ads', :aid, NOW())
         ON DUPLICATE KEY UPDATE
            setting_value = VALUES(setting_value),
            setting_group = 'ads',
            updated_by_admin_id = VALUES(updated_by_admin_id),
            updated_at = NOW()"
    );

    $adminId = (int) $admin['id'];
    foreach ($updates as $k => $v) {
        $stmt->execute([
            'k'   => $k,
            'v'   => $v,
            'aid' => $adminId,
        ]);
    }

    $pdo->commit();
    clearRuntimeConfigCache();
    $newCfg = getAdRuntimeConfig(true);

    $actionName = ($wasEnabled !== ($adsEnabled === '1')) ? 'ads.global_toggle' : 'ads.update';
    $summary = ($wasEnabled !== ($adsEnabled === '1'))
        ? ('Global Ads turned ' . ($adsEnabled === '1' ? 'ON' : 'OFF'))
        : 'Updated Ads Management settings';

    logAdminActivity(
        $pdo,
        $adminId,
        (string) $admin['username'],
        $actionName,
        $summary,
        'site_settings',
        'ads',
        [
            'ads_enabled'            => $adsEnabled,
            'ads_guest_enabled'      => $guestEnabled,
            'ads_logged_in_enabled'  => $loggedInEnabled,
            'ads_home_enabled'       => $homeEnabled,
            'ads_features_enabled'   => $featuresEnabled,
            'ads_pdf_tools_enabled'  => $pdfToolsEnabled,
            'ads_tool_pages_enabled' => $toolPagesEnabled,
            'ads_android_enabled'    => $androidAdsEnabled,
        ]
    );

    if (expectsJsonResponse()) {
        jsonSuccess('Ads settings saved successfully.', [
            'ads' => $newCfg,
        ]);
    }

    setAdminFlash('success', 'Ads settings saved successfully.');
    header('Location: ' . $adsPageUrl, true, 302);
    exit;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[ScanPro Admin Ads Save Error] ' . $e->getMessage());
    if (expectsJsonResponse()) {
        jsonError('Unable to save ads settings right now.', 500);
    }
    setAdminFlash('error', 'Unable to save ads settings.');
    header('Location: ' . $adsPageUrl, true, 302);
    exit;
}