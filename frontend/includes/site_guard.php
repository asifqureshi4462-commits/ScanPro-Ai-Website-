<?php
declare(strict_types=1);

/**
 * ScanPro AI - Public Website Runtime Guard & Announcement Renderer
 * Enforces maintenance mode, section visibility, tool feature flags, and live top banners safely.
 */

require_once __DIR__ . '/../../backend/helpers/site_runtime.php';

/**
 * Renders a standalone HTTP 503 Unavailable / Maintenance page and terminates execution.
 */
function renderWebsiteUnavailablePage(
    string $heading,
    string $message,
    string $siteName,
    string $contactEmail,
    int $httpStatus = 503,
    string $backUrl = '',
    string $backLabel = 'Go Back Home'
): void {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        http_response_code($httpStatus);
        if ($httpStatus === 503) {
            header('Retry-After: 3600');
        }
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($heading) ?> | <?= e($siteName) ?></title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0F172A;
            color: #F8FAFC;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background-image:
                radial-gradient(circle at 20% 20%, rgba(34, 211, 238, 0.12), transparent 42%),
                radial-gradient(circle at 80% 80%, rgba(22, 199, 181, 0.10), transparent 42%);
        }
        .box {
            width: 100%;
            max-width: 520px;
            background: #1E293B;
            border: 1px solid rgba(34, 211, 238, 0.30);
            border-radius: 20px;
            padding: 32px 24px;
            text-align: center;
            box-shadow: 0 24px 56px rgba(0, 0, 0, 0.55);
        }
        .pill {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.40);
            color: #FDE68A;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        h1 { font-size: 1.55rem; margin-bottom: 12px; color: #FFFFFF; }
        p { color: #94A3B8; font-size: 0.96rem; line-height: 1.6; margin-bottom: 20px; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        .btn {
            display: inline-block;
            padding: 11px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            background: linear-gradient(135deg, #22D3EE, #16C7B5);
            color: #061B32;
        }
        .btn-outline {
            background: transparent;
            color: #E2E8F0;
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
    </style>
</head>
<body>
    <main class="box">
        <span class="pill"><?= e($siteName) ?></span>
        <h1><?= e($heading) ?></h1>
        <p><?= e($message) ?></p>
        <div class="actions">
            <?php if ($backUrl !== ''): ?>
                <a href="<?= e($backUrl) ?>" class="btn"><?= e($backLabel) ?></a>
            <?php endif; ?>
            <?php if ($contactEmail !== ''): ?>
                <a href="mailto:<?= e($contactEmail) ?>" class="btn btn-outline">Contact Support</a>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
    <?php
    exit;
}

/**
 * Checks Website maintenance mode, page visibility flags, and per-tool feature flags
 * (covering files-to-zip.php, all PDF tools, and workspace.php) before HTML output starts.
 */
function enforceWebsiteRuntimeGuard(string $activePage = '', string $prefix = ''): void
{
    $settings = getSiteSettings();
    $siteName = (string) ($settings['site_name'] ?? APP_NAME);
    $contactEmail = (string) ($settings['contact_email'] ?? APP_SUPPORT_EMAIL);

    $isMaint = ($settings['maintenance_mode'] ?? '0') === '1'
        || ($settings['site_status'] ?? 'active') === 'maintenance';

    if ($isMaint) {
        $msg = trim((string) ($settings['maintenance_message'] ?? ''));
        if ($msg === '') {
            $msg = 'ScanPro AI is undergoing scheduled maintenance. Please check back shortly.';
        }
        renderWebsiteUnavailablePage('Scheduled Maintenance', $msg, $siteName, $contactEmail, 503);
    }

    if ($activePage === 'workspace') {
        if (($settings['show_web_workspace'] ?? '1') === '0') {
            renderWebsiteUnavailablePage(
                'Web Workspace Unavailable',
                'The Web Document Workspace is currently disabled by the administrator.',
                $siteName,
                $contactEmail,
                503,
                $prefix . 'index.php'
            );
        }
        $wsAvail = getWebToolAvailability('workspace');
        if (!$wsAvail['enabled']) {
            renderWebsiteUnavailablePage(
                $wsAvail['status'] === 'maintenance' ? 'Workspace Under Maintenance' : 'Workspace Unavailable',
                $wsAvail['message'],
                $siteName,
                $contactEmail,
                503,
                $prefix . 'index.php'
            );
        }
    }

    if ($activePage === 'pricing' && ($settings['show_pricing_section'] ?? '1') === '0') {
        renderWebsiteUnavailablePage(
            'Pricing Temporarily Unavailable',
            'The Pricing page is currently hidden by the administrator.',
            $siteName,
            $contactEmail,
            503,
            $prefix . 'index.php'
        );
    }

    // Enforce Admin feature flags on individual tool pages (/frontend/tools/*.php)
    $scriptPath = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if (str_contains($scriptPath, '/frontend/tools/')) {
        $toolSlug = basename($scriptPath, '.php');
        if ($toolSlug !== '') {
            $toolAvail = getWebToolAvailability($toolSlug);
            if (!$toolAvail['enabled']) {
                $heading = $toolAvail['status'] === 'maintenance'
                    ? ($toolAvail['name'] . ' Under Maintenance')
                    : ($toolAvail['name'] . ' Unavailable');
                renderWebsiteUnavailablePage(
                    $heading,
                    $toolAvail['message'],
                    $siteName,
                    $contactEmail,
                    503,
                    $prefix . 'pdf-tools.php',
                    'Back to All Tools'
                );
            }
        }
    }

    // Hide Android APK download block on ai-scanner.php if disabled by admin
    if ($activePage === 'ai-scanner' && ($settings['show_android_download'] ?? '1') === '0') {
        $GLOBALS['_scanproHideAndroidDownload'] = true;
    }
}

/**
 * Renders live website announcement, homepage notice, and scheduled announcements inside <body>.
 */
function renderSiteTopNotices(string $activePage = ''): void
{
    $settings = getSiteSettings();
    $notices = [];

    if (($settings['website_announcement_enabled'] ?? '0') === '1') {
        $text = trim((string) ($settings['website_announcement_text'] ?? ''));
        if ($text !== '') {
            $notices[] = [
                'id'          => 'site-global',
                'title'       => 'Notice',
                'message'     => $text,
                'type'        => 'info',
                'dismissible' => true,
            ];
        }
    }

    if ($activePage === 'home' && ($settings['homepage_notice_enabled'] ?? '0') === '1') {
        $homeText = trim((string) ($settings['homepage_notice_text'] ?? ''));
        if ($homeText !== '') {
            $notices[] = [
                'id'          => 'site-home',
                'title'       => 'Update',
                'message'     => $homeText,
                'type'        => 'update',
                'dismissible' => true,
            ];
        }
    }

    foreach (getActiveAnnouncements('website') as $ann) {
        $notices[] = [
            'id'          => 'ann-' . (int) $ann['id'],
            'title'       => (string) ($ann['title'] ?? ''),
            'message'     => (string) ($ann['message'] ?? ''),
            'type'        => (string) ($ann['type'] ?? 'info'),
            'dismissible' => ((int) ($ann['is_dismissible'] ?? 1)) === 1,
        ];
    }

    if (empty($notices) && empty($GLOBALS['_scanproHideAndroidDownload'])) {
        return;
    }

    if (!empty($GLOBALS['_scanproHideAndroidDownload'])) {
        echo '<style>#mobile-app, a[href="#mobile-app"] { display: none !important; }</style>' . "\n";
    }

    if (empty($notices)) {
        return;
    }
    ?>
    <div class="sp-top-notices" id="spTopNotices" style="background:#061B32;border-bottom:1px solid rgba(34,211,238,0.28);position:relative;z-index:1001;">
        <div class="container" style="display:flex;flex-direction:column;gap:6px;padding-top:8px;padding-bottom:8px;">
            <?php foreach ($notices as $notice): ?>
                <?php
                $toneColor = '#22D3EE';
                if ($notice['type'] === 'warning') {
                    $toneColor = '#F59E0B';
                } elseif ($notice['type'] === 'update') {
                    $toneColor = '#16C7B5';
                }
                ?>
                <div
                    class="sp-notice-row"
                    data-notice-id="<?= e($notice['id']) ?>"
                    style="display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:0.86rem;color:#F8FAFC;"
                >
                    <div style="display:flex;align-items:center;gap:8px;min-width:0;">
                        <span style="display:inline-block;padding:2px 8px;border-radius:999px;font-size:0.72rem;font-weight:700;text-transform:uppercase;background:rgba(34,211,238,0.14);color:<?= e($toneColor) ?>;border:1px solid <?= e($toneColor) ?>55;flex-shrink:0;">
                            <?= e($notice['title'] !== '' ? $notice['title'] : 'Info') ?>
                        </span>
                        <span style="word-break:break-word;"><?= e($notice['message']) ?></span>
                    </div>
                    <?php if (!empty($notice['dismissible'])): ?>
                        <button
                            type="button"
                            class="sp-notice-close"
                            aria-label="Dismiss notice"
                            style="background:transparent;border:none;color:#94A3B8;cursor:pointer;padding:4px 6px;flex-shrink:0;"
                        >
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
    (function () {
        var wrap = document.getElementById('spTopNotices');
        if (!wrap) return;
        wrap.querySelectorAll('.sp-notice-row').forEach(function (row) {
            var id = row.getAttribute('data-notice-id');
            try {
                if (id && sessionStorage.getItem('sp_dismiss_' + id) === '1') {
                    row.remove();
                }
            } catch (e) {}
            var btn = row.querySelector('.sp-notice-close');
            if (btn) {
                btn.addEventListener('click', function () {
                    try {
                        if (id) sessionStorage.setItem('sp_dismiss_' + id, '1');
                    } catch (e) {}
                    row.remove();
                    if (!wrap.querySelector('.sp-notice-row')) {
                        wrap.remove();
                    }
                });
            }
        });
        if (!wrap.querySelector('.sp-notice-row')) {
            wrap.remove();
        }
    })();
    </script>
    <?php
}