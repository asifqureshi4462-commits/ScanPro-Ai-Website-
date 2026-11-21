<?php
declare(strict_types=1);

/**
 * ScanPro AI - Public Versioned Remote Configuration API for Android Application (v1)
 *
 * Exposes ONLY minimal public configuration needed by com.aistudio.scanproai.app:
 * - Application active & maintenance status
 * - Version & mandatory update evaluation (accepts optional ?version_code=<int>)
 * - Active Android announcements (app-level + unified announcements center)
 * - Supported Android feature flags
 * - Android ad configuration
 *
 * Never exposes admin credentials, internal settings, or user PII.
 */

require_once __DIR__ . '/../../helpers/site_runtime.php';
require_once __DIR__ . '/../../helpers/response.php';

applySecurityHeaders();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    jsonError('Method not allowed. Use GET.', 405);
}

$clientVersionCode = 0;
if (isset($_GET['version_code']) && is_string($_GET['version_code']) && ctype_digit($_GET['version_code'])) {
    $clientVersionCode = (int) $_GET['version_code'];
}

try {
    $cfg = getAndroidAppConfig(true);
    $adCfg = getAdRuntimeConfig(true);
    $allFlags = getAllFeatureFlags(true);

    $latestCode = (int) ($cfg['latest_version_code'] ?? 1);
    $minCode = (int) ($cfg['min_supported_version_code'] ?? 1);
    $updateAvailableFlag = ((int) ($cfg['update_available'] ?? 0)) === 1;
    $mandatoryFlag = ((int) ($cfg['mandatory_update'] ?? 0)) === 1;

    $forceUpdateRequired = false;
    $updateRecommended = $updateAvailableFlag;

    if ($clientVersionCode > 0) {
        if ($clientVersionCode < $minCode || ($mandatoryFlag && $clientVersionCode < $latestCode)) {
            $forceUpdateRequired = true;
            $updateRecommended = true;
        } elseif ($clientVersionCode < $latestCode) {
            $updateRecommended = true;
        } else {
            $updateRecommended = false;
        }
    } elseif ($mandatoryFlag && $updateAvailableFlag) {
        $forceUpdateRequired = true;
    }

    // Build Android announcements list (app-specific announcement + unified Announcement Center)
    $announcements = [];
    if (((int) ($cfg['announcement_enabled'] ?? 0)) === 1) {
        $now = time();
        $startOk = empty($cfg['announcement_start_at']) || strtotime((string) $cfg['announcement_start_at']) <= $now;
        $endOk = empty($cfg['announcement_end_at']) || strtotime((string) $cfg['announcement_end_at']) >= $now;
        $title = trim((string) ($cfg['announcement_title'] ?? ''));
        $content = trim((string) ($cfg['announcement_content'] ?? ''));

        if ($startOk && $endOk && ($title !== '' || $content !== '')) {
            $announcements[] = [
                'id'          => 'app_primary',
                'title'       => $title,
                'message'     => $content,
                'type'        => (string) ($cfg['announcement_type'] ?? 'info'),
                'dismissible' => true,
                'start_at'    => $cfg['announcement_start_at'] ?? null,
                'end_at'      => $cfg['announcement_end_at'] ?? null,
            ];
        }
    }

    foreach (getActiveAnnouncements('android') as $row) {
        $announcements[] = [
            'id'          => 'ann_' . (int) $row['id'],
            'title'       => (string) ($row['title'] ?? ''),
            'message'     => (string) ($row['message'] ?? ''),
            'type'        => (string) ($row['type'] ?? 'info'),
            'dismissible' => ((int) ($row['is_dismissible'] ?? 1)) === 1,
            'start_at'    => $row['start_at'] ?? null,
            'end_at'      => $row['end_at'] ?? null,
        ];
    }

    // Expose only features targeting 'android' or 'both'
    $androidFeatures = [];
    foreach ($allFlags as $flag) {
        $platform = (string) ($flag['platform'] ?? 'both');
        if ($platform !== 'android' && $platform !== 'both') {
            continue;
        }
        $key = (string) ($flag['feature_key'] ?? '');
        if ($key === '') {
            continue;
        }
        $status = (string) ($flag['status'] ?? 'active');
        $enabled = ((int) ($flag['is_enabled'] ?? 1)) === 1 && $status === 'active';
        $androidFeatures[$key] = [
            'name'                => (string) ($flag['feature_name'] ?? $key),
            'enabled'             => $enabled,
            'status'              => $enabled ? 'active' : ($status === 'active' ? 'disabled' : $status),
            'maintenance_message' => (string) ($flag['maintenance_message'] ?? ''),
        ];
    }

    $androidAdsEnabled = !empty($adCfg['ads_enabled']) && !empty($adCfg['android_ads_enabled']);

    sendJsonResponse([
        'success'      => true,
        'api_version'  => '1.0',
        'generated_at' => gmdate('c'),
        'app'          => [
            'is_active'           => ((int) ($cfg['is_active'] ?? 1)) === 1,
            'maintenance_mode'    => ((int) ($cfg['maintenance_mode'] ?? 0)) === 1,
            'maintenance_message' => (string) ($cfg['maintenance_message'] ?? ''),
            'restricted_notice'   => (string) ($cfg['restricted_notice'] ?? ''),
        ],
        'version'      => [
            'current_version'            => (string) ($cfg['current_version'] ?? '1.0'),
            'latest_version'             => (string) ($cfg['latest_version'] ?? '1.0'),
            'min_supported_version'      => (string) ($cfg['min_supported_version'] ?? '1.0'),
            'latest_version_code'        => $latestCode,
            'min_supported_version_code' => $minCode,
            'update_available'           => $updateAvailableFlag,
            'mandatory_update'           => $mandatoryFlag,
            'force_update_required'      => $forceUpdateRequired,
            'update_recommended'         => $updateRecommended,
            'update_title'               => (string) ($cfg['update_title'] ?? 'Update Available'),
            'update_announcement'        => (string) ($cfg['update_announcement'] ?? ''),
            'release_notes'              => (string) ($cfg['release_notes'] ?? ''),
            'download_url'               => (string) ($cfg['update_download_url'] ?? ''),
        ],
        'announcements' => $announcements,
        'features'      => $androidFeatures,
        'ads'           => [
            'android_enabled' => $androidAdsEnabled,
            'banner_id'       => $androidAdsEnabled ? (string) ($adCfg['android_banner_id'] ?? '') : '',
        ],
    ], 200);
} catch (Throwable $e) {
    error_log('[ScanPro Public App Config API Error] ' . $e->getMessage());
    jsonError('Unable to load application configuration right now.', 500);
}