<?php
declare(strict_types=1);

/**
 * ScanPro AI - Unified Control Center Layout Shell
 */

require_once __DIR__ . '/../../backend/auth/admin_auth.php';

/**
 * Renders the opening HTML, sidebar navigation, mobile drawer, and topbar.
 *
 * @param array<string, mixed> $admin
 */
function renderAdminHeader(
    array $admin,
    string $activeNav,
    string $pageTitle,
    string $pageSubtitle = ''
): void {
    $csrfToken = getAdminCsrfToken();
    $flashes = consumeAdminFlashes();

    $unreadMessages = 0;
    try {
        $pdo = getDbConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'");
        $unreadMessages = $stmt ? (int) $stmt->fetchColumn() : 0;
    } catch (Throwable $e) {
        $unreadMessages = 0;
    }

    $adminName = (string) ($admin['full_name'] ?? $admin['username'] ?? 'Admin');
    $adminRole = str_replace('_', ' ', (string) ($admin['role'] ?? 'admin'));
    $words = preg_split('/\s+/', trim($adminName)) ?: [];
    $initials = '';
    foreach (array_slice($words, 0, 2) as $w) {
        if ($w !== '') {
            $initials .= mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8');
        }
    }
    if ($initials === '') {
        $initials = 'AD';
    }

    $navGroups = [
        'Overview' => [
            ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'index.php', 'icon' => 'fa-chart-pie'],
            ['key' => 'analytics', 'label' => 'Analytics', 'href' => 'analytics.php', 'icon' => 'fa-chart-line'],
        ],
        'Platforms' => [
            ['key' => 'website', 'label' => 'Website Manager', 'href' => 'website.php', 'icon' => 'fa-globe'],
            ['key' => 'android', 'label' => 'Android App', 'href' => 'android-app.php', 'icon' => 'fa-brands fa-android'],
            ['key' => 'features', 'label' => 'Feature Controls', 'href' => 'features.php', 'icon' => 'fa-sliders'],
            ['key' => 'ads', 'label' => 'Ads Manager', 'href' => 'ads.php', 'icon' => 'fa-rectangle-ad'],
        ],
        'Operations' => [
            ['key' => 'users', 'label' => 'Users', 'href' => 'users.php', 'icon' => 'fa-users'],
            ['key' => 'announcements', 'label' => 'Announcements', 'href' => 'announcements.php', 'icon' => 'fa-bullhorn'],
            ['key' => 'messages', 'label' => 'Messages', 'href' => 'messages.php', 'icon' => 'fa-envelope', 'badge' => $unreadMessages],
        ],
        'System' => [
            ['key' => 'logs', 'label' => 'Activity Logs', 'href' => 'logs.php', 'icon' => 'fa-clock-rotate-left'],
            ['key' => 'settings', 'label' => 'Admin Settings', 'href' => 'settings.php', 'icon' => 'fa-gear'],
        ],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <meta name="theme-color" content="#0B0B0F">
    <title><?= e($pageTitle) ?> | ScanPro AI Control Center</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="assets/css/admin.css?v=1.0">
</head>
<body>
<div class="adm-shell">
    <div class="adm-overlay" id="admOverlay"></div>

    <aside class="adm-sidebar" id="admSidebar" aria-label="Admin Navigation">
        <div class="adm-sidebar-head">
            <a href="index.php" class="adm-brand">
                <div class="adm-brand-mark">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="adm-brand-title">ScanPro AI</div>
                    <div class="adm-brand-sub">Control Center</div>
                </div>
            </a>
            <button type="button" class="adm-drawer-close" id="admDrawerClose" aria-label="Close navigation">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="adm-nav">
            <?php foreach ($navGroups as $groupLabel => $items): ?>
                <div class="adm-nav-label"><?= e($groupLabel) ?></div>
                <?php foreach ($items as $item): ?>
                    <?php
                    $iconClass = str_contains($item['icon'], 'fa-brands')
                        ? $item['icon']
                        : 'fa-solid ' . $item['icon'];
                    $badgeVal = (int) ($item['badge'] ?? 0);
                    ?>
                    <a
                        href="<?= e($item['href']) ?>"
                        class="adm-nav-link<?= $activeNav === $item['key'] ? ' active' : '' ?>"
                        <?= $activeNav === $item['key'] ? 'aria-current="page"' : '' ?>
                    >
                        <span class="adm-nav-link-main">
                            <i class="<?= e($iconClass) ?>" aria-hidden="true"></i>
                            <span><?= e($item['label']) ?></span>
                        </span>
                        <?php if ($badgeVal > 0): ?>
                            <span class="adm-nav-pill"><?= $badgeVal ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>

        <div class="adm-sidebar-foot">
            <div class="adm-user-mini">
                <div class="adm-avatar"><?= e($initials) ?></div>
                <div class="adm-user-meta">
                    <div class="adm-user-name"><?= e($adminName) ?></div>
                    <div class="adm-user-role"><?= e($adminRole) ?></div>
                </div>
            </div>
            <form action="../backend/api/admin/auth.php" method="POST">
                <?= adminCsrfInput() ?>
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="adm-btn adm-btn-danger adm-btn-sm" style="width:100%;">
                    <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="adm-main-wrap">
        <header class="adm-topbar">
            <div class="adm-topbar-left">
                <button
                    type="button"
                    class="adm-menu-btn"
                    id="admMenuBtn"
                    aria-label="Open navigation menu"
                    aria-expanded="false"
                    aria-controls="admSidebar"
                >
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                </button>
                <div style="min-width:0;">
                    <div class="adm-page-title"><?= e($pageTitle) ?></div>
                    <?php if ($pageSubtitle !== ''): ?>
                        <div class="adm-page-sub"><?= e($pageSubtitle) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="adm-topbar-actions">
                <a href="../frontend/index.php" target="_blank" rel="noopener noreferrer" class="adm-btn adm-btn-secondary adm-btn-sm">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    <span class="hide-mobile">View Website</span>
                </a>
            </div>
        </header>

        <main class="adm-content">
            <?php foreach ($flashes as $flash): ?>
                <?php $ftype = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true) ? $flash['type'] : 'info'; ?>
                <div class="adm-alert adm-alert--<?= e($ftype) ?>" role="alert">
                    <i class="fa-solid fa-circle-info" aria-hidden="true" style="margin-top:2px;"></i>
                    <div><?= e($flash['message']) ?></div>
                    <button type="button" class="adm-alert-close" aria-label="Dismiss alert">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
            <?php endforeach; ?>
    <?php
}

/**
 * Renders the closing Admin shell markup and runtime script.
 *
 * @param array<int, string> $extraScripts
 */
function renderAdminFooter(array $extraScripts = []): void
{
    ?>
        </main>
    </div>
</div>
<script src="assets/js/admin.js?v=1.0" defer></script>
<?php
foreach ($extraScripts as $script) {
    if (is_string($script) && $script !== '') {
        echo '<script src="' . e($script) . '?v=1.0" defer></script>' . "\n";
    }
}
?>
</body>
</html>
    <?php
}