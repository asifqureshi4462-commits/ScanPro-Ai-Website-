<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/helpers/security.php';

startSecureSession();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php', true, 302);
    exit;
}

$pageTitle = 'Forgot Password';
$pageDescription = 'Request a secure password reset token for your ScanPro AI account.';
$activePage = 'login';
$extraScripts = ['assets/js/auth.js'];

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-wrapper cyber-grid-bg">
    <div class="auth-card">
        <div class="auth-header">
            <span class="eyebrow">ACCOUNT RECOVERY</span>
            <h1>Reset Your Password</h1>
            <p>Enter your account email to generate a time-limited reset token.</p>
        </div>

        <?php if (!MAIL_DELIVERY_ENABLED): ?>
            <div class="alert alert--warning" role="status">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true" style="margin-top:2px;"></i>
                <div class="alert-body">
                    <strong>SMTP Mail Not Configured:</strong> Reset tokens are generated and stored securely in MySQL, but outbound email delivery is disabled on this local server until SMTP is configured.
                </div>
            </div>
        <?php endif; ?>

        <?php require __DIR__ . '/includes/alerts.php'; ?>
        <div id="authFormAlerts" aria-live="polite"></div>

        <form id="forgotPasswordForm" action="../backend/api/forgot_password.php" method="POST" novalidate>
            <?= csrfHiddenInput() ?>

            <div class="form-group">
                <label for="forgotEmail" class="form-label">Registered Email Address</label>
                <input
                    type="email"
                    id="forgotEmail"
                    name="email"
                    class="form-control"
                    placeholder="you@example.com"
                    required
                    maxlength="190"
                    autocomplete="email"
                >
                <span class="form-error-text" id="forgotEmailError"></span>
            </div>

            <button type="submit" id="forgotSubmitBtn" class="btn btn-primary btn-block btn-lg">
                <i class="fa-solid fa-key" aria-hidden="true"></i>
                <span>Request Reset Token</span>
            </button>
        </form>

        <p class="text-center text-muted mt-3" style="font-size:0.9rem;">
            Remembered your password?
            <a href="login.php" class="form-link">Back to Sign In</a>
        </p>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>