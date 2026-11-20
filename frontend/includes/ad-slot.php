<?php
/**
 * ScanPro AI - Ad slot helper.
 * Usage (inside a page, between sections): <?php renderAdSlot('home'); ?>
 * - Shows at most ONE ad per page (the zone container id must exist only once).
 * - The slot stays collapsed (no label, no empty space) until an ad has really loaded.
 * - Never shown to logged-in users or on pages outside ADS_PLACEMENTS.
 */
require_once __DIR__ . '/../../backend/config/ads.php';

function adsShouldShow(string $placement): bool
{
    if (!ADS_ENABLED || !in_array($placement, ADS_PLACEMENTS, true)) {
        return false;
    }
    if ($placement === 'tool' && !ADS_ON_TOOL_PAGES) {
        return false;
    }
    if (ADS_HIDE_FOR_LOGGED_IN && !empty($_SESSION['user_id'])) {
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

    global $prefix;
    $assetPrefix = isset($prefix) ? (string) $prefix : '';
    ?>
    <section class="ad-section" data-ad-slot data-ad-pending aria-label="Advertisement">
        <div class="container">
            <div class="ad-slot">
                <span class="ad-label">Advertisement</span>
                <div class="ad-box" data-ad-box>
                    <script async="async" data-cfasync="false" src="<?= e(ADS_NATIVE_SCRIPT_SRC) ?>"></script>
                    <div id="<?= e(ADS_NATIVE_CONTAINER_ID) ?>" data-ad-container></div>
                </div>
            </div>
        </div>
    </section>
    <link rel="stylesheet" href="<?= e($assetPrefix) ?>assets/css/ads.css?v=1">
    <script src="<?= e($assetPrefix) ?>assets/js/ads.js?v=1" defer></script>
    <?php
}
