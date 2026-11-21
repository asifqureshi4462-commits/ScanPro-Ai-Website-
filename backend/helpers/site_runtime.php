<?php
declare(strict_types=1);

/**
 * ScanPro AI - Runtime Configuration, Feature Flag & Announcement Loader
 *
 * Reads live configuration from MySQL with safe in-request caching and automatic
 * fallback to backend/config/app.php and backend/config/ads.php defaults if
 * tables are missing or the database is temporarily unavailable.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

if (is_file(__DIR__ . '/../config/ads.php')) {
    require_once __DIR__ . '/../config/ads.php';
}

/**
 * Internal per-request runtime cache.
 *
 * @return array<string, mixed>
 */
function &_scanproRuntimeCache(): array
{
    static $cache = [];
    return $cache;
}

/**
 * Clears cached runtime values (used after Admin updates settings).
 */
function clearRuntimeConfigCache(): void
{
    $cache = &_scanproRuntimeCache();
    $cache = [];
}

/**
 * Returns default site & ads settings matching app.php and ads.php.
 *
 * @return array<string, string>
 */
function getDefaultSiteSettings(): array
{
    return [
        'site_name'                    => APP_NAME,
        'site_tagline'                 => APP_TAGLINE,
        'site_description'             => 'ScanPro AI is an all-in-one AI document scanner, OCR engine, PDF studio, and secure cloud vault for mobile and web productivity.',
        'site_status'                  => 'active',
        'maintenance_mode'             => '0',
        'maintenance_message'          => 'ScanPro AI is undergoing scheduled maintenance. Please check back shortly.',
        'website_announcement_enabled' => '0',
        'website_announcement_text'    => '',
        'homepage_notice_enabled'      => '0',
        'homepage_notice_text'         => '',
        'contact_email'                => APP_SUPPORT_EMAIL,
        'contact_receiver_email'       => CONTACT_RECEIVER_EMAIL,
        'show_web_workspace'           => '1',
        'show_pricing_section'         => '1',
        'show_android_download'        => '1',
        'ads_enabled'                  => defined('ADS_ENABLED') && ADS_ENABLED ? '1' : '0',
        'ads_guest_enabled'            => '1',
        'ads_logged_in_enabled'        => defined('ADS_HIDE_FOR_LOGGED_IN') && ADS_HIDE_FOR_LOGGED_IN ? '0' : '1',
        'ads_home_enabled'             => '1',
        'ads_features_enabled'         => '1',
        'ads_pdf_tools_enabled'        => '1',
        'ads_tool_pages_enabled'       => defined('ADS_ON_TOOL_PAGES') && ADS_ON_TOOL_PAGES ? '1' : '0',
        'ads_native_script_src'        => defined('ADS_NATIVE_SCRIPT_SRC') ? ADS_NATIVE_SCRIPT_SRC : '',
        'ads_native_container_id'      => defined('ADS_NATIVE_CONTAINER_ID') ? ADS_NATIVE_CONTAINER_ID : '',
        'ads_android_enabled'          => '0',
        'ads_android_banner_id'        => '',
        'admin_session_timeout'        => '1800',
    ];
}

/**
 * Loads all site_settings rows merged over safe defaults.
 *
 * @return array<string, string>
 */
function getSiteSettings(bool $fresh = false): array
{
    $cache = &_scanproRuntimeCache();
    if (!$fresh && isset($cache['site_settings'])) {
        return $cache['site_settings'];
    }

    $settings = getDefaultSiteSettings();

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM site_settings');
        if ($stmt) {
            foreach ($stmt->fetchAll() as $row) {
                $key = (string) ($row['setting_key'] ?? '');
                if ($key !== '') {
                    $settings[$key] = (string) ($row['setting_value'] ?? '');
                }
            }
        }
    } catch (Throwable $e) {
        error_log('[ScanPro Runtime Settings Fallback] ' . $e->getMessage());
    }

    $cache['site_settings'] = $settings;
    return $settings;
}

/**
 * Reads a single runtime site setting with safe default.
 */
function getSiteSetting(string $key, string $default = ''): string
{
    $all = getSiteSettings();
    return array_key_exists($key, $all) ? (string) $all[$key] : $default;
}

/**
 * Checks a boolean setting ('1', 'true', 'on', 'yes').
 */
