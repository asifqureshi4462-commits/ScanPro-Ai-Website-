<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$admin = requireAdminWebAuth();
$currentTimeout = getConfiguredAdminIdleTimeout();
$timeoutOptions = [
    300   => '5 Minutes',
    900   => '15 Minutes',
    1800  => '30 Minutes (Default)',
    3600  => '1 Hour',
    7200  => '2 Hours',
    14400 => '4 Hours',
    86400 => '24 Hours',
];

renderAdminHeader($admin, 'settings', 'Admin Settings', 'Profile, password security, session timeout & quick system links');
?>

<div class="adm-grid-2">
    <!-- Admin Profile -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-solid fa-user-shield" style="color:var(--adm-cyan);margin-right:8px;" aria-hidden="true"></i>Admin Profile</h3>
            <span class="adm-badge badge--info"><?= e(strtoupper(str_replace('_', ' ', (string) ($admin['role'] ?? 'admin')))) ?></span>
        </div>

        <form id="adminProfileForm" action="../backend/api/admin/settings.php" method="POST" novalidate>
            <?= adminCsrfInput() ?>
            <input type="hidden" name="action" value="update_profile">

            <div class="adm-form-grid">
                <div class="adm-field adm-field--full">
                    <label class="adm-label">Username (Read-only)</label>
                    <input type="text" class="adm-input" value="<?= e((string) ($admin['username'] ?? '')) ?>" disabled>
                </div>

                <div class="adm-field adm-field--full">
                    <label for="admFullName" class="adm-label">Full Name</label>
                    <input type="text" id="admFullName" name="full_name" class="adm-input" maxlength="100" required value="<?= e((string) ($admin['full_name'] ?? '')) ?>">
                </div>

                <div class="adm-field adm-field--full">
                    <label for="admEmail" class="adm-label">Email Address</label>
                    <input type="email" id="admEmail" name="email" class="adm-input" maxlength="190" required value="<?= e((string) ($admin['email'] ?? '')) ?>">
                </div>
            </div>

            <div style="margin-top:16px;display:flex;justify-content:flex-end;">
                <button type="submit" id="saveProfileBtn" class="adm-btn adm-btn-primary">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    <span>Save Profile</span>
                </button>
            </div>
        </form>
    </section>

    <!-- Change Password -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-solid fa-key" style="color:var(--adm-teal);margin-right:8px;" aria-hidden="true"></i>Change Admin Password</h3>
        </div>

        <form id="adminPasswordForm" action="../backend/api/admin/settings.php" method="POST" novalidate>
            <?= adminCsrfInput() ?>
            <input type="hidden" name="action" value="change_password">

            <div class="adm-form-grid">
                <div class="adm-field adm-field--full">
                    <label for="admCurrentPwd" class="adm-label">Current Password</label>
                    <input type="password" id="admCurrentPwd" name="current_password" class="adm-input" required autocomplete="current-password">
                </div>

                <div class="adm-field adm-field--full">
                    <label for="admNewPwd" class="adm-label">New Password (min <?= (int) ADMIN_PASSWORD_MIN_LENGTH ?> chars, letters + numbers)</label>
                    <input type="password" id="admNewPwd" name="new_password" class="adm-input" required minlength="<?= (int) ADMIN_PASSWORD_MIN_LENGTH ?>" autocomplete="new-password">
                </div>

                <div class="adm-field adm-field--full">
                    <label for="admConfirmPwd" class="adm-label">Confirm New Password</label>
                    <input type="password" id="admConfirmPwd" name="confirm_password" class="adm-input" required minlength="<?= (int) ADMIN_PASSWORD_MIN_LENGTH ?>" autocomplete="new-password">
                </div>
            </div>

            <div style="margin-top:16px;display:flex;justify-content:flex-end;">
                <button type="submit" id="savePwdBtn" class="adm-btn adm-btn-primary">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    <span>Update Password</span>
                </button>
            </div>
        </form>
    </section>
</div>

<div class="adm-grid-2">
    <!-- Security & Session Management -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-solid fa-clock" style="color:var(--adm-blue);margin-right:8px;" aria-hidden="true"></i>Session &amp; Security Settings</h3>
        </div>

        <form id="adminSecurityForm" action="../backend/api/admin/settings.php" method="POST" novalidate>
            <?= adminCsrfInput() ?>
            <input type="hidden" name="action" value="update_security">

            <div class="adm-field">
                <label for="admSessionTimeout" class="adm-label">Admin Session Idle Timeout</label>
                <select id="admSessionTimeout" name="admin_session_timeout" class="adm-select">
                    <?php foreach ($timeoutOptions as $sec => $label): ?>
                        <option value="<?= (int) $sec ?>"<?= $currentTimeout === $sec ? ' selected' : '' ?>>
                            <?= e($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="adm-hint">Automatically signs out inactive admin sessions.</span>
            </div>

            <div class="adm-feed" style="margin-top:14px;">
                <div class="adm-feed-item">
                    <span class="adm-card-note">Session Cookie</span>
                    <code><?= e(ADMIN_SESSION_NAME) ?></code>
                </div>
                <div class="adm-feed-item">
                    <span class="adm-card-note">Last Sign-In</span>
                    <strong><?= !empty($admin['last_login_at']) ? e(date('M d, Y H:i', strtotime((string) $admin['last_login_at']))) : 'Current session' ?></strong>
                </div>
            </div>

            <div style="margin-top:16px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;">
                <button type="submit" id="saveSecBtn" class="adm-btn adm-btn-primary">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    <span>Save Security Setting</span>
                </button>
            </div>
        </form>

        <form action="../backend/api/admin/auth.php" method="POST" style="margin-top:14px;padding-top:14px;border-top:1px solid var(--adm-border);">
            <?= adminCsrfInput() ?>
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="adm-btn adm-btn-danger" style="width:100%;">
                <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                <span>Sign Out of Admin Control Center</span>
            </button>
        </form>
    </section>

    <!-- Quick Configuration Shortcuts -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-solid fa-sliders" style="color:var(--adm-cyan);margin-right:8px;" aria-hidden="true"></i>Configuration Shortcuts</h3>
        </div>

        <div class="adm-feed">
            <a href="website.php" class="adm-feed-item">
                <div>
                    <div style="font-weight:700;color:#FFFFFF;">Website &amp; Maintenance Settings</div>
                    <div class="adm-card-note">Maintenance mode, global banner, contact emails &amp; visibility</div>
                </div>
                <i class="fa-solid fa-chevron-right" style="color:var(--adm-cyan);" aria-hidden="true"></i>
            </a>

            <a href="android-app.php" class="adm-feed-item">
                <div>
                    <div style="font-weight:700;color:#FFFFFF;">Android Application Config</div>
                    <div class="adm-card-note">App status, version rules, mandatory updates &amp; APK link</div>
                </div>
                <i class="fa-solid fa-chevron-right" style="color:var(--adm-cyan);" aria-hidden="true"></i>
            </a>

            <a href="features.php" class="adm-feed-item">
                <div>
                    <div style="font-weight:700;color:#FFFFFF;">Feature Control Center</div>
                    <div class="adm-card-note">Toggle Files to ZIP/7Z, PDF tools, OCR &amp; Android features</div>
                </div>
                <i class="fa-solid fa-chevron-right" style="color:var(--adm-cyan);" aria-hidden="true"></i>
            </a>

            <a href="ads.php" class="adm-feed-item">
                <div>
                    <div style="font-weight:700;color:#FFFFFF;">Ads Management</div>
                    <div class="adm-card-note">Global, guest, logged-in &amp; per-page ad placements</div>
                </div>
                <i class="fa-solid fa-chevron-right" style="color:var(--adm-cyan);" aria-hidden="true"></i>
            </a>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const profileForm = document.getElementById('adminProfileForm');
    const pwdForm = document.getElementById('adminPasswordForm');
    const secForm = document.getElementById('adminSecurityForm');

    if (profileForm) {
        profileForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('saveProfileBtn');
            btn.disabled = true;
            try {
                const data = await window.AdminUI.postJson(profileForm.getAttribute('action'), {
                    action: 'update_profile',
                    full_name: document.getElementById('admFullName').value.trim(),
                    email: document.getElementById('admEmail').value.trim()
                });
                window.AdminUI.toast('success', data.message || 'Profile saved.');
            } catch (err) {
                window.AdminUI.toast('error', err.message || 'Could not save profile.');
            } finally {
                btn.disabled = false;
            }
        });
    }

    if (pwdForm) {
        pwdForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('savePwdBtn');
            btn.disabled = true;
            try {
                const data = await window.AdminUI.postJson(pwdForm.getAttribute('action'), {
                    action: 'change_password',
                    current_password: document.getElementById('admCurrentPwd').value,
                    new_password: document.getElementById('admNewPwd').value,
                    confirm_password: document.getElementById('admConfirmPwd').value
                });
                pwdForm.reset();
                window.AdminUI.toast('success', data.message || 'Password updated.');
            } catch (err) {
                window.AdminUI.toast('error', err.message || 'Could not update password.');
            } finally {
                btn.disabled = false;
            }
        });
    }

    if (secForm) {
        secForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('saveSecBtn');
            btn.disabled = true;
            try {
                const data = await window.AdminUI.postJson(secForm.getAttribute('action'), {
                    action: 'update_security',
                    admin_session_timeout: Number(document.getElementById('admSessionTimeout').value)
                });
                window.AdminUI.toast('success', data.message || 'Security settings saved.');
            } catch (err) {
                window.AdminUI.toast('error', err.message || 'Could not save security settings.');
            } finally {
                btn.disabled = false;
            }
        });
    }
});
</script>

<?php renderAdminFooter(); ?>