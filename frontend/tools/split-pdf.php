<?php
require_once __DIR__ . '/../../backend/config/app.php';
require_once __DIR__ . '/../../backend/helpers/security.php';

$routePrefix = '../';
$pageTitle = 'Split PDF';
$pageDescription = 'Split a PDF into separate PDF files using custom page ranges and download individually or as a ZIP archive.';
$activePage = 'pdf-tools';
$extraStyles = ['assets/css/pdf-tools.css'];
$loadPdfLibs = true;
$loadZipLib = true;
$extraScripts = [
    'assets/js/pdf-core/pdf-utils.js',
    'assets/js/pdf-core/pdf-renderer.js',
    'assets/js/pdf-core/pdf-uploader.js',
    'assets/js/pdf-tools/split-pdf.js',
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
                    <div class="card-icon card-icon--cyan" style="margin-bottom:0;">
                        <i class="fa-solid fa-scissors" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h1>Split PDF</h1>
                        <p>Upload a PDF, choose page ranges or split intervals, and download parts individually or as a ZIP.</p>
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
                <!-- Left: Upload + Thumbnails -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>1. Upload PDF Document</h2>
                        </div>

                        <div
                            class="dropzone"
                            id="splitDropzone"
                            role="button"
                            tabindex="0"
                            aria-label="Upload PDF file to split"
                        >
                            <div class="dropzone-icon">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                            </div>
                            <h3>Drop your PDF here or tap to browse</h3>
                            <p>Pick a PDF document from your phone or computer storage.</p>
                            <div class="dropzone-actions">
                                <button type="button" class="btn btn-primary btn-sm" id="splitBrowseBtn">
                                    <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                    <span>Choose PDF File</span>
                                </button>
                            </div>
                            <input type="file" id="splitFileInput" accept="application/pdf,.pdf" class="sr-only">
                        </div>

                        <div id="splitFileMeta" class="file-meta-bar" style="display:none;">
                            <div class="file-meta-main">
                                <i class="fa-solid fa-file-pdf" style="color:var(--accent-cyan);" aria-hidden="true"></i>
                                <div>
                                    <div class="file-meta-name" id="splitFileName">document.pdf</div>
                                    <div class="file-meta-sub" id="splitFileSub">0 pages • 0 KB</div>
                                </div>
                            </div>
                            <button type="button" id="splitResetBtn" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <div class="workspace-panel" id="splitPreviewPanel" style="display:none;">
                        <div class="panel-header">
                            <h3>Page Thumbnails</h3>
                            <span class="text-muted" id="splitPageCountHint" style="font-size:0.84rem;"></span>
                        </div>
                        <div class="thumb-grid" id="splitThumbGrid"></div>
                    </div>
                </div>

                <!-- Right: Split Settings & Output -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>2. Split Options</h2>
                        </div>

                        <div class="form-group">
                            <label for="splitModeSelect" class="form-label">Split Method</label>
                            <select id="splitModeSelect" class="form-control">
                                <option value="ranges">Custom Page Ranges (one PDF per range)</option>
                                <option value="fixed">Every N Pages</option>
                                <option value="single">Every Page into Separate PDFs</option>
                            </select>
                        </div>

                        <div class="form-group" id="splitRangesGroup">
                            <label for="splitRangesInput" class="form-label">Page Ranges</label>
                            <input
                                type="text"
                                id="splitRangesInput"
                                class="form-control"
                                placeholder="Example: 1-3, 4-5, 6"
                            >
                            <small class="text-muted" style="display:block;margin-top:6px;font-size:0.8rem;">
                                Separate each output PDF with a comma (e.g., 1-2, 3, 4-6).
                            </small>
                            <span class="form-error-text" id="splitRangeError"></span>
                        </div>

                        <div class="form-group" id="splitFixedGroup" style="display:none;">
                            <label for="splitFixedInput" class="form-label">Pages Per Split File</label>
                            <input
                                type="number"
                                id="splitFixedInput"
                                class="form-control"
                                min="1"
                                value="2"
                            >
                            <span class="form-error-text" id="splitFixedError"></span>
                        </div>

                        <button type="button" id="splitExecuteBtn" class="btn btn-primary btn-block btn-lg" disabled>
                            <i class="fa-solid fa-scissors" aria-hidden="true"></i>
                            <span>Split PDF</span>
                        </button>

                        <div class="progress-panel" id="splitProgressPanel" style="display:none;">
                            <div class="progress-header">
                                <span class="progress-label">Splitting...</span>
                                <span class="progress-percent">0%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill"></div>
                            </div>
                        </div>

                        <div class="result-panel" id="splitResultPanel" style="display:none;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i class="fa-solid fa-circle-check" style="color:var(--color-success);font-size:1.25rem;" aria-hidden="true"></i>
                                <strong id="splitResultSummary">Split PDFs Ready</strong>
                            </div>

                            <button type="button" id="splitDownloadZipBtn" class="btn btn-primary btn-block mt-2">
                                <i class="fa-solid fa-file-zipper" aria-hidden="true"></i>
                                <span id="splitDownloadZipLabel">Download All as ZIP</span>
                            </button>

                            <div class="output-item-list" id="splitOutputList"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php require_once __DIR__ . '/../includes/ad-slot.php'; renderAdSlot('tool'); ?>

</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>