function isSiteSettingEnabled(string $key, bool $default = false): bool
{
    $all = getSiteSettings();
    if (!array_key_exists($key, $all)) {
        return $default;
    }
    return in_array(strtolower(trim((string) $all[$key])), ['1', 'true', 'on', 'yes'], true);
}

/**
 * Returns structured Ads configuration combining DB overrides and ads.php defaults.
 *
 * @return array<string, mixed>
 */
function getAdRuntimeConfig(bool $fresh = false): array
{
    $s = getSiteSettings($fresh);
    $placements = [];
    if (($s['ads_home_enabled'] ?? '1') === '1') {
        $placements[] = 'home';
    }
    if (($s['ads_features_enabled'] ?? '1') === '1') {
        $placements[] = 'features';
    }
    if (($s['ads_pdf_tools_enabled'] ?? '1') === '1') {
        $placements[] = 'pdf-tools';
    }
    if (($s['ads_tool_pages_enabled'] ?? '1') === '1') {
        $placements[] = 'tool';
    }

    return [
        'ads_enabled'             => ($s['ads_enabled'] ?? '1') === '1',
        'guest_enabled'           => ($s['ads_guest_enabled'] ?? '1') === '1',
        'logged_in_enabled'       => ($s['ads_logged_in_enabled'] ?? '0') === '1',
        'placements'              => $placements,
        'home_enabled'            => ($s['ads_home_enabled'] ?? '1') === '1',
        'features_enabled'        => ($s['ads_features_enabled'] ?? '1') === '1',
        'pdf_tools_enabled'       => ($s['ads_pdf_tools_enabled'] ?? '1') === '1',
        'tool_pages_enabled'      => ($s['ads_tool_pages_enabled'] ?? '1') === '1',
        'native_script_src'       => (string) ($s['ads_native_script_src'] ?? ''),
        'native_container_id'     => (string) ($s['ads_native_container_id'] ?? ''),
        'android_ads_enabled'     => ($s['ads_android_enabled'] ?? '0') === '1',
        'android_banner_id'       => (string) ($s['ads_android_banner_id'] ?? ''),
    ];
}

/**
 * Returns default Android configuration matching app.php constants.
 *
 * @return array<string, mixed>
 */
function getDefaultAndroidAppConfig(): array
{
    return [
        'id'                         => 1,
        'is_active'                  => 1,
        'maintenance_mode'           => 0,
        'maintenance_message'        => 'ScanPro AI Android app is undergoing scheduled maintenance.',
        'restricted_notice'          => '',
        'current_version'            => defined('ANDROID_APP_VERSION') ? ANDROID_APP_VERSION : '1.0',
        'latest_version'             => defined('ANDROID_APP_VERSION') ? ANDROID_APP_VERSION : '1.0',
        'min_supported_version'      => defined('ANDROID_APP_VERSION') ? ANDROID_APP_VERSION : '1.0',
        'latest_version_code'        => 1,
        'min_supported_version_code' => 1,
        'update_available'           => 0,
        'mandatory_update'           => 0,
        'update_title'               => 'Update Available',
        'update_announcement'        => '',
        'release_notes'              => '',
        'update_download_url'        => defined('ANDROID_APK_URL') ? ANDROID_APK_URL : '',
        'announcement_enabled'       => 0,
        'announcement_title'         => '',
        'announcement_content'       => '',
        'announcement_type'          => 'info',
        'announcement_start_at'      => null,
        'announcement_end_at'        => null,
    ];
}

/**
 * Loads Android application remote configuration from DB with safe fallback.
 *
 * @return array<string, mixed>
 */
function getAndroidAppConfig(bool $fresh = false): array
{
    $cache = &_scanproRuntimeCache();
    if (!$fresh && isset($cache['android_app_config'])) {
        return $cache['android_app_config'];
    }

    $config = getDefaultAndroidAppConfig();

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query('SELECT * FROM android_app_config WHERE id = 1 LIMIT 1');
        $row = $stmt ? $stmt->fetch() : false;
        if (is_array($row)) {
            $config = array_merge($config, $row);
        }
    } catch (Throwable $e) {
        error_log('[ScanPro Runtime Android Config Fallback] ' . $e->getMessage());
    }

    $cache['android_app_config'] = $config;
    return $config;
}

/**
 * Returns fallback feature flags list when DB table is unavailable.
 *
 * @return array<int, array<string, mixed>>
 */
function getDefaultFeatureFlags(): array
{
    $items = [
        ['ai_scanner', 'AI Scanner', 'core', 'both', null, 10],
        ['document_scanner', 'Document Scanner', 'core', 'android', null, 20],
        ['id_scanner', 'ID Scanner', 'core', 'android', null, 30],
        ['smart_eraser', 'Smart Eraser', 'ai_tool', 'android', null, 40],
        ['ai_tools', 'AI Tools (Gemini Suite)', 'ai_tool', 'android', null, 50],
        ['ocr', 'OCR Text Extraction', 'ai_tool', 'both', null, 60],
        ['pdf_editor', 'PDF Editor & Web Workspace', 'core', 'both', 'workspace', 70],
        ['files_to_zip', 'Files to ZIP / 7Z', 'archive_tool', 'website', 'files-to-zip', 80],
        ['merge_pdf', 'Merge PDF', 'pdf_tool', 'both', 'merge-pdf', 90],
        ['split_pdf', 'Split PDF', 'pdf_tool', 'both', 'split-pdf', 100],
        ['compress_pdf', 'Compress PDF', 'pdf_tool', 'both', 'compress-pdf', 110],
        ['image_to_pdf', 'Image to PDF (JPG to PDF)', 'pdf_tool', 'both', 'jpg-to-pdf', 120],
        ['pdf_to_image', 'PDF to Image (PDF to JPG)', 'pdf_tool', 'both', 'pdf-to-jpg', 130],
        ['rotate_pdf', 'Rotate PDF', 'pdf_tool', 'website', 'rotate-pdf', 140],
        ['watermark_pdf', 'Watermark PDF', 'pdf_tool', 'website', 'watermark-pdf', 150],
        ['add_page_numbers', 'Add Page Numbers', 'pdf_tool', 'website', 'add-page-numbers', 160],
        ['extract_pages', 'Extract PDF Pages', 'pdf_tool', 'website', 'extract-pages', 170],
        ['reorder_pages', 'Reorder PDF Pages', 'pdf_tool', 'website', 'reorder-pages', 180],
        ['remove_pages', 'Delete PDF Pages', 'pdf_tool', 'website', 'remove-pages', 190],
        ['crop_pdf', 'Crop PDF', 'pdf_tool', 'website', 'crop-pdf', 200],
        ['pdf_to_text', 'PDF to Text', 'pdf_tool', 'website', 'pdf-to-text', 210],
        ['pdf_to_word', 'PDF to Word', 'pdf_tool', 'website', 'pdf-to-word', 220],
        ['pdf_to_markdown', 'PDF to Markdown', 'pdf_tool', 'website', 'pdf-to-markdown', 230],
        ['repair_pdf', 'Repair PDF', 'pdf_tool', 'website', 'repair-pdf', 240],
        ['compare_pdf', 'Compare PDF', 'pdf_tool', 'website', 'compare-pdf', 250],
        ['sign_pdf', 'Sign PDF', 'pdf_tool', 'website', 'sign-pdf', 260],
        ['redact_pdf', 'Redact PDF', 'pdf_tool', 'website', 'redact-pdf', 270],
    ];

    $out = [];
    foreach ($items as $idx => $row) {
        $out[] = [
            'id'                  => $idx + 1,
            'feature_key'         => $row[0],
            'feature_name'        => $row[1],
            'category'            => $row[2],
            'platform'            => $row[3],
            'is_enabled'          => 1,
            'status'              => 'active',
            'maintenance_message' => '',
            'web_tool_slug'       => $row[4],
            'sort_order'          => $row[5],
        ];
    }
    return $out;
}

/**
 * Loads all feature flags ordered by sort_order.
 *
 * @return array<int, array<string, mixed>>
 */
function getAllFeatureFlags(bool $fresh = false): array
{
    $cache = &_scanproRuntimeCache();
    if (!$fresh && isset($cache['feature_flags'])) {
        return $cache['feature_flags'];
    }

    $flags = getDefaultFeatureFlags();

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query(
            'SELECT id, feature_key, feature_name, category, platform, is_enabled, status, maintenance_message, web_tool_slug, sort_order, updated_at
             FROM feature_flags
             ORDER BY sort_order ASC, id ASC'
        );
        $rows = $stmt ? $stmt->fetchAll() : [];
        if (!empty($rows)) {
            $flags = $rows;
        }
    } catch (Throwable $e) {
        error_log('[ScanPro Runtime Feature Flags Fallback] ' . $e->getMessage());
    }

    $cache['feature_flags'] = $flags;
    return $flags;
}

/**
 * Finds a feature flag row by feature_key.
 *
 * @return array<string, mixed>|null
 */
function getFeatureByKey(string $featureKey): ?array
{
    foreach (getAllFeatureFlags() as $flag) {
        if (($flag['feature_key'] ?? '') === $featureKey) {
            return $flag;
        }
    }
    return null;
}

/**
 * Finds a feature flag row by website tool slug (e.g. 'files-to-zip', 'merge-pdf', 'workspace').
 *
 * @return array<string, mixed>|null
 */
function getFeatureByWebToolSlug(string $toolSlug): ?array
{
    foreach (getAllFeatureFlags() as $flag) {
        if (($flag['web_tool_slug'] ?? null) === $toolSlug) {
            return $flag;
        }
    }
    return null;
}

/**
 * Checks whether a feature is active for the requested platform ('website' or 'android').
 */
function isPlatformFeatureAvailable(string $featureKey, string $platform = 'website'): bool
{
    $flag = getFeatureByKey($featureKey);
    if ($flag === null) {
        return true;
    }
    $targetPlatform = (string) ($flag['platform'] ?? 'both');
    if ($targetPlatform !== 'both' && $targetPlatform !== $platform) {
        return false;
    }
    return ((int) ($flag['is_enabled'] ?? 1)) === 1 && ($flag['status'] ?? 'active') === 'active';
}

/**
 * Checks whether a website tool slug is currently active or disabled/maintenance.
 *
 * @return array{enabled: bool, status: string, name: string, message: string}
 */
function getWebToolAvailability(string $toolSlug): array
{
    $flag = getFeatureByWebToolSlug($toolSlug);
    if ($flag === null) {
        return [
            'enabled' => true,
            'status'  => 'active',
            'name'    => '',
            'message' => '',
        ];
    }

    $platform = (string) ($flag['platform'] ?? 'both');
    $isEnabled = ((int) ($flag['is_enabled'] ?? 1)) === 1;
    $status = (string) ($flag['status'] ?? 'active');
    $name = (string) ($flag['feature_name'] ?? $toolSlug);
    $customMsg = trim((string) ($flag['maintenance_message'] ?? ''));

    // If platform is switched exclusively to 'android', tool is unavailable on website
    if ($platform === 'android') {
        return [
            'enabled' => false,
            'status'  => 'disabled',
            'name'    => $name,
            'message' => $customMsg !== '' ? $customMsg : ($name . ' is currently restricted to the Android application.'),
        ];
    }

    if (!$isEnabled || $status !== 'active') {
        $defaultMsg = $status === 'maintenance'
            ? ($name . ' is currently under maintenance. Please check back shortly.')
            : ($name . ' is currently disabled by the administrator.');
        return [
            'enabled' => false,
            'status'  => $status === 'active' ? 'disabled' : $status,
            'name'    => $name,
            'message' => $customMsg !== '' ? $customMsg : $defaultMsg,
        ];
    }

    return [
        'enabled' => true,
        'status'  => 'active',
        'name'    => $name,
        'message' => '',
    ];
}

/**
 * Returns currently active announcements filtered by platform ('website' or 'android') and schedule.
 *
 * @return array<int, array<string, mixed>>
 */
function getActiveAnnouncements(string $platform = 'website'): array
{
    $allowedPlatform = in_array($platform, ['website', 'android'], true) ? $platform : 'website';
    $cache = &_scanproRuntimeCache();
    $cacheKey = 'announcements_' . $allowedPlatform;

    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $rows = [];
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare(
            "SELECT id, title, message, type, platform, is_dismissible, start_at, end_at, created_at
             FROM announcements
             WHERE is_active = 1
               AND (platform = :platform OR platform = 'both')
               AND (start_at IS NULL OR start_at <= NOW())
               AND (end_at IS NULL OR end_at >= NOW())
             ORDER BY id DESC
             LIMIT 5"
        );
        $stmt->execute(['platform' => $allowedPlatform]);
        $rows = $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('[ScanPro Runtime Announcements Fallback] ' . $e->getMessage());
    }

    $cache[$cacheKey] = is_array($rows) ? $rows : [];
    return $cache[$cacheKey];
}