<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$admin = requireAdminWebAuth();
$siteSettings = getSiteSettings(true);
$androidConfig = getAndroidAppConfig(true);
$adConfig = getAdRuntimeConfig(true);
$allFeatures = getAllFeatureFlags(true);

$metrics = [
    'total_users'         => null,
    'active_users'        => null,
    'total_visitors'      => null,
    'today_visitors'      => null,
    'app_users'           => null,
    'pdf_operations'      => null,
    'downloads'           => null,
    'unread_messages'     => null,
    'analytics_available' => false,
];

$popularTools = [];
$recentActivities = [];
$visitorLabels = [];
$visitorValues = [];
$userGrowthLabels = [];
$userGrowthValues = [];
$toolChartLabels = [];
$toolChartValues = [];
$activityLabels = [];
$activityValues = [];

// Build last 7 days map for consistent chart axes
$last7Days = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $last7Days[$d] = date('M j', strtotime($d));
}

try {
    $pdo = getDbConnection();

    // 1. Registered Users (Real DB data)
    $metrics['total_users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $metrics['active_users'] = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();

    // User registration growth over last 7 days
    $userGrowthMap = array_fill_keys(array_keys($last7Days), 0);
    $uStmt = $pdo->query(
        'SELECT DATE(created_at) AS d, COUNT(*) AS cnt
         FROM users
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(created_at)'
    );
    if ($uStmt) {
        foreach ($uStmt->fetchAll() as $row) {
            $day = (string) ($row['d'] ?? '');
            if (isset($userGrowthMap[$day])) {
                $userGrowthMap[$day] = (int) $row['cnt'];
            }
        }
    }
    $userGrowthLabels = array_values($last7Days);
    $userGrowthValues = array_values($userGrowthMap);

    // 2. Contact Messages (Real DB data)
    $metrics['unread_messages'] = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();

    // 3. Privacy-Conscious Analytics Events (Real DB data if events exist)
    try {
        $totalEvents = (int) $pdo->query('SELECT COUNT(*) FROM analytics_events')->fetchColumn();
        $metrics['analytics_available'] = $totalEvents > 0;

        $metrics['total_visitors'] = (int) $pdo->query(
            "SELECT COUNT(DISTINCT COALESCE(visitor_hash, CAST(id AS CHAR))) FROM analytics_events WHERE event_type = 'page_view'"
        )->fetchColumn();

        $metrics['today_visitors'] = (int) $pdo->query(
            "SELECT COUNT(DISTINCT COALESCE(visitor_hash, CAST(id AS CHAR))) FROM analytics_events WHERE event_type = 'page_view' AND event_date = CURDATE()"
        )->fetchColumn();

        $metrics['app_users'] = (int) $pdo->query(
            "SELECT COUNT(DISTINCT COALESCE(visitor_hash, CAST(id AS CHAR))) FROM analytics_events WHERE event_type = 'app_open' AND platform = 'android'"
        )->fetchColumn();

        $metrics['pdf_operations'] = (int) $pdo->query(
            "SELECT COUNT(*) FROM analytics_events WHERE event_type = 'tool_run'"
        )->fetchColumn();

        $metrics['downloads'] = (int) $pdo->query(
            "SELECT COUNT(*) FROM analytics_events WHERE event_type = 'download'"
        )->fetchColumn();

        // Visitors 7-day chart
        $visMap = array_fill_keys(array_keys($last7Days), 0);
        $vStmt = $pdo->query(
            "SELECT event_date AS d, COUNT(DISTINCT COALESCE(visitor_hash, CAST(id AS CHAR))) AS cnt
             FROM analytics_events
             WHERE event_type = 'page_view' AND event_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY event_date"
        );
        if ($vStmt) {
            foreach ($vStmt->fetchAll() as $row) {
                $day = (string) ($row['d'] ?? '');
                if (isset($visMap[$day])) {
                    $visMap[$day] = (int) $row['cnt'];
                }
            }
        }
        $visitorLabels = array_values($last7Days);
        $visitorValues = array_values($visMap);

        // Popular tools
        $tStmt = $pdo->query(
            "SELECT target_key, COUNT(*) AS runs
             FROM analytics_events
             WHERE event_type = 'tool_run'
             GROUP BY target_key
             ORDER BY runs DESC
             LIMIT 6"
        );
        if ($tStmt) {
            $popularTools = $tStmt->fetchAll();
            foreach ($popularTools as $pt) {
                $toolChartLabels[] = (string) $pt['target_key'];
                $toolChartValues[] = (int) $pt['runs'];
            }
        }
    } catch (Throwable $ae) {
        error_log('[ScanPro Admin Dashboard Analytics] ' . $ae->getMessage());
    }

    // 4. Admin Activity Logs & 7-Day Activity Chart (Real DB data)
    $actMap = array_fill_keys(array_keys($last7Days), 0);
    $aChartStmt = $pdo->query(
        'SELECT DATE(created_at) AS d, COUNT(*) AS cnt
         FROM admin_activity_logs
         WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY DATE(created_at)'
    );
    if ($aChartStmt) {
        foreach ($aChartStmt->fetchAll() as $row) {
            $day = (string) ($row['d'] ?? '');
            if (isset($actMap[$day])) {
                $actMap[$day] = (int) $row['cnt'];
            }
        }
    }
    $activityLabels = array_values($last7Days);
    $activityValues = array_values($actMap);

    $recentStmt = $pdo->query(
        'SELECT id, admin_username, action, summary, created_at
         FROM admin_activity_logs
         ORDER BY id DESC
         LIMIT 8'
    );
    if ($recentStmt) {
        $recentActivities = $recentStmt->fetchAll();
    }
} catch (Throwable $e) {
    error_log('[ScanPro Admin Dashboard Error] ' . $e->getMessage());
}

$activeFeaturesCount = 0;
foreach ($allFeatures as $f) {
    if (((int) ($f['is_enabled'] ?? 1)) === 1 && ($f['status'] ?? 'active') === 'active') {
        $activeFeaturesCount += 1;
    }
}

$isWebsiteMaintenance = ($siteSettings['maintenance_mode'] ?? '0') === '1' || ($siteSettings['site_status'] ?? 'active') === 'maintenance';
$isAppActive = ((int) ($androidConfig['is_active'] ?? 1)) === 1;
$isAppMaintenance = ((int) ($androidConfig['maintenance_mode'] ?? 0)) === 1;
$isAdsEnabled = !empty($adConfig['ads_enabled']);

renderAdminHeader($admin, 'dashboard', 'Control Center Overview', 'Real-time Website, Android App & Operations Status');
?>

<!-- System Status Strip -->
<section class="adm-grid-3" aria-label="Platform Status">
    <div class="adm-card">
        <div class="adm-card-head" style="margin-bottom:8px;">
            <h3><i class="fa-solid fa-globe" style="color:var(--adm-cyan);margin-right:8px;" aria-hidden="true"></i>Website Status</h3>
            <?php if ($isWebsiteMaintenance): ?>
                <span class="adm-badge badge--warn">Maintenance Mode</span>
            <?php else: ?>
                <span class="adm-badge badge--ok">Online &amp; Active</span>
            <?php endif; ?>
        </div>
        <p class="adm-card-note">
            Site: <strong><?= e($siteSettings['site_name'] ?? APP_NAME) ?></strong> •
            Active features: <?= $activeFeaturesCount ?> / <?= count($allFeatures) ?>
        </p>
    </div>

    <div class="adm-card">
        <div class="adm-card-head" style="margin-bottom:8px;">
            <h3><i class="fa-brands fa-android" style="color:var(--adm-teal);margin-right:8px;" aria-hidden="true"></i>Android App Status</h3>
            <?php if (!$isAppActive): ?>
                <span class="adm-badge badge--danger">Disabled</span>
            <?php elseif ($isAppMaintenance): ?>
                <span class="adm-badge badge--warn">Maintenance</span>
            <?php else: ?>
                <span class="adm-badge badge--ok">Active (v<?= e((string) ($androidConfig['latest_version'] ?? '1.0')) ?>)</span>
            <?php endif; ?>
        </div>
        <p class="adm-card-note">
            Remote config ready via API (Batch 5). App telemetry requires Android integration.
        </p>
    </div>

    <div class="adm-card">
        <div class="adm-card-head" style="margin-bottom:8px;">
            <h3><i class="fa-solid fa-rectangle-ad" style="color:var(--adm-blue);margin-right:8px;" aria-hidden="true"></i>Ads System</h3>
            <?php if ($isAdsEnabled): ?>
                <span class="adm-badge badge--ok">Enabled</span>
            <?php else: ?>
                <span class="adm-badge badge--muted">Paused</span>
            <?php endif; ?>
        </div>
        <p class="adm-card-note">
            Active web placements: <?= count($adConfig['placements'] ?? []) ?> •
            Logged-in ads: <?= !empty($adConfig['logged_in_enabled']) ? 'ON' : 'OFF' ?>
        </p>
    </div>
</section>

<!-- Main KPIs (Real DB Data + Honest Unavailable Labels) -->
<section class="kpi-grid" aria-label="Key Statistics">
    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-label">Registered Users</span>
            <span class="kpi-icon cyan"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
        </div>
        <div class="kpi-value"><?= $metrics['total_users'] !== null ? number_format($metrics['total_users']) : 'Unavailable' ?></div>
        <div class="kpi-meta">
            <?= $metrics['active_users'] !== null ? number_format($metrics['active_users']) . ' active website accounts' : 'Database unavailable' ?>
        </div>
    </article>

    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-label">Website Visitors</span>
            <span class="kpi-icon teal"><i class="fa-solid fa-eye" aria-hidden="true"></i></span>
        </div>
        <?php if (!empty($metrics['analytics_available'])): ?>
            <div class="kpi-value"><?= number_format((int) $metrics['total_visitors']) ?></div>
            <div class="kpi-meta">Today: <?= number_format((int) $metrics['today_visitors']) ?> unique daily</div>
        <?php else: ?>
            <div class="kpi-value" style="font-size:1.15rem;color:var(--adm-muted);">Not Tracked Yet</div>
            <div class="kpi-meta">Tracking starts once analytics hook is enabled (Batch 9)</div>
        <?php endif; ?>
    </article>

    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-label">Android App Users</span>
            <span class="kpi-icon blue"><i class="fa-brands fa-android" aria-hidden="true"></i></span>
        </div>
        <?php if (!empty($metrics['app_users']) && $metrics['app_users'] > 0): ?>
            <div class="kpi-value"><?= number_format((int) $metrics['app_users']) ?></div>
            <div class="kpi-meta">Tracked via Android API pings</div>
        <?php else: ?>
            <div class="kpi-value" style="font-size:1.15rem;color:var(--adm-muted);">Unavailable</div>
            <div class="kpi-meta">Android app has not sent telemetry yet</div>
        <?php endif; ?>
    </article>

    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-label">PDF Operations</span>
            <span class="kpi-icon green"><i class="fa-solid fa-file-pdf" aria-hidden="true"></i></span>
        </div>
        <?php if (!empty($metrics['pdf_operations']) && $metrics['pdf_operations'] > 0): ?>
            <div class="kpi-value"><?= number_format((int) $metrics['pdf_operations']) ?></div>
            <div class="kpi-meta">Downloads: <?= number_format((int) $metrics['downloads']) ?></div>
        <?php else: ?>
            <div class="kpi-value" style="font-size:1.15rem;color:var(--adm-muted);">Not Tracked Yet</div>
            <div class="kpi-meta">Downloads: <?= !empty($metrics['downloads']) ? number_format((int) $metrics['downloads']) : 'Not tracked yet' ?></div>
        <?php endif; ?>
    </article>
</section>

<!-- Real Data Charts -->
<section class="adm-grid-2">
    <article class="adm-card">
        <div class="adm-card-head">
            <h3>Visitors Overview (Last 7 Days)</h3>
            <span class="adm-badge badge--info">Website</span>
        </div>
        <div class="chart-box">
            <canvas
                data-chart-labels="<?= e(json_encode($visitorLabels)) ?>"
                data-chart-values="<?= e(json_encode($visitorValues)) ?>"
                data-chart-color="#22D3EE"
            ></canvas>
            <div class="chart-empty" style="display:none;">
                <i class="fa-solid fa-chart-column" aria-hidden="true"></i>
                <div>No visitor events recorded yet.<br>Real counts appear here once page tracking (Batch 9) is active.</div>
            </div>
        </div>
    </article>

    <article class="adm-card">
        <div class="adm-card-head">
            <h3>User Registrations (Last 7 Days)</h3>
            <span class="adm-badge badge--ok">Live DB</span>
        </div>
        <div class="chart-box">
            <canvas
                data-chart-labels="<?= e(json_encode($userGrowthLabels)) ?>"
                data-chart-values="<?= e(json_encode($userGrowthValues)) ?>"
                data-chart-color="#16C7B5"
            ></canvas>
            <div class="chart-empty" style="display:none;">
                <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                <div>No new user registrations in the last 7 days.</div>
            </div>
        </div>
    </article>

    <article class="adm-card">
        <div class="adm-card-head">
            <h3>Popular PDF &amp; Archive Tools</h3>
            <span class="adm-badge badge--info">Tool Runs</span>
        </div>
        <div class="chart-box">
            <canvas
                data-chart-labels="<?= e(json_encode($toolChartLabels)) ?>"
                data-chart-values="<?= e(json_encode($toolChartValues)) ?>"
                data-chart-color="#3B82F6"
            ></canvas>
            <div class="chart-empty" style="display:none;">
                <i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i>
                <div>Tool usage tracking is not active yet.<br>No synthetic stats are displayed.</div>
            </div>
        </div>
    </article>

    <article class="adm-card">
        <div class="adm-card-head">
            <h3>Admin Activity (Last 7 Days)</h3>
            <span class="adm-badge badge--ok">Live Audit</span>
        </div>
        <div class="chart-box">
            <canvas
                data-chart-labels="<?= e(json_encode($activityLabels)) ?>"
                data-chart-values="<?= e(json_encode($activityValues)) ?>"
                data-chart-color="#22D3EE"
            ></canvas>
            <div class="chart-empty" style="display:none;">
                <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                <div>No admin activity recorded in the last 7 days.</div>
            </div>
        </div>
    </article>
</section>

<!-- Bottom Split: Recent Activities & Quick Summary -->
<section class="adm-grid-2">
    <article class="adm-card">
        <div class="adm-card-head">
            <h3>Recent Admin Activities</h3>
            <a href="logs.php" class="adm-btn adm-btn-secondary adm-btn-sm">All Logs</a>
        </div>

        <?php if (empty($recentActivities)): ?>
            <p class="adm-card-note">No audit entries recorded yet.</p>
        <?php else: ?>
            <div class="adm-feed">
                <?php foreach ($recentActivities as $act): ?>
                    <div class="adm-feed-item">
                        <div>
                            <div style="font-weight:600;color:#FFFFFF;"><?= e((string) $act['summary']) ?></div>
                            <div class="adm-card-note">
                                Action: <code><?= e((string) $act['action']) ?></code>
                                <?php if (!empty($act['admin_username'])): ?>
                                    • by <?= e((string) $act['admin_username']) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="adm-feed-time"><?= e(date('M d, H:i', strtotime((string) $act['created_at']))) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <article class="adm-card">
        <div class="adm-card-head">
            <h3>Inbox &amp; Quick Overview</h3>
            <a href="messages.php" class="adm-btn adm-btn-secondary adm-btn-sm">Open Inbox</a>
        </div>

        <div class="adm-feed">
            <div class="adm-feed-item">
                <div>
                    <div style="font-weight:600;color:#FFFFFF;">Unread Contact Messages</div>
                    <div class="adm-card-note">From public <code>contact.php</code> form</div>
                </div>
                <span class="adm-badge <?= ((int) ($metrics['unread_messages'] ?? 0)) > 0 ? 'badge--warn' : 'badge--ok' ?>">
                    <?= $metrics['unread_messages'] !== null ? (int) $metrics['unread_messages'] . ' Unread' : 'Unavailable' ?>
                </span>
            </div>

            <div class="adm-feed-item">
                <div>
                    <div style="font-weight:600;color:#FFFFFF;">Files to ZIP / 7Z &amp; PDF Tools</div>
                    <div class="adm-card-note">Managed centrally from Feature Control Center</div>
                </div>
                <span class="adm-badge badge--info"><?= $activeFeaturesCount ?> Active</span>
            </div>

            <div class="adm-feed-item">
                <div>
                    <div style="font-weight:600;color:#FFFFFF;">Popular Tools Status</div>
                    <div class="adm-card-note">
                        <?php if (!empty($popularTools)): ?>
                            Top tool: <?= e((string) $popularTools[0]['target_key']) ?> (<?= (int) $popularTools[0]['runs'] ?> runs)
                        <?php else: ?>
                            Not tracked yet (no fabricated rankings)
                        <?php endif; ?>
                    </div>
                </div>
                <span class="adm-badge <?= !empty($popularTools) ? 'badge--ok' : 'badge--muted' ?>">
                    <?= !empty($popularTools) ? 'Live' : 'Pending Hook' ?>
                </span>
            </div>
        </div>
    </article>
</section>

<?php renderAdminFooter(); ?>