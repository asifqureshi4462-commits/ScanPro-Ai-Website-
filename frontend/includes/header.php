<?php
require_once __DIR__ . '/../../backend/helpers/security.php';

$prefix = isset($routePrefix) && is_string($routePrefix) ? $routePrefix : '';
?>
<header class="site-header" id="siteHeader">
    <div class="container header-inner">
        <a href="<?= e($prefix) ?>index.php" class="brand-logo" aria-label="<?= e(APP_NAME) ?> Home">
            <div class="brand-mark">
                <img src="<?= e($prefix) ?>assets/images/scanpro-logo.png" alt="<?= e(APP_NAME) ?> Logo">
            </div>
            <div class="brand-text">
                <span class="brand-name">ScanPro <span>AI</span></span>
                <span class="brand-sub">DOCUMENT SUITE</span>
            </div>
        </a>

        <?php require __DIR__ . '/navbar.php'; ?>
    </div>
</header>