<?php
require_once __DIR__ . '/../../backend/config/app.php';
require_once __DIR__ . '/../../backend/helpers/security.php';

$routePrefix = '../';
$pageTitle = 'JPG to PDF';
$pageDescription = 'Upload JPG or PNG images, preview and reorder them, choose page size and orientation, and create a clean PDF in your browser.';
$activePage = 'pdf-tools';
$extraStyles = ['assets/css/pdf-tools.css'];
$loadPdfLibs = true;
$extraScripts = [
    'assets/js/pdf-core/pdf-utils.js',
    'assets/js/pdf-core/pdf-renderer.js',
    'assets/js/pdf-core/pdf-uploader.js',
    'assets/js/pdf-tools/jpg-to-pdf.js',
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
                    <div class="card-icon card-icon--blue" style="margin-bottom:0;">
                        <i class="fa-solid fa-images" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h1>JPG to PDF</h1>
                        <p>Upload JPG or PNG images, arrange their order, adjust page size &amp; orientation, and export PDF.</p>
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
                <!-- Left: Upload & Reorderable Image Preview Grid -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>1. Select Images (JPG / PNG)</h2>
                            <button type="button" id="jpg2pdfAddMoreBtn" class="btn btn-secondary btn-sm" style="display:none;">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                <span>Add More Images</span>
                            </button>
                        </div>

                        <div
                            class="dropzone"
                            id="jpg2pdfDropzone"
                            role="button"
                            tabindex="0"
                            aria-label="Upload JPG or PNG images"
                        >
                            <div class="dropzone-icon">
                                <i class="fa-solid fa-images" aria-hidden="true"></i>
                            </div>
                            <h3>Drop images here or tap to browse</h3>
                            <p>Pick one or multiple JPG / PNG images from your phone gallery, Files Manager, or computer.</p>
                            <div class="dropzone-actions">
                                <button type="button" class="btn btn-primary btn-sm" id="jpg2pdfBrowseBtn">
                                    <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                    <span>Choose Images</span>
                                </button>
                            </div>
                            <input
                                type="file"
                                id="jpg2pdfFileInput"
                                accept="image/jpeg,image/png,.jpg,.jpeg,.png"
                                multiple
                                class="sr-only"
                            >
                        </div>

                        <div id="jpg2pdfMeta" class="file-meta-bar" style="display:none;">
                            <div class="file-meta-main">
                                <i class="fa-solid fa-images" style="color:var(--accent-cyan);" aria-hidden="true"></i>
                                <div>
                                    <div class="file-meta-name" id="jpg2pdfSummaryTitle">0 images selected</div>
                                    <div class="file-meta-sub" id="jpg2pdfSummarySub">0 KB total</div>
                                </div>
                            </div>
                            <button type="button" id="jpg2pdfResetBtn" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <div class="workspace-panel" id="jpg2pdfPreviewPanel" style="display:none;">
                        <div class="panel-header">
                            <h3>Image Order &amp; Previews</h3>
                            <span class="text-muted" style="font-size:0.82rem;">Drag or use arrows to reorder</span>
                        </div>
                        <div class="thumb-grid" id="jpg2pdfThumbGrid"></div>
                    </div>
                </div>

                <!-- Right: Layout Options & Export -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>2. PDF Page Layout</h2>
                        </div>

                        <div class="form-group">
                            <label for="jpg2pdfPageSize" class="form-label">Page Size</label>
                            <select id="jpg2pdfPageSize" class="form-control">
                                <option value="a4" selected>A4 (210 × 297 mm)</option>
                                <option value="letter">US Letter (8.5 × 11 in)</option>
                                <option value="fit">Fit to Original Image Size</option>
                            </select>
                        </div>

                        <div class="form-group" id="jpg2pdfOrientationGroup">
                            <label for="jpg2pdfOrientation" class="form-label">Page Orientation</label>
                            <select id="jpg2pdfOrientation" class="form-control">
                                <option value="auto" selected>Auto (Match Each Image)</option>
                                <option value="portrait">Portrait (Vertical)</option>
                                <option value="landscape">Landscape (Horizontal)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="jpg2pdfMargin" class="form-label">Page Margin</label>
                            <select id="jpg2pdfMargin" class="form-control">
                                <option value="0">No Margin (Edge-to-Edge)</option>
                                <option value="20" selected>Small Margin (20 pt)</option>
                                <option value="40">Medium Margin (40 pt)</option>
                            </select>
                        </div>

                        <button type="button" id="jpg2pdfExecuteBtn" class="btn btn-primary btn-block btn-lg" disabled>
                            <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                            <span>Create PDF</span>
                        </button>

                        <div class="progress-panel" id="jpg2pdfProgressPanel" style="display:none;">
                            <div class="progress-header">
                                <span class="progress-label">Building PDF...</span>
                                <span class="progress-percent">0%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill"></div>
                            </div>
                        </div>

                        <div class="result-panel" id="jpg2pdfResultPanel" style="display:none;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i class="fa-solid fa-circle-check" style="color:var(--color-success);font-size:1.25rem;" aria-hidden="true"></i>
                                <strong>PDF Created Successfully</strong>
                            </div>

                            <div class="result-stats">
                                <div class="result-stat-box">
                                    <span>Pages</span>
                                    <strong id="jpg2pdfResultPages">0</strong>
                                </div>
                                <div class="result-stat-box">
                                    <span>File Size</span>
                                    <strong id="jpg2pdfResultSize">0 KB</strong>
                                </div>
                            </div>

                            <button type="button" id="jpg2pdfDownloadBtn" class="btn btn-primary btn-block">
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                                <span id="jpg2pdfDownloadLabel">Download PDF</span>
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