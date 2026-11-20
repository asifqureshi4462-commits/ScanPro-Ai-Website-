<?php
require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/helpers/security.php';
require_once __DIR__ . '/includes/live-3d-scenes.php';

$pageTitle = 'Features';
$pageDescription = 'Explore ScanPro AI capabilities across smart camera scanning, Gemini AI OCR, PDF management, multi-format office viewing, and encrypted vault security.';
$activePage = 'features';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <div class="container" style="padding-top: 16px;">
        <?php require __DIR__ . '/includes/alerts.php'; ?>
    </div>

    <section class="page-hero cyber-grid-bg">
        <div class="container">
            <span class="eyebrow">COMPLETE PLATFORM OVERVIEW</span>
            <h1>Built for Every Stage of Your <span class="text-gradient">Document Workflow</span></h1>
            <p>
                From capturing crisp scans with on-device computer vision to extracting intelligence with Gemini AI and securing your files in a unified vault.
            </p>
        </div>
    </section>

    <!-- Category 1: Smart Camera Scanner -->
    <section class="section" id="smart-scanner">
        <div class="container">
            <div class="section-header section-header--left">
                <span class="eyebrow">01 • CAPTURE ENGINE</span>
                <h2>Smart Camera <span class="text-gradient">Scanner</span></h2>
                <p class="section-subtitle">
                    Precision edge detection, perspective correction, and specialized capture modes tailored for every paper format.
                </p>
            </div>

            <div class="grid grid-3">
                <article class="card card--tool">
                    <div class="card-icon card-icon--cyan">
                        <i class="fa-solid fa-vector-square" aria-hidden="true"></i>
                    </div>
                    <h3>Live Edge Detection</h3>
                    <p>Automatic 4-point border detection and perspective warp flatten angled photos into clean rectangular pages.</p>
                    <a href="ai-scanner.php" class="card-link">
                        <span>Scanner Overview</span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>

                <article class="card card--tool">
                    <div class="card-icon card-icon--teal">
                        <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                    </div>
                    <h3>11 Enhancement Filters</h3>
                    <p>Apply Original, Lighten, Enhance, Magic Pro, No Shadow, No Watermark, No Handwriting, B&amp;W, Eco, Grayscale, or Invert.</p>
                    <a href="ai-scanner.php#enhancement" class="card-link">
                        <span>See Filter Pipeline</span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>

                <article class="card card--tool">
                    <div class="card-icon card-icon--pink">
                        <i class="fa-solid fa-id-card" aria-hidden="true"></i>
                    </div>
                    <h3>17+ Specialized Modes</h3>
                    <p>Switch seamlessly between Documents, ID Cards, Passport Photos, Books, Whiteboards, Slides, Timestamps, and QR Codes.</p>
                    <a href="ai-scanner.php#scan-modes" class="card-link">
                        <span>Explore Modes</span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>
            </div>
        </div>
    </section>

    <!-- Category 2: Gemini AI Intelligence -->
    <section class="section section--deep" id="ai-intelligence">
        <div class="container">
            <div class="section-header section-header--left">
                <span class="eyebrow eyebrow--purple">02 • GEMINI AI ENGINE</span>
                <h2>AI Document <span class="text-gradient-ai">Intelligence</span></h2>
                <p class="section-subtitle">
                    Turn static scans into searchable, structured, and actionable knowledge with Gemini 2.5 Flash integration.
                </p>
            </div>

            <div class="grid grid-3">
                <article class="card">
                    <div class="card-icon card-icon--purple">
                        <i class="fa-solid fa-font" aria-hidden="true"></i>
                    </div>
                    <h3>AI OCR Extraction</h3>
                    <p>Extract clean, editable text from photographed receipts, contracts, and printed worksheets while preserving layout.</p>
                    <a href="ai-scanner.php#ocr-overview" class="card-link">
                        <span>Read OCR Details</span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>

                <article class="card">
                    <div class="card-icon card-icon--cyan">
                        <i class="fa-solid fa-comments" aria-hidden="true"></i>
                    </div>
                    <h3>Contextual Q&amp;A &amp; Summaries</h3>
                    <p>Generate concise executive summaries or ask specific questions about dates, clauses, and figures inside a document.</p>
                    <a href="ai-scanner.php#ocr-overview" class="card-link">
                        <span>Explore AI Chat</span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>

                <article class="card">
                    <div class="card-icon card-icon--green">
                        <i class="fa-solid fa-language" aria-hidden="true"></i>
                    </div>
                    <h3>50+ Language Translation</h3>
                    <p>Translate extracted document text cleanly across major global languages plus parse structured tables and question sets.</p>
                    <a href="pricing.php" class="card-link">
                        <span>Check Plan Limits</span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>
            </div>
        </div>
    </section>

    <!-- Category 3: PDF Studio -->
    <section class="section" id="pdf-studio">
        <div class="container">
            <div class="section-header section-header--left">
                <span class="eyebrow">03 • PDF TOOLKIT</span>
                <h2>Complete <span class="text-gradient">PDF Studio</span></h2>
                <p class="section-subtitle">
                    Organize, convert, optimize, and lock PDF files without compromising layout integrity.
                </p>
            </div>

            <div class="grid grid-3">
                <article class="card card--tool">
                    <div class="card-icon card-icon--red">
                        <i class="fa-solid fa-object-group" aria-hidden="true"></i>
                    </div>
                    <h3>Merge, Split &amp; Reorder</h3>
                    <p>Combine multiple PDFs, extract custom page ranges, delete unwanted sheets, or rearrange page sequences.</p>
                    <a href="pdf-tools.php#pdf-management" class="card-link">
                        <span>View Management Tools</span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>

                <article class="card card--tool">
                    <div class="card-icon card-icon--amber">
                        <i class="fa-solid fa-key" aria-hidden="true"></i>
                    </div>
                    <h3>AES-128 Lock &amp; Watermark</h3>
                    <p>Encrypt confidential documents with real password protection, stamp security watermarks, or add digital signatures.</p>
                    <a href="pdf-tools.php#pdf-security" class="card-link">
                        <span>View Security Tools</span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>

                <article class="card card--tool">
                    <div class="card-icon card-icon--blue">
                        <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                    </div>
                    <h3>Cross-Format Conversions</h3>
                    <p>Bridge PDFs with Word (.docx), Excel (.csv), and high-resolution JPG/PNG image sequences seamlessly.</p>
                    <a href="pdf-tools.php#pdf-conversions" class="card-link">
                        <span>View Conversion Tools</span>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </article>
            </div>
        </div>
    </section>

    <?php require_once __DIR__ . '/includes/ad-slot.php'; renderAdSlot('features'); ?>

    <!-- Category 4: Multi-Format Office Suite -->
    <section class="section section--deep" id="office-suite">
        <div class="container split-grid">
            <div>
                <span class="eyebrow">04 • MULTI-FORMAT VIEWERS</span>
                <h2>One Workspace for <span class="text-gradient">Office, Code &amp; Archives</span></h2>
                <p class="text-muted mt-2">
                    ScanPro AI extends beyond scanned PDFs with dedicated viewers and editors for everyday office, academic, and developer formats.
                </p>

                <ul class="check-list">
                    <li>
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span><strong>Word Documents (.docx / .txt):</strong> Rich text editing, formatting controls, and PDF export.</span>
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span><strong>Spreadsheets (.xlsx / .csv):</strong> Tabular grid editing, formula bar, and AI table insights.</span>
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span><strong>Presentations (.pptx):</strong> Slide deck viewer with presentation mode and laser pointer.</span>
                    </li>
                    <li>
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                        <span><strong>Code, EPUB &amp; ZIP:</strong> HTML/JSON/XML viewer, Day/Sepia/Night eBook reader, and ZIP extractor.</span>
                    </li>
                </ul>
            </div>

            <div>
                <?php renderLive3DScene('office-formats'); ?>
            </div>
        </div>
    </section>

    <!-- Category 5: Security & Cloud Vault -->
    <section class="section" id="security">
        <div class="container">
            <div class="section-header">
                <span class="eyebrow">05 • SECURITY &amp; CLOUD VAULT</span>
                <h2>Privacy Engineered at <span class="text-gradient">Every Layer</span></h2>
                <p class="section-subtitle">
                    Local-first storage paired with strict authentication and transparent account management.
                </p>
            </div>

            <div class="grid grid-3">
                <article class="card">
                    <div class="card-icon card-icon--green">
                        <i class="fa-solid fa-database" aria-hidden="true"></i>
                    </div>
                    <h3>Offline Room Vault</h3>
                    <p>Your scanned files and AI chat history stay cached locally on your Android device so you remain productive offline.</p>
                </article>

                <article class="card">
                    <div class="card-icon card-icon--cyan">
                        <i class="fa-solid fa-user-lock" aria-hidden="true"></i>
                    </div>
                    <h3>PIN App Lock &amp; Fail-Closed Auth</h3>
                    <p>Protect your mobile workspace with a custom PIN and secure web sessions backed by strong password hashing.</p>
                </article>

                <article class="card">
                    <div class="card-icon card-icon--purple">
                        <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                    </div>
                    <h3>Scalable Cloud Storage</h3>
                    <p>Start with 5 GB free tier organization and upgrade to 100 GB or 1 TB vault tiers as your archive grows.</p>
                </article>
            </div>

            <div class="text-center mt-4" style="display:flex;justify-content:center;flex-wrap:wrap;gap:12px;">
                <a href="register.php" class="btn btn-primary btn-lg">Create Free Account</a>
                <a href="pricing.php" class="btn btn-secondary btn-lg">Compare Plans</a>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>