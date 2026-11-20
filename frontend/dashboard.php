<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/helpers/security.php';
require_once __DIR__ . '/../backend/auth/auth_guard.php';
require_once __DIR__ . '/../backend/auth/auth_service.php';

$userId = requireWebAuthentication();

$userProfile = null;
$dbLoadError = null;

try {
    $pdo = getDbConnection();
    $userProfile = fetchUserDashboardProfile($pdo, $userId);
    if (!$userProfile || ($userProfile['status'] ?? '') !== 'active') {
        performUserLogout();
        startSecureSession();
        setFlashMessage('warning', 'Your session is no longer valid. Please sign in again.');
        header('Location: login.php', true, 302);
        exit;
    }
} catch (Throwable $e) {
    error_log('[ScanPro Dashboard Load Error] ' . $e->getMessage());
    $dbLoadError = 'Unable to load full account details from the database right now.';
    $userProfile = [
        'full_name'          => (string) ($_SESSION['user_name'] ?? 'ScanPro User'),
        'email'              => (string) ($_SESSION['user_email'] ?? ''),
        'role'               => (string) ($_SESSION['user_role'] ?? 'user'),
        'phone'              => '',
        'organization'       => '',
        'bio'                => '',
        'preferred_language' => 'English',
        'plan_name'          => 'Free',
        'plan_tagline'       => 'Essential document scanning & offline vault',
        'storage_label'      => '5 GB Vault Storage',
        'billing_cycle'      => 'monthly',
        'subscription_status'=> 'active',
        'created_at'         => date('Y-m-d H:i:s'),
        'last_login_at'      => null,
    ];
}

$fullName = (string) ($userProfile['full_name'] ?? 'User');
$isGoogleAccount = (($userProfile['auth_provider'] ?? 'password') === 'google');
$avatarUrl = (string) ($userProfile['avatar_url'] ?? '');
if (preg_match('#^https://#i', $avatarUrl) !== 1) {
    $avatarUrl = '';
}
$words = preg_split('/\s+/', trim($fullName)) ?: [];
$initials = '';
foreach (array_slice($words, 0, 2) as $word) {
    if ($word !== '') {
        $initials .= mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');
    }
}
if ($initials === '') {
    $initials = 'SP';
}

$languages = ['English', 'Spanish', 'French', 'German', 'Hindi', 'Urdu', 'Japanese'];
$selectedLang = (string) ($userProfile['preferred_language'] ?? 'English');

$pageTitle = 'Dashboard';
$pageDescription = 'Manage your ScanPro AI profile, account security settings, and current subscription plan.';
$activePage = 'dashboard';
$extraScripts = ['assets/js/dashboard.js'];

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main class="section" style="padding-top: 36px;">
    <div class="container">
        <?php require __DIR__ . '/includes/alerts.php'; ?>

        <?php if ($dbLoadError !== null): ?>
            <div class="alert alert--warning" role="alert">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true" style="margin-top:2px;"></i>
                <div class="alert-body"><?= e($dbLoadError) ?></div>
            </div>
        <?php endif; ?>

        <div id="dashboardAlerts" aria-live="polite"></div>

        <div class="dashboard-grid">
            <!-- Sidebar -->
            <aside class="dashboard-sidebar">
                <div class="dashboard-user-box">
                    <div class="avatar-circle" id="sidebarAvatarInitials"<?= $avatarUrl !== '' ? ' style="padding:0;overflow:hidden;"' : '' ?>>
                        <?php if ($avatarUrl !== ''): ?>
                            <img src="<?= e($avatarUrl) ?>" alt="" width="48" height="48" referrerpolicy="no-referrer" style="width:100%;height:100%;object-fit:cover;display:block;">
                        <?php else: ?>
                            <?= e($initials) ?>
                        <?php endif; ?>
                    </div>
                    <div style="min-width:0;">
                        <h3 id="sidebarUserName" style="font-size:1rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= e($fullName) ?>
                        </h3>
                        <p class="text-muted" style="font-size:0.8rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= e((string) ($userProfile['email'] ?? '')) ?>
                        </p>
                        <?php if ($isGoogleAccount): ?>
                            <p style="font-size:0.74rem;color:var(--accent-cyan);margin-top:2px;"><i class="fa-brands fa-google" aria-hidden="true"></i> Signed in with Google</p>
                        <?php endif; ?>
                    </div>
                </div>

                <nav class="dashboard-nav" aria-label="Dashboard Sections">
                    <button type="button" class="dashboard-nav-btn active" data-panel="overviewPanel">
                        <i class="fa-solid fa-house-user" aria-hidden="true"></i>
                        <span>Overview &amp; Plan</span>
                    </button>
                    <button type="button" class="dashboard-nav-btn" data-panel="profilePanel">
                        <i class="fa-solid fa-user-pen" aria-hidden="true"></i>
                        <span>Profile Settings</span>
                    </button>
                    <button type="button" class="dashboard-nav-btn" data-panel="securityPanel">
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        <span>Password &amp; Security</span>
                    </button>
                </nav>

                <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--glass-border);">
                    <form action="../backend/api/logout.php" method="POST">
                        <?= csrfHiddenInput() ?>
                        <button type="submit" class="btn btn-danger btn-block">
                            <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                            <span>Sign Out</span>
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Main Content Panels -->
            <div style="display:flex;flex-direction:column;gap:24px;">
                <!-- Panel 1: Overview & Current Plan -->
                <section class="dashboard-panel" id="overviewPanel">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
                        <div>
                            <span class="eyebrow">AUTHENTICATED ACCOUNT</span>
                            <h2>Welcome, <span class="text-gradient" id="welcomeUserName"><?= e($fullName) ?></span></h2>
                            <p class="text-muted mt-1" style="font-size:0.92rem;">
                                Review your account status, current subscription tier, and connected settings.
                            </p>
                        </div>
                        <span class="card-badge card-badge--cyan">
                            Role: <?= e(strtoupper((string) ($userProfile['role'] ?? 'user'))) ?>
                        </span>
                    </div>

                    <div class="grid grid-3 mt-3">
                        <div class="card card--tool" style="padding:18px;">
                            <span class="text-muted" style="font-size:0.78rem;text-transform:uppercase;letter-spacing:1px;">Current Plan</span>
                            <h3 class="mt-1" style="color:var(--accent-cyan);">
                                <?= e((string) ($userProfile['plan_name'] ?? 'Free')) ?> Plan
                            </h3>
                            <p style="font-size:0.84rem;">
                                Status: <?= e(ucfirst((string) ($userProfile['subscription_status'] ?? 'active'))) ?>
                                (<?= e(ucfirst((string) ($userProfile['billing_cycle'] ?? 'monthly'))) ?>)
                            </p>
                        </div>

                        <div class="card card--tool" style="padding:18px;">
                            <span class="text-muted" style="font-size:0.78rem;text-transform:uppercase;letter-spacing:1px;">Vault Tier Limit</span>
                            <h3 class="mt-1"><?= e((string) ($userProfile['storage_label'] ?? '5 GB Vault Storage')) ?></h3>
                            <p style="font-size:0.84rem;">
                                Preferred OCR: <span id="overviewLang"><?= e($selectedLang) ?></span>
                            </p>
                        </div>

                        <div class="card card--tool" style="padding:18px;">
                            <span class="text-muted" style="font-size:0.78rem;text-transform:uppercase;letter-spacing:1px;">Member Since</span>
                            <h3 class="mt-1">
                                <?= e(date('M d, Y', strtotime((string) ($userProfile['created_at'] ?? 'now')))) ?>
                            </h3>
                            <p style="font-size:0.84rem;">
                                Last login:
                                <?= !empty($userProfile['last_login_at'])
                                    ? e(date('M d, Y H:i', strtotime((string) $userProfile['last_login_at'])))
                                    : 'Current session' ?>
                            </p>
                        </div>
                    </div>

                    <div class="card card--highlight mt-3" style="padding:20px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">
                            <div>
                                <h3><?= e((string) ($userProfile['plan_name'] ?? 'Free')) ?> Tier Details</h3>
                                <p class="mt-1" style="font-size:0.9rem;">
                                    <?= e((string) ($userProfile['plan_tagline'] ?? 'Essential document scanning & offline vault')) ?>.
                                    Paid online upgrades will connect here once a live payment gateway is integrated.
                                </p>
                            </div>
                            <a href="pricing.php" class="btn btn-secondary">Compare Plans</a>
                        </div>
                    </div>
                </section>

                <!-- Panel 2: Profile Settings -->
                <section class="dashboard-panel" id="profilePanel" style="display:none;">
                    <h2>Profile &amp; Account Settings</h2>
                    <p class="text-muted mt-1 mb-3" style="font-size:0.92rem;">
                        Update your display name, contact phone, organization, and default OCR language preference.
                    </p>

                    <form id="profileUpdateForm" action="../backend/api/profile.php" method="POST" novalidate>
                        <?= csrfHiddenInput() ?>
                        <input type="hidden" name="action" value="update_profile">

                        <div class="grid grid-2">
                            <div class="form-group">
                                <label for="profileFullName" class="form-label">Full Name</label>
                                <input
                                    type="text"
                                    id="profileFullName"
                                    name="full_name"
                                    class="form-control"
                                    value="<?= e($fullName) ?>"
                                    required
                                    minlength="2"
                                    maxlength="100"
                                >
                                <span class="form-error-text" id="profileFullNameError"></span>
                            </div>

                            <div class="form-group">
                                <label for="profileEmail" class="form-label">Email Address (Read-only)</label>
                                <input
                                    type="email"
                                    id="profileEmail"
                                    class="form-control"
                                    value="<?= e((string) ($userProfile['email'] ?? '')) ?>"
                                    disabled
                                >
                            </div>
                        </div>

                        <div class="grid grid-2">
                            <div class="form-group">
                                <label for="profilePhone" class="form-label">Phone Number (Optional)</label>
                                <input
                                    type="text"
                                    id="profilePhone"
                                    name="phone"
                                    class="form-control"
                                    placeholder="+1 555 010 2030"
                                    value="<?= e((string) ($userProfile['phone'] ?? '')) ?>"
                                    maxlength="30"
                                >
                                <span class="form-error-text" id="profilePhoneError"></span>
                            </div>

                            <div class="form-group">
                                <label for="profileOrg" class="form-label">Organization / Company (Optional)</label>
                                <input
                                    type="text"
                                    id="profileOrg"
                                    name="organization"
                                    class="form-control"
                                    placeholder="Your company or workspace"
                                    value="<?= e((string) ($userProfile['organization'] ?? '')) ?>"
                                    maxlength="120"
                                >
                                <span class="form-error-text" id="profileOrgError"></span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="profileLanguage" class="form-label">Preferred OCR Language</label>
                            <select id="profileLanguage" name="preferred_language" class="form-control">
                                <?php foreach ($languages as $lang): ?>
                                    <option value="<?= e($lang) ?>"<?= $selectedLang === $lang ? ' selected' : '' ?>>
                                        <?= e($lang) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="profileBio" class="form-label">Short Bio / Notes (Optional)</label>
                            <textarea
                                id="profileBio"
                                name="bio"
                                class="form-control"
                                maxlength="500"
                                placeholder="Add workspace notes or role description..."
                            ><?= e((string) ($userProfile['bio'] ?? '')) ?></textarea>
                            <span class="form-error-text" id="profileBioError"></span>
                        </div>

                        <button type="submit" id="profileSaveBtn" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                            <span>Save Profile Changes</span>
                        </button>
                    </form>
                </section>

                <!-- Panel 3: Password & Security -->
                <section class="dashboard-panel" id="securityPanel" style="display:none;">
                    <h2>Change Password</h2>
                    <p class="text-muted mt-1 mb-3" style="font-size:0.92rem;">
                        Verify your current password to set a new strong password (minimum <?= (int) PASSWORD_MIN_LENGTH ?> characters).
                    </p>

                    <?php if ($isGoogleAccount): ?>
                        <div class="alert alert--info" role="note">
                            <div class="alert-body">Your account signs in with Google, so there is no ScanPro password to change. Manage your Google password in your Google Account.</div>
                        </div>
                    <?php else: ?>
                    <form id="passwordUpdateForm" action="../backend/api/profile.php" method="POST" novalidate>
                        <?= csrfHiddenInput() ?>
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <div class="input-wrap">
                                <input
                                    type="password"
                                    id="currentPassword"
                                    name="current_password"
                                    class="form-control form-control--with-icon"
                                    required
                                    autocomplete="current-password"
                                >
                                <button type="button" class="password-toggle" aria-label="Show password">
                                    <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <span class="form-error-text" id="currentPasswordError"></span>
                        </div>

                        <div class="form-group">
                            <label for="newPassword" class="form-label">New Password</label>
                            <div class="input-wrap">
                                <input
                                    type="password"
                                    id="newPassword"
                                    name="new_password"
                                    class="form-control form-control--with-icon"
                                    required
                                    minlength="<?= (int) PASSWORD_MIN_LENGTH ?>"
                                    autocomplete="new-password"
                                >
                                <button type="button" class="password-toggle" aria-label="Show password">
                                    <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <span class="form-error-text" id="newPasswordError"></span>
                        </div>

                        <div class="form-group">
                            <label for="confirmNewPassword" class="form-label">Confirm New Password</label>
                            <div class="input-wrap">
                                <input
                                    type="password"
                                    id="confirmNewPassword"
                                    name="confirm_new_password"
                                    class="form-control form-control--with-icon"
                                    required
                                    minlength="<?= (int) PASSWORD_MIN_LENGTH ?>"
                                    autocomplete="new-password"
                                >
                                <button type="button" class="password-toggle" aria-label="Show password">
                                    <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <span class="form-error-text" id="confirmNewPasswordError"></span>
                        </div>

                        <button type="submit" id="passwordSaveBtn" class="btn btn-primary">
                            <i class="fa-solid fa-key" aria-hidden="true"></i>
                            <span>Update Password</span>
                        </button>
                    </form>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>