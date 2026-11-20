/**
 * ScanPro AI - Tool 9: Extract PDF Pages
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const utils = window.ScanProPdfUtils;
    const renderer = window.ScanProPdfRenderer;
    const uploader = window.ScanProUploader;
    const ui = window.ScanProUI;
    if (!utils || !renderer || !uploader || !ui) return;

    const alertContainer = document.getElementById('toolAlertContainer');
    const dropzoneEl = document.getElementById('extractDropzone');
    const fileInputEl = document.getElementById('extractFileInput');
    const browseBtnEl = document.getElementById('extractBrowseBtn');
    const fileMetaEl = document.getElementById('extractFileMeta');
    const fileNameEl = document.getElementById('extractFileName');
    const fileSubEl = document.getElementById('extractFileSub');
    const resetBtn = document.getElementById('extractResetBtn');

    const previewPanelEl = document.getElementById('extractPreviewPanel');
    const selectedCountEl = document.getElementById('extractSelectedCount');
    const selectAllBtn = document.getElementById('extractSelectAllBtn');
    const selectOddBtn = document.getElementById('extractSelectOddBtn');
    const selectEvenBtn = document.getElementById('extractSelectEvenBtn');
    const clearSelBtn = document.getElementById('extractClearSelBtn');
    const thumbGridEl = document.getElementById('extractThumbGrid');

    const rangeInputEl = document.getElementById('extractRangeInput');
    const applyRangeBtn = document.getElementById('extractApplyRangeBtn');
    const rangeErrorEl = document.getElementById('extractRangeError');
    const selectionSummaryEl = document.getElementById('extractSelectionSummary');
    const selectedListTextEl = document.getElementById('extractSelectedListText');

    const executeBtn = document.getElementById('extractExecuteBtn');
    const progressPanelEl = document.getElementById('extractProgressPanel');
    const resultPanelEl = document.getElementById('extractResultPanel');
    const resultPagesEl = document.getElementById('extractResultPages');
    const resultSizeEl = document.getElementById('extractResultSize');
    const downloadBtn = document.getElementById('extractDownloadBtn');
    const downloadLabelEl = document.getElementById('extractDownloadLabel');

    let currentBytes = null;
    let pdfJsDoc = null;
    let totalPages = 0;
    const selectedPages = new Set();
    let outputBlob = null;
    let outputFilename = '';

    const clearResult = () => {
        outputBlob = null;
        outputFilename = '';
        resultPanelEl.style.display = 'none';
    };

    const syncSelectionUi = () => {
        const sorted = Array.from(selectedPages).sort((a, b) => a - b);
        selectedCountEl.textContent = String(sorted.length);
        executeBtn.disabled = sorted.length === 0 || !currentBytes;

        if (totalPages > 0) {
            selectionSummaryEl.style.display = 'flex';
            if (sorted.length === 0) {
                selectedListTextEl.textContent = 'None selected';
            } else if (sorted.length <= 12) {
                selectedListTextEl.textContent = sorted.join(', ');
            } else {
                selectedListTextEl.textContent = `${sorted.slice(0, 10).join(', ')} ... (${sorted.length} pages)`;
            }
        } else {
            selectionSummaryEl.style.display = 'none';
        }

        thumbGridEl.querySelectorAll('.thumb-card[data-page]').forEach((card) => {
            const p = parseInt(card.dataset.page || '0', 10);
            const isSel = selectedPages.has(p);
            card.classList.toggle('is-selected', isSel);
            const checkEl = card.querySelector('.thumb-check');
            if (checkEl) {
                checkEl.innerHTML = isSel
                    ? '<i class="fa-solid fa-circle-check" style="color:var(--accent-cyan);" aria-hidden="true"></i>'
                    : '<i class="fa-regular fa-circle" style="color:var(--text-muted);" aria-hidden="true"></i>';
            }
        });
    };

    const resetWorkspace = async () => {
        if (pdfJsDoc) {
            await renderer.destroyDocument(pdfJsDoc);
            pdfJsDoc = null;
        }
        currentBytes = null;
        totalPages = 0;
        selectedPages.clear();
        clearResult();
        utils.hideProgress(progressPanelEl);
        ui.clearAlert(alertContainer);
        if (rangeErrorEl) rangeErrorEl.textContent = '';
        rangeInputEl.value = '';
        applyRangeBtn.disabled = true;
        thumbGridEl.innerHTML = '';
        fileMetaEl.style.display = 'none';
        previewPanelEl.style.display = 'none';
        selectionSummaryEl.style.display = 'none';
        dropzoneEl.style.display = 'block';
        executeBtn.disabled = true;
    };

    const renderThumbnails = async () => {
        thumbGridEl.innerHTML = '';
        previewPanelEl.style.display = 'block';

        for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
            const card = document.createElement('div');
            card.className = 'thumb-card is-selectable';
            card.dataset.page = String(pageNum);
            card.setAttribute('role', 'button');
            card.setAttribute('tabindex', '0');

            const canvasWrap = document.createElement('div');
            canvasWrap.className = 'thumb-canvas-wrap';
            const canvas = document.createElement('canvas');
            canvasWrap.appendChild(canvas);

            const footer = document.createElement('div');
            footer.className = 'thumb-footer';
            footer.innerHTML = `<span class="thumb-badge">Page ${pageNum}</span><span class="thumb-check"></span>`;

            card.appendChild(canvasWrap);
            card.appendChild(footer);
            thumbGridEl.appendChild(card);

            const togglePage = () => {
                if (selectedPages.has(pageNum)) {
                    selectedPages.delete(pageNum);
                } else {
                    selectedPages.add(pageNum);
                }
                clearResult();
                syncSelectionUi();
            };

            card.addEventListener('click', togglePage);
            card.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    togglePage();
                }
            });

            try {
                await renderer.renderPageToCanvas(pdfJsDoc, pageNum, canvas, { targetWidth: 180 });
            } catch (err) {
                canvasWrap.innerHTML = '<span style="color:#475569;font-size:0.75rem;">Preview failed</span>';
            }
        }

        syncSelectionUi();
    };

    const handleFileSelected = async (files) => {
        await resetWorkspace();
        const file = files[0];
        if (!file) return;

        try {
            utils.ensurePdfLibraries(false);
        } catch (err) {
            ui.showAlert(alertContainer, 'error', err.message);
            return;
        }

        const check = await utils.validatePdfFile(file);
        if (!check.ok) {
            ui.showAlert(alertContainer, 'error', check.message);
            return;
        }

        try {
            const pdfLibDoc = await window.PDFLib.PDFDocument.load(check.bytes);
            totalPages = pdfLibDoc.getPageCount();
            if (totalPages < 1) {
                ui.showAlert(alertContainer, 'error', 'This PDF contains 0 pages.');
                return;
            }

            currentBytes = check.bytes;
            pdfJsDoc = await renderer.loadDocument(check.bytes);
            selectedPages.add(1);

            fileNameEl.textContent = file.name;
            fileSubEl.textContent = `${totalPages} page${totalPages !== 1 ? 's' : ''} • ${utils.formatBytes(file.size)}`;
            fileMetaEl.style.display = 'flex';
            dropzoneEl.style.display = 'none';
            applyRangeBtn.disabled = false;

            const largeWarn = utils.checkLargeFileWarning(file);
            if (largeWarn) {
                ui.showAlert(alertContainer, 'warning', largeWarn);
            }

            await renderThumbnails();
        } catch (err) {
            ui.showAlert(alertContainer, 'error', utils.describePdfError(err));
        }
    };

    uploader.bind({
        dropzoneEl,
        inputEl: fileInputEl,
        browseBtnEl,
        multiple: false,
        onFilesSelected: handleFileSelected
    });

    selectAllBtn.addEventListener('click', () => {
        for (let p = 1; p <= totalPages; p++) selectedPages.add(p);
        clearResult();
        syncSelectionUi();
    });

    selectOddBtn.addEventListener('click', () => {
        selectedPages.clear();
        for (let p = 1; p <= totalPages; p += 2) selectedPages.add(p);
        clearResult();
        syncSelectionUi();
    });

    selectEvenBtn.addEventListener('click', () => {
        selectedPages.clear();
        for (let p = 2; p <= totalPages; p += 2) selectedPages.add(p);
        clearResult();
        syncSelectionUi();
    });

    clearSelBtn.addEventListener('click', () => {
        selectedPages.clear();
        clearResult();
        syncSelectionUi();
    });

    applyRangeBtn.addEventListener('click', () => {
        if (rangeErrorEl) rangeErrorEl.textContent = '';
        const parsed = utils.parsePageRanges(rangeInputEl.value, totalPages);
        if (!parsed.ok) {
            if (rangeErrorEl) rangeErrorEl.textContent = parsed.error;
            return;
        }
        selectedPages.clear();
        parsed.flat.forEach((p) => selectedPages.add(p));
        clearResult();
        syncSelectionUi();
    });

    resetBtn.addEventListener('click', resetWorkspace);

    executeBtn.addEventListener('click', async () => {
        if (!currentBytes || selectedPages.size === 0) {
            ui.showAlert(alertContainer, 'error', 'Please select at least 1 page to extract.');
            return;
        }

        const pagesToExtract = Array.from(selectedPages).sort((a, b) => a - b);
        ui.clearAlert(alertContainer);
        clearResult();
        ui.setButtonLoading(executeBtn, true, 'Extracting...');
        utils.setProgress(progressPanelEl, 15, 'Loading source PDF...');

        try {
            const srcDoc = await window.PDFLib.PDFDocument.load(currentBytes);
            const outDoc = await window.PDFLib.PDFDocument.create();

            utils.setProgress(progressPanelEl, 55, `Copying ${pagesToExtract.length} selected page(s)...`);
            const zeroIndices = pagesToExtract.map((p) => p - 1);
            const copiedPages = await outDoc.copyPages(srcDoc, zeroIndices);
            copiedPages.forEach((p) => outDoc.addPage(p));

            utils.setProgress(progressPanelEl, 92, 'Saving extracted PDF...');
            const outBytes = await outDoc.save();
            outputBlob = new Blob([outBytes], { type: 'application/pdf' });
            outputFilename = utils.buildDownloadName('ExtractedPDF', 'pdf');

            utils.setProgress(progressPanelEl, 100, 'Complete');
            resultPagesEl.textContent = String(pagesToExtract.length);
            resultSizeEl.textContent = utils.formatBytes(outputBlob.size);
            downloadLabelEl.textContent = `Download ${outputFilename}`;
            resultPanelEl.style.display = 'block';
        } catch (err) {
            utils.hideProgress(progressPanelEl);
            ui.showAlert(alertContainer, 'error', utils.describePdfError(err));
        } finally {
            ui.setButtonLoading(executeBtn, false);
        }
    });

    downloadBtn.addEventListener('click', () => {
        if (!outputBlob || !outputFilename) return;
        utils.triggerDownload(outputBlob, outputFilename, 'application/pdf');
    });
});