<?php
/**
 * ScanPro AI - Client-Side PDF Library Loader
 *
 * Libraries & Versions:
 * - pdf-lib v1.17.1 (MIT) - PDF creation & modification
 * - pdfjs-dist v3.11.174 (Apache-2.0) - PDF rendering & previews
 * - jszip v3.10.2 (MIT/GPLv3) - Multi-file ZIP packaging (loaded when $loadZipLib is true)
 */
?>
<script>
window.SCANPRO_PDF_LIBS = {
    pdfLibVersion: '1.17.1',
    pdfJsVersion: '3.11.174',
    jsZipVersion: '3.10.2',
    pdfJsWorkerSrc: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js'
};
</script>
<script src="https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/dist/pdf-lib.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js" defer></script>
<?php if (!empty($loadZipLib)): ?>
<script src="https://cdn.jsdelivr.net/npm/jszip@3.10.2/dist/jszip.min.js" defer></script>
<?php endif; ?>