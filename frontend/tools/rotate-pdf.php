<?php
require_once __DIR__ . '/../../backend/config/app.php';
require_once __DIR__ . '/../../backend/helpers/security.php';

$routePrefix = '../';
$pageTitle = 'Rotate PDF';
$pageDescription = 'Preview PDF pages and rotate individual pages or the whole document by 90°, 180°, or 270° in your browser.';
$activePage = 'pdf-tools';
$extraStyles = ['assets/css/pdf-tools.css'];
$loadPdfLibs = true;
$extraScripts = [
    'assets/js/pdf-core/pdf-utils.js',
    'assets/js/pdf-core/pdf-renderer.js',
    'assets/js/pdf-core/pdf-uploader.js',
    'assets/js/pdf-tools/rotate-pdf.js',
];

require_once __DIR__ . '/../includes/head.php';
require_once __DIR__ . '/../includes/header.php';
?>

<main>
    <section class="tool-topbar cyber-grid-bg">
        <div class="container">
            <a href="../pdf-tools.php" class="tool-breadcrumb">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Back to All PDF Tools</span>
            </a>

            <div class="tool-title-row">
                <div class="tool-title-group">
                    <div class="card-icon card-icon--amber" style="margin-bottom:0;">
                        <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h1>Rotate PDF</h1>
                        <p>Rotate individual pages or all pages by 90°, 180°, or 270° with live page previews.</p>
                    </div>
                </div>
                <span class="privacy-pill">
                    <i class="fa-solid fa-lock" aria-hidden="true"></i>
                    <span>Processed locally in browser</span>
                </span>
            </div>
        </div>
    </section>

    <section class="tool-workspace">
        <div class="container">
            <?php require __DIR__ . '/../includes/alerts.php'; ?>
            <div id="toolAlertContainer" aria-live="polite"></div>

            <div class="tool-grid">
                <!-- Left: Upload & Per-Page Rotatable Thumbnails -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>1. Upload PDF Document</h2>
                        </div>

                        <div
                            class="dropzone"
                            id="rotateDropzone"
                            role="button"
                            tabindex="0"
                            aria-label="Upload PDF file to rotate"
                        >
                            <div class="dropzone-icon">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                            </div>
                            <h3>Drop your PDF here or tap to browse</h3>
                            <p>Select a PDF document from your phone storage, Files Manager, or computer.</p>
                            <div class="dropzone-actions">
                                <button type="button" class="btn btn-primary btn-sm" id="rotateBrowseBtn">
                                    <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                    <span>Choose PDF File</span>
                                </button>
                            </div>
                            <input type="file" id="rotateFileInput" accept="application/pdf,.pdf" class="sr-only">
                        </div>

                        <div id="rotateFileMeta" class="file-meta-bar" style="display:none;">
                            <div class="file-meta-main">
                                <i class="fa-solid fa-file-pdf" style="color:var(--accent-amber);" aria-hidden="true"></i>
                                <div>
                                    <div class="file-meta-name" id="rotateFileName">document.pdf</div>
                                    <div class="file-meta-sub" id="rotateFileSub">0 pages • 0 KB</div>
                                </div>
                            </div>
                            <button type="button" id="rotateResetBtn" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                <span>Reset File</span>
                            </button>
                        </div>
                    </div>

                    <div class="workspace-panel" id="rotatePreviewPanel" style="display:none;">
                        <div class="panel-header">
                            <h3>Page Thumbnails (Tap Rotate on Any Page)</h3>
                        </div>
                        <div class="thumb-grid" id="rotateThumbGrid"></div>
                    </div>
                </div>

                <!-- Right: Bulk Controls & Export -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>2. Rotation Controls</h2>
                        </div>

                        <p class="text-muted" style="font-size:0.88rem;margin-bottom:14px;">
                            Rotate all pages together below, or rotate individual pages directly on their preview cards.
                        </p>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;">
                            <button type="button" class="btn btn-secondary btn-sm" id="rotateAll90Btn" disabled>
                                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                <span>All +90°</span>
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" id="rotateAll180Btn" disabled>
                                <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                                <span>All 180°</span>
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" id="rotateAll270Btn" disabled>
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                <span>All 270°</span>
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" id="rotateResetAnglesBtn" disabled>
                                <span>Reset Angles</span>
                            </button>
                        </div>

                        <button type="button" id="rotateExecuteBtn" class="btn btn-primary btn-block btn-lg" disabled>
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                            <span>Apply Rotation &amp; Save PDF</span>
                        </button>

                        <div class="progress-panel" id="rotateProgressPanel" style="display:none;">
                            <div class="progress-header">
                                <span class="progress-label">Rotating pages...</span>
                                <span class="progress-percent">0%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill"></div>
                            </div>
                        </div>

                        <div class="result-panel" id="rotateResultPanel" style="display:none;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i class="fa-solid fa-circle-check" style="color:var(--color-success);font-size:1.25rem;" aria-hidden="true"></i>
                                <strong>Rotated PDF Ready</strong>
                            </div>

                            <div class="result-stats">
                                <div class="result-stat-box">
                                    <span>Pages Updated</span>
                                    <strong id="rotateResultChanged">0</strong>
                                </div>
                                <div class="result-stat-box">
                                    <span>File Size</span>
                                    <strong id="rotateResultSize">0 KB</strong>
                                </div>
                            </div>

                            <button type="button" id="rotateDownloadBtn" class="btn btn-primary btn-block">
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                                <span id="rotateDownloadLabel">Download Rotated PDF</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php require_once __DIR__ . '/../includes/ad-slot.php'; renderAdSlot('tool'); ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>