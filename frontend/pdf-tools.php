<?php
require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/helpers/security.php';
require_once __DIR__ . '/../backend/helpers/site_runtime.php';

$pageTitle = 'PDF & File Tools';
$pageDescription = 'Use working, private, browser-based ScanPro AI tools: Files to ZIP/7Z, Merge, Split, Compress, PDF to JPG, JPG to PDF, Rotate, Watermark, Page Numbers, Extract, Reorder, Sign, Redact & more.';
$activePage = 'pdf-tools';
$extraStyles = ['assets/css/pdf-tools.css'];

$toolsCatalog = [
    [
        'slug'  => 'files-to-zip',
        'name'  => 'Files to ZIP / 7Z',
        'desc'  => 'Compress any files or folders into ZIP, 7Z, TAR.GZ, TAR.XZ, or TAR archives with password & compression level options.',
        'icon'  => 'fa-file-zipper',
        'tone'  => 'cyan',
        'badge' => 'Archive',
        'href'  => 'tools/files-to-zip.php',
        'keys'  => 'zip 7z tar gz xz bz2 compress archive password folder files',
    ],
    [
        'slug'  => 'merge-pdf',
        'name'  => 'Merge PDF',
        'desc'  => 'Combine multiple PDF files, reorder them in your preferred sequence, and download a single unified PDF.',
        'icon'  => 'fa-object-group',
        'tone'  => 'pink',
        'badge' => 'Organize',
        'href'  => 'tools/merge-pdf.php',
        'keys'  => 'merge combine join multiple pdf',
    ],
    [
        'slug'  => 'split-pdf',
        'name'  => 'Split PDF',
        'desc'  => 'Split a PDF by custom page ranges or intervals into separate PDFs and download individually or as a ZIP.',
        'icon'  => 'fa-scissors',
        'tone'  => 'cyan',
        'badge' => 'Organize',
        'href'  => 'tools/split-pdf.php',
        'keys'  => 'split cut range zip separate pdf',
    ],
    [
        'slug'  => 'compress-pdf',
        'name'  => 'Compress PDF',
        'desc'  => 'Optimize PDF file size with configurable profiles and inspect exact original vs resulting file sizes.',
        'icon'  => 'fa-minimize',
        'tone'  => 'teal',
        'badge' => 'Optimize',
        'href'  => 'tools/compress-pdf.php',
        'keys'  => 'compress shrink reduce size optimize pdf',
    ],
    [
        'slug'  => 'pdf-to-jpg',
        'name'  => 'PDF to JPG',
        'desc'  => 'Preview PDF pages, select specific pages, choose JPEG quality, and download individual JPGs or a ZIP.',
        'icon'  => 'fa-file-image',
        'tone'  => 'purple',
        'badge' => 'Convert',
        'href'  => 'tools/pdf-to-jpg.php',
        'keys'  => 'pdf to jpg image convert export zip',
    ],
    [
        'slug'  => 'jpg-to-pdf',
        'name'  => 'JPG to PDF',
        'desc'  => 'Upload multiple JPG or PNG images, reorder them, choose page size and orientation, and export PDF.',
        'icon'  => 'fa-images',
        'tone'  => 'blue',
        'badge' => 'Convert',
        'href'  => 'tools/jpg-to-pdf.php',
        'keys'  => 'jpg png images to pdf convert create',
    ],
    [
        'slug'  => 'rotate-pdf',
        'name'  => 'Rotate PDF',
        'desc'  => 'Preview every page and rotate individual pages or the entire document by 90°, 180°, or 270°.',
        'icon'  => 'fa-rotate-right',
        'tone'  => 'amber',
        'badge' => 'Edit',
        'href'  => 'tools/rotate-pdf.php',
        'keys'  => 'rotate 90 180 270 orientation turn pdf',
    ],
    [
        'slug'  => 'watermark-pdf',
        'name'  => 'Watermark PDF',
        'desc'  => 'Add custom text watermarks with live preview and position, size, opacity, color, and rotation controls.',
        'icon'  => 'fa-stamp',
        'tone'  => 'cyan',
        'badge' => 'Brand',
        'href'  => 'tools/watermark-pdf.php',
        'keys'  => 'watermark text stamp opacity rotation pdf',
    ],
    [
        'slug'  => 'add-page-numbers',
        'name'  => 'Add Page Numbers',
        'desc'  => 'Stamp clean page numbers across your PDF with selectable position, font size, format, and starting number.',
        'icon'  => 'fa-list-ol',
        'tone'  => 'green',
        'badge' => 'Edit',
        'href'  => 'tools/add-page-numbers.php',
        'keys'  => 'page numbers numbering footer header start pdf',
    ],
    [
        'slug'  => 'extract-pages',
        'name'  => 'Extract PDF Pages',
        'desc'  => 'Browse page thumbnails, select only the pages you need, and export them into a new PDF file.',
        'icon'  => 'fa-file-export',
        'tone'  => 'pink',
        'badge' => 'Pages',
        'href'  => 'tools/extract-pages.php',
        'keys'  => 'extract select keep pages pdf',
    ],
    [
        'slug'  => 'reorder-pages',
        'name'  => 'Reorder PDF Pages',
        'desc'  => 'Drag and drop page thumbnails (or use mobile arrows), preview the new sequence, and export PDF.',
        'icon'  => 'fa-arrow-down-up-across-line',
        'tone'  => 'purple',
        'badge' => 'Pages',
        'href'  => 'tools/reorder-pages.php',
        'keys'  => 'reorder rearrange sort drag drop pages pdf',
    ],
    [
        'slug'  => 'remove-pages',
        'name'  => 'Delete PDF Pages',
        'desc'  => 'Remove unwanted pages from a PDF and download the rest as a new file.',
        'icon'  => 'fa-trash-can',
        'tone'  => 'pink',
        'badge' => 'Pages',
        'href'  => 'tools/remove-pages.php',
        'keys'  => 'delete remove pages pdf',
    ],
    [
        'slug'  => 'crop-pdf',
        'name'  => 'Crop PDF',
        'desc'  => 'Trim margins from all or selected pages of a PDF.',
        'icon'  => 'fa-crop-simple',
        'tone'  => 'teal',
        'badge' => 'Layout',
        'href'  => 'tools/crop-pdf.php',
        'keys'  => 'crop trim margins pdf',
    ],
    [
        'slug'  => 'pdf-to-text',
        'name'  => 'PDF to Text',
        'desc'  => 'Extract the selectable text from a PDF into a plain .txt file.',
        'icon'  => 'fa-file-lines',
        'tone'  => 'cyan',
        'badge' => 'Convert',
        'href'  => 'tools/pdf-to-text.php',
        'keys'  => 'text extract txt pdf',
    ],
    [
        'slug'  => 'pdf-to-word',
        'name'  => 'PDF to Word',
        'desc'  => 'Convert a text PDF into an editable Word (.docx) file with headings and paragraphs.',
        'icon'  => 'fa-file-word',
        'tone'  => 'purple',
        'badge' => 'Convert',
        'href'  => 'tools/pdf-to-word.php',
        'keys'  => 'word docx convert pdf',
    ],
    [
        'slug'  => 'pdf-to-markdown',
        'name'  => 'PDF to Markdown',
        'desc'  => 'Turn a PDF into clean Markdown with headings and lists for notes, docs and AI tools.',
        'icon'  => 'fa-hashtag',
        'tone'  => 'teal',
        'badge' => 'Convert',
        'href'  => 'tools/pdf-to-markdown.php',
        'keys'  => 'markdown md convert pdf',
    ],
    [
        'slug'  => 'repair-pdf',
        'name'  => 'Repair PDF',
        'desc'  => 'Rebuild a damaged or broken PDF into a clean file you can open again.',
        'icon'  => 'fa-screwdriver-wrench',
        'tone'  => 'pink',
        'badge' => 'Repair',
        'href'  => 'tools/repair-pdf.php',
        'keys'  => 'repair fix broken corrupt pdf',
    ],
    [
        'slug'  => 'compare-pdf',
        'name'  => 'Compare PDF',
        'desc'  => 'Compare the text of two PDF versions and get a report of what changed.',
        'icon'  => 'fa-code-compare',
        'tone'  => 'cyan',
        'badge' => 'Review',
        'href'  => 'tools/compare-pdf.php',
        'keys'  => 'compare diff difference versions pdf',
    ],
    [
        'slug'  => 'sign-pdf',
        'name'  => 'Sign PDF',
        'desc'  => 'Draw or type your signature and place it on any page of your PDF.',
        'icon'  => 'fa-signature',
        'tone'  => 'purple',
        'badge' => 'Security',
        'href'  => 'tools/sign-pdf.php',
        'keys'  => 'sign signature pdf',
    ],
    [
        'slug'  => 'redact-pdf',
        'name'  => 'Redact PDF',
        'desc'  => 'Black out sensitive text or images and permanently remove them from the PDF.',
        'icon'  => 'fa-eraser',
        'tone'  => 'pink',
        'badge' => 'Security',
        'href'  => 'tools/redact-pdf.php',
        'keys'  => 'redact blackout hide private pdf',
    ],
];

// Filter and annotate tools dynamically from Admin Feature Control Center
$visibleTools = [];
foreach ($toolsCatalog as $tool) {
    $avail = getWebToolAvailability($tool['slug']);
    if (!$avail['enabled'] && $avail['status'] !== 'maintenance') {
        continue; // Hidden when disabled or restricted to Android only
    }
    $tool['_availability'] = $avail;
    $visibleTools[] = $tool;
}

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
?>

<main>
    <div class="container" style="padding-top: 16px;">
        <?php require __DIR__ . '/includes/alerts.php'; ?>
    </div>

    <section class="page-hero cyber-grid-bg">
        <div class="container">
            <span class="eyebrow">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                <span>PRIVATE CLIENT-SIDE PDF &amp; ARCHIVE WORKSPACE</span>
            </span>
            <h1>All <?= count($visibleTools) ?> <span class="text-gradient">PDF &amp; Archive Tools</span> Ready in Your Browser</h1>
            <p>
                Every tool below runs locally in your browser—your files stay private on your device and never upload to our server.
            </p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="form-group" style="max-width:520px;margin:0 auto 28px;">
                <div class="input-wrap">
                    <input
                        type="search"
                        id="pdfToolsFilterInput"
                        class="form-control"
                        placeholder="Filter tools (e.g. zip, 7z, merge, split, compress, word, sign, redact)..."
                        aria-label="Filter PDF and archive tools"
                    >
                </div>
            </div>

            <div class="pdf-hub-grid" id="pdfToolsHubGrid">
                <?php foreach ($visibleTools as $tool): ?>
                    <?php
                    $avail = $tool['_availability'];
                    $isMaint = ($avail['status'] === 'maintenance');
                    ?>
                    <article
                        class="pdf-hub-card"
                        data-tool-search="<?= e(strtolower($tool['name'] . ' ' . $tool['desc'] . ' ' . $tool['keys'])) ?>"
                    >
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
                            <div class="card-icon card-icon--<?= e($tool['tone']) ?>">
                                <i class="fa-solid <?= e($tool['icon']) ?>" aria-hidden="true"></i>
                            </div>
                            <?php if ($isMaint): ?>
                                <span class="card-badge card-badge--pro">Maintenance</span>
                            <?php else: ?>
                                <span class="card-badge card-badge--cyan"><?= e($tool['badge']) ?></span>
                            <?php endif; ?>
                        </div>
                        <h3><?= e($tool['name']) ?></h3>
                        <p><?= e($isMaint ? $avail['message'] : $tool['desc']) ?></p>
                        <?php if ($isMaint): ?>
                            <button type="button" class="btn btn-secondary btn-block" disabled>
                                <i class="fa-solid fa-wrench" aria-hidden="true"></i>
                                <span>Under Maintenance</span>
                            </button>
                        <?php else: ?>
                            <a href="<?= e($tool['href']) ?>" class="btn btn-primary btn-block">
                                <span>Open Tool</span>
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>

            <p id="pdfToolsEmptyFilter" class="text-center text-muted mt-4" style="display:none;">
                No tools matched your search query.
            </p>
        </div>
    </section>
    <?php require_once __DIR__ . '/includes/ad-slot.php'; renderAdSlot('pdf-tools'); ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>