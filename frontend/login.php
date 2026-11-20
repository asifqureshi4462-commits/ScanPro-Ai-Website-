<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/helpers/security.php';

startSecureSession();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php', true, 302);
    exit;
}

$pageTitle = 'Login';
$pageDescription = 'Sign in to your ScanPro AI account to access your profile, subscription status, and workspace settings.';
$activePage = 'login';
$extraStyles = ['assets/css/google-auth.css'];
$extraScripts = ['assets/js/auth.js'];

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-wrapper cyber-grid-bg">
    <div class="auth-card">
        <div class="auth-header">
            <span class="eyebrow">SECURE SIGN IN</span>
            <h1>Welcome Back</h1>
            <p>Sign in to access your <?= e(APP_NAME) ?> dashboard.</p>
        </div>

        <?php require __DIR__ . '/includes/alerts.php'; ?>
        <div id="authFormAlerts" aria-live="polite"></div>

        <form id="loginForm" action="../backend/api/login.php" method="POST" novalidate>
            <?= csrfHiddenInput() ?>

            <div class="form-group">
                <label for="loginEmail" class="form-label">Email Address</label>
                <input
                    type="email"
                    id="loginEmail"
                    name="email"
                    class="form-control"
                    placeholder="you@example.com"
                    required
                    maxlength="190"
                    autocomplete="email"
                >
                <span class="form-error-text" id="loginEmailError"></span>
            </div>

            <div class="form-group">
                <label for="loginPassword" class="form-label">Password</label>
                <div class="input-wrap">
                    <input
                        type="password"
                        id="loginPassword"
                        name="password"
                        class="form-control form-control--with-icon"
                        placeholder="Enter your password"
                        required
                        autocomplete="current-password"
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        aria-label="Show password"
                    >
                        <i class="fa-regular fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <span class="form-error-text" id="loginPasswordError"></span>
            </div>

            <div class="form-row-between">
                <label class="checkbox-label">
                    <input type="checkbox" id="rememberMe" name="remember_me" value="1">
                    <span>Remember me for <?= (int) REMEMBER_ME_DAYS ?> days</span>
                </label>

                <a href="forgot-password.php" class="form-link">Forgot password?</a>
            </div>

            <button type="submit" id="loginSubmitBtn" class="btn btn-primary btn-block btn-lg">
                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
                <span>Sign In</span>
            </button>
        </form>

        <?php require __DIR__ . '/includes/google-auth-block.php'; ?>

        <p class="text-center text-muted mt-3" style="font-size:0.9rem;">
            Don't have an account?
            <a href="register.php" class="form-link">Create one free</a>
        </p>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>