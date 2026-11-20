<?php
require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/helpers/security.php';
require_once __DIR__ . '/includes/live-3d-scenes.php';

$pageTitle = 'AI Document Scanner & PDF Suite';
$pageDescription = 'ScanPro AI is an all-in-one AI document scanner, OCR engine, PDF studio, and secure cloud vault for mobile and web productivity.';
$activePage = 'home';

$homeTopTools = [
    [
        'title' => 'Merge PDF',
        'desc'  => 'Combine multiple PDF files, reorder them in your preferred sequence, and download one PDF.',
        'icon'  => 'fa-object-group',
        'tone'  => 'pink',
        'href'  => 'tools/merge-pdf.php',
    ],
    [
        'title' => 'Split PDF',
        'desc'  => 'Split a PDF by custom page ranges or intervals and download individual files or a ZIP.',
        'icon'  => 'fa-scissors',
        'tone'  => 'cyan',
        'href'  => 'tools/split-pdf.php',
    ],
    [
        'title' => 'Compress PDF',
        'desc'  => 'Optimize PDF file size in your browser and inspect exact original vs resulting sizes.',
        'icon'  => 'fa-minimize',
        'tone'  => 'teal',
        'href'  => 'tools/compress-pdf.php',
    ],
    [
        'title' => 'PDF to JPG',
        'desc'  => 'Preview PDF pages, pick the pages you need, adjust quality, and export JPGs or ZIP.',
        'icon'  => 'fa-file-image',
        'tone'  => 'purple',
        'href'  => 'tools/pdf-to-jpg.php',
    ],
    [
        'title' => 'JPG to PDF',
        'desc'  => 'Upload JPG or PNG images, arrange page order, set layout size, and create a PDF.',
        'icon'  => 'fa-images',
        'tone'  => 'blue',
        'href'  => 'tools/jpg-to-pdf.php',
    ],
];

$fallbackPlans = [
    [
        'slug'          => 'free',
        'name'          => 'Free',
        'tagline'       => 'Essential scanning & local vault tools',
        'price_monthly' => '0.00',
        'price_yearly'  => '0.00',
        'storage_label' => '5 GB Vault Storage',
        'is_featured'   => 0,
        'features_json' => json_encode([
            'Smart Document Camera & Edge Crop',
            '11 Real-Time Enhancement Filters',
            'Basic PDF Viewing & Organization',
            'Offline Local Document Vault',
        ]),
    ],
    [
        'slug'          => 'pro',
        'name'          => 'Pro',
        'tagline'       => 'AI OCR, PDF studio & cloud synchronization',
        'price_monthly' => '9.99',
        'price_yearly'  => '95.88',
        'storage_label' => '100 GB Cloud Vault',
        'is_featured'   => 1,
        'features_json' => json_encode([
            'Gemini AI OCR & Document Summarization',
            'Multi-Language Translation (50+ Languages)',
            'Full PDF Studio Showcase & Priority Access',
            'AES-128 Encrypted Document Protection',
        ]),
    ],
    [
        'slug'          => 'ultra',
        'name'          => 'Ultra',
        'tagline'       => 'Unlimited AI workflows for power teams',
        'price_monthly' => '19.99',
        'price_yearly'  => '191.88',
        'storage_label' => '1 TB Cloud Vault',
        'is_featured'   => 0,
        'features_json' => json_encode([
            'Unlimited AI Table & Question Set Parsing',
            'Multi-Format Office Suite Sync',
            'Priority Cloud Backup & Device Sync',
            'Dedicated Priority Support Channel',
        ]),
    ],
];

$plans = $fallbackPlans;
try {
    $pdo = getDbConnection();
    $stmt = $pdo->query('SELECT slug, name, tagline, price_monthly, price_yearly, storage_label, is_featured, features_json FROM plans WHERE is_active = 1 ORDER BY sort_order ASC, id ASC LIMIT 3');
    $dbPlans = $stmt->fetchAll();
    if (!empty($dbPlans)) {
        $plans = $dbPlans;
    }
} catch (Throwable $e) {
    // Fallback presentation plans remain active until MySQL schema is imported
}

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';

$isLoggedIn = !empty($_SESSION['user_id']);
?>

<main>
    <div class="container" style="padding-top: 16px;">
        <?php require __DIR__ . '/includes/alerts.php'; ?>
    </div>

    <!-- Hero Section -->
    <section class="hero cyber-grid-bg">
        <div class="container hero-grid">
            <div class="hero-content">
                <span class="eyebrow">
                    <i class="fa-solid fa-sparkles" aria-hidden="true"></i>
                    <span>AI-POWERED DOCUMENT WORKSPACE</span>
                </span>
                <h1>Scan Smarter.<br><span class="text-gradient">Work Faster</span> with ScanPro AI.</h1>
                <p class="hero-desc">
                    Transform paper documents, receipts, ID cards, and books into crystal-clear digital files. Powered by intelligent edge detection, Gemini AI document analysis, and an organized PDF &amp; Office ecosystem.
                </p>

                <div class="hero-actions">
                    <?php if ($isLoggedIn): ?>
                        <a href="dashboard.php" class="btn btn-primary btn-lg">
                            <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
                            <span>Open Dashboard</span>
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary btn-lg">
                            <span>Get Started Free</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    <?php endif; ?>
                    <a href="pdf-tools.php" class="btn btn-secondary btn-lg">
                        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                        <span>All PDF Tools</span>
                    </a>
                    <a href="workspace.php" class="btn btn-outline btn-lg">
                        <i class="fa-solid fa-pen-ruler" aria-hidden="true"></i>
                        <span>Web Workspace</span>
                    </a>
                </div>

                <div class="hero-stats">
                    <div class="hero-stat">
                        <strong>10 Tools</strong>
                        <span>Browser PDF Studio</span>
                    </div>
                    <div class="hero-stat">
                        <strong>50+ Languages</strong>
                        <span>AI OCR &amp; Translation</span>
                    </div>
                    <div class="hero-stat">
                        <strong>100% Private</strong>
                        <span>On-Device Processing</span>
                    </div>
                </div>
            </div>

            <div class="hero-visual">
                <?php renderLive3DScene('hero-scanner'); ?>
            </div>
        </div>
    </section>

    <!-- First 5 Popular Tools + View All Tools Card -->
    <section class="section" id="features-overview">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">TOP PDF TOOLS</span>
                <h2>Start With Our <span class="text-gradient">Most Popular Tools</span></h2>
                <p class="section-subtitle">
                    Tap any tool below to open its full-screen browser workspace immediately, or shift to the full Tools hub.
                </p>
            </div>

            <div class="grid grid-3">
                <?php foreach ($homeTopTools as $tool): ?>
                    <article class="card card--tool">
                        <div class="card-icon card-icon--<?= e($tool['tone']) ?>">
                            <i class="fa-solid <?= e($tool['icon']) ?>" aria-hidden="true"></i>
                        </div>
                        <h3><?= e($tool['title']) ?></h3>
                        <p><?= e($tool['desc']) ?></p>
                        <a href="<?= e($tool['href']) ?>" class="card-link">
                            <span>Open Tool</span>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </article>
                <?php endforeach; ?>

                <article class="card card--highlight card--tool">
                    <div class="card-icon card-icon--cyan">
                        <i class="fa-solid fa-table-cells" aria-hidden="true"></i>
                    </div>
                    <h3>All Tools &amp; Features</h3>
                    <p>View all 10 working PDF tools (Rotate, Watermark, Page Numbers, Extract, Reorder &amp; more) on the Tools page.</p>
                    <a href="pdf-tools.php" class="card-link">
                        <span>Explore All Tools</span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>
            </div>

            <div class="text-center mt-4" style="display:flex;justify-content:center;flex-wrap:wrap;gap:12px;">
                <a href="pdf-tools.php" class="btn btn-primary btn-lg">
                    <span>View All PDF Tools</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
                <a href="features.php" class="btn btn-secondary btn-lg">
                    <span>All Platform Features</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Why Choose ScanPro AI -->
    <section class="section section--deep">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow eyebrow--purple">WHY CHOOSE SCANPRO AI</span>
                <h2>Built for Speed, Clarity &amp; <span class="text-gradient-ai">Zero-Compromise Privacy</span></h2>
                <p class="section-subtitle">
                    Every workflow is engineered for real-world reliability without clutter or bloated steps.
                </p>
            </div>

            <div class="grid grid-4">
                <div class="card">
                    <div class="card-icon card-icon--teal">
                        <i class="fa-solid fa-microchip" aria-hidden="true"></i>
                    </div>
                    <h3>On-Device Precision</h3>
                    <p>Luminance edge detection and browser PDF processing run locally on your device for instant results.</p>
                </div>

                <div class="card">
                    <div class="card-icon card-icon--cyan">
                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    </div>
                    <h3>Fail-Closed Auth</h3>
                    <p>Strict session handling, hashed credentials, and zero silent authentication bypasses protect your account.</p>
                </div>

                <div class="card">
                    <div class="card-icon card-icon--purple">
                        <i class="fa-solid fa-wand-sparkles" aria-hidden="true"></i>
                    </div>
                    <h3>11 Smart Filters</h3>
                    <p>Remove shadows, reduce watermarks, sharpen ink, or apply Magic Pro enhancement in one tap.</p>
                </div>

                <div class="card">
                    <div class="card-icon card-icon--pink">
                        <i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i>
                    </div>
                    <h3>Unified Ecosystem</h3>
                    <p>Seamlessly connects your Android mobile scanning workflow with your web tools and account dashboard.</p>
                </div>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/includes/ad-slot.php'; renderAdSlot('home'); ?>

    <!-- App Introduction -->
    <section class="section">
        <div class="container split-grid">
            <div>
                <?php renderLive3DScene('phone-scanner'); ?>
            </div>

            <div>
                <span class="eyebrow">ANDROID COMPANION APP</span>
                <h2>Carry a Complete <span class="text-gradient">AI Document Studio</span> in Your Pocket</h2>
                <p class="text-muted mt-2">
                    Built natively in Kotlin and Jetpack Compose with CameraX, ScanPro AI gives you a fast, distraction-free scanner with Single &amp; Batch modes, 17+ specialized ribbon modes, and offline SQLite persistence.
                </p>

                <ul class="check-list">
                    <li>
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span>Live edge detection &amp; perspective warp with manual fine-tuning</span>
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span>Instant OCR text extraction, exam question parser &amp; table-to-CSV tools</span>
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span>ID Card front/back alignment, ID Photo print sheets &amp; timestamp stamps</span>
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span>Offline Room vault with optional Firebase cloud sync</span>
                    </li>
                </ul>

                <div class="hero-actions mt-3">
                    <a
                        href="<?= e(ANDROID_APK_URL) ?>"
                        class="btn btn-primary"
                        target="_blank"
                        rel="noopener noreferrer"
                        download
                    >
                        <i class="fa-brands fa-android" aria-hidden="true"></i>
                        <span>Download Android APK (v<?= e(ANDROID_APP_VERSION) ?>)</span>
                    </a>
                    <a href="ai-scanner.php#mobile-app" class="btn btn-secondary">App Details</a>
                    <a href="about.php" class="btn btn-outline">Our Mission</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Preview -->
    <section class="section section--deep" id="pricing-preview">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">TRANSPARENT PLANS</span>
                <h2>Simple Plans That Scale <span class="text-gradient">With Your Documents</span></h2>
                <p class="section-subtitle">
                    Start free and upgrade when you need expanded cloud vault capacity and higher AI limits.
                </p>
            </div>

            <div class="grid grid-3">
                <?php foreach ($plans as $plan): ?>
                    <?php
                    $isFeatured = ((int) ($plan['is_featured'] ?? 0)) === 1;
                    $features = json_decode((string) ($plan['features_json'] ?? '[]'), true);
                    if (!is_array($features)) {
                        $features = [];
                    }
                    $monthlyPrice = (float) ($plan['price_monthly'] ?? 0);
                    ?>
                    <article class="card pricing-card<?= $isFeatured ? ' card--highlight' : '' ?>">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                            <h3><?= e((string) $plan['name']) ?></h3>
                            <?php if ($isFeatured): ?>
                                <span class="card-badge card-badge--pro">Most Popular</span>
                            <?php endif; ?>
                        </div>
                        <p class="mt-1"><?= e((string) ($plan['tagline'] ?? '')) ?></p>

                        <div class="pricing-price">
                            <span class="pricing-amount">$<?= e(number_format($monthlyPrice, 2)) ?></span>
                            <span class="pricing-cycle">/ month</span>
                        </div>
                        <span class="card-badge card-badge--cyan" style="align-self:flex-start;">
                            <?= e((string) ($plan['storage_label'] ?? 'Cloud Vault')) ?>
                        </span>

                        <ul class="pricing-features">
                            <?php foreach ($features as $featureText): ?>
                                <li>
                                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                                    <span><?= e((string) $featureText) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <a
                            href="<?= $monthlyPrice <= 0 ? 'register.php' : 'pricing.php' ?>"
                            class="btn <?= $isFeatured ? 'btn-primary' : 'btn-secondary' ?> btn-block"
                        >
                            <?= $monthlyPrice <= 0 ? 'Start Free' : 'Compare & Select' ?>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-4">
                <a href="pricing.php" class="btn btn-outline">
                    <span>View Monthly / Yearly Toggle &amp; Full Comparison</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="section" id="faq">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">FREQUENTLY ASKED QUESTIONS</span>
                <h2>Got Questions? <span class="text-gradient">We Have Answers</span></h2>
            </div>

            <div class="faq-list">
                <div class="faq-item open">
                    <button type="button" class="faq-question" aria-expanded="true">
                        <span>How does ScanPro AI handle my document privacy?</span>
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="faq-answer">
                        Browser PDF tools execute locally inside your browser without uploading files to our PHP server, and mobile scanning/filters execute on-device. Your web account uses hashed passwords and strict session controls.
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" aria-expanded="false">
                        <span>Can I use ScanPro AI offline on my Android phone?</span>
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="faq-answer">
                        Yes. Scanning, cropping, filters, local Room database storage, and offline PDF management work without an active internet connection. Internet is only required for cloud sync and Gemini AI features.
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" aria-expanded="false">
                        <span>What file formats are supported across the ScanPro AI suite?</span>
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="faq-answer">
                        ScanPro AI supports PDF, JPG, PNG, Word (.docx/.txt), Excel (.xlsx/.csv), PowerPoint (.pptx), HTML/JSON/XML code files, EPUB eBooks, and ZIP archives.
                    </div>
                </div>

                <div class="faq-item">
                    <button type="button" class="faq-question" aria-expanded="false">
                        <span>How do Pro and Ultra billing subscriptions work?</span>
                        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                    </button>
                    <div class="faq-answer">
                        All new accounts start on the Free plan automatically. Paid Pro and Ultra checkout integration is scheduled for a future billing phase, and no fake payment charges are ever simulated on this site.
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>