<?php
require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/helpers/security.php';
require_once __DIR__ . '/includes/live-3d-scenes.php';

$pageTitle = 'About';
$pageDescription = 'Learn about ScanPro AI, our mission, vision, privacy-first architecture, and mobile-to-web document suite.';
$activePage = 'about';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <div class="container" style="padding-top: 16px;">
        <?php require __DIR__ . '/includes/alerts.php'; ?>
    </div>

    <section class="page-hero cyber-grid-bg">
        <div class="container">
            <span class="eyebrow">ABOUT SCANPRO AI</span>
            <h1>Reimagining How You <span class="text-gradient">Capture, Understand &amp; Secure</span> Documents</h1>
            <p>
                ScanPro AI bridges mobile computer vision, Google Gemini AI, and multi-format document tools into one cohesive platform.
            </p>
        </div>
    </section>

    <!-- Introduction -->
    <section class="section">
        <div class="container split-grid">
            <div>
                <span class="eyebrow">PRODUCT STORY</span>
                <h2>Built for Real-World <span class="text-gradient">Productivity</span></h2>
                <p class="text-muted mt-2">
                    Most scanning utilities only take a photo and stop there. ScanPro AI was created to eliminate the friction between paper records and structured digital work—combining edge detection, 11 optical filters, Gemini AI text/table understanding, and clean PDF organization in a single workspace.
                </p>
                <p class="text-muted mt-2">
                    Whether you are digitizing receipts, parsing exam worksheets, preparing passport print sheets, or reviewing contracts, ScanPro AI prioritizes speed, clarity, and strict security boundaries.
                </p>
            </div>

            <div>
                <?php renderLive3DScene('hero-scanner'); ?>
            </div>
        </div>
    </section>

    <!-- Mission & Vision -->
    <section class="section section--deep">
        <div class="container grid grid-2">
            <article class="card card--tool">
                <div class="card-icon card-icon--cyan">
                    <i class="fa-solid fa-bullseye" aria-hidden="true"></i>
                </div>
                <h3>Our Mission</h3>
                <p>
                    To make intelligent document capture, OCR extraction, and multi-format PDF organization fast, accessible, and privacy-respecting for students, professionals, and teams everywhere.
                </p>
            </article>

            <article class="card card--tool">
                <div class="card-icon card-icon--purple">
                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                </div>
                <h3>Our Vision</h3>
                <p>
                    A unified mobile and web document ecosystem where physical paperwork seamlessly becomes structured, searchable, encrypted knowledge—ready whenever and wherever you work.
                </p>
            </article>
        </div>
    </section>

    <!-- Technology & Architecture Info -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">PLATFORM ARCHITECTURE</span>
                <h2>Modern Engineering Across <span class="text-gradient">Mobile &amp; Web</span></h2>
                <p class="section-subtitle">
                    Clean separation between presentation, security, and persistent storage ensures long-term maintainability.
                </p>
            </div>

            <div class="grid grid-3">
                <article class="card">
                    <div class="card-icon card-icon--green">
                        <i class="fa-brands fa-android" aria-hidden="true"></i>
                    </div>
                    <h3>Android Native Suite</h3>
                    <p>Kotlin, Jetpack Compose, CameraX, Apache PDFBox, ML Kit barcode scanning, and Room SQLite offline vault.</p>
                </article>

                <article class="card">
                    <div class="card-icon card-icon--cyan">
                        <i class="fa-solid fa-server" aria-hidden="true"></i>
                    </div>
                    <h3>Web Platform Stack</h3>
                    <p>HTML5, CSS3, Vanilla ES6+ JavaScript, PHP 8+ REST architecture, PDO prepared statements, and MySQL storage.</p>
                </article>

                <article class="card">
                    <div class="card-icon card-icon--amber">
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    </div>
                    <h3>Security Principles</h3>
                    <p>Fail-closed authentication, password hashing, CSRF protection, strict output escaping, and zero silent failures.</p>
                </article>
            </div>

            <div class="text-center mt-4" style="display:flex;justify-content:center;flex-wrap:wrap;gap:12px;">
                <a href="contact.php" class="btn btn-primary">Contact Our Team</a>
                <a href="features.php" class="btn btn-outline">Explore All Features</a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>