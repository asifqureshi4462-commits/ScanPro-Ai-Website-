<?php
require_once __DIR__ . '/../../backend/config/app.php';
require_once __DIR__ . '/../../backend/helpers/security.php';

$routePrefix = '../';
$pageTitle = 'Reorder PDF Pages';
$pageDescription = 'Drag and drop PDF page thumbnails to change page order, preview the new sequence, and download your updated PDF.';
$activePage = 'pdf-tools';
$extraStyles = ['assets/css/pdf-tools.css'];
$loadPdfLibs = true;
$extraScripts = [
    'assets/js/pdf-core/pdf-utils.js',
    'assets/js/pdf-core/pdf-renderer.js',
    'assets/js/pdf-core/pdf-uploader.js',
    'assets/js/pdf-tools/reorder-pages.js',
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
                        <i class="fa-solid fa-arrow-down-up-across-line" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h1>Reorder PDF Pages</h1>
                        <p>Drag and drop thumbnails (or use arrows on mobile), preview the new order, and export.</p>
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
                <!-- Left: Upload & Draggable Thumbnails -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>1. Upload PDF Document</h2>
                        </div>

                        <div
                            class="dropzone"
                            id="reorderDropzone"
                            role="button"
                            tabindex="0"
                            aria-label="Upload PDF to reorder pages"
                        >
                            <div class="dropzone-icon">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                            </div>
                            <h3>Drop your PDF here or tap to browse</h3>
                            <p>Select a multi-page PDF document from phone storage, Files Manager, or computer.</p>
                            <div class="dropzone-actions">
                                <button type="button" class="btn btn-primary btn-sm" id="reorderBrowseBtn">
                                    <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                    <span>Choose PDF File</span>
                                </button>
                            </div>
                            <input type="file" id="reorderFileInput" accept="application/pdf,.pdf" class="sr-only">
                        </div>

                        <div id="reorderFileMeta" class="file-meta-bar" style="display:none;">
                            <div class="file-meta-main">
                                <i class="fa-solid fa-file-pdf" style="color:var(--accent-purple);" aria-hidden="true"></i>
                                <div>
                                    <div class="file-meta-name" id="reorderFileName">document.pdf</div>
                                    <div class="file-meta-sub" id="reorderFileSub">0 pages • 0 KB</div>
                                </div>
                            </div>
                            <button type="button" id="reorderResetBtn" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <div class="workspace-panel" id="reorderPreviewPanel" style="display:none;">
                        <div class="panel-header">
                            <h3>Drag &amp; Drop Page Thumbnails</h3>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                <button type="button" id="reorderReverseBtn" class="btn btn-secondary btn-sm">Reverse Order</button>
                                <button type="button" id="reorderRestoreBtn" class="btn btn-outline btn-sm">Original Order</button>
                            </div>
                        </div>
                        <p class="text-muted" style="font-size:0.84rem;">Drag thumbnails on desktop, or use left/right arrow buttons on mobile.</p>
                        <div class="thumb-grid" id="reorderThumbGrid"></div>
                    </div>
                </div>

                <!-- Right: Live Order Preview & Export -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>2. New Page Sequence</h2>
                        </div>

                        <div class="file-meta-bar" id="reorderOrderPreviewBox" style="display:none;margin-top:0;margin-bottom:16px;">
                            <div class="file-meta-main">
                                <i class="fa-solid fa-list-ol" style="color:var(--accent-cyan);" aria-hidden="true"></i>
                                <div>
                                    <div class="file-meta-name">Preview Order</div>
                                    <div class="file-meta-sub text-mono" id="reorderSequenceText">—</div>
                                </div>
                            </div>
                        </div>

                        <button type="button" id="reorderExecuteBtn" class="btn btn-primary btn-block btn-lg" disabled>
                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                            <span>Export Reordered PDF</span>
                        </button>

                        <div class="progress-panel" id="reorderProgressPanel" style="display:none;">
                            <div class="progress-header">
                                <span class="progress-label">Rebuilding PDF...</span>
                                <span class="progress-percent">0%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill"></div>
                            </div>
                        </div>

                        <div class="result-panel" id="reorderResultPanel" style="display:none;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i class="fa-solid fa-circle-check" style="color:var(--color-success);font-size:1.25rem;" aria-hidden="true"></i>
                                <strong>Reordered PDF Ready</strong>
                            </div>

                            <div class="result-stats">
                                <div class="result-stat-box">
                                    <span>Total Pages</span>
                                    <strong id="reorderResultPages">0</strong>
                                </div>
                                <div class="result-stat-box">
                                    <span>File Size</span>
                                    <strong id="reorderResultSize">0 KB</strong>
                                </div>
                            </div>

                            <button type="button" id="reorderDownloadBtn" class="btn btn-primary btn-block">
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                                <span id="reorderDownloadLabel">Download Reordered PDF</span>
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