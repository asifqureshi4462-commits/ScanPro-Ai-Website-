<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$admin = requireAdminWebAuth();

$totalUsers = 0;
$activeUsers = 0;
$suspendedUsers = 0;
$googleUsers = 0;
$hasProviderCol = false;
$users = [];

try {
    $pdo = getDbConnection();
    $totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $activeUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    $suspendedUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();

    try {
        $pdo->query('SELECT auth_provider FROM users LIMIT 1');
        $hasProviderCol = true;
        $googleUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE auth_provider = 'google'")->fetchColumn();
    } catch (PDOException $e) {
        $hasProviderCol = false;
    }

    $providerExpr = $hasProviderCol
        ? "COALESCE(u.auth_provider, 'password') AS auth_provider, COALESCE(u.email_verified, 0) AS email_verified,"
        : "'password' AS auth_provider, 0 AS email_verified,";

    $stmt = $pdo->query(
        "SELECT
            u.id,
            u.full_name,
            u.email,
            u.role,
            u.status,
            {$providerExpr}
            u.last_login_at,
            u.created_at,
            up.phone,
            up.organization,
            up.preferred_language,
            COALESCE(p.name, 'Free') AS plan_name,
            COALESCE(us.billing_cycle, 'monthly') AS billing_cycle
         FROM users u
         LEFT JOIN user_profiles up ON up.user_id = u.id
         LEFT JOIN user_subscriptions us ON us.id = (
             SELECT us2.id
             FROM user_subscriptions us2
             WHERE us2.user_id = u.id AND us2.status = 'active'
             ORDER BY us2.id DESC
             LIMIT 1
         )
         LEFT JOIN plans p ON p.id = us.plan_id
         ORDER BY u.id DESC
         LIMIT 100"
    );
    $users = $stmt ? ($stmt->fetchAll() ?: []) : [];
} catch (Throwable $e) {
    error_log('[ScanPro Admin Users Page Error] ' . $e->getMessage());
}

renderAdminHeader($admin, 'users', 'User Management', 'Manage registered accounts, activity timestamps, and status');
?>

<style>
.user-filters {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr;
    gap: 12px;
}
.user-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
    z-index: 1500;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 16px;
}
.user-modal-backdrop.open {
    display: flex;
}
.user-modal {
    width: 100%;
    max-width: 480px;
    background: var(--adm-card);
    border: 1px solid var(--adm-border-cyan);
    border-radius: 16px;
    padding: 22px;
    max-height: 90dvh;
    overflow-y: auto;
}
.detail-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 9px 0;
    border-bottom: 1px solid var(--adm-border);
    font-size: 0.86rem;
}
.detail-row:last-child { border-bottom: none; }
.detail-row span { color: var(--adm-muted); }
.detail-row strong { color: #FFFFFF; text-align: right; word-break: break-word; }
@media (max-width: 820px) {
    .user-filters { grid-template-columns: 1fr; }
}
</style>

<section class="kpi-grid">
    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-label">Total Users</span>
            <span class="kpi-icon cyan"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
        </div>
        <div class="kpi-value" id="statTotalUsers"><?= number_format($totalUsers) ?></div>
        <div class="kpi-meta">Showing latest <?= count($users) ?></div>
    </article>

    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-label">Active Accounts</span>
            <span class="kpi-icon green"><i class="fa-solid fa-user-check" aria-hidden="true"></i></span>
        </div>
        <div class="kpi-value" id="statActiveUsers"><?= number_format($activeUsers) ?></div>
        <div class="kpi-meta">Enabled for login</div>
    </article>

    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-label">Suspended</span>
            <span class="kpi-icon amber"><i class="fa-solid fa-user-slash" aria-hidden="true"></i></span>
        </div>
        <div class="kpi-value" id="statSuspendedUsers"><?= number_format($suspendedUsers) ?></div>
        <div class="kpi-meta">Blocked from signing in</div>
    </article>

    <article class="kpi-card">
        <div class="kpi-top">
            <span class="kpi-label">Google Sign-In</span>
            <span class="kpi-icon blue"><i class="fa-brands fa-google" aria-hidden="true"></i></span>
        </div>
        <div class="kpi-value"><?= $hasProviderCol ? number_format($googleUsers) : 'N/A' ?></div>
        <div class="kpi-meta"><?= $hasProviderCol ? 'Firebase Google accounts' : 'Auth provider column absent' ?></div>
    </article>
</section>

<section class="adm-card">
    <div class="user-filters">
        <div class="adm-field">
            <label for="userSearchInput" class="adm-label">Search Name or Email</label>
            <input type="search" id="userSearchInput" class="adm-input" placeholder="Search users...">
        </div>
        <div class="adm-field">
            <label for="userStatusFilter" class="adm-label">Account Status</label>
            <select id="userStatusFilter" class="adm-select">
                <option value="all">All Statuses</option>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>
        <div class="adm-field">
            <label for="userSourceFilter" class="adm-label">Account Source</label>
            <select id="userSourceFilter" class="adm-select">
                <option value="all">All Sources</option>
                <option value="password">Website Email</option>
                <option value="google">Google (Firebase)</option>
            </select>
        </div>
    </div>
</section>

<section class="adm-card">
    <div class="adm-card-head">
        <h3>Registered Accounts</h3>
        <span class="adm-card-note">Passwords and security tokens are never displayed.</span>
    </div>

    <div class="adm-table-wrap">
        <table class="adm-table" id="usersTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Source</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;color:var(--adm-muted);padding:24px;">No registered users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): ?>
                        <?php
                        $uid = (int) $u['id'];
                        $st = (string) ($u['status'] ?? 'active');
                        $src = (string) ($u['auth_provider'] ?? 'password');
                        $isActive = $st === 'active';
                        $safeDetails = [
                            'id'            => $uid,
                            'full_name'     => (string) ($u['full_name'] ?? ''),
                            'email'         => (string) ($u['email'] ?? ''),
                            'status'        => $st,
                            'source'        => $src === 'google' ? 'Google (Firebase)' : 'Website Email',
                            'plan'          => (string) ($u['plan_name'] ?? 'Free') . ' (' . ucfirst((string) ($u['billing_cycle'] ?? 'monthly')) . ')',
                            'organization'  => (string) ($u['organization'] ?? '—'),
                            'language'      => (string) ($u['preferred_language'] ?? 'English'),
                            'created_at'    => date('M d, Y H:i', strtotime((string) ($u['created_at'] ?? 'now'))),
                            'last_login_at' => !empty($u['last_login_at']) ? date('M d, Y H:i', strtotime((string) $u['last_login_at'])) : 'Never recorded',
                        ];
                        ?>
                        <tr
                            class="js-user-row"
                            data-user-id="<?= $uid ?>"
                            data-search="<?= e(strtolower((string) $u['full_name'] . ' ' . (string) $u['email'])) ?>"
                            data-status="<?= e($st) ?>"
                            data-source="<?= e($src) ?>"
                        >
                            <td>
                                <div style="font-weight:700;color:#FFFFFF;"><?= e((string) $u['full_name']) ?></div>
                                <div class="adm-card-note"><?= e((string) $u['email']) ?></div>
                            </td>
                            <td>
                                <span class="adm-badge <?= $src === 'google' ? 'badge--info' : 'badge--muted' ?>">
                                    <?= $src === 'google' ? 'Google' : 'Website Email' ?>
                                </span>
                            </td>
                            <td><?= e((string) ($u['plan_name'] ?? 'Free')) ?></td>
                            <td>
                                <span class="adm-badge js-user-status-badge <?= $isActive ? 'badge--ok' : 'badge--danger' ?>">
                                    <?= $isActive ? 'Active' : 'Suspended' ?>
                                </span>
                            </td>
                            <td><?= e(date('M d, Y', strtotime((string) $u['created_at']))) ?></td>
                            <td>
                                <?= !empty($u['last_login_at']) ? e(date('M d, Y H:i', strtotime((string) $u['last_login_at']))) : '—' ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <button
                                        type="button"
                                        class="adm-btn adm-btn-secondary adm-btn-sm js-view-user"
                                        data-user="<?= e(json_encode($safeDetails, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>"
                                    >
                                        Details
                                    </button>
                                    <button
                                        type="button"
                                        class="adm-btn adm-btn-sm js-toggle-user <?= $isActive ? 'adm-btn-danger' : 'adm-btn-primary' ?>"
                                        data-next-status="<?= $isActive ? 'suspended' : 'active' ?>"
                                    >
                                        <?= $isActive ? 'Suspend' : 'Restore' ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<div class="user-modal-backdrop" id="userDetailModal" role="dialog" aria-modal="true" aria-labelledby="userModalTitle">
    <div class="user-modal">
        <div class="adm-card-head">
            <h3 id="userModalTitle">User Account Details</h3>
            <button type="button" class="adm-btn adm-btn-secondary adm-btn-sm" id="closeUserModal">Close</button>
        </div>
        <div id="userDetailBody"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchEl = document.getElementById('userSearchInput');
    const statusEl = document.getElementById('userStatusFilter');
    const sourceEl = document.getElementById('userSourceFilter');
    const rows = Array.from(document.querySelectorAll('.js-user-row'));
    const modal = document.getElementById('userDetailModal');
    const modalBody = document.getElementById('userDetailBody');
    const closeModalBtn = document.getElementById('closeUserModal');

    function applyFilter() {
        const q = (searchEl.value || '').trim().toLowerCase();
        const st = statusEl.value;
        const src = sourceEl.value;
        rows.forEach((row) => {
            const okQ = !q || (row.dataset.search || '').includes(q);
            const okSt = st === 'all' || row.dataset.status === st;
            const okSrc = src === 'all' || row.dataset.source === src;
            row.style.display = (okQ && okSt && okSrc) ? '' : 'none';
        });
    }

    [searchEl, statusEl, sourceEl].forEach((el) => {
        if (el) {
            el.addEventListener('input', applyFilter);
            el.addEventListener('change', applyFilter);
        }
    });

    function openDetails(info) {
        const pairs = [
            ['User ID', '#' + info.id],
            ['Full Name', info.full_name],
            ['Email', info.email],
            ['Status', info.status.toUpperCase()],
            ['Account Source', info.source],
            ['Subscription Plan', info.plan],
            ['Organization', info.organization || '—'],
            ['Preferred OCR', info.language],
            ['Registered On', info.created_at],
            ['Last Login', info.last_login_at]
        ];
        modalBody.innerHTML = '';
        pairs.forEach(([k, v]) => {
            const div = document.createElement('div');
            div.className = 'detail-row';
            const s = document.createElement('span');
            s.textContent = k;
            const st = document.createElement('strong');
            st.textContent = v;
            div.append(s, st);
            modalBody.appendChild(div);
        });
        modal.classList.add('open');
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => modal.classList.remove('open'));
    }
    if (modal) {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.remove('open');
        });
    }

    rows.forEach((row) => {
        const viewBtn = row.querySelector('.js-view-user');
        const toggleBtn = row.querySelector('.js-toggle-user');
        const badge = row.querySelector('.js-user-status-badge');

        if (viewBtn) {
            viewBtn.addEventListener('click', () => {
                try {
                    const info = JSON.parse(viewBtn.getAttribute('data-user') || '{}');
                    openDetails(info);
                } catch (e) {}
            });
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', async () => {
                const nextStatus = toggleBtn.dataset.nextStatus;
                const actionLabel = nextStatus === 'suspended' ? 'suspend' : 'restore';
                if (!window.confirm('Are you sure you want to ' + actionLabel + ' this user account?')) {
                    return;
                }

                const orig = toggleBtn.textContent;
                toggleBtn.disabled = true;
                toggleBtn.textContent = 'Saving...';

                try {
                    const data = await window.AdminUI.postJson('../backend/api/admin/users.php', {
                        action: 'set_status',
                        user_id: Number(row.dataset.userId),
                        status: nextStatus
                    });

                    const isNowActive = data.status === 'active';
                    row.dataset.status = data.status;
                    badge.className = 'adm-badge js-user-status-badge ' + (isNowActive ? 'badge--ok' : 'badge--danger');
                    badge.textContent = isNowActive ? 'Active' : 'Suspended';

                    toggleBtn.className = 'adm-btn adm-btn-sm js-toggle-user ' + (isNowActive ? 'adm-btn-danger' : 'adm-btn-primary');
                    toggleBtn.dataset.nextStatus = isNowActive ? 'suspended' : 'active';
                    toggleBtn.textContent = isNowActive ? 'Suspend' : 'Restore';

                    applyFilter();
                    window.AdminUI.toast('success', data.message || 'User status updated.');
                } catch (err) {
                    toggleBtn.textContent = orig;
                    window.AdminUI.toast('error', err.message || 'Could not update user status.');
                } finally {
                    toggleBtn.disabled = false;
                }
            });
        }
    });
});
</script>

<?php renderAdminFooter(); ?>