<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$admin = requireAdminWebAuth();
$logs = [];

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query(
        'SELECT id, admin_id, admin_username, action, target_type, target_id, summary, details_json, created_at
         FROM admin_activity_logs
         ORDER BY id DESC
         LIMIT 150'
    );
    $logs = $stmt ? ($stmt->fetchAll() ?: []) : [];
} catch (Throwable $e) {
    error_log('[ScanPro Admin Logs Page Error] ' . $e->getMessage());
}

renderAdminHeader($admin, 'logs', 'Activity Audit Logs', 'Chronological trail of administrative actions (passwords & tokens redacted)');
?>

<style>
.logs-toolbar {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 12px;
}
.log-details-pre {
    margin-top: 6px;
    font-size: 0.74rem;
    color: var(--adm-muted);
    background: var(--adm-bg);
    border: 1px solid var(--adm-border);
    border-radius: 6px;
    padding: 6px 8px;
    word-break: break-all;
}
@media (max-width: 720px) {
    .logs-toolbar { grid-template-columns: 1fr; }
}
</style>

<section class="adm-card">
    <div class="logs-toolbar">
        <div class="adm-field">
            <label for="logSearchInput" class="adm-label">Search Activity Logs</label>
            <input type="search" id="logSearchInput" class="adm-input" placeholder="Search admin, action, summary, or target...">
        </div>
        <div class="adm-field">
            <label for="logActionFilter" class="adm-label">Category</label>
            <select id="logActionFilter" class="adm-select">
                <option value="all">All Categories</option>
                <option value="admin.">Admin Auth &amp; Settings</option>
                <option value="website.">Website &amp; Maintenance</option>
                <option value="android.">Android App</option>
                <option value="feature.">Feature Controls</option>
                <option value="ads.">Ads Manager</option>
                <option value="user.">User Management</option>
                <option value="announcement.">Announcements</option>
                <option value="message.">Contact Messages</option>
            </select>
        </div>
    </div>
</section>

<section class="adm-card">
    <div class="adm-card-head">
        <h3>Latest 150 Audit Entries</h3>
        <span class="adm-card-note">IPs are hashed; sensitive keys are automatically redacted.</span>
    </div>

    <div class="adm-table-wrap">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Admin</th>
                    <th>Action</th>
                    <th>Summary &amp; Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="4" style="text-align:center;color:var(--adm-muted);padding:24px;">No activity logs recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $act = (string) ($log['action'] ?? '');
                        $badgeClass = 'badge--info';
                        if (str_contains($act, 'fail') || str_contains($act, 'block') || str_contains($act, 'delete') || str_contains($act, 'suspend')) {
                            $badgeClass = 'badge--warn';
                        } elseif (str_contains($act, 'login') || str_contains($act, 'publish') || str_contains($act, 'restore')) {
                            $badgeClass = 'badge--ok';
                        }
                        $searchStr = strtolower(
                            (string) ($log['admin_username'] ?? '') . ' ' . $act . ' ' . (string) $log['summary'] . ' ' . (string) ($log['target_id'] ?? '')
                        );
                        ?>
                        <tr class="js-log-row" data-action="<?= e($act) ?>" data-search="<?= e($searchStr) ?>">
                            <td style="white-space:nowrap;color:var(--adm-muted);">
                                <?= e(date('M d, H:i:s', strtotime((string) $log['created_at']))) ?>
                            </td>
                            <td>
                                <strong><?= e((string) ($log['admin_username'] ?: 'system')) ?></strong>
                            </td>
                            <td>
                                <span class="adm-badge <?= e($badgeClass) ?>"><code><?= e($act) ?></code></span>
                            </td>
                            <td>
                                <div style="color:#FFFFFF;font-weight:500;"><?= e((string) $log['summary']) ?></div>
                                <?php if (!empty($log['details_json'])): ?>
                                    <div class="log-details-pre"><code><?= e((string) $log['details_json']) ?></code></div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchEl = document.getElementById('logSearchInput');
    const catEl = document.getElementById('logActionFilter');
    const rows = Array.from(document.querySelectorAll('.js-log-row'));

    function applyFilter() {
        const q = (searchEl.value || '').trim().toLowerCase();
        const prefix = catEl.value;
        rows.forEach((row) => {
            const okQ = !q || (row.dataset.search || '').includes(q);
            const okCat = prefix === 'all' || (row.dataset.action || '').startsWith(prefix);
            row.style.display = (okQ && okCat) ? '' : 'none';
        });
    }

    if (searchEl) searchEl.addEventListener('input', applyFilter);
    if (catEl) catEl.addEventListener('change', applyFilter);
});
</script>

<?php renderAdminFooter(); ?>