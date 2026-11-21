<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/helpers/security.php';
require_once __DIR__ . '/../backend/helpers/site_runtime.php';

$pageTitle = 'Contact';
$pageDescription = 'Contact the ScanPro AI team for product questions, Android app early access, or technical support.';
$activePage = 'contact';
$extraScripts = ['assets/js/contact.js'];

startSecureSession();

$prefillName = (string) ($_SESSION['user_name'] ?? '');
$prefillEmail = (string) ($_SESSION['user_email'] ?? '');
$prefillSubject = isset($_GET['subject']) && is_string($_GET['subject'])
    ? trim(substr($_GET['subject'], 0, 150))
    : '';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';

$supportEmail = getSiteSetting('contact_email', APP_SUPPORT_EMAIL);
?>

<main>
    <div class="container" style="padding-top: 16px;">
        <?php require __DIR__ . '/includes/alerts.php'; ?>
    </div>

    <section class="page-hero cyber-grid-bg">
        <div class="container">
            <span class="eyebrow">CONTACT &amp; SUPPORT</span>
            <h1>Get in Touch With <span class="text-gradient">ScanPro AI</span></h1>
            <p>
                Have a question about features, Android app availability, or upcoming plan subscriptions? Send us a message directly.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container split-grid" style="align-items:start;">
            <div style="display:flex;flex-direction:column;gap:18px;">
                <article class="card card--tool">
                    <div class="card-icon card-icon--cyan">
                        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                    </div>
                    <h3>Direct Support Email</h3>
                    <p>Reach our support inbox for account assistance or technical inquiries.</p>
                    <a href="mailto:<?= e($supportEmail) ?>" class="card-link">
                        <span><?= e($supportEmail) ?></span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>

                <article class="card card--tool">
                    <div class="card-icon card-icon--purple">
                        <i class="fa-brands fa-android" aria-hidden="true"></i>
                    </div>
                    <h3>Android App Inquiries</h3>
                    <p>Ask about Android APK access, Gemini AI API setup, or device compatibility (Android 7.0+ / SDK 24+).</p>
                    <a href="ai-scanner.php#mobile-app" class="card-link">
                        <span>View Android Overview</span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>

                <article class="card card--tool">
                    <div class="card-icon card-icon--green">
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    </div>
                    <h3>Secure Message Handling</h3>
                    <p>All submissions are validated server-side with CSRF protection and stored securely in our MySQL database.</p>
                </article>
            </div>

            <div class="card" style="padding:30px;">
                <h2>Send a Message</h2>
                <p class="text-muted mt-1 mb-3" style="font-size:0.92rem;">
                    Fill out the form below and our team will review your inquiry.
                </p>

                <div id="contactFormAlerts" aria-live="polite"></div>

                <form id="contactForm" action="../backend/api/contact.php" method="POST" novalidate>
                    <?= csrfHiddenInput() ?>

                    <div class="form-group">
                        <label for="contactName" class="form-label">Full Name</label>
                        <input
                            type="text"
                            id="contactName"
                            name="name"
                            class="form-control"
                            placeholder="Enter your full name"
                            value="<?= e($prefillName) ?>"
                            required
                            minlength="2"
                            maxlength="100"
                            autocomplete="name"
                        >
                        <span class="form-error-text" id="contactNameError"></span>
                    </div>

                    <div class="form-group">
                        <label for="contactEmail" class="form-label">Email Address</label>
                        <input
                            type="email"
                            id="contactEmail"
                            name="email"
                            class="form-control"
                            placeholder="you@example.com"
                            value="<?= e($prefillEmail) ?>"
                            required
                            maxlength="190"
                            autocomplete="email"
                        >
                        <span class="form-error-text" id="contactEmailError"></span>
                    </div>

                    <div class="form-group">
                        <label for="contactSubject" class="form-label">Subject</label>
                        <input
                            type="text"
                            id="contactSubject"
                            name="subject"
                            class="form-control"
                            placeholder="How can we help?"
                            value="<?= e($prefillSubject) ?>"
                            required
                            minlength="3"
                            maxlength="150"
                        >
                        <span class="form-error-text" id="contactSubjectError"></span>
                    </div>

                    <div class="form-group">
                        <label for="contactMessage" class="form-label">Message</label>
                        <textarea
                            id="contactMessage"
                            name="message"
                            class="form-control"
                            placeholder="Write your message here (minimum 10 characters)..."
                            required
                            minlength="10"
                            maxlength="3000"
                        ></textarea>
                        <span class="form-error-text" id="contactMessageError"></span>
                    </div>

                    <button type="submit" id="contactSubmitBtn" class="btn btn-primary btn-block btn-lg">
                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                        <span>Send Message</span>
                    </button>
                </form>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>