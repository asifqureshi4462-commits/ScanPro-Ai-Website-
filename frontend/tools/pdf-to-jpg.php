<?php
require_once __DIR__ . '/../../backend/config/app.php';
require_once __DIR__ . '/../../backend/helpers/security.php';

$routePrefix = '../';
$pageTitle = 'PDF to JPG';
$pageDescription = 'Preview PDF pages, select specific pages, choose JPEG quality, and download JPG images or a ZIP archive.';
$activePage = 'pdf-tools';
$extraStyles = ['assets/css/pdf-tools.css'];
$loadPdfLibs = true;
$loadZipLib = true;
$extraScripts = [
    'assets/js/pdf-core/pdf-utils.js',
    'assets/js/pdf-core/pdf-renderer.js',
    'assets/js/pdf-core/pdf-uploader.js',
    'assets/js/pdf-tools/pdf-to-jpg.js',
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
                    <div class="card-icon card-icon--purple" style="margin-bottom:0;">
                        <i class="fa-solid fa-file-image" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h1>PDF to JPG</h1>
                        <p>Preview PDF pages, select the pages you want, choose JPEG quality, and download.</p>
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
                <!-- Left: Upload & Page Thumbnail Picker -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>1. Upload PDF Document</h2>
                        </div>

                        <div
                            class="dropzone"
                            id="pdf2jpgDropzone"
                            role="button"
                            tabindex="0"
                            aria-label="Upload PDF to convert to JPG"
                        >
                            <div class="dropzone-icon">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                            </div>
                            <h3>Drop your PDF here or tap to browse</h3>
                            <p>Select a PDF file from phone storage, Files Manager, or computer.</p>
                            <div class="dropzone-actions">
                                <button type="button" class="btn btn-primary btn-sm" id="pdf2jpgBrowseBtn">
                                    <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                    <span>Choose PDF File</span>
                                </button>
                            </div>
                            <input type="file" id="pdf2jpgFileInput" accept="application/pdf,.pdf" class="sr-only">
                        </div>

                        <div id="pdf2jpgFileMeta" class="file-meta-bar" style="display:none;">
                            <div class="file-meta-main">
                                <i class="fa-solid fa-file-pdf" style="color:var(--accent-purple);" aria-hidden="true"></i>
                                <div>
                                    <div class="file-meta-name" id="pdf2jpgFileName">document.pdf</div>
                                    <div class="file-meta-sub" id="pdf2jpgFileSub">0 pages • 0 KB</div>
                                </div>
                            </div>
                            <button type="button" id="pdf2jpgResetBtn" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <div class="workspace-panel" id="pdf2jpgPreviewPanel" style="display:none;">
                        <div class="panel-header">
                            <h3>Select Pages (<span id="pdf2jpgSelectedCount">0</span> selected)</h3>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <button type="button" id="pdf2jpgSelectAllBtn" class="btn btn-secondary btn-sm">Select All</button>
                                <button type="button" id="pdf2jpgClearSelBtn" class="btn btn-outline btn-sm">Clear</button>
                            </div>
                        </div>
                        <p class="text-muted" style="font-size:0.84rem;">Tap any page thumbnail to include or exclude it.</p>
                        <div class="thumb-grid" id="pdf2jpgThumbGrid"></div>
                    </div>
                </div>

                <!-- Right: Quality Settings & Download -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>2. Image Settings</h2>
                        </div>

                        <div class="form-group">
                            <label for="pdf2jpgScaleSelect" class="form-label">Resolution Scale</label>
                            <select id="pdf2jpgScaleSelect" class="form-control">
                                <option value="1.5">Standard (1.5x — fast &amp; light)</option>
                                <option value="2.0" selected>High Clarity (2.0x — recommended)</option>
                                <option value="2.5">Print Quality (2.5x — larger files)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="pdf2jpgQualityRange" class="form-label">
                                JPEG Quality: <strong id="pdf2jpgQualityValue">85%</strong>
                            </label>
                            <input
                                type="range"
                                id="pdf2jpgQualityRange"
                                class="range-control"
                                min="40"
                                max="98"
                                value="85"
                            >
                        </div>

                        <button type="button" id="pdf2jpgExecuteBtn" class="btn btn-primary btn-block btn-lg" disabled>
                            <i class="fa-solid fa-images" aria-hidden="true"></i>
                            <span>Convert Selected Pages to JPG</span>
                        </button>

                        <div class="progress-panel" id="pdf2jpgProgressPanel" style="display:none;">
                            <div class="progress-header">
                                <span class="progress-label">Rendering JPGs...</span>
                                <span class="progress-percent">0%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill"></div>
                            </div>
                        </div>

                        <div class="result-panel" id="pdf2jpgResultPanel" style="display:none;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i class="fa-solid fa-circle-check" style="color:var(--color-success);font-size:1.25rem;" aria-hidden="true"></i>
                                <strong id="pdf2jpgResultTitle">JPG Images Ready</strong>
                            </div>

                            <button type="button" id="pdf2jpgDownloadZipBtn" class="btn btn-primary btn-block mt-2">
                                <i class="fa-solid fa-file-zipper" aria-hidden="true"></i>
                                <span id="pdf2jpgDownloadZipLabel">Download All as ZIP</span>
                            </button>

                            <div class="output-item-list" id="pdf2jpgOutputList"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php require_once __DIR__ . '/../includes/ad-slot.php'; renderAdSlot('tool'); ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>