/**
 * ScanPro AI - Tool 4: PDF to JPG
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const utils = window.ScanProPdfUtils;
    const renderer = window.ScanProPdfRenderer;
    const uploader = window.ScanProUploader;
    const ui = window.ScanProUI;
    if (!utils || !renderer || !uploader || !ui) return;

    const alertContainer = document.getElementById('toolAlertContainer');
    const dropzoneEl = document.getElementById('pdf2jpgDropzone');
    const fileInputEl = document.getElementById('pdf2jpgFileInput');
    const browseBtnEl = document.getElementById('pdf2jpgBrowseBtn');
    const fileMetaEl = document.getElementById('pdf2jpgFileMeta');
    const fileNameEl = document.getElementById('pdf2jpgFileName');
    const fileSubEl = document.getElementById('pdf2jpgFileSub');
    const resetBtn = document.getElementById('pdf2jpgResetBtn');

    const previewPanelEl = document.getElementById('pdf2jpgPreviewPanel');
    const selectedCountEl = document.getElementById('pdf2jpgSelectedCount');
    const selectAllBtn = document.getElementById('pdf2jpgSelectAllBtn');
    const clearSelBtn = document.getElementById('pdf2jpgClearSelBtn');
    const thumbGridEl = document.getElementById('pdf2jpgThumbGrid');

    const scaleSelectEl = document.getElementById('pdf2jpgScaleSelect');
    const qualityRangeEl = document.getElementById('pdf2jpgQualityRange');
    const qualityValueEl = document.getElementById('pdf2jpgQualityValue');
    const executeBtn = document.getElementById('pdf2jpgExecuteBtn');
    const progressPanelEl = document.getElementById('pdf2jpgProgressPanel');

    const resultPanelEl = document.getElementById('pdf2jpgResultPanel');
    const resultTitleEl = document.getElementById('pdf2jpgResultTitle');
    const downloadZipBtn = document.getElementById('pdf2jpgDownloadZipBtn');
    const downloadZipLabelEl = document.getElementById('pdf2jpgDownloadZipLabel');
    const outputListEl = document.getElementById('pdf2jpgOutputList');

    let pdfJsDoc = null;
    let totalPages = 0;
    const selectedPages = new Set();
    let jpgOutputs = [];

    qualityRangeEl.addEventListener('input', () => {
        qualityValueEl.textContent = `${qualityRangeEl.value}%`;
    });

    const clearOutputs = () => {
        jpgOutputs = [];
        outputListEl.innerHTML = '';
        resultPanelEl.style.display = 'none';
    };

    const syncSelectionUi = () => {
        selectedCountEl.textContent = String(selectedPages.size);
        executeBtn.disabled = selectedPages.size === 0 || !pdfJsDoc;
        thumbGridEl.querySelectorAll('.thumb-card[data-page]').forEach((card) => {
            const pageNum = parseInt(card.dataset.page || '0', 10);
            const isSel = selectedPages.has(pageNum);
            card.classList.toggle('is-selected', isSel);
            const badge = card.querySelector('.thumb-check');
            if (badge) {
                badge.innerHTML = isSel
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
        totalPages = 0;
        selectedPages.clear();
        clearOutputs();
        utils.hideProgress(progressPanelEl);
        ui.clearAlert(alertContainer);
        thumbGridEl.innerHTML = '';
        fileMetaEl.style.display = 'none';
        previewPanelEl.style.display = 'none';
        dropzoneEl.style.display = 'block';
        executeBtn.disabled = true;
    };

    const renderThumbnails = async () => {
        thumbGridEl.innerHTML = '';
        previewPanelEl.style.display = 'block';

        for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
            selectedPages.add(pageNum);

            const card = document.createElement('div');
            card.className = 'thumb-card is-selectable is-selected';
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
                clearOutputs();
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
            pdfJsDoc = await renderer.loadDocument(check.bytes);
            totalPages = pdfJsDoc.numPages;
            if (totalPages < 1) {
                ui.showAlert(alertContainer, 'error', 'This PDF contains 0 pages.');
                return;
            }

            fileNameEl.textContent = file.name;
            fileSubEl.textContent = `${totalPages} page${totalPages !== 1 ? 's' : ''} • ${utils.formatBytes(file.size)}`;
            fileMetaEl.style.display = 'flex';
            dropzoneEl.style.display = 'none';

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
        clearOutputs();
        syncSelectionUi();
    });

    clearSelBtn.addEventListener('click', () => {
        selectedPages.clear();
        clearOutputs();
        syncSelectionUi();
    });

    resetBtn.addEventListener('click', resetWorkspace);

    executeBtn.addEventListener('click', async () => {
        if (!pdfJsDoc || selectedPages.size === 0) {
            ui.showAlert(alertContainer, 'error', 'Please select at least 1 page to convert.');
            return;
        }

        const pagesToExport = Array.from(selectedPages).sort((a, b) => a - b);
        const scale = parseFloat(scaleSelectEl.value || '2.0');
        const quality = Math.max(0.4, Math.min(0.98, parseInt(qualityRangeEl.value, 10) / 100));

        ui.clearAlert(alertContainer);
        clearOutputs();
        ui.setButtonLoading(executeBtn, true, 'Converting...');
        utils.setProgress(progressPanelEl, 5, 'Starting conversion...');

        try {
            for (let i = 0; i < pagesToExport.length; i++) {
                const pageNum = pagesToExport[i];
                const pct = 10 + Math.round(((i + 1) / pagesToExport.length) * 85);
                utils.setProgress(progressPanelEl, pct, `Converting Page ${pageNum} (${i + 1}/${pagesToExport.length})...`);

                const blob = await renderer.renderPageToJpegBlob(pdfJsDoc, pageNum, scale, quality);
                const filename = utils.buildDownloadName(`Page${pageNum}`, 'jpg');
                jpgOutputs.push({ pageNum, name: filename, blob });
            }

            utils.setProgress(progressPanelEl, 100, 'Conversion complete');
            resultTitleEl.textContent = `${jpgOutputs.length} JPG image${jpgOutputs.length !== 1 ? 's' : ''} ready`;
            downloadZipBtn.style.display = jpgOutputs.length > 1 ? 'inline-flex' : 'none';
            downloadZipLabelEl.textContent = `Download ZIP (${utils.buildDownloadName('PDFtoJPG', 'zip')})`;

            outputListEl.innerHTML = '';
            jpgOutputs.forEach((item) => {
                const row = document.createElement('div');
                row.className = 'output-item-row';

                const meta = document.createElement('div');
                meta.style.minWidth = '0';
                meta.innerHTML = `<div class="file-meta-name">${ui.escapeHtml(item.name)}</div><div class="file-meta-sub">Page ${item.pageNum} • ${utils.formatBytes(item.blob.size)}</div>`;

                const dlBtn = document.createElement('button');
                dlBtn.type = 'button';
                dlBtn.className = 'btn btn-secondary btn-sm';
                dlBtn.innerHTML = '<i class="fa-solid fa-download" aria-hidden="true"></i><span>Download</span>';
                dlBtn.addEventListener('click', () => {
                    utils.triggerDownload(item.blob, item.name, 'image/jpeg');
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
        if (jpgOutputs.length === 0) return;
        ui.setButtonLoading(downloadZipBtn, true, 'Building ZIP...');
        try {
            const entries = jpgOutputs.map((o) => ({ name: o.name, data: o.blob }));
            const zipBlob = await utils.buildZipBlob(entries);
            utils.triggerDownload(zipBlob, utils.buildDownloadName('PDFtoJPG', 'zip'), 'application/zip');
        } catch (err) {
            ui.showAlert(alertContainer, 'error', utils.describePdfError(err));
        } finally {
            ui.setButtonLoading(downloadZipBtn, false);
        }
    });
});