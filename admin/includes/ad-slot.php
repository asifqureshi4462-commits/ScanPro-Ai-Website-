<?php
/**
 * ScanPro AI - Ad slot helper.
 * Usage (inside a page, between sections): <?php renderAdSlot('home'); ?>
 * - Controlled dynamically by Admin Ads Management (site_settings) with safe fallback to backend/config/ads.php.
 * - Shows at most ONE inline ad per page (the zone container id must exist only once).
 * - Stays collapsed until an ad has really loaded (via assets/css/ads.css & assets/js/ads.js).
 * - Never shown on login, register, dashboard, pricing, contact, or workspace pages.
 */
require_once __DIR__ . '/../../backend/config/ads.php';
require_once __DIR__ . '/../../backend/helpers/site_runtime.php';

function adsShouldShow(string $placement): bool
{
    $cfg = getAdRuntimeConfig();

    if (empty($cfg['ads_enabled'])) {
        return false;
    }

    if (!in_array($placement, $cfg['placements'] ?? [], true)) {
        return false;
    }

    $isLoggedIn = !empty($_SESSION['user_id']);
    if ($isLoggedIn && empty($cfg['logged_in_enabled'])) {
        return false;
    }
    if (!$isLoggedIn && empty($cfg['guest_enabled'])) {
        return false;
    }

    $scriptSrc = trim((string) ($cfg['native_script_src'] ?? ''));
    $containerId = trim((string) ($cfg['native_container_id'] ?? ''));

    if ($scriptSrc === '' || $containerId === '') {
        return false;
    }
    if (preg_match('#^https?://#i', $scriptSrc) !== 1) {
        return false;
    }
    if (preg_match('/^[a-zA-Z0-9_-]{3,120}$/', $containerId) !== 1) {
        return false;
    }

    return true;
}

function renderAdSlot(string $placement): void
{
    static $rendered = false;
    if ($rendered || !adsShouldShow($placement)) {
        return;
    }
    $rendered = true;

    $cfg = getAdRuntimeConfig();
    $scriptSrc = trim((string) $cfg['native_script_src']);
    $containerId = trim((string) $cfg['native_container_id']);

    global $prefix, $routePrefix;
    $assetPrefix = isset($prefix)
        ? (string) $prefix
        : (isset($routePrefix) ? (string) $routePrefix : '');
    ?>
    <section class="ad-section" data-ad-slot data-ad-pending aria-label="Advertisement">
        <div class="container">
            <div class="ad-slot">
                <span class="ad-label">Advertisement</span>
                <div class="ad-box" data-ad-box>
                    <script async="async" data-cfasync="false" src="<?= e($scriptSrc) ?>"></script>
                    <div id="<?= e($containerId) ?>" data-ad-container></div>
                </div>
            </div>
        </div>
    </section>
    <link rel="stylesheet" href="<?= e($assetPrefix) ?>assets/css/ads.css?v=1">
    <script src="<?= e($assetPrefix) ?>assets/js/ads.js?v=1" defer></script>
    <?php
}