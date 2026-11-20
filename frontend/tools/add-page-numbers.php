<?php
require_once __DIR__ . '/../../backend/config/app.php';
require_once __DIR__ . '/../../backend/helpers/security.php';

$routePrefix = '../';
$pageTitle = 'Add Page Numbers';
$pageDescription = 'Add clean page numbers to your PDF with customizable position, font size, format, and starting number.';
$activePage = 'pdf-tools';
$extraStyles = ['assets/css/pdf-tools.css'];
$loadPdfLibs = true;
$extraScripts = [
    'assets/js/pdf-core/pdf-utils.js',
    'assets/js/pdf-core/pdf-renderer.js',
    'assets/js/pdf-core/pdf-uploader.js',
    'assets/js/pdf-tools/add-page-numbers.js',
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
                    <div class="card-icon card-icon--green" style="margin-bottom:0;">
                        <i class="fa-solid fa-list-ol" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h1>Add Page Numbers</h1>
                        <p>Choose numbering position, font size, format, and starting number with live preview.</p>
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
                <!-- Left: Upload & Live Preview -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>1. Upload PDF Document</h2>
                        </div>

                        <div
                            class="dropzone"
                            id="pnDropzone"
                            role="button"
                            tabindex="0"
                            aria-label="Upload PDF to add page numbers"
                        >
                            <div class="dropzone-icon">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                            </div>
                            <h3>Drop your PDF here or tap to browse</h3>
                            <p>Select a PDF document from phone storage, Files Manager, or computer.</p>
                            <div class="dropzone-actions">
                                <button type="button" class="btn btn-primary btn-sm" id="pnBrowseBtn">
                                    <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                    <span>Choose PDF File</span>
                                </button>
                            </div>
                            <input type="file" id="pnFileInput" accept="application/pdf,.pdf" class="sr-only">
                        </div>

                        <div id="pnFileMeta" class="file-meta-bar" style="display:none;">
                            <div class="file-meta-main">
                                <i class="fa-solid fa-file-pdf" style="color:var(--color-success);" aria-hidden="true"></i>
                                <div>
                                    <div class="file-meta-name" id="pnFileName">document.pdf</div>
                                    <div class="file-meta-sub" id="pnFileSub">0 pages • 0 KB</div>
                                </div>
                            </div>
                            <button type="button" id="pnResetBtn" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <div class="workspace-panel" id="pnPreviewPanel" style="display:none;">
                        <div class="panel-header">
                            <h3>Live Preview (Page 1)</h3>
                        </div>
                        <div class="preview-stage">
                            <canvas id="pnPreviewCanvas"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Right: Numbering Controls -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>2. Numbering Options</h2>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Position</label>
                            <div class="position-grid" id="pnPositionGrid">
                                <button type="button" class="position-btn" data-pos="top-left">Top Left</button>
                                <button type="button" class="position-btn" data-pos="top-center">Top Center</button>
                                <button type="button" class="position-btn" data-pos="top-right">Top Right</button>
                                <button type="button" class="position-btn" data-pos="bottom-left">Bottom Left</button>
                                <button type="button" class="position-btn active" data-pos="bottom-center">Bottom Mid</button>
                                <button type="button" class="position-btn" data-pos="bottom-right">Bottom Right</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="pnFormatSelect" class="form-label">Label Format</label>
                            <select id="pnFormatSelect" class="form-control">
                                <option value="number" selected>1, 2, 3</option>
                                <option value="page-n">Page 1, Page 2</option>
                                <option value="page-n-of-total">Page 1 of N</option>
                                <option value="n-slash-total">1 / N</option>
                            </select>
                        </div>

                        <div class="grid grid-2" style="gap:12px;">
                            <div class="form-group">
                                <label for="pnStartInput" class="form-label">Starting Number</label>
                                <input type="number" id="pnStartInput" class="form-control" min="1" value="1">
                            </div>

                            <div class="form-group">
                                <label for="pnMarginSelect" class="form-label">Edge Margin</label>
                                <select id="pnMarginSelect" class="form-control">
                                    <option value="18">Tight (18 pt)</option>
                                    <option value="28" selected>Standard (28 pt)</option>
                                    <option value="42">Wide (42 pt)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="pnFontSizeRange" class="form-label">
                                Font Size: <strong id="pnFontSizeValue">12 pt</strong>
                            </label>
                            <input type="range" id="pnFontSizeRange" class="range-control" min="8" max="28" value="12">
                        </div>

                        <div class="form-group">
                            <label for="pnColorSelect" class="form-label">Text Color</label>
                            <select id="pnColorSelect" class="form-control">
                                <option value="slate" selected>Dark Slate</option>
                                <option value="black">Pure Black</option>
                                <option value="blue">Navy Blue</option>
                            </select>
                        </div>

                        <button type="button" id="pnExecuteBtn" class="btn btn-primary btn-block btn-lg" disabled>
                            <i class="fa-solid fa-list-ol" aria-hidden="true"></i>
                            <span>Add Page Numbers</span>
                        </button>

                        <div class="progress-panel" id="pnProgressPanel" style="display:none;">
                            <div class="progress-header">
                                <span class="progress-label">Adding page numbers...</span>
                                <span class="progress-percent">0%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill"></div>
                            </div>
                        </div>

                        <div class="result-panel" id="pnResultPanel" style="display:none;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i class="fa-solid fa-circle-check" style="color:var(--color-success);font-size:1.25rem;" aria-hidden="true"></i>
                                <strong>Numbered PDF Ready</strong>
                            </div>

                            <div class="result-stats">
                                <div class="result-stat-box">
                                    <span>Pages Numbered</span>
                                    <strong id="pnResultPages">0</strong>
                                </div>
                                <div class="result-stat-box">
                                    <span>File Size</span>
                                    <strong id="pnResultSize">0 KB</strong>
                                </div>
                            </div>

                            <button type="button" id="pnDownloadBtn" class="btn btn-primary btn-block">
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                                <span id="pnDownloadLabel">Download Numbered PDF</span>
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