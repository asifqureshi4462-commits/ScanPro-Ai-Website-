<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$admin = requireAdminWebAuth();

$stats = [
    'daily_visitors'   => 0,
    'weekly_visitors'  => 0,
    'monthly_visitors' => 0,
    'total_page_views' => 0,
    'tool_runs_30d'    => 0,
    'downloads_30d'    => 0,
    'app_opens_30d'    => 0,
    'has_events'       => false,
];

$popularPages = [];
$popularTools = [];
$deviceBreakdown = [];
$dailyVisitorValues = [];
$regValues = [];

$last14Days = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $last14Days[$d] = date('M j', strtotime($d));
}
$dayLabels = array_values($last14Days);
$visMap = array_fill_keys(array_keys($last14Days), 0);
$regMap = array_fill_keys(array_keys($last14Days), 0);

try {
    $pdo = getDbConnection();

    $uStmt = $pdo->query(
        'SELECT DATE(created_at) AS d, COUNT(*) AS cnt
         FROM users
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
         GROUP BY DATE(created_at)'
    );
    if ($uStmt) {
        foreach ($uStmt->fetchAll() as $r) {
            $d = (string) ($r['d'] ?? '');
            if (isset($regMap[$d])) {
                $regMap[$d] = (int) $r['cnt'];
            }
        }
    }
    $regValues = array_values($regMap);

    $totalEvents = (int) $pdo->query('SELECT COUNT(*) FROM analytics_events')->fetchColumn();
    $stats['has_events'] = $totalEvents > 0;

    $stats['daily_visitors'] = (int) $pdo->query(
        "SELECT COUNT(DISTINCT COALESCE(visitor_hash, CAST(id AS CHAR)))
         FROM analytics_events
         WHERE event_type = 'page_view' AND event_date = CURDATE()"
    )->fetchColumn();

    $stats['weekly_visitors'] = (int) $pdo->query(
        "SELECT COUNT(DISTINCT COALESCE(visitor_hash, CAST(id AS CHAR)))
         FROM analytics_events
         WHERE event_type = 'page_view' AND event_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)"
    )->fetchColumn();

    $stats['monthly_visitors'] = (int) $pdo->query(
        "SELECT COUNT(DISTINCT COALESCE(visitor_hash, CAST(id AS CHAR)))
         FROM analytics_events
         WHERE event_type = 'page_view' AND event_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)"
    )->fetchColumn();

    $stats['total_page_views'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM analytics_events WHERE event_type = 'page_view' AND event_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)"
    )->fetchColumn();

    $stats['tool_runs_30d'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM analytics_events WHERE event_type = 'tool_run' AND event_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)"
    )->fetchColumn();

    $stats['downloads_30d'] = (int) $pdo->query(
        "SELECT COUNT(*) FROM analytics_events WHERE event_type = 'download' AND event_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)"
    )->fetchColumn();

    $stats['app_opens_30d'] = (int) $pdo->query(
        "SELECT COUNT(DISTINCT COALESCE(visitor_hash, CAST(id AS CHAR)))
         FROM analytics_events
         WHERE event_type = 'app_open' AND platform = 'android' AND event_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)"
    )->fetchColumn();

    $vStmt = $pdo->query(
        "SELECT event_date AS d, COUNT(DISTINCT COALESCE(visitor_hash, CAST(id AS CHAR))) AS cnt
         FROM analytics_events
         WHERE event_type = 'page_view' AND event_date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
         GROUP BY event_date"
    );
    if ($vStmt) {
        foreach ($vStmt->fetchAll() as $r) {
            $d = (string) ($r['d'] ?? '');
            if (isset($visMap[$d])) {
                $visMap[$d] = (int) $r['cnt'];
            }
        }
    }
    $dailyVisitorValues = array_values($visMap);

    $pStmt = $pdo->query(
        "SELECT target_key, COUNT(*) AS views
         FROM analytics_events
         WHERE event_type = 'page_view' AND event_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
         GROUP BY target_key
         ORDER BY views DESC
         LIMIT 8"
    );
    $popularPages = $pStmt ? ($pStmt->fetchAll() ?: []) : [];

    $tStmt = $pdo->query(
        "SELECT target_key, COUNT(*) AS runs
         FROM analytics_events
         WHERE event_type = 'tool_run' AND event_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
         GROUP BY target_key
         ORDER BY runs DESC
         LIMIT 8"
    );
    $popularTools = $tStmt ? ($tStmt->fetchAll() ?: []) : [];

    $dStmt = $pdo->query(
        "SELECT device_category, COUNT(*) AS cnt
         FROM analytics_events
         WHERE event_date >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
         GROUP BY device_category
         ORDER BY cnt DESC"
    );
    $deviceBreakdown = $dStmt ? ($dStmt->fetchAll() ?: []) : [];
} catch (Throwable $e) {
    error_log('[ScanPro Admin Analytics Error] ' . $e->getMessage());
}

renderAdminHeader($admin, 'analytics', 'Analytics & Usage', 'Privacy-conscious visitor, tool usage & registration trends');
?>

<div class="adm-alert adm-alert--info" role="note">
    <i class="fa-solid fa-user-shield" aria-hidden="true" style="margin-top:2px;"></i>
    <div>
        <strong>Privacy-Conscious Tracking:</strong> Visitors are counted using daily rotating salted hashes (no raw IP addresses or personal profiles stored). Metrics without recorded events are shown honestly as zero/unavailable.
    </div>
</div>

<section class="kpi-grid">
    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-label">Daily Visitors (Today)</span>
            <span class="kpi-icon cyan"><i class="fa-solid fa-user-clock" aria-hidden="true"></i></span>
        </div>
        <div class="kpi-value"><?= $stats['has_events'] ? number_format($stats['daily_visitors']) : '0' ?></div>
        <div class="kpi-meta"><?= $stats['has_events'] ? 'Unique today' : 'Waiting for first visits' ?></div>
    </article>

    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-label">Weekly Visitors (7d)</span>
            <span class="kpi-icon teal"><i class="fa-solid fa-calendar-week" aria-hidden="true"></i></span>
        </div>
        <div class="kpi-value"><?= number_format($stats['weekly_visitors']) ?></div>
        <div class="kpi-meta">Unique daily-hashed visits</div>
    </article>

    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-label">Monthly Visitors (30d)</span>
            <span class="kpi-icon blue"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></span>
        </div>
        <div class="kpi-value"><?= number_format($stats['monthly_visitors']) ?></div>
        <div class="kpi-meta">Page views (30d): <?= number_format($stats['total_page_views']) ?></div>
    </article>

    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-label">Tool Runs &amp; Downloads</span>
            <span class="kpi-icon green"><i class="fa-solid fa-download" aria-hidden="true"></i></span>
        </div>
        <div class="kpi-value"><?= number_format($stats['tool_runs_30d']) ?></div>
        <div class="kpi-meta">
            Downloads (30d): <?= number_format($stats['downloads_30d']) ?> •
            App pings: <?= $stats['app_opens_30d'] > 0 ? number_format($stats['app_opens_30d']) : 'Unavailable' ?>
        </div>
    </article>
</section>

<section class="adm-grid-2">
    <article class="adm-card">
        <div class="adm-card-head">
            <h3>Daily Visitors (Last 14 Days)</h3>
            <span class="adm-badge badge--info">Website</span>
        </div>
        <div class="chart-box">
            <canvas
                data-chart-labels="<?= e(json_encode($dayLabels)) ?>"
                data-chart-values="<?= e(json_encode($dailyVisitorValues)) ?>"
                data-chart-color="#22D3EE"
            ></canvas>
            <div class="chart-empty" style="display:none;">
                <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                <div>No visitor events recorded in the last 14 days.</div>
            </div>
        </div>
    </article>

    <article class="adm-card">
        <div class="adm-card-head">
            <h3>User Registrations (Last 14 Days)</h3>
            <span class="adm-badge badge--ok">Live DB</span>
        </div>
        <div class="chart-box">
            <canvas
                data-chart-labels="<?= e(json_encode($dayLabels)) ?>"
                data-chart-values="<?= e(json_encode($regValues)) ?>"
                data-chart-color="#16C7B5"
            ></canvas>
            <div class="chart-empty" style="display:none;">
                <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                <div>No user registrations in the last 14 days.</div>
            </div>
        </div>
    </article>
</section>

<section class="adm-grid-3">
    <article class="adm-card">
        <div class="adm-card-head">
            <h3>Popular Pages (30d)</h3>
        </div>
        <?php if (empty($popularPages)): ?>
            <p class="adm-card-note">No page views recorded yet.</p>
        <?php else: ?>
            <div class="adm-feed">
                <?php foreach ($popularPages as $row): ?>
                    <div class="adm-feed-item">
                        <code><?= e((string) $row['target_key']) ?></code>
                        <span class="adm-badge badge--info"><?= number_format((int) $row['views']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="adm-card">
        <div class="adm-card-head">
            <h3>Popular PDF &amp; Archive Tools (30d)</h3>
        </div>
        <?php if (empty($popularTools)): ?>
            <p class="adm-card-note">No tool executions recorded yet.</p>
        <?php else: ?>
            <div class="adm-feed">
                <?php foreach ($popularTools as $row): ?>
                    <div class="adm-feed-item">
                        <code><?= e((string) $row['target_key']) ?></code>
                        <span class="adm-badge badge--ok"><?= number_format((int) $row['runs']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="adm-card">
        <div class="adm-card-head">
            <h3>Device Categories (30d)</h3>
        </div>
        <?php if (empty($deviceBreakdown)): ?>
            <p class="adm-card-note">No device category data recorded yet.</p>
        <?php else: ?>
            <div class="adm-feed">
                <?php foreach ($deviceBreakdown as $row): ?>
                    <div class="adm-feed-item">
                        <strong><?= e(ucfirst((string) $row['device_category'])) ?></strong>
                        <span class="adm-badge badge--muted"><?= number_format((int) $row['cnt']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<?php renderAdminFooter(); ?>