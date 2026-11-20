<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/helpers/security.php';

startSecureSession();
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php', true, 302);
    exit;
}

$pageTitle = 'Create Account';
$pageDescription = 'Register a free ScanPro AI account to manage your profile and access your document dashboard.';
$activePage = 'register';
$extraStyles = ['assets/css/google-auth.css'];
$extraScripts = ['assets/js/auth.js'];

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-wrapper cyber-grid-bg">
    <div class="auth-card">
        <div class="auth-header">
            <span class="eyebrow">FREE ACCOUNT</span>
            <h1>Create Your Account</h1>
            <p>Start on the Free plan with 5 GB vault tier configuration.</p>
        </div>

        <?php require __DIR__ . '/includes/alerts.php'; ?>
        <div id="authFormAlerts" aria-live="polite"></div>

        <form id="registerForm" action="../backend/api/register.php" method="POST" novalidate>
            <?= csrfHiddenInput() ?>

            <div class="form-group">
                <label for="registerName" class="form-label">Full Name</label>
                <input
                    type="text"
                    id="registerName"
                    name="full_name"
                    class="form-control"
                    placeholder="Alex Morgan"
                    required
                    minlength="2"
                    maxlength="100"
                    autocomplete="name"
                >
                <span class="form-error-text" id="registerNameError"></span>
            </div>

            <div class="form-group">
                <label for="registerEmail" class="form-label">Email Address</label>
                <input
                    type="email"
                    id="registerEmail"
                    name="email"
                    class="form-control"
                    placeholder="you@example.com"
                    required
                    maxlength="190"
                    autocomplete="email"
                >
                <span class="form-error-text" id="registerEmailError"></span>
            </div>

            <div class="form-group">
                <label for="registerPassword" class="form-label">Password</label>
                <div class="input-wrap">
                    <input
                        type="password"
                        id="registerPassword"
                        name="password"
                        class="form-control form-control--with-icon"
                        placeholder="Minimum <?= (int) PASSWORD_MIN_LENGTH ?> characters"
                        required
                        minlength="<?= (int) PASSWORD_MIN_LENGTH ?>"
                        autocomplete="new-password"
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        aria-label="Show password"
                    >
                        <i class="fa-regular fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <span class="form-error-text" id="registerPasswordError"></span>
            </div>

            <div class="form-group">
                <label for="registerConfirmPassword" class="form-label">Confirm Password</label>
                <div class="input-wrap">
                    <input
                        type="password"
                        id="registerConfirmPassword"
                        name="confirm_password"
                        class="form-control form-control--with-icon"
                        placeholder="Re-enter your password"
                        required
                        minlength="<?= (int) PASSWORD_MIN_LENGTH ?>"
                        autocomplete="new-password"
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        aria-label="Show password"
                    >
                        <i class="fa-regular fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <span class="form-error-text" id="registerConfirmPasswordError"></span>
            </div>

            <button type="submit" id="registerSubmitBtn" class="btn btn-primary btn-block btn-lg">
                <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                <span>Create Free Account</span>
            </button>
        </form>

        <?php require __DIR__ . '/includes/google-auth-block.php'; ?>

        <p class="text-center text-muted mt-3" style="font-size:0.9rem;">
            Already have an account?
            <a href="login.php" class="form-link">Sign in</a>
        </p>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>