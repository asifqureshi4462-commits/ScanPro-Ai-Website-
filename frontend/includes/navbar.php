<?php
require_once __DIR__ . '/../../backend/helpers/security.php';

startSecureSession();

$prefix = isset($routePrefix) && is_string($routePrefix) ? $routePrefix : '';
$currentNav = $activePage ?? 'home';
$isLoggedIn = !empty($_SESSION['user_id']);
$csrfTokenValue = getCsrfToken();

$navItems = [
    ['key' => 'home',       'label' => 'Home',       'href' => 'index.php',      'icon' => 'fa-house'],
    ['key' => 'features',   'label' => 'Features',   'href' => 'features.php',   'icon' => 'fa-layer-group'],
    ['key' => 'ai-scanner', 'label' => 'AI Scanner', 'href' => 'ai-scanner.php', 'icon' => 'fa-wand-magic-sparkles'],
    ['key' => 'workspace',  'label' => 'Workspace',  'href' => 'workspace.php',  'icon' => 'fa-pen-ruler'],
    ['key' => 'pdf-tools',  'label' => 'PDF Tools',  'href' => 'pdf-tools.php',  'icon' => 'fa-file-pdf'],
    ['key' => 'pricing',    'label' => 'Pricing',    'href' => 'pricing.php',    'icon' => 'fa-tags'],
    ['key' => 'about',      'label' => 'About',      'href' => 'about.php',      'icon' => 'fa-circle-info'],
    ['key' => 'contact',    'label' => 'Contact',    'href' => 'contact.php',    'icon' => 'fa-envelope'],
];
?>
<nav class="desktop-nav" aria-label="Primary Navigation">
    <?php foreach ($navItems as $item): ?>
        <a
            href="<?= e($prefix . $item['href']) ?>"
            class="nav-link<?= $currentNav === $item['key'] ? ' active' : '' ?>"
            <?= $currentNav === $item['key'] ? 'aria-current="page"' : '' ?>
        >
            <?= e($item['label']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<div class="nav-actions">
    <button
        type="button"
        class="header-search-btn"
        id="desktopSearchToggle"
        aria-label="Search PDF tools"
        aria-expanded="false"
        aria-controls="headerSearchPanel"
    >
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <span>Search</span>
    </button>

    <?php if ($isLoggedIn): ?>
        <a href="<?= e($prefix) ?>dashboard.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
            <span>Dashboard</span>
        </a>
        <form action="<?= e($prefix) ?>../backend/api/logout.php" method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?= e($csrfTokenValue) ?>">
            <button type="submit" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                <span>Logout</span>
            </button>
        </form>
    <?php else: ?>
        <a href="<?= e($prefix) ?>login.php" class="btn btn-outline btn-sm">Login</a>
        <a href="<?= e($prefix) ?>register.php" class="btn btn-primary btn-sm">Get Started</a>
    <?php endif; ?>
</div>

<div class="mobile-header-controls">
    <button
        type="button"
        class="mobile-toggle"
        id="mobileSearchToggle"
        aria-label="Search tools"
        aria-expanded="false"
        aria-controls="headerSearchPanel"
    >
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
    </button>

    <button
        type="button"
        class="mobile-toggle"
        id="mobileMenuOpen"
        aria-label="Open navigation menu"
        aria-expanded="false"
        aria-controls="mobileDrawer"
    >
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
    </button>
</div>

<div
    class="header-search-panel"
    id="headerSearchPanel"
    data-route-prefix="<?= e($prefix) ?>"
    aria-hidden="true"
>
    <div class="container">
        <div class="header-search-box">
            <i class="fa-solid fa-magnifying-glass header-search-icon" aria-hidden="true"></i>
            <input
                type="search"
                id="headerSearchInput"
                class="header-search-input"
                placeholder="Search tools (merge, split, compress, jpg, rotate, watermark, numbers...)"
                autocomplete="off"
                aria-label="Search ScanPro AI tools"
            >
            <button type="button" class="header-search-close" id="headerSearchClose" aria-label="Close search">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div class="header-search-results" id="headerSearchResults" role="listbox" aria-label="Matching tools"></div>
    </div>
</div>

<div class="mobile-overlay" id="mobileOverlay"></div>

<aside class="mobile-drawer" id="mobileDrawer" aria-label="Mobile Navigation">
    <div class="mobile-drawer-header">
        <span class="brand-name">ScanPro <span>AI</span></span>
        <button type="button" class="mobile-drawer-close" id="mobileMenuClose" aria-label="Close navigation menu">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>

    <div class="mobile-nav-list">
        <?php foreach ($navItems as $item): ?>
            <a
                href="<?= e($prefix . $item['href']) ?>"
                class="nav-link<?= $currentNav === $item['key'] ? ' active' : '' ?>"
                <?= $currentNav === $item['key'] ? 'aria-current="page"' : '' ?>
            >
                <i class="fa-solid <?= e($item['icon']) ?>" aria-hidden="true"></i>
                <span><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="mobile-drawer-actions">
        <?php if ($isLoggedIn): ?>
            <a href="<?= e($prefix) ?>dashboard.php" class="btn btn-primary btn-block">
                <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
                <span>Dashboard</span>
            </a>
            <form action="<?= e($prefix) ?>../backend/api/logout.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e($csrfTokenValue) ?>">
                <button type="submit" class="btn btn-danger btn-block">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                    <span>Logout</span>
                </button>
            </form>
        <?php else: ?>
            <a href="<?= e($prefix) ?>login.php" class="btn btn-secondary btn-block">Login</a>
            <a href="<?= e($prefix) ?>register.php" class="btn btn-primary btn-block">Get Started Free</a>
        <?php endif; ?>
    </div>
</aside>