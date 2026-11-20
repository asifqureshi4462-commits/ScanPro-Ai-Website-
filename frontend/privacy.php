<?php
require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/helpers/security.php';

$pageTitle = 'Privacy Policy';
$pageDescription = 'How ScanPro AI handles your files, account data, cookies and advertising.';
$activePage = 'privacy';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="page-hero cyber-grid-bg">
        <div class="container">
            <span class="eyebrow">PRIVACY</span>
            <h1>Privacy <span class="text-gradient">Policy</span></h1>
            <p>Last updated: 20 November 2026. Plain-language summary of what ScanPro AI does with your data.</p>
        </div>
    </section>

    <section class="section">
        <div class="container" style="max-width:820px;">
            <div class="card" style="padding:28px;">
                <h2>Your files</h2>
                <p>PDF tools and the Web Workspace process your files <strong>inside your own browser</strong>. Your documents are not uploaded to our servers. The text extraction (OCR) feature downloads its language data from a public CDN, but your document itself stays on your device.</p>

                <h2 style="margin-top:24px;">Account data</h2>
                <p>If you create an account we store your name, email address and a password hash (never the password itself). If you choose &ldquo;Continue with Google&rdquo;, Google (through Firebase Authentication) gives us your name, email address and profile photo. We do not request access to your Google Drive when you sign in.</p>

                <h2 style="margin-top:24px;">Messages you send us</h2>
                <p>Messages sent through the contact form are saved in our database and emailed to us so that we can reply.</p>

                <h2 style="margin-top:24px;">Cookies and security</h2>
                <p>We use a secure session cookie to keep you signed in and security tokens to protect forms. To limit password guessing we store a one-way hash of your IP address together with failed sign-in attempts for a short time.</p>

                <h2 style="margin-top:24px;">Advertising</h2>
                <p>Visitors who are not signed in may see one advertisement on some public pages (home, features, PDF tools and the PDF tool pages). Ads are never shown on sign-in, registration, account, pricing or contact pages. Our advertising partner may use cookies or similar technologies to deliver and measure ads. This partner does not receive your documents. You can block or limit this through your browser settings, an ad blocker, or by creating a free account (signed-in users do not see ads).</p>

                <h2 style="margin-top:24px;">Third-party services</h2>
                <p>The website loads open-source libraries from public CDNs (for example jsDelivr and Google/Firebase) and uses Google for optional sign-in. These services may receive standard technical information such as your IP address when your browser requests their files.</p>

                <h2 style="margin-top:24px;">Your choices</h2>
                <p>You can ask us to access, correct or delete your account data at any time by emailing <a href="mailto:<?= e(APP_SUPPORT_EMAIL) ?>" class="form-link"><?= e(APP_SUPPORT_EMAIL) ?></a>.</p>

                <h2 style="margin-top:24px;">Changes</h2>
                <p>If we change how we handle data, we will update this page and the date above.</p>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
