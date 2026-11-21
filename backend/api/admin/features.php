<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../../auth/admin_auth.php';
require_once __DIR__ . '/../../helpers/validator.php';

$admin = requireAdminApiAuth();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$featuresPageUrl = getAdminRootUrlPath() . '/features.php';

if ($method === 'GET') {
    jsonSuccess('Feature flags loaded.', [
        'features' => getAllFeatureFlags(true),
    ]);
}

if ($method !== 'POST') {
    if (expectsJsonResponse()) {
        jsonError('Method not allowed.', 405);
    }
    header('Location: ' . $featuresPageUrl, true, 302);
    exit;
}

$payload = getRequestPayload();
$submittedCsrf = extractAdminCsrfToken($payload);

if (!verifyAdminCsrfToken($submittedCsrf)) {
    if (expectsJsonResponse()) {
        jsonError('Security token expired or invalid. Please refresh the page.', 403);
    }
    setAdminFlash('error', 'Security token expired or invalid.');
    header('Location: ' . $featuresPageUrl, true, 302);
    exit;
}

$action = cleanText($payload['action'] ?? 'update_feature');

if ($action !== 'update_feature') {
    if (expectsJsonResponse()) {
        jsonError('Unsupported feature action.', 400);
    }
    header('Location: ' . $featuresPageUrl, true, 302);
    exit;
}

$featureKey = cleanText($payload['feature_key'] ?? '');
$platform = cleanText($payload['platform'] ?? 'both');
$status = cleanText($payload['status'] ?? 'active');
$isEnabled = filter_var($payload['is_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
$maintMsg = cleanText($payload['maintenance_message'] ?? '');

$errors = [];
if (preg_match('/^[a-z0-9_]{2,64}$/', $featureKey) !== 1) {
    $errors['feature_key'] = 'Invalid feature key.';
}
if (!in_array($platform, ['website', 'android', 'both'], true)) {
    $errors['platform'] = 'Invalid platform selection.';
}
if (!in_array($status, ['active', 'maintenance', 'disabled'], true)) {
    $errors['status'] = 'Invalid feature status.';
}
if (mb_strlen($maintMsg, 'UTF-8') > 300) {
    $errors['maintenance_message'] = 'Maintenance message must not exceed 300 characters.';
}

if (!empty($errors)) {
    if (expectsJsonResponse()) {
        jsonError('Please review the feature settings.', 422, $errors);
    }
    setAdminFlash('error', 'Invalid feature parameters submitted.');
    header('Location: ' . $featuresPageUrl, true, 302);
    exit;
}

// Keep is_enabled and status logically synchronized
if ($status === 'disabled') {
    $isEnabled = 0;
} elseif ($isEnabled === 0 && $status === 'active') {
    $status = 'disabled';
}

try {
    $pdo = getDbConnection();
    $checkStmt = $pdo->prepare('SELECT id, feature_name, is_enabled, status FROM feature_flags WHERE feature_key = :fkey LIMIT 1');
    $checkStmt->execute(['fkey' => $featureKey]);
    $existing = $checkStmt->fetch();

    if (!is_array($existing)) {
        if (expectsJsonResponse()) {
            jsonError('Feature not found in database. Ensure migration 002 is imported.', 404);
        }
        setAdminFlash('error', 'Feature not found in database.');
        header('Location: ' . $featuresPageUrl, true, 302);
        exit;
    }

    $adminId = (int) $admin['id'];
    $upd = $pdo->prepare(
        'UPDATE feature_flags
         SET platform = :platform,
             is_enabled = :is_enabled,
             status = :status,
             maintenance_message = :msg,
             updated_by_admin_id = :aid,
             updated_at = NOW()
         WHERE feature_key = :fkey'
    );
    $upd->execute([
        'platform'    => $platform,
        'is_enabled'  => $isEnabled,
        'status'      => $status,
        'msg'         => $maintMsg,
        'aid'         => $adminId,
        'fkey'        => $featureKey,
    ]);

    clearRuntimeConfigCache();
    $updatedFeature = getFeatureByKey($featureKey);

    $featureName = (string) ($existing['feature_name'] ?? $featureKey);
    logAdminActivity(
        $pdo,
        $adminId,
        (string) $admin['username'],
        'feature.update',
        sprintf('Updated feature "%s" (%s / %s)', $featureName, $status, $platform),
        'feature_flags',
        $featureKey,
        [
            'feature_key' => $featureKey,
            'platform'    => $platform,
            'is_enabled'  => $isEnabled,
            'status'      => $status,
        ]
    );

    if (expectsJsonResponse()) {
        jsonSuccess($featureName . ' updated successfully.', [
            'feature' => $updatedFeature,
        ]);
    }

    setAdminFlash('success', $featureName . ' updated successfully.');
    header('Location: ' . $featuresPageUrl, true, 302);
    exit;
} catch (Throwable $e) {
    error_log('[ScanPro Admin Features API Error] ' . $e->getMessage());
    if (expectsJsonResponse()) {
        jsonError('Unable to update feature flag right now.', 500);
    }
    setAdminFlash('error', 'Unable to update feature flag right now.');
    header('Location: ' . $featuresPageUrl, true, 302);
    exit;
}