<?php
require_once __DIR__ . '/../../backend/config/app.php';
require_once __DIR__ . '/../../backend/helpers/security.php';

$routePrefix = '../';
$pageTitle = 'Watermark PDF';
$pageDescription = 'Add a custom text watermark to every PDF page with live preview and position, size, opacity, and rotation controls.';
$activePage = 'pdf-tools';
$extraStyles = ['assets/css/pdf-tools.css'];
$loadPdfLibs = true;
$extraScripts = [
    'assets/js/pdf-core/pdf-utils.js',
    'assets/js/pdf-core/pdf-renderer.js',
    'assets/js/pdf-core/pdf-uploader.js',
    'assets/js/pdf-tools/watermark-pdf.js',
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
                        <i class="fa-solid fa-stamp" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h1>Watermark PDF</h1>
                        <p>Configure watermark text, position, size, opacity, and rotation with a live page preview.</p>
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
                <!-- Left: Upload & Live Watermark Preview -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>1. Upload PDF Document</h2>
                        </div>

                        <div
                            class="dropzone"
                            id="wmDropzone"
                            role="button"
                            tabindex="0"
                            aria-label="Upload PDF to watermark"
                        >
                            <div class="dropzone-icon">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                            </div>
                            <h3>Drop your PDF here or tap to browse</h3>
                            <p>Pick a PDF document from your phone storage, Files Manager, or computer.</p>
                            <div class="dropzone-actions">
                                <button type="button" class="btn btn-primary btn-sm" id="wmBrowseBtn">
                                    <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                    <span>Choose PDF File</span>
                                </button>
                            </div>
                            <input type="file" id="wmFileInput" accept="application/pdf,.pdf" class="sr-only">
                        </div>

                        <div id="wmFileMeta" class="file-meta-bar" style="display:none;">
                            <div class="file-meta-main">
                                <i class="fa-solid fa-file-pdf" style="color:var(--accent-cyan);" aria-hidden="true"></i>
                                <div>
                                    <div class="file-meta-name" id="wmFileName">document.pdf</div>
                                    <div class="file-meta-sub" id="wmFileSub">0 pages • 0 KB</div>
                                </div>
                            </div>
                            <button type="button" id="wmResetBtn" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                <span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <div class="workspace-panel" id="wmPreviewPanel" style="display:none;">
                        <div class="panel-header">
                            <h3>Live Watermark Preview (Page 1)</h3>
                        </div>
                        <div class="preview-stage">
                            <canvas id="wmPreviewCanvas"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Right: Watermark Controls -->
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header">
                            <h2>2. Watermark Settings</h2>
                        </div>

                        <div class="form-group">
                            <label for="wmTextInput" class="form-label">Watermark Text</label>
                            <input
                                type="text"
                                id="wmTextInput"
                                class="form-control"
                                value="CONFIDENTIAL"
                                maxlength="50"
                                placeholder="Enter watermark text..."
                            >
                            <span class="form-error-text" id="wmTextError"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Position</label>
                            <div class="position-grid" id="wmPositionGrid">
                                <button type="button" class="position-btn" data-pos="top-left">Top Left</button>
                                <button type="button" class="position-btn" data-pos="top-center">Top Center</button>
                                <button type="button" class="position-btn" data-pos="top-right">Top Right</button>
                                <button type="button" class="position-btn" data-pos="middle-left">Mid Left</button>
                                <button type="button" class="position-btn active" data-pos="center">Center</button>
                                <button type="button" class="position-btn" data-pos="middle-right">Mid Right</button>
                                <button type="button" class="position-btn" data-pos="bottom-left">Bottom Left</button>
                                <button type="button" class="position-btn" data-pos="bottom-center">Bottom Mid</button>
                                <button type="button" class="position-btn" data-pos="bottom-right">Bottom Right</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="wmColorSelect" class="form-label">Color</label>
                            <select id="wmColorSelect" class="form-control">
                                <option value="gray" selected>Slate Gray</option>
                                <option value="red">Alert Red</option>
                                <option value="blue">Electric Blue</option>
                                <option value="cyan">Cyber Teal</option>
                                <option value="black">Dark Black</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="wmSizeRange" class="form-label">
                                Font Size: <strong id="wmSizeValue">46 pt</strong>
                            </label>
                            <input type="range" id="wmSizeRange" class="range-control" min="16" max="96" value="46">
                        </div>

                        <div class="form-group">
                            <label for="wmOpacityRange" class="form-label">
                                Opacity: <strong id="wmOpacityValue">30%</strong>
                            </label>
                            <input type="range" id="wmOpacityRange" class="range-control" min="10" max="90" value="30">
                        </div>

                        <div class="form-group">
                            <label for="wmRotationRange" class="form-label">
                                Rotation Angle: <strong id="wmRotationValue">-35°</strong>
                            </label>
                            <input type="range" id="wmRotationRange" class="range-control" min="-90" max="90" value="-35">
                        </div>

                        <button type="button" id="wmExecuteBtn" class="btn btn-primary btn-block btn-lg" disabled>
                            <i class="fa-solid fa-stamp" aria-hidden="true"></i>
                            <span>Apply Watermark</span>
                        </button>

                        <div class="progress-panel" id="wmProgressPanel" style="display:none;">
                            <div class="progress-header">
                                <span class="progress-label">Applying watermark...</span>
                                <span class="progress-percent">0%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill"></div>
                            </div>
                        </div>

                        <div class="result-panel" id="wmResultPanel" style="display:none;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i class="fa-solid fa-circle-check" style="color:var(--color-success);font-size:1.25rem;" aria-hidden="true"></i>
                                <strong>Watermarked PDF Ready</strong>
                            </div>

                            <div class="result-stats">
                                <div class="result-stat-box">
                                    <span>Pages Stamped</span>
                                    <strong id="wmResultPages">0</strong>
                                </div>
                                <div class="result-stat-box">
                                    <span>File Size</span>
                                    <strong id="wmResultSize">0 KB</strong>
                                </div>
                            </div>

                            <button type="button" id="wmDownloadBtn" class="btn btn-primary btn-block">
                                <i class="fa-solid fa-download" aria-hidden="true"></i>
                                <span id="wmDownloadLabel">Download Watermarked PDF</span>
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