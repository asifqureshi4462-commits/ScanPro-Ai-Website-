<?php
require_once __DIR__ . '/../../backend/config/app.php';
require_once __DIR__ . '/../../backend/helpers/security.php';

$routePrefix = '../';
$pageTitle = 'Extract PDF Pages';
$pageDescription = 'Browse PDF page thumbnails, select the pages you want to keep, and export them into a new PDF in your browser.';
$activePage = 'pdf-tools';
$extraStyles = ['assets/css/pdf-tools.css'];
$loadPdfLibs = true;
$extraScripts = [
    'assets/js/pdf-core/pdf-utils.js',
    'assets/js/pdf-core/pdf-renderer.js',
    'assets/js/pdf-core/pdf-uploader.js',
    'assets/js/pdf-tools/extract-pages.js',
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
                    <div class="card-icon card-icon--pink" style="margin-bottom:0;">
                        <i class="fa-solid fa-file-export" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h1>Extract PDF Pages</h1>
                        <p>Preview page thumbnails, pick the pages you need, and save them as a new PDF.</p>
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
                <!-- Left: Upload & Selectable Page Thumbnails -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>1. Upload PDF Document</h2>
                        </div>

                        <div
                            class="dropzone"
                            id="extractDropzone"
                            role="button"
                            tabindex="0"
                            aria-label="Upload PDF to extract pages"
                        >
                            <div class="dropzone-icon">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                            </div>
                            <h3>Drop your PDF here or tap to browse</h3>
                            <p>Select a PDF document from your phone storage, Files Manager, or computer.</p>
                            <div class="dropzone-actions">
                                <button type="button" class="btn btn-primary btn-sm" id="extractBrowseBtn">
                                    <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                    <span>Choose PDF File</span>
                                </button>
                            </div>
                            <input type="file" id="extractFileInput" accept="application/pdf,.pdf" class="sr-only">
                        </div>

                        <div id="extractFileMeta" class="file-meta-bar" style="display:none;">
                            <div class="file-meta-main">
                                <i class="fa-solid fa-file-pdf" style="color:var(--accent-pink);" aria-hidden="true"></i>
                                <div>
                                    <div class="file-meta-name" id="extractFileName">document.pdf</div>
                                    <div class="file-meta-sub" id="extractFileSub">0 pages • 0 KB</div>
                                </div>
                            </div>
                            <button type="button" id="extractResetBtn" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <div class="workspace-panel" id="extractPreviewPanel" style="display:none;">
                        <div class="panel-header">
                            <h3>Select Pages (<span id="extractSelectedCount">0</span> selected)</h3>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <button type="button" id="extractSelectAllBtn" class="btn btn-secondary btn-sm">All</button>
                                <button type="button" id="extractSelectOddBtn" class="btn btn-outline btn-sm">Odd</button>
                                <button type="button" id="extractSelectEvenBtn" class="btn btn-outline btn-sm">Even</button>
                                <button type="button" id="extractClearSelBtn" class="btn btn-outline btn-sm">Clear</button>
                            </div>
                        </div>
                        <p class="text-muted" style="font-size:0.84rem;">Tap any thumbnail below to select or unselect that page.</p>
                        <div class="thumb-grid" id="extractThumbGrid"></div>
                    </div>
                </div>

                <!-- Right: Range Quick Select & Export -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>2. Extraction Controls</h2>
                        </div>

                        <div class="form-group">
                            <label for="extractRangeInput" class="form-label">Quick Select by Range (Optional)</label>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <input
                                    type="text"
                                    id="extractRangeInput"
                                    class="form-control"
                                    placeholder="e.g. 1-3, 5, 8"
                                    style="flex:1;min-width:140px;"
                                >
                                <button type="button" id="extractApplyRangeBtn" class="btn btn-secondary btn-sm" disabled>
                                    Apply
                                </button>
                            </div>
                            <span class="form-error-text" id="extractRangeError"></span>
                        </div>

                        <div class="file-meta-bar" id="extractSelectionSummary" style="display:none;margin-bottom:16px;">
                            <div class="file-meta-main">
                                <i class="fa-solid fa-check-double" style="color:var(--accent-cyan);" aria-hidden="true"></i>
                                <div>
                                    <div class="file-meta-name">Selected Pages</div>
                                    <div class="file-meta-sub" id="extractSelectedListText">None</div>
                                </div>
                            </div>
                        </div>

                        <button type="button" id="extractExecuteBtn" class="btn btn-primary btn-block btn-lg" disabled>
                            <i class="fa-solid fa-file-export" aria-hidden="true"></i>
                            <span>Extract Selected Pages</span>
                        </button>

                        <div class="progress-panel" id="extractProgressPanel" style="display:none;">
                            <div class="progress-header">
                                <span class="progress-label">Extracting pages...</span>
                                <span class="progress-percent">0%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill"></div>
                            </div>
                        </div>

                        <div class="result-panel" id="extractResultPanel" style="display:none;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i class="fa-solid fa-circle-check" style="color:var(--color-success);font-size:1.25rem;" aria-hidden="true"></i>
                                <strong>Extracted PDF Ready</strong>
                            </div>

                            <div class="result-stats">
                                <div class="result-stat-box">
                                    <span>Extracted Pages</span>
                                    <strong id="extractResultPages">0</strong>
                                </div>
                                <div class="result-stat-box">
                                    <span>File Size</span>
                                    <strong id="extractResultSize">0 KB</strong>
                                </div>
                            </div>

                            <button type="button" id="extractDownloadBtn" class="btn btn-primary btn-block">
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                                <span id="extractDownloadLabel">Download Extracted PDF</span>
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