<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/helpers/security.php';

$pageTitle = 'Web Document Workspace';
$pageDescription = 'Upload, edit and export documents right in your browser: crop, rotate, filters, OCR text extraction, PDF and image export. Files never leave your device.';
$activePage = 'workspace';
$extraStyles = ['assets/css/workspace.css'];
$loadPdfLibs = true;
$loadZipLib = true;
$extraScripts = ['assets/js/workspace.js'];

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <section class="tool-topbar cyber-grid-bg">
        <div class="container">
            <div class="ws-title-row">
                <div>
                    <span class="eyebrow">WEB WORKSPACE</span>
                    <h1>Edit Your Documents <span class="text-gradient">Right Here</span></h1>
                    <p>Upload images or PDFs, adjust them, extract text and download. No app needed.</p>
                </div>
                <span class="privacy-pill"><i class="fa-solid fa-lock" aria-hidden="true"></i> Processed in your browser. Files are not uploaded to our server.</span>
            </div>
        </div>
    </section>

    <section class="section" style="padding-top:24px;">
        <div class="container">
            <!-- Empty state / upload -->
            <div class="ws-drop" id="wsDrop" tabindex="0" role="button" aria-label="Upload documents">
                <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                <strong>Drop files here or tap to upload</strong>
                <span>JPG, PNG, WebP images and PDF files (up to 30 pages)</span>
                <input type="file" id="wsFile" accept="image/*,application/pdf" multiple hidden>
            </div>

            <!-- Editor -->
            <div class="ws-app" id="wsApp" hidden>
                <div class="ws-toolbar" role="toolbar" aria-label="Editing tools">
                    <button type="button" class="btn btn-secondary btn-sm" id="wsAdd"><i class="fa-solid fa-plus"></i><span>Add</span></button>
                    <button type="button" class="btn btn-secondary btn-sm" id="wsRotL" title="Rotate left"><i class="fa-solid fa-rotate-left"></i><span>Left</span></button>
                    <button type="button" class="btn btn-secondary btn-sm" id="wsRotR" title="Rotate right"><i class="fa-solid fa-rotate-right"></i><span>Right</span></button>
                    <button type="button" class="btn btn-secondary btn-sm" id="wsFlip" title="Flip horizontally"><i class="fa-solid fa-left-right"></i><span>Flip</span></button>
                    <button type="button" class="btn btn-secondary btn-sm" id="wsCrop"><i class="fa-solid fa-crop-simple"></i><span>Crop</span></button>
                    <button type="button" class="btn btn-secondary btn-sm" id="wsUndo"><i class="fa-solid fa-arrow-rotate-left"></i><span>Undo</span></button>
                    <button type="button" class="btn btn-secondary btn-sm" id="wsOcr"><i class="fa-solid fa-font"></i><span>Extract Text</span></button>
                    <button type="button" class="btn btn-danger btn-sm" id="wsDelete"><i class="fa-solid fa-trash"></i><span>Remove</span></button>
                </div>

                <div class="ws-grid">
                    <div class="ws-stage">
                        <div class="ws-canvas-wrap" id="wsCanvasWrap">
                            <canvas id="wsCanvas"></canvas>
                            <div class="ws-crop-box" id="wsCropBox" hidden></div>
                        </div>
                        <div class="ws-crop-actions" id="wsCropActions" hidden>
                            <span>Drag on the image to choose the area</span>
                            <button type="button" class="btn btn-primary btn-sm" id="wsCropApply">Apply Crop</button>
                            <button type="button" class="btn btn-outline btn-sm" id="wsCropCancel">Cancel</button>
                        </div>
                        <div class="ws-pages" id="wsPages" aria-label="Pages"></div>
                    </div>

                    <aside class="ws-panel">
                        <h3>Adjust</h3>
                        <label class="ws-slider">Brightness <output id="oB">0</output>
                            <input type="range" id="rB" min="-50" max="50" value="0"></label>
                        <label class="ws-slider">Contrast <output id="oC">0</output>
                            <input type="range" id="rC" min="-50" max="80" value="0"></label>
                        <label class="ws-slider">Saturation <output id="oS">0</output>
                            <input type="range" id="rS" min="-100" max="100" value="0"></label>

                        <h3>Filters</h3>
                        <div class="ws-filters" id="wsFilters">
                            <button type="button" data-f="original" class="active">Original</button>
                            <button type="button" data-f="magic">Magic Enhance</button>
                            <button type="button" data-f="lighten">Lighten</button>
                            <button type="button" data-f="gray">Grayscale</button>
                            <button type="button" data-f="bw">B&amp;W Clean</button>
                            <button type="button" data-f="eco">Eco Print</button>
                        </div>

                        <h3>Export</h3>
                        <div class="ws-export">
                            <label>Format
                                <select id="wsFormat" class="ws-select">
                                    <option value="pdf">PDF document</option>
                                    <option value="png">PNG image</option>
                                    <option value="jpg">JPG image</option>
                                    <option value="txt">Text (.txt)</option>
                                </select></label>
                            <label>Pages
                                <select id="wsScope" class="ws-select">
                                    <option value="all">All pages</option>
                                    <option value="current">Current page only</option>
                                </select></label>
                            <button type="button" class="btn btn-primary btn-block" id="wsDownload">
                                <i class="fa-solid fa-download"></i><span>Download / Export</span>
                            </button>
                        </div>
                    </aside>
                </div>

                <div class="ws-ocr" id="wsOcrBox" hidden>
                    <div class="ws-ocr-head">
                        <h3>Extracted Text</h3>
                        <select id="wsLang" class="ws-select" aria-label="Language">
                            <option value="eng">English</option>
                            <option value="urd">Urdu</option>
                            <option value="hin">Hindi</option>
                            <option value="ara">Arabic</option>
                        </select>
                    </div>
                    <p class="ws-status" id="wsOcrStatus"></p>
                    <textarea id="wsOcrText" class="ws-textarea" rows="8" placeholder="Text from the current page appears here. You can edit it."></textarea>
                    <button type="button" class="btn btn-secondary btn-sm" id="wsCopy"><i class="fa-solid fa-copy"></i><span>Copy Text</span></button>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
