<?php
require_once __DIR__ . '/../../backend/helpers/security.php';
require_once __DIR__ . '/../../backend/helpers/site_runtime.php';

$prefix = isset($routePrefix) && is_string($routePrefix) ? $routePrefix : '';
$footerSiteName = getSiteSetting('site_name', APP_NAME);
$footerSupportEmail = getSiteSetting('contact_email', APP_SUPPORT_EMAIL);
$trackEndpoint = getProjectBasePath() . '/backend/api/track.php';
$pageTrackKey = isset($activePage) && is_string($activePage) && $activePage !== '' ? $activePage : 'page';
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="<?= e($prefix) ?>index.php" class="brand-logo">
                    <div class="brand-mark">
                        <img src="<?= e($prefix) ?>assets/images/scanpro-logo.png" alt="<?= e($footerSiteName) ?> Logo">
                    </div>
                    <div class="brand-text">
                        <span class="brand-name">ScanPro <span>AI</span></span>
                        <span class="brand-sub">SCAN • EXTRACT • ORGANIZE</span>
                    </div>
                </a>
                <p class="text-muted mt-2" style="font-size:0.9rem;max-width:340px;">
                    Next-generation AI document scanner, multi-format office suite, and encrypted PDF workspace built for mobile and web productivity.
                </p>
            </div>

            <div class="footer-col">
                <h4>Product</h4>
                <ul>
                    <li><a href="<?= e($prefix) ?>features.php">All Features</a></li>
                    <li><a href="<?= e($prefix) ?>ai-scanner.php">AI Scanner</a></li>
                    <li><a href="<?= e($prefix) ?>pdf-tools.php">PDF Tools</a></li>
                    <li><a href="<?= e($prefix) ?>pricing.php">Pricing Plans</a></li>
                    <li><a href="<?= e(ANDROID_APK_URL) ?>" target="_blank" rel="noopener noreferrer" download>Download Android APK</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Company</h4>
                <ul>
                    <li><a href="<?= e($prefix) ?>about.php">About ScanPro AI</a></li>
                    <li><a href="<?= e($prefix) ?>contact.php">Contact Support</a></li>
                    <li><a href="<?= e($prefix) ?>privacy.php">Privacy Policy</a></li>
                    <li><a href="<?= e($prefix) ?>pricing.php#faq">Pricing FAQs</a></li>
                    <li><a href="<?= e($prefix) ?>index.php#faq">General FAQs</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Account</h4>
                <ul>
                    <?php if (!empty($_SESSION['user_id'])): ?>
                        <li><a href="<?= e($prefix) ?>dashboard.php">User Dashboard</a></li>
                        <li><a href="<?= e($prefix) ?>pricing.php">Manage Plan</a></li>
                    <?php else: ?>
                        <li><a href="<?= e($prefix) ?>login.php">Sign In</a></li>
                        <li><a href="<?= e($prefix) ?>register.php">Create Account</a></li>
                        <li><a href="<?= e($prefix) ?>forgot-password.php">Reset Password</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= e($footerSiteName) ?>. All rights reserved.</span>
            <span>Support: <a href="mailto:<?= e($footerSupportEmail) ?>" class="form-link"><?= e($footerSupportEmail) ?></a></span>
        </div>
    </div>
</footer>

<script src="<?= e($prefix) ?>assets/js/main.js?v=2.4" defer></script>
<script src="<?= e($prefix) ?>assets/js/interactions.js?v=2.4" defer></script>
<script src="<?= e($prefix) ?>assets/js/live-3d.js?v=2.4" defer></script>
<script src="<?= e($prefix) ?>assets/js/site-3d.js?v=3.0" defer></script>
<?php
if (!empty($loadPdfLibs)) {
    require __DIR__ . '/pdf-tool-libs.php';
}
if (!empty($extraScripts) && is_array($extraScripts)) {
    foreach ($extraScripts as $scriptPath) {
        if (is_string($scriptPath) && $scriptPath !== '') {
            $src = preg_match('#^https?://#i', $scriptPath) ? $scriptPath : ($prefix . $scriptPath . '?v=2.4');
            echo '<script src="' . e($src) . '" defer></script>' . "\n";
        }
    }
}
?>
<script>
(function () {
    var endpoint = <?= json_encode($trackEndpoint, JSON_UNESCAPED_SLASHES) ?>;
    var pageKey = <?= json_encode($pageTrackKey, JSON_UNESCAPED_SLASHES) ?>;
    var path = window.location.pathname || '';
    var toolMatch = path.match(/\/frontend\/tools\/([a-z0-9\-]+)\.php$/i);
    var targetKey = toolMatch ? ('tool:' + toolMatch[1].toLowerCase()) : pageKey;

    function sendEvent(eventType, key) {
        try {
            var body = JSON.stringify({ event_type: eventType, target_key: key, platform: 'website' });
            if (navigator.sendBeacon) {
                navigator.sendBeacon(endpoint, new Blob([body], { type: 'application/json' }));
            } else if (window.fetch) {
                fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: body, keepalive: true }).catch(function () {});
            }
        } catch (e) {}
    }

    sendEvent('page_view', targetKey);

    document.addEventListener('click', function (e) {
        var el = e.target && e.target.closest ? e.target.closest('button, a') : null;
        if (!el) return;
        var id = el.id || '';
        if (toolMatch) {
            if (/ExecuteBtn$|^stRun$|^zipExecuteBtn$/.test(id)) {
                sendEvent('tool_run', toolMatch[1].toLowerCase());
            } else if (/Download.*Btn$|^stDownload$|^zipDownloadBtn$/.test(id)) {
                sendEvent('download', toolMatch[1].toLowerCase());
            }
        } else if (id === 'wsDownload') {
            sendEvent('tool_run', 'workspace');
            sendEvent('download', 'workspace');
        } else if (el.tagName === 'A' && el.hasAttribute('download') && (el.getAttribute('href') || '').indexOf('.apk') !== -1) {
            sendEvent('download', 'android-apk');
        }
    }, { passive: true });
})();
</script>
</body>
</html>