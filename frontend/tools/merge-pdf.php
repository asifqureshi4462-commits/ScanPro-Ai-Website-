<?php
require_once __DIR__ . '/../../backend/config/app.php';
require_once __DIR__ . '/../../backend/helpers/security.php';

$routePrefix = '../';
$pageTitle = 'Merge PDF';
$pageDescription = 'Combine multiple PDF files in any order directly inside your browser with ScanPro AI.';
$activePage = 'pdf-tools';
$extraStyles = ['assets/css/pdf-tools.css'];
$loadPdfLibs = true;
$extraScripts = [
    'assets/js/pdf-core/pdf-utils.js',
    'assets/js/pdf-core/pdf-renderer.js',
    'assets/js/pdf-core/pdf-uploader.js',
    'assets/js/pdf-tools/merge-pdf.js',
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
                        <i class="fa-solid fa-object-group" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h1>Merge PDF</h1>
                        <p>Select multiple PDFs, arrange their order, preview pages, and combine into one PDF.</p>
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
                <!-- Left Column: Upload & File Queue + Previews -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>1. Select PDF Files</h2>
                            <button type="button" id="addMoreBtn" class="btn btn-secondary btn-sm" style="display:none;">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                <span>Add More PDFs</span>
                            </button>
                        </div>

                        <div
                            class="dropzone"
                            id="mergeDropzone"
                            role="button"
                            tabindex="0"
                            aria-label="Upload PDF files to merge"
                        >
                            <div class="dropzone-icon">
                                <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                            </div>
                            <h3>Drop PDF files here or tap to browse</h3>
                            <p>Select 2 or more PDF files from phone storage, Files Manager, or computer.</p>
                            <div class="dropzone-actions">
                                <button type="button" class="btn btn-primary btn-sm" id="mergeBrowseBtn">
                                    <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                    <span>Choose PDF Files</span>
                                </button>
                            </div>
                            <input
                                type="file"
                                id="mergeFileInput"
                                accept="application/pdf,.pdf"
                                multiple
                                class="sr-only"
                            >
                        </div>

                        <div id="mergeQueueMeta" class="file-meta-bar" style="display:none;">
                            <div class="file-meta-main">
                                <i class="fa-solid fa-layer-group" style="color:var(--accent-cyan);" aria-hidden="true"></i>
                                <div>
                                    <div class="file-meta-name" id="mergeSummaryTitle">0 PDFs selected</div>
                                    <div class="file-meta-sub" id="mergeSummarySub">0 pages • 0 KB</div>
                                </div>
                            </div>
                            <button type="button" id="mergeClearBtn" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                <span>Reset</span>
                            </button>
                        </div>

                        <ul class="file-queue-list" id="mergeFileList"></ul>
                    </div>

                    <div class="workspace-panel" id="mergePreviewPanel" style="display:none;">
                        <div class="panel-header">
                            <h3>First-Page Previews (In Merge Order)</h3>
                        </div>
                        <div class="thumb-grid" id="mergePreviewGrid"></div>
                    </div>
                </div>

                <!-- Right Column: Controls & Download -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>2. Merge &amp; Download</h2>
                        </div>

                        <p class="text-muted" style="font-size:0.9rem;margin-bottom:16px;">
                            Files are merged in the exact top-to-bottom order shown in your list. Use drag-and-drop or the arrow buttons to reorder.
                        </p>

                        <button type="button" id="mergeExecuteBtn" class="btn btn-primary btn-block btn-lg" disabled>
                            <i class="fa-solid fa-object-group" aria-hidden="true"></i>
                            <span>Merge PDFs</span>
                        </button>

                        <div class="progress-panel" id="mergeProgressPanel" style="display:none;">
                            <div class="progress-header">
                                <span class="progress-label">Merging...</span>
                                <span class="progress-percent">0%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill"></div>
                            </div>
                        </div>

                        <div class="result-panel" id="mergeResultPanel" style="display:none;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i class="fa-solid fa-circle-check" style="color:var(--color-success);font-size:1.25rem;" aria-hidden="true"></i>
                                <strong>Merged PDF Ready</strong>
                            </div>

                            <div class="result-stats">
                                <div class="result-stat-box">
                                    <span>Total Pages</span>
                                    <strong id="mergeResultPages">0</strong>
                                </div>
                                <div class="result-stat-box">
                                    <span>Result Size</span>
                                    <strong id="mergeResultSize">0 KB</strong>
                                </div>
                            </div>

                            <button type="button" id="mergeDownloadBtn" class="btn btn-primary btn-block">
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                                <span id="mergeDownloadLabel">Download Merged PDF</span>
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