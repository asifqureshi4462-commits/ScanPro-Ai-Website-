<?php
require_once __DIR__ . '/../../backend/config/app.php';
require_once __DIR__ . '/../../backend/helpers/security.php';

$routePrefix = '../';
$pageTitle = 'Compress PDF';
$pageDescription = 'Optimize PDF file size in your browser and inspect exact original vs resulting file sizes with ScanPro AI.';
$activePage = 'pdf-tools';
$extraStyles = ['assets/css/pdf-tools.css'];
$loadPdfLibs = true;
$extraScripts = [
    'assets/js/pdf-core/pdf-utils.js',
    'assets/js/pdf-core/pdf-renderer.js',
    'assets/js/pdf-core/pdf-uploader.js',
    'assets/js/pdf-tools/compress-pdf.js',
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
                    <div class="card-icon card-icon--teal" style="margin-bottom:0;">
                        <i class="fa-solid fa-minimize" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h1>Compress PDF</h1>
                        <p>Choose an optimization mode, compare original and resulting file sizes, and download.</p>
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
                <!-- Left: Upload & Preview -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>1. Upload PDF Document</h2>
                        </div>

                        <div
                            class="dropzone"
                            id="compressDropzone"
                            role="button"
                            tabindex="0"
                            aria-label="Upload PDF file to compress"
                        >
                            <div class="dropzone-icon">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                            </div>
                            <h3>Drop your PDF here or tap to browse</h3>
                            <p>Select a PDF document from your phone, Files Manager, or computer.</p>
                            <div class="dropzone-actions">
                                <button type="button" class="btn btn-primary btn-sm" id="compressBrowseBtn">
                                    <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                    <span>Choose PDF File</span>
                                </button>
                            </div>
                            <input type="file" id="compressFileInput" accept="application/pdf,.pdf" class="sr-only">
                        </div>

                        <div id="compressFileMeta" class="file-meta-bar" style="display:none;">
                            <div class="file-meta-main">
                                <i class="fa-solid fa-file-pdf" style="color:var(--accent-teal);" aria-hidden="true"></i>
                                <div>
                                    <div class="file-meta-name" id="compressFileName">document.pdf</div>
                                    <div class="file-meta-sub" id="compressFileSub">0 pages • 0 KB</div>
                                </div>
                            </div>
                            <button type="button" id="compressResetBtn" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <div class="workspace-panel" id="compressPreviewPanel" style="display:none;">
                        <div class="panel-header">
                            <h3>Document Preview (Page 1)</h3>
                        </div>
                        <div class="preview-stage">
                            <canvas id="compressPreviewCanvas"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Right: Compression Controls & Results -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>2. Compression Options</h2>
                        </div>

                        <div class="form-group">
                            <label for="compressModeSelect" class="form-label">Compression Mode</label>
                            <select id="compressModeSelect" class="form-control">
                                <option value="structural">Lossless Structure Cleanup (Keeps crisp selectable text)</option>
                                <option value="balanced" selected>Balanced Page Optimization (Re-encodes pages at 1.5x / 75% JPEG)</option>
                                <option value="strong">Compact Screen Mode (Re-encodes pages at 1.1x / 55% JPEG)</option>
                            </select>
                            <small id="compressModeNote" class="text-muted" style="display:block;margin-top:8px;font-size:0.82rem;">
                                Balanced mode re-encodes PDF pages as compressed JPEG images, which works best for scanned/image-heavy PDFs. Text selectability is not retained in image re-encoding modes.
                            </small>
                        </div>

                        <button type="button" id="compressExecuteBtn" class="btn btn-primary btn-block btn-lg" disabled>
                            <i class="fa-solid fa-minimize" aria-hidden="true"></i>
                            <span>Compress PDF</span>
                        </button>

                        <div class="progress-panel" id="compressProgressPanel" style="display:none;">
                            <div class="progress-header">
                                <span class="progress-label">Compressing...</span>
                                <span class="progress-percent">0%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill"></div>
                            </div>
                        </div>

                        <div class="result-panel" id="compressResultPanel" style="display:none;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i id="compressResultIcon" class="fa-solid fa-circle-check" style="color:var(--color-success);font-size:1.25rem;" aria-hidden="true"></i>
                                <strong id="compressResultTitle">Compression Result</strong>
                            </div>

                            <p id="compressResultMessage" class="text-muted mt-1" style="font-size:0.88rem;"></p>

                            <div class="result-stats">
                                <div class="result-stat-box">
                                    <span>Original Size</span>
                                    <strong id="compressOriginalSize">0 KB</strong>
                                </div>
                                <div class="result-stat-box">
                                    <span>Resulting Size</span>
                                    <strong id="compressNewSize">0 KB</strong>
                                </div>
                            </div>

                            <button type="button" id="compressDownloadBtn" class="btn btn-primary btn-block">
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                                <span id="compressDownloadLabel">Download Compressed PDF</span>
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