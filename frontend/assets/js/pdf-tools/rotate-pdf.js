/**
 * ScanPro AI - Tool 6: Rotate PDF
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const utils = window.ScanProPdfUtils;
    const renderer = window.ScanProPdfRenderer;
    const uploader = window.ScanProUploader;
    const ui = window.ScanProUI;
    if (!utils || !renderer || !uploader || !ui) return;

    const alertContainer = document.getElementById('toolAlertContainer');
    const dropzoneEl = document.getElementById('rotateDropzone');
    const fileInputEl = document.getElementById('rotateFileInput');
    const browseBtnEl = document.getElementById('rotateBrowseBtn');
    const fileMetaEl = document.getElementById('rotateFileMeta');
    const fileNameEl = document.getElementById('rotateFileName');
    const fileSubEl = document.getElementById('rotateFileSub');
    const resetBtn = document.getElementById('rotateResetBtn');
    const previewPanelEl = document.getElementById('rotatePreviewPanel');
    const thumbGridEl = document.getElementById('rotateThumbGrid');

    const all90Btn = document.getElementById('rotateAll90Btn');
    const all180Btn = document.getElementById('rotateAll180Btn');
    const all270Btn = document.getElementById('rotateAll270Btn');
    const resetAnglesBtn = document.getElementById('rotateResetAnglesBtn');
    const executeBtn = document.getElementById('rotateExecuteBtn');
    const progressPanelEl = document.getElementById('rotateProgressPanel');

    const resultPanelEl = document.getElementById('rotateResultPanel');
    const resultChangedEl = document.getElementById('rotateResultChanged');
    const resultSizeEl = document.getElementById('rotateResultSize');
    const downloadBtn = document.getElementById('rotateDownloadBtn');
    const downloadLabelEl = document.getElementById('rotateDownloadLabel');

    let currentBytes = null;
    let pdfJsDoc = null;
    let totalPages = 0;
    let pageDeltas = [];
    let outputBlob = null;
    let outputFilename = '';

    const setButtonsEnabled = (enabled) => {
        [all90Btn, all180Btn, all270Btn, resetAnglesBtn, executeBtn].forEach((b) => {
            if (b) b.disabled = !enabled;
        });
    };

    const clearResult = () => {
        outputBlob = null;
        outputFilename = '';
        resultPanelEl.style.display = 'none';
    };

    const resetWorkspace = async () => {
        if (pdfJsDoc) {
            await renderer.destroyDocument(pdfJsDoc);
            pdfJsDoc = null;
        }
        currentBytes = null;
        totalPages = 0;
        pageDeltas = [];
        clearResult();
        utils.hideProgress(progressPanelEl);
        ui.clearAlert(alertContainer);
        thumbGridEl.innerHTML = '';
        fileMetaEl.style.display = 'none';
        previewPanelEl.style.display = 'none';
        dropzoneEl.style.display = 'block';
        setButtonsEnabled(false);
    };

    const updateCardVisual = (pageIndex) => {
        const card = thumbGridEl.querySelector(`.thumb-card[data-page-idx="${pageIndex}"]`);
        if (!card) return;
        const delta = pageDeltas[pageIndex] || 0;
        const canvas = card.querySelector('canvas');
        const angleBadge = card.querySelector('.rotate-angle-badge');
        if (canvas) {
            canvas.style.transform = `rotate(${delta}deg)`;
        }
        if (angleBadge) {
            angleBadge.textContent = `${delta}°`;
        }
        card.classList.toggle('is-selected', delta !== 0);
    };

    const rotateSinglePage = (pageIndex, deltaDeg) => {
        pageDeltas[pageIndex] = ((pageDeltas[pageIndex] || 0) + deltaDeg + 360) % 360;
        clearResult();
        updateCardVisual(pageIndex);
    };

    const rotateAllPages = (deltaDeg) => {
        for (let i = 0; i < totalPages; i++) {
            pageDeltas[i] = ((pageDeltas[i] || 0) + deltaDeg + 360) % 360;
            updateCardVisual(i);
        }
        clearResult();
    };

    const renderThumbnails = async () => {
        thumbGridEl.innerHTML = '';
        previewPanelEl.style.display = 'block';

        for (let idx = 0; idx < totalPages; idx++) {
            const pageNum = idx + 1;
            const card = document.createElement('div');
            card.className = 'thumb-card';
            card.dataset.pageIdx = String(idx);

            const canvasWrap = document.createElement('div');
            canvasWrap.className = 'thumb-canvas-wrap';
            const canvas = document.createElement('canvas');
            canvasWrap.appendChild(canvas);

            const footer = document.createElement('div');
            footer.className = 'thumb-footer';
            footer.innerHTML = `<span class="thumb-badge">Page ${pageNum}</span><span class="rotate-angle-badge">0°</span>`;

            const btnRow = document.createElement('div');
            btnRow.style.display = 'flex';
            btnRow.style.justifyContent = 'space-between';
            btnRow.style.gap = '6px';

            const leftBtn = document.createElement('button');
            leftBtn.type = 'button';
            leftBtn.className = 'icon-btn';
            leftBtn.title = 'Rotate 270° (90° Left)';
            leftBtn.innerHTML = '<i class="fa-solid fa-rotate-left" aria-hidden="true"></i>';
            leftBtn.addEventListener('click', () => rotateSinglePage(idx, 270));

            const flipBtn = document.createElement('button');
            flipBtn.type = 'button';
            flipBtn.className = 'icon-btn';
            flipBtn.title = 'Rotate 180°';
            flipBtn.innerHTML = '<span style="font-size:0.7rem;font-weight:700;">180°</span>';
            flipBtn.addEventListener('click', () => rotateSinglePage(idx, 180));

            const rightBtn = document.createElement('button');
            rightBtn.type = 'button';
            rightBtn.className = 'icon-btn';
            rightBtn.title = 'Rotate 90° Right';
            rightBtn.innerHTML = '<i class="fa-solid fa-rotate-right" aria-hidden="true"></i>';
            rightBtn.addEventListener('click', () => rotateSinglePage(idx, 90));

            btnRow.appendChild(leftBtn);
            btnRow.appendChild(flipBtn);
            btnRow.appendChild(rightBtn);

            card.appendChild(canvasWrap);
            card.appendChild(footer);
            card.appendChild(btnRow);
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
            pageDeltas = new Array(totalPages).fill(0);
            pdfJsDoc = await renderer.loadDocument(check.bytes);

            fileNameEl.textContent = file.name;
            fileSubEl.textContent = `${totalPages} page${totalPages !== 1 ? 's' : ''} • ${utils.formatBytes(file.size)}`;
            fileMetaEl.style.display = 'flex';
            dropzoneEl.style.display = 'none';
            setButtonsEnabled(true);

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

    all90Btn.addEventListener('click', () => rotateAllPages(90));
    all180Btn.addEventListener('click', () => rotateAllPages(180));
    all270Btn.addEventListener('click', () => rotateAllPages(270));
    resetAnglesBtn.addEventListener('click', () => {
        pageDeltas = new Array(totalPages).fill(0);
        for (let i = 0; i < totalPages; i++) updateCardVisual(i);
        clearResult();
    });
    resetBtn.addEventListener('click', resetWorkspace);

    executeBtn.addEventListener('click', async () => {
        if (!currentBytes || totalPages < 1) {
            ui.showAlert(alertContainer, 'error', 'Please upload a valid PDF first.');
            return;
        }

        const changedCount = pageDeltas.filter((d) => d !== 0).length;
        if (changedCount === 0) {
            ui.showAlert(alertContainer, 'warning', 'No rotation applied yet. Rotate at least one page before saving.');
            return;
        }

        ui.clearAlert(alertContainer);
        clearResult();
        ui.setButtonLoading(executeBtn, true, 'Applying...');
        utils.setProgress(progressPanelEl, 15, 'Loading PDF...');

        try {
            const pdfDoc = await window.PDFLib.PDFDocument.load(currentBytes);
            const pages = pdfDoc.getPages();

            pages.forEach((page, idx) => {
                const delta = pageDeltas[idx] || 0;
                if (delta !== 0) {
                    const existingAngle = page.getRotation().angle || 0;
                    const finalAngle = (existingAngle + delta) % 360;
                    page.setRotation(window.PDFLib.degrees(finalAngle));
                }
            });

            utils.setProgress(progressPanelEl, 85, 'Saving rotated PDF...');
            const modifiedBytes = await pdfDoc.save();
            outputBlob = new Blob([modifiedBytes], { type: 'application/pdf' });
            outputFilename = utils.buildDownloadName('RotatedPDF', 'pdf');

            utils.setProgress(progressPanelEl, 100, 'Complete');
            resultChangedEl.textContent = `${changedCount} of ${totalPages}`;
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