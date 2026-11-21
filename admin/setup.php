<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth/admin_auth.php';
require_once __DIR__ . '/../backend/helpers/validator.php';

startAdminSession();
applyAdminSecurityHeaders();

$loginUrl = getAdminRootUrlPath() . '/login.php';
$dbReady = true;
$setupLocked = isAdminSetupLockedOnDisk();
$adminCount = 0;

if (!$setupLocked) {
    try {
        $pdo = getDbConnection();
        $adminCount = countAdminAccounts($pdo);
        if ($adminCount > 0) {
            $storageDir = dirname(ADMIN_SETUP_LOCK_FILE);
            if (!is_dir($storageDir)) {
                @mkdir($storageDir, 0750, true);
            }
            @file_put_contents(ADMIN_SETUP_LOCK_FILE, 'locked:' . date('c') . "\n", LOCK_EX);
            $setupLocked = true;
        }
    } catch (Throwable $e) {
        $dbReady = false;
        error_log('[ScanPro Admin Setup Check] ' . $e->getMessage());
    }
}

if ($setupLocked) {
    setAdminFlash('info', 'Admin setup is already complete. Please sign in.');
    header('Location: ' . $loginUrl, true, 302);
    exit;
}

$errors = [];
$formMessage = null;
$username = '';
$email = '';
$fullName = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $dbReady) {
    $submittedCsrf = extractAdminCsrfToken();

    if (!verifyAdminCsrfToken($submittedCsrf)) {
        $formMessage = 'Security token expired or invalid. Please refresh and try again.';
    } else {
        $pdo = getDbConnection();
        $ipKey = 'setup|' . rateLimitClientIp();

        if (rateLimitIsBlocked($pdo, 'admin_setup', $ipKey, 5, 10, 900)) {
            $formMessage = 'Too many setup attempts. Please wait 15 minutes and try again.';
        } else {
            $dbNameCheck = cleanText($_POST['db_name_verify'] ?? '');
            $dbUserCheck = cleanText($_POST['db_user_verify'] ?? '');
            $username = strtolower(cleanText($_POST['username'] ?? ''));
            $email = normalizeEmail($_POST['email'] ?? '');
            $fullName = cleanText($_POST['full_name'] ?? '');
            $password = is_string($_POST['password'] ?? null) ? (string) $_POST['password'] : '';
            $confirmPassword = is_string($_POST['confirm_password'] ?? null) ? (string) $_POST['confirm_password'] : '';

            // Verify server-side database credentials without ever exposing them in HTML
            if (!hash_equals(DB_NAME, $dbNameCheck) || !hash_equals(DB_USER, $dbUserCheck)) {
                $errors['db_verify'] = 'Database name or database username does not match server configuration.';
            }

            if (!preg_match('/^[a-z0-9_.-]{3,40}$/', $username)) {
                $errors['username'] = 'Username must be 3–40 characters (letters, numbers, dot, underscore, or hyphen).';
            }
            if (!isValidEmailAddress($email)) {
                $errors['email'] = 'Please enter a valid email address.';
            }
            if (!isLengthBetween($fullName, 2, 100)) {
                $errors['full_name'] = 'Full name must be between 2 and 100 characters.';
            }
            if (mb_strlen($password, 'UTF-8') < ADMIN_PASSWORD_MIN_LENGTH || mb_strlen($password, 'UTF-8') > 128) {
                $errors['password'] = 'Admin password must be between ' . ADMIN_PASSWORD_MIN_LENGTH . ' and 128 characters.';
            } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
                $errors['password'] = 'Admin password must include both letters and numbers.';
            }
            if ($confirmPassword !== $password) {
                $errors['confirm_password'] = 'Passwords do not match.';
            }

            if (!empty($errors)) {
                rateLimitRecordFailure($pdo, 'admin_setup', $ipKey);
                $formMessage = 'Please fix the highlighted fields below.';
            } else {
                $res = createInitialSuperAdmin($pdo, $username, $email, $fullName, $password);
                if (!$res['ok']) {
                    $formMessage = $res['message'];
                } else {
                    @unlink(__FILE__);
                    setAdminFlash('success', 'Super Admin account created! Please sign in below.');
                    header('Location: ' . $loginUrl, true, 302);
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Initial Admin Setup | ScanPro AI Control Center</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #0B0B0F;
            color: #F8FAFC;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 16px;
            background-image:
                radial-gradient(circle at 15% 15%, rgba(34, 211, 238, 0.10), transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(22, 199, 181, 0.08), transparent 40%);
        }
        .card {
            width: 100%;
            max-width: 480px;
            background: #17171D;
            border: 1px solid rgba(34, 211, 238, 0.24);
            border-radius: 18px;
            padding: 28px 22px;
            box-shadow: 0 24px 54px rgba(0, 0, 0, 0.65);
        }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; }
        .brand-icon {
            width: 42px; height: 42px; border-radius: 12px;
            background: linear-gradient(135deg, #22D3EE, #16C7B5);
            color: #061B32; display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem; flex-shrink: 0;
        }
        .brand-title { font-size: 1.08rem; font-weight: 800; color: #FFFFFF; }
        .brand-sub { font-size: 0.72rem; letter-spacing: 1px; text-transform: uppercase; color: #22D3EE; font-weight: 700; }
        h1 { font-size: 1.35rem; margin-bottom: 6px; }
        .lead { font-size: 0.88rem; color: #94A3B8; margin-bottom: 20px; line-height: 1.5; }
        .alert {
            padding: 12px 14px; border-radius: 10px; font-size: 0.86rem; margin-bottom: 16px;
            display: flex; align-items: flex-start; gap: 10px; line-height: 1.45;
        }
        .alert-error { background: rgba(239, 68, 68, 0.14); border: 1px solid rgba(239, 68, 68, 0.42); color: #FECACA; }
        .alert-info { background: rgba(34, 211, 238, 0.12); border: 1px solid rgba(34, 211, 238, 0.35); color: #CFFAFE; }
        .section-tag {
            font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 700;
            color: #16C7B5; margin: 16px 0 10px; padding-top: 12px; border-top: 1px solid rgba(255,255,255,0.08);
        }
        .form-group { margin-bottom: 14px; }
        label { display: block; font-size: 0.83rem; font-weight: 600; color: #E2E8F0; margin-bottom: 6px; }
        .input-wrap { position: relative; }
        input {
            width: 100%; padding: 12px 14px; border-radius: 10px; font-size: 16px;
            background: #0B0B0F; border: 1px solid rgba(255,255,255,0.12); color: #FFFFFF;
            outline: none; transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        input:focus { border-color: #22D3EE; box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.16); }
        .pwd-input { padding-right: 44px; }
        .toggle-btn {
            position: absolute; right: 8px; top: 50%; transform: translateY(-50%);
            background: transparent; border: none; color: #94A3B8; padding: 8px; cursor: pointer;
        }
        .toggle-btn:hover { color: #22D3EE; }
        .err-text { display: block; margin-top: 5px; font-size: 0.78rem; color: #FCA5A5; }
        .btn-submit {
            width: 100%; margin-top: 8px; padding: 13px 18px; border: none; border-radius: 11px;
            background: linear-gradient(135deg, #22D3EE, #16C7B5); color: #061B32;
            font-weight: 800; font-size: 0.95rem; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-submit:hover { opacity: 0.94; }
    </style>
</head>
<body>
<div class="card">
    <div class="brand">
        <div class="brand-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></div>
        <div>
            <div class="brand-title">ScanPro AI</div>
            <div class="brand-sub">Unified Control Center</div>
        </div>
    </div>

    <h1>One-Time Super Admin Setup</h1>
    <p class="lead">
        Create your initial administrator account. This setup page locks permanently as soon as the first admin is created.
    </p>

    <?php if (!$dbReady): ?>
        <div class="alert alert-error" role="alert">
            <i class="fa-solid fa-database" aria-hidden="true" style="margin-top:2px;"></i>
            <div>Please import <code>database/migrations/002_admin_control_center.sql</code> in phpMyAdmin first, then reload this page.</div>
        </div>
    <?php endif; ?>

    <?php if ($formMessage !== null): ?>
        <div class="alert alert-error" role="alert">
            <i class="fa-solid fa-circle-exclamation" aria-hidden="true" style="margin-top:2px;"></i>
            <div><?= e($formMessage) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($dbReady): ?>
    <form method="POST" autocomplete="off" novalidate>
        <?= adminCsrfInput() ?>

        <div class="alert alert-info" style="margin-bottom:14px;">
            <i class="fa-solid fa-key" aria-hidden="true" style="margin-top:2px;"></i>
            <div>Ownership check: Enter your configured MySQL <strong>Database Name</strong> and <strong>Database Username</strong> from <code>backend/config/database.php</code>.</div>
        </div>

        <div class="form-group">
            <label for="dbNameVerify">Server Database Name</label>
            <input type="text" id="dbNameVerify" name="db_name_verify" required placeholder="Your MySQL database name">
        </div>

        <div class="form-group">
            <label for="dbUserVerify">Server Database Username</label>
            <input type="text" id="dbUserVerify" name="db_user_verify" required placeholder="Your MySQL username">
            <?php if (!empty($errors['db_verify'])): ?>
                <span class="err-text"><?= e($errors['db_verify']) ?></span>
            <?php endif; ?>
        </div>

        <div class="section-tag">Super Admin Account Details</div>

        <div class="form-group">
            <label for="setupUsername">Admin Username</label>
            <input type="text" id="setupUsername" name="username" value="<?= e($username) ?>" required placeholder="e.g. asif_admin">
            <?php if (!empty($errors['username'])): ?>
                <span class="err-text"><?= e($errors['username']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="setupEmail">Admin Email</label>
            <input type="email" id="setupEmail" name="email" value="<?= e($email) ?>" required placeholder="admin@example.com">
            <?php if (!empty($errors['email'])): ?>
                <span class="err-text"><?= e($errors['email']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="setupFullName">Full Name</label>
            <input type="text" id="setupFullName" name="full_name" value="<?= e($fullName) ?>" required placeholder="Your full name">
            <?php if (!empty($errors['full_name'])): ?>
                <span class="err-text"><?= e($errors['full_name']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="setupPassword">Password (min <?= (int) ADMIN_PASSWORD_MIN_LENGTH ?> chars, letters + numbers)</label>
            <div class="input-wrap">
                <input type="password" id="setupPassword" name="password" class="pwd-input" required minlength="<?= (int) ADMIN_PASSWORD_MIN_LENGTH ?>">
                <button type="button" class="toggle-btn" data-toggle-pwd="setupPassword" aria-label="Show password">
                    <i class="fa-regular fa-eye" aria-hidden="true"></i>
                </button>
            </div>
            <?php if (!empty($errors['password'])): ?>
                <span class="err-text"><?= e($errors['password']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="setupConfirm">Confirm Password</label>
            <div class="input-wrap">
                <input type="password" id="setupConfirm" name="confirm_password" class="pwd-input" required minlength="<?= (int) ADMIN_PASSWORD_MIN_LENGTH ?>">
                <button type="button" class="toggle-btn" data-toggle-pwd="setupConfirm" aria-label="Show password">
                    <i class="fa-regular fa-eye" aria-hidden="true"></i>
                </button>
            </div>
            <?php if (!empty($errors['confirm_password'])): ?>
                <span class="err-text"><?= e($errors['confirm_password']) ?></span>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn-submit">
            <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
            <span>Create Super Admin &amp; Lock Setup</span>
        </button>
    </form>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('[data-toggle-pwd]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(btn.getAttribute('data-toggle-pwd'));
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        var icon = btn.querySelector('i');
        if (icon) icon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    });
});
</script>
</body>
</html>