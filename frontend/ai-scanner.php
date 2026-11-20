<?php
require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/helpers/security.php';
require_once __DIR__ . '/includes/live-3d-scenes.php';

$pageTitle = 'AI Scanner';
$pageDescription = 'Discover ScanPro AI smart camera scanning, 11 image enhancement filters, Gemini AI OCR extraction, document editing, and download the Android APK.';
$activePage = 'ai-scanner';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <div class="container" style="padding-top: 16px;">
        <?php require __DIR__ . '/includes/alerts.php'; ?>
    </div>

    <section class="page-hero cyber-grid-bg">
        <div class="container">
            <span class="eyebrow">SMART CAMERA &amp; AI ENGINE</span>
            <h1>Intelligent Scanning Meets <span class="text-gradient">Gemini AI Precision</span></h1>
            <p>
                Capture physical documents with automatic perspective alignment, clean shadows in real time, and convert images into structured digital text.
            </p>
            <div class="hero-actions" style="justify-content:center;">
                <a href="workspace.php" class="btn btn-primary">
                    <i class="fa-solid fa-pen-ruler" aria-hidden="true"></i>
                    <span>Open Web Workspace</span>
                </a>
                <a href="pdf-tools.php" class="btn btn-secondary">Explore PDF Tools</a>
                <a href="#mobile-app" class="btn btn-outline">
                    <i class="fa-brands fa-android" aria-hidden="true"></i>
                    <span>Download Android APK</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Document Scanning Overview -->
    <section class="section" id="scan-modes">
        <div class="container split-grid">
            <div>
                <span class="eyebrow">DOCUMENT SCANNING</span>
                <h2>Single &amp; Batch Capture with <span class="text-gradient">Live Edge Detection</span></h2>
                <p class="text-muted mt-2">
                    Powered by CameraX and custom luminance edge detection, the scanner detects document corners automatically, applies 4-point perspective warping, and supports up to 12MP capture resolution.
                </p>

                <ul class="check-list">
                    <li>
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span><strong>Single &amp; Batch Modes:</strong> Capture one-off receipts or scan multi-page contracts continuously.</span>
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span><strong>ID Cards &amp; Passports:</strong> Guided front-and-back alignment onto a single printable A4 layout.</span>
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span><strong>Books &amp; Whiteboards:</strong> Flattens spine curvature and minimizes glare and thumb shadows.</span>
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span><strong>ID Photo Maker, Timestamp &amp; QR:</strong> Standard passport sheets, verified time/GPS stamps, and ML Kit barcode scanning.</span>
                    </li>
                </ul>
            </div>

            <div>
                <?php renderLive3DScene('phone-scanner'); ?>
            </div>
        </div>
    </section>

    <!-- Image Enhancement Overview -->
    <section class="section section--deep" id="enhancement">
        <div class="container split-grid">
            <div>
                <?php renderLive3DScene('ai-enhancement'); ?>
            </div>

            <div>
                <span class="eyebrow eyebrow--purple">IMAGE ENHANCEMENT</span>
                <h2>11 Studio Filters for <span class="text-gradient-ai">Crystal-Clear Legibility</span></h2>
                <p class="text-muted mt-2">
                    Clean up uneven lighting, faded ink, shadows, and unwanted marks immediately after capture without altering your original image non-destructively. Tap the filter chips on the 3D preview to test live.
                </p>

                <div class="grid grid-2 mt-3" style="gap:12px;">
                    <div class="card" style="padding:14px;">
                        <h4 style="font-size:0.95rem;">Magic Pro &amp; Enhance</h4>
                        <p style="font-size:0.84rem;margin-top:4px;">Boosts contrast and normalizes paper whites for crisp readability.</p>
                    </div>
                    <div class="card" style="padding:14px;">
                        <h4 style="font-size:0.95rem;">No Shadow &amp; Lighten</h4>
                        <p style="font-size:0.84rem;margin-top:4px;">Smooths uneven desk shadows and brightens dim captures.</p>
                    </div>
                    <div class="card" style="padding:14px;">
                        <h4 style="font-size:0.95rem;">No Handwriting / Watermark</h4>
                        <p style="font-size:0.84rem;margin-top:4px;">Separates printed black text from light background marks or pen ink.</p>
                    </div>
                    <div class="card" style="padding:14px;">
                        <h4 style="font-size:0.95rem;">B&amp;W Clean &amp; Eco</h4>
                        <p style="font-size:0.84rem;margin-top:4px;">Sharp monochrome output and toner-friendly eco mode for printing.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- OCR & AI Intelligence Introduction -->
    <section class="section" id="ocr-overview">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow eyebrow--purple">AI OCR &amp; ASSISTANT</span>
                <h2>Extract Text, Tables &amp; Insights <span class="text-gradient-ai">With Gemini AI</span></h2>
                <p class="section-subtitle">
                    Go beyond static images with AI tools that understand structure, language, and context.
                </p>
            </div>

            <div class="grid grid-4">
                <article class="card card--tool">
                    <div class="card-icon card-icon--purple">
                        <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                    </div>
                    <h3>Accurate OCR</h3>
                    <p>Convert scans into editable text preserving line breaks and multi-paragraph structure.</p>
                </article>

                <article class="card card--tool">
                    <div class="card-icon card-icon--green">
                        <i class="fa-solid fa-table" aria-hidden="true"></i>
                    </div>
                    <h3>Table-to-CSV Parser</h3>
                    <p>Extract invoice line items and ledger tables into structured spreadsheet-ready CSV rows.</p>
                </article>

                <article class="card card--tool">
                    <div class="card-icon card-icon--cyan">
                        <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                    </div>
                    <h3>Question Set Extractor</h3>
                    <p>Parse exam sheets and worksheets into clean numbered questions and multiple-choice options.</p>
                </article>

                <article class="card card--tool">
                    <div class="card-icon card-icon--amber">
                        <i class="fa-solid fa-robot" aria-hidden="true"></i>
                    </div>
                    <h3>Document Q&amp;A</h3>
                    <p>Ask natural questions, summarize long agreements, and translate text into 50+ languages.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- Document Editing Overview -->
    <section class="section section--deep" id="editing-overview">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">POST-CAPTURE STUDIO</span>
                <h2>Non-Destructive <span class="text-gradient">Document Editor &amp; Exporter</span></h2>
                <p class="section-subtitle">
                    Fine-tune every page before saving or sharing.
                </p>
            </div>

            <div class="grid grid-3">
                <article class="card">
                    <div class="card-icon card-icon--cyan">
                        <i class="fa-solid fa-crop-simple" aria-hidden="true"></i>
                    </div>
                    <h3>Precision Crop &amp; Rotate</h3>
                    <p>Adjust all 4 corners and mid-edge handles freely, rotate 90° left/right, or trigger Auto Crop anytime.</p>
                </article>

                <article class="card">
                    <div class="card-icon card-icon--teal">
                        <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                    </div>
                    <h3>Live Brightness &amp; Contrast</h3>
                    <p>Real-time GPU sliders let you dial in brightness, contrast, and saturation with instant Before/After Compare.</p>
                </article>

                <article class="card">
                    <div class="card-icon card-icon--blue">
                        <i class="fa-solid fa-file-export" aria-hidden="true"></i>
                    </div>
                    <h3>Flexible Page Export</h3>
                    <p>Manage multi-page order and export as PDF (A4, Letter, Original), JPG, or PNG with quality control and print support.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- Mobile App Promotion & Download Section -->
    <section class="section" id="mobile-app">
        <div class="container split-grid">
            <div>
                <?php renderLive3DScene('scanner-ready'); ?>
            </div>

            <div>
                <span class="eyebrow">DOWNLOAD APPLICATION</span>
                <h2>Download ScanPro AI for <span class="text-gradient">Android</span></h2>
                <p class="text-muted mt-2">
                    Compatible with <?= e(ANDROID_MIN_OS) ?>. Built natively in Kotlin with Jetpack Compose, CameraX, Apache PDFBox, and Room offline persistence.
                </p>

                <div class="card mt-3" style="padding:18px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                        <div>
                            <strong style="color:var(--text-white);">ScanPro AI Android APK (v<?= e(ANDROID_APP_VERSION) ?>)</strong>
                            <p style="font-size:0.85rem;margin-top:4px;">Package: <span class="text-mono">com.aistudio.scanproai.app</span> • <?= e(ANDROID_MIN_OS) ?></p>
                        </div>
                        <span class="card-badge card-badge--cyan">Direct APK Ready</span>
                    </div>
                    <p class="text-muted mt-2" style="font-size:0.82rem;">
                        <i class="fa-solid fa-circle-info" aria-hidden="true" style="color:var(--accent-cyan);"></i>
                        After downloading the <code>.apk</code> file, tap it to install and allow <em>"Install unknown apps"</em> for your browser if Android asks.
                    </p>
                </div>

                <div class="hero-actions mt-3">
                    <a
                        href="<?= e(ANDROID_APK_URL) ?>"
                        class="btn btn-primary btn-lg"
                        target="_blank"
                        rel="noopener noreferrer"
                        download
                    >
                        <i class="fa-solid fa-download" aria-hidden="true"></i>
                        <span>Download Android APK (v<?= e(ANDROID_APP_VERSION) ?>)</span>
                    </a>
                    <a href="register.php" class="btn btn-secondary">Create Web Vault Account</a>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>