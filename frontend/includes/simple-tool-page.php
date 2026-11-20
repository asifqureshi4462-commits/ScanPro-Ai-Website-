<?php
/**
 * Shared page template for the simple browser PDF tools.
 * Expects $tool = [slug, title, desc, icon, multi(bool), accept, dropTitle, dropHint, runLabel, runIcon]
 */
require_once __DIR__ . '/../../backend/config/app.php';
require_once __DIR__ . '/../../backend/helpers/security.php';

$routePrefix = '../';
$pageTitle = $tool['title'];
$pageDescription = $tool['desc'];
$activePage = 'pdf-tools';
$extraStyles = ['assets/css/pdf-tools.css', 'assets/css/pdf-advanced.css'];
$loadPdfLibs = true;
$loadZipLib = true;
$extraScripts = [
    'assets/js/pdf-core/pdf-utils.js',
    'assets/js/pdf-core/pdf-renderer.js',
    'assets/js/pdf-core/pdf-uploader.js',
    'assets/js/pdf-tools/advanced-tools.js',
    'assets/js/pdf-tools/simple-tools.js',
];

require_once __DIR__ . '/head.php';
require_once __DIR__ . '/header.php';
$multi = !empty($tool['multi']);
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
                        <i class="fa-solid <?= e($tool['icon']) ?>" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h1><?= e($tool['title']) ?></h1>
                        <p><?= e($tool['desc']) ?></p>
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
            <div id="toolAlertContainer" aria-live="polite"></div>

            <div class="tool-grid" id="stRoot" data-tool="<?= e($tool['slug']) ?>" data-multi="<?= $multi ? '1' : '0' ?>">
                <div>
                    <div class="workspace-panel">
                        <div class="panel-header"><h2>1. Upload <?= $multi ? 'Images' : 'PDF Document' ?></h2></div>
                        <div class="dropzone" id="stDrop" role="button" tabindex="0" aria-label="Upload file">
                            <div class="dropzone-icon"><i class="fa-solid <?= $multi ? 'fa-images' : 'fa-file-pdf' ?>" aria-hidden="true"></i></div>
                            <h3><?= e($tool['dropTitle']) ?></h3>
                            <p><?= e($tool['dropHint']) ?></p>
                            <div class="dropzone-actions">
                                <button type="button" class="btn btn-primary btn-sm" id="stBrowse">
                                    <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                                    <span><?= $multi ? 'Choose Images' : 'Choose PDF File' ?></span>
                                </button>
                            </div>
                            <input type="file" id="stInput" accept="<?= e($tool['accept']) ?>" class="sr-only">
                        </div>
                        <div id="stMeta" class="file-meta-bar" style="display:none;">
                            <div class="file-meta-main">
                                <i class="fa-solid <?= $multi ? 'fa-images' : 'fa-file-pdf' ?>" style="color:var(--accent-cyan);" aria-hidden="true"></i>
                                <div>
                                    <div class="file-meta-name" id="stName">file</div>
                                    <div class="file-meta-sub" id="stSub"></div>
                                </div>
                            </div>
                            <button type="button" id="stReset" class="btn btn-outline btn-sm">
                                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span>Reset</span>
                            </button>
                        </div>
                    </div>

                    <div class="workspace-panel" id="stListPanel" style="display:none;">
                        <div class="panel-header">
                            <h3 id="stListTitle">Pages</h3>
                            <span class="text-muted" id="stListHint" style="font-size:0.84rem;"></span>
                        </div>
                        <div class="thumb-grid" id="stList"></div>
                    </div>
                </div>

                <div>
                    <div class="workspace-panel">
                        <div class="panel-header"><h2>2. Options</h2></div>
                        <div id="stOptions"></div>

                        <button type="button" id="stRun" class="btn btn-primary btn-block btn-lg" disabled>
                            <i class="fa-solid <?= e($tool['runIcon']) ?>" aria-hidden="true"></i>
                            <span><?= e($tool['runLabel']) ?></span>
                        </button>

                        <div class="progress-panel" id="stProgress" style="display:none;">
                            <div class="progress-header">
                                <span class="progress-label">Working...</span>
                                <span class="progress-percent">0%</span>
                            </div>
                            <div class="progress-track"><div class="progress-fill"></div></div>
                        </div>

                        <div class="result-panel" id="stResult" style="display:none;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <i class="fa-solid fa-circle-check" style="color:var(--color-success);font-size:1.25rem;" aria-hidden="true"></i>
                                <strong id="stResultText">Done</strong>
                            </div>
                            <button type="button" id="stDownload" class="btn btn-primary btn-block mt-2">
                                <i class="fa-solid fa-download" aria-hidden="true"></i><span>Download</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php require_once __DIR__ . '/ad-slot.php'; renderAdSlot('tool'); ?>

</main>

<?php require_once __DIR__ . '/footer.php'; ?>
