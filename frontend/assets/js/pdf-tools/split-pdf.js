/**
 * ScanPro AI - Tool 2: Split PDF
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const utils = window.ScanProPdfUtils;
    const renderer = window.ScanProPdfRenderer;
    const uploader = window.ScanProUploader;
    const ui = window.ScanProUI;
    if (!utils || !renderer || !uploader || !ui) return;

    const alertContainer = document.getElementById('toolAlertContainer');
    const dropzoneEl = document.getElementById('splitDropzone');
    const fileInputEl = document.getElementById('splitFileInput');
    const browseBtnEl = document.getElementById('splitBrowseBtn');
    const fileMetaEl = document.getElementById('splitFileMeta');
    const fileNameEl = document.getElementById('splitFileName');
    const fileSubEl = document.getElementById('splitFileSub');
    const resetBtn = document.getElementById('splitResetBtn');
    const previewPanelEl = document.getElementById('splitPreviewPanel');
    const pageCountHintEl = document.getElementById('splitPageCountHint');
    const thumbGridEl = document.getElementById('splitThumbGrid');

    const modeSelectEl = document.getElementById('splitModeSelect');
    const rangesGroupEl = document.getElementById('splitRangesGroup');
    const rangesInputEl = document.getElementById('splitRangesInput');
    const rangeErrorEl = document.getElementById('splitRangeError');
    const fixedGroupEl = document.getElementById('splitFixedGroup');
    const fixedInputEl = document.getElementById('splitFixedInput');
    const fixedErrorEl = document.getElementById('splitFixedError');

    const executeBtn = document.getElementById('splitExecuteBtn');
    const progressPanelEl = document.getElementById('splitProgressPanel');
    const resultPanelEl = document.getElementById('splitResultPanel');
    const resultSummaryEl = document.getElementById('splitResultSummary');
    const downloadZipBtn = document.getElementById('splitDownloadZipBtn');
    const downloadZipLabelEl = document.getElementById('splitDownloadZipLabel');
    const outputListEl = document.getElementById('splitOutputList');

    let currentFile = null;
    let currentBytes = null;
    let totalPages = 0;
    let pdfJsDoc = null;
    let splitOutputs = [];

    const clearOutputs = () => {
        splitOutputs = [];
        outputListEl.innerHTML = '';
        resultPanelEl.style.display = 'none';
    };

    const resetWorkspace = async () => {
        if (pdfJsDoc) {
            await renderer.destroyDocument(pdfJsDoc);
            pdfJsDoc = null;
        }
        currentFile = null;
        currentBytes = null;
        totalPages = 0;
        clearOutputs();
        utils.hideProgress(progressPanelEl);
        ui.clearAlert(alertContainer);
        if (rangeErrorEl) rangeErrorEl.textContent = '';
        if (fixedErrorEl) fixedErrorEl.textContent = '';
        thumbGridEl.innerHTML = '';
        fileMetaEl.style.display = 'none';
        previewPanelEl.style.display = 'none';
        dropzoneEl.style.display = 'block';
        executeBtn.disabled = true;
    };

    const syncModeVisibility = () => {
        const mode = modeSelectEl.value;
        rangesGroupEl.style.display = mode === 'ranges' ? 'block' : 'none';
        fixedGroupEl.style.display = mode === 'fixed' ? 'block' : 'none';
    };

    modeSelectEl.addEventListener('change', syncModeVisibility);
    syncModeVisibility();

    const renderThumbnails = async () => {
        thumbGridEl.innerHTML = '';
        previewPanelEl.style.display = 'block';
        pageCountHintEl.textContent = `${totalPages} page${totalPages !== 1 ? 's' : ''}`;

        for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
            const card = document.createElement('div');
            card.className = 'thumb-card';

            const canvasWrap = document.createElement('div');
            canvasWrap.className = 'thumb-canvas-wrap';
            const canvas = document.createElement('canvas');
            canvasWrap.appendChild(canvas);

            const footer = document.createElement('div');
            footer.className = 'thumb-footer';
            footer.innerHTML = `<span class="thumb-badge">Page ${pageNum}</span>`;

            card.appendChild(canvasWrap);
            card.appendChild(footer);
            thumbGridEl.appendChild(card);

            try {
                await renderer.renderPageToCanvas(pdfJsDoc, pageNum, canvas, { targetWidth: 180 });
            } catch (err) {
                canvasWrap.innerHTML = '<span style="color:#475569;font-size:0.75rem;">Preview failed</span>';
            }
        }
    };

    const handleFileSelected = async (files) => {
        await resetWorkspace();
        const file = files[0];
        if (!file) return;

        try {
            utils.ensurePdfLibraries(true);
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

            currentFile = file;
            currentBytes = check.bytes;
            pdfJsDoc = await renderer.loadDocument(check.bytes);

            fileNameEl.textContent = file.name;
            fileSubEl.textContent = `${totalPages} page${totalPages !== 1 ? 's' : ''} • ${utils.formatBytes(file.size)}`;
            fileMetaEl.style.display = 'flex';
            dropzoneEl.style.display = 'none';
            executeBtn.disabled = false;

            if (totalPages >= 2 && !rangesInputEl.value.trim()) {
                const mid = Math.ceil(totalPages / 2);
                rangesInputEl.value = `1-${mid}, ${mid + 1}-${totalPages}`;
            } else if (totalPages === 1) {
                rangesInputEl.value = '1';
            }

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

    resetBtn.addEventListener('click', resetWorkspace);

    const buildSplitGroups = () => {
        if (rangeErrorEl) rangeErrorEl.textContent = '';
        if (fixedErrorEl) fixedErrorEl.textContent = '';

        const mode = modeSelectEl.value;
        if (mode === 'ranges') {
            const parsed = utils.parsePageRanges(rangesInputEl.value, totalPages);
            if (!parsed.ok) {
                if (rangeErrorEl) rangeErrorEl.textContent = parsed.error;
                return null;
            }
            return parsed.groups;
        }

        if (mode === 'fixed') {
            const n = parseInt(fixedInputEl.value, 10);
            if (isNaN(n) || n < 1) {
                if (fixedErrorEl) fixedErrorEl.textContent = 'Enter a number of 1 or more.';
                return null;
            }
            const groups = [];
            for (let start = 1; start <= totalPages; start += n) {
                const chunk = [];
                for (let p = start; p < start + n && p <= totalPages; p++) {
                    chunk.push(p);
                }
                groups.push(chunk);
            }
            return groups;
        }

        const singleGroups = [];
        for (let p = 1; p <= totalPages; p++) {
            singleGroups.push([p]);
        }
        return singleGroups;
    };

    executeBtn.addEventListener('click', async () => {
        if (!currentBytes || totalPages < 1) {
            ui.showAlert(alertContainer, 'error', 'Please upload a valid PDF first.');
            return;
        }

        const groups = buildSplitGroups();
        if (!groups || groups.length === 0) return;

        if (groups.length > 100) {
            ui.showAlert(alertContainer, 'error', `This would generate ${groups.length} separate PDFs. Please keep split parts at 100 or fewer to stay within browser memory limits.`);
            return;
        }

        ui.clearAlert(alertContainer);
        clearOutputs();
        ui.setButtonLoading(executeBtn, true, 'Splitting...');
        utils.setProgress(progressPanelEl, 5, 'Loading source PDF...');

        try {
            const srcDoc = await window.PDFLib.PDFDocument.load(currentBytes);

            for (let i = 0; i < groups.length; i++) {
                const pageList = groups[i];
                const pct = 10 + Math.round(((i + 1) / groups.length) * 85);
                utils.setProgress(progressPanelEl, pct, `Creating part ${i + 1} of ${groups.length}...`);

                const outDoc = await window.PDFLib.PDFDocument.create();
                const zeroIndices = pageList.map((p) => p - 1);
                const copied = await outDoc.copyPages(srcDoc, zeroIndices);
                copied.forEach((p) => outDoc.addPage(p));

                const bytes = await outDoc.save();
                const blob = new Blob([bytes], { type: 'application/pdf' });
                const fileName = utils.buildDownloadName(`SplitPDF_Part${i + 1}`, 'pdf');
                splitOutputs.push({
                    name: fileName,
                    blob,
                    bytes,
                    pageCount: pageList.length,
                    label: pageList.length === 1 ? `Page ${pageList[0]}` : `Pages ${pageList[0]}-${pageList[pageList.length - 1]}`
                });
            }

            utils.setProgress(progressPanelEl, 100, 'Split complete');
            resultSummaryEl.textContent = `${splitOutputs.length} PDF file${splitOutputs.length !== 1 ? 's' : ''} ready`;
            downloadZipLabelEl.textContent = `Download ZIP (${utils.buildDownloadName('SplitPDF', 'zip')})`;

            outputListEl.innerHTML = '';
            splitOutputs.forEach((part) => {
                const row = document.createElement('div');
                row.className = 'output-item-row';

                const meta = document.createElement('div');
                meta.style.minWidth = '0';
                meta.innerHTML = `<div class="file-meta-name">${ui.escapeHtml(part.name)}</div><div class="file-meta-sub">${ui.escapeHtml(part.label)} • ${utils.formatBytes(part.blob.size)}</div>`;

                const dlBtn = document.createElement('button');
                dlBtn.type = 'button';
                dlBtn.className = 'btn btn-secondary btn-sm';
                dlBtn.innerHTML = '<i class="fa-solid fa-download" aria-hidden="true"></i><span>Download</span>';
                dlBtn.addEventListener('click', () => {
                    utils.triggerDownload(part.blob, part.name, 'application/pdf');
                });

                row.appendChild(meta);
                row.appendChild(dlBtn);
                outputListEl.appendChild(row);
            });

            resultPanelEl.style.display = 'block';
        } catch (err) {
            utils.hideProgress(progressPanelEl);
            ui.showAlert(alertContainer, 'error', utils.describePdfError(err));
        } finally {
            ui.setButtonLoading(executeBtn, false);
        }
    });

    downloadZipBtn.addEventListener('click', async () => {
        if (splitOutputs.length === 0) return;
        ui.setButtonLoading(downloadZipBtn, true, 'Building ZIP...');
        try {
            const entries = splitOutputs.map((o) => ({ name: o.name, data: o.bytes }));
            const zipBlob = await utils.buildZipBlob(entries);
            utils.triggerDownload(zipBlob, utils.buildDownloadName('SplitPDF', 'zip'), 'application/zip');
        } catch (err) {
            ui.showAlert(alertContainer, 'error', utils.describePdfError(err));
        } finally {
            ui.setButtonLoading(downloadZipBtn, false);
        }
    });
});