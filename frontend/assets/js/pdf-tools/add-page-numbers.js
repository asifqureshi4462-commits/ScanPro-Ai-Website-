/**
 * ScanPro AI - Tool 8: Add Page Numbers
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const utils = window.ScanProPdfUtils;
    const renderer = window.ScanProPdfRenderer;
    const uploader = window.ScanProUploader;
    const ui = window.ScanProUI;
    if (!utils || !renderer || !uploader || !ui) return;

    const alertContainer = document.getElementById('toolAlertContainer');
    const dropzoneEl = document.getElementById('pnDropzone');
    const fileInputEl = document.getElementById('pnFileInput');
    const browseBtnEl = document.getElementById('pnBrowseBtn');
    const fileMetaEl = document.getElementById('pnFileMeta');
    const fileNameEl = document.getElementById('pnFileName');
    const fileSubEl = document.getElementById('pnFileSub');
    const resetBtn = document.getElementById('pnResetBtn');
    const previewPanelEl = document.getElementById('pnPreviewPanel');
    const previewCanvasEl = document.getElementById('pnPreviewCanvas');

    const posGridEl = document.getElementById('pnPositionGrid');
    const formatSelectEl = document.getElementById('pnFormatSelect');
    const startInputEl = document.getElementById('pnStartInput');
    const marginSelectEl = document.getElementById('pnMarginSelect');
    const fontSizeRangeEl = document.getElementById('pnFontSizeRange');
    const fontSizeValueEl = document.getElementById('pnFontSizeValue');
    const colorSelectEl = document.getElementById('pnColorSelect');

    const executeBtn = document.getElementById('pnExecuteBtn');
    const progressPanelEl = document.getElementById('pnProgressPanel');
    const resultPanelEl = document.getElementById('pnResultPanel');
    const resultPagesEl = document.getElementById('pnResultPages');
    const resultSizeEl = document.getElementById('pnResultSize');
    const downloadBtn = document.getElementById('pnDownloadBtn');
    const downloadLabelEl = document.getElementById('pnDownloadLabel');

    let currentBytes = null;
    let pdfJsDoc = null;
    let totalPages = 0;
    let selectedPos = 'bottom-center';
    let basePreviewCanvas = null;
    let basePagePointWidth = 595;
    let outputBlob = null;
    let outputFilename = '';

    const colorMap = {
        slate: { css: 'rgb(51, 65, 85)', pdf: [51 / 255, 65 / 255, 85 / 255] },
        black: { css: 'rgb(0, 0, 0)',    pdf: [0, 0, 0] },
        blue:  { css: 'rgb(30, 64, 175)', pdf: [30 / 255, 64 / 255, 175 / 255] }
    };

    const formatNumberLabel = (displayNum, totalDisplay) => {
        switch (formatSelectEl.value) {
            case 'page-n':
                return `Page ${displayNum}`;
            case 'page-n-of-total':
                return `Page ${displayNum} of ${totalDisplay}`;
            case 'n-slash-total':
                return `${displayNum} / ${totalDisplay}`;
            default:
                return `${displayNum}`;
        }
    };

    const clearResult = () => {
        outputBlob = null;
        outputFilename = '';
        resultPanelEl.style.display = 'none';
    };

    const drawPreview = () => {
        if (!basePreviewCanvas) return;
        const w = basePreviewCanvas.width;
        const h = basePreviewCanvas.height;
        previewCanvasEl.width = w;
        previewCanvasEl.height = h;

        const ctx = previewCanvasEl.getContext('2d');
        ctx.clearRect(0, 0, w, h);
        ctx.drawImage(basePreviewCanvas, 0, 0);

        const startNum = Math.max(1, parseInt(startInputEl.value, 10) || 1);
        const totalDisplay = startNum + Math.max(totalPages, 1) - 1;
        const label = formatNumberLabel(startNum, totalDisplay);
        const fontPt = parseInt(fontSizeRangeEl.value, 10) || 12;
        const marginPt = parseInt(marginSelectEl.value, 10) || 28;
        const scaleFactor = w / Math.max(basePagePointWidth, 1);
        const fontPx = Math.max(9, fontPt * scaleFactor);
        const marginPx = marginPt * scaleFactor;
        const col = colorMap[colorSelectEl.value] || colorMap.slate;

        ctx.save();
        ctx.font = `600 ${fontPx}px Helvetica, Arial, sans-serif`;
        ctx.fillStyle = col.css;

        let x = w / 2;
        let y = h - marginPx;

        if (selectedPos.includes('left')) {
            ctx.textAlign = 'left';
            x = marginPx;
        } else if (selectedPos.includes('right')) {
            ctx.textAlign = 'right';
            x = w - marginPx;
        } else {
            ctx.textAlign = 'center';
            x = w / 2;
        }

        if (selectedPos.startsWith('top')) {
            ctx.textBaseline = 'top';
            y = marginPx;
        } else {
            ctx.textBaseline = 'bottom';
            y = h - marginPx;
        }

        ctx.fillText(label, x, y);
        ctx.restore();
    };

    posGridEl.querySelectorAll('.position-btn[data-pos]').forEach((btn) => {
        btn.addEventListener('click', () => {
            selectedPos = btn.dataset.pos || 'bottom-center';
            posGridEl.querySelectorAll('.position-btn').forEach((b) => b.classList.toggle('active', b === btn));
            clearResult();
            drawPreview();
        });
    });

    const syncLabels = () => {
        fontSizeValueEl.textContent = `${fontSizeRangeEl.value} pt`;
    };

    [formatSelectEl, startInputEl, marginSelectEl, fontSizeRangeEl, colorSelectEl].forEach((el) => {
        el.addEventListener('input', () => {
            syncLabels();
            clearResult();
            drawPreview();
        });
        el.addEventListener('change', () => {
            syncLabels();
            clearResult();
            drawPreview();
        });
    });
    syncLabels();

    const resetWorkspace = async () => {
        if (pdfJsDoc) {
            await renderer.destroyDocument(pdfJsDoc);
            pdfJsDoc = null;
        }
        currentBytes = null;
        totalPages = 0;
        if (basePreviewCanvas) {
            basePreviewCanvas.width = 0;
            basePreviewCanvas.height = 0;
            basePreviewCanvas = null;
        }
        previewCanvasEl.width = 0;
        previewCanvasEl.height = 0;
        clearResult();
        utils.hideProgress(progressPanelEl);
        ui.clearAlert(alertContainer);
        fileMetaEl.style.display = 'none';
        previewPanelEl.style.display = 'none';
        dropzoneEl.style.display = 'block';
        executeBtn.disabled = true;
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

            const page1 = await pdfJsDoc.getPage(1);
            basePagePointWidth = page1.getViewport({ scale: 1 }).width || 595;
            if (typeof page1.cleanup === 'function') page1.cleanup();

            basePreviewCanvas = document.createElement('canvas');
            await renderer.renderPageToCanvas(pdfJsDoc, 1, basePreviewCanvas, { targetWidth: 380 });

            fileNameEl.textContent = file.name;
            fileSubEl.textContent = `${totalPages} page${totalPages !== 1 ? 's' : ''} • ${utils.formatBytes(file.size)}`;
            fileMetaEl.style.display = 'flex';
            dropzoneEl.style.display = 'none';
            previewPanelEl.style.display = 'block';
            executeBtn.disabled = false;

            const largeWarn = utils.checkLargeFileWarning(file);
            if (largeWarn) {
                ui.showAlert(alertContainer, 'warning', largeWarn);
            }

            drawPreview();
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

    executeBtn.addEventListener('click', async () => {
        if (!currentBytes || totalPages < 1) {
            ui.showAlert(alertContainer, 'error', 'Please upload a valid PDF first.');
            return;
        }

        const startNum = parseInt(startInputEl.value, 10);
        if (isNaN(startNum) || startNum < 1) {
            ui.showAlert(alertContainer, 'error', 'Starting number must be 1 or greater.');
            return;
        }

        ui.clearAlert(alertContainer);
        clearResult();
        ui.setButtonLoading(executeBtn, true, 'Numbering...');
        utils.setProgress(progressPanelEl, 10, 'Loading PDF...');

        try {
            const pdfDoc = await window.PDFLib.PDFDocument.load(currentBytes);
            const font = await pdfDoc.embedFont(window.PDFLib.StandardFonts.HelveticaBold);
            const pages = pdfDoc.getPages();

            const fontSize = parseInt(fontSizeRangeEl.value, 10) || 12;
            const margin = parseInt(marginSelectEl.value, 10) || 28;
            const col = (colorMap[colorSelectEl.value] || colorMap.slate).pdf;
            const totalDisplay = startNum + pages.length - 1;

            pages.forEach((page, idx) => {
                const pct = 15 + Math.round(((idx + 1) / pages.length) * 78);
                utils.setProgress(progressPanelEl, pct, `Numbering page ${idx + 1} of ${pages.length}...`);

                const displayNum = startNum + idx;
                const label = formatNumberLabel(displayNum, totalDisplay);
                const textWidth = font.widthOfTextAtSize(label, fontSize);
                const textHeight = font.heightAtSize(fontSize);
                const { width, height } = page.getSize();

                let x;
                if (selectedPos.includes('left')) {
                    x = margin;
                } else if (selectedPos.includes('right')) {
                    x = Math.max(margin, width - margin - textWidth);
                } else {
                    x = (width - textWidth) / 2;
                }

                let y;
                if (selectedPos.startsWith('top')) {
                    y = Math.max(margin, height - margin - textHeight * 0.8);
                } else {
                    y = margin;
                }

                page.drawText(label, {
                    x,
                    y,
                    size: fontSize,
                    font,
                    color: window.PDFLib.rgb(col[0], col[1], col[2])
                });
            });

            utils.setProgress(progressPanelEl, 96, 'Saving PDF...');
            const outBytes = await pdfDoc.save();
            outputBlob = new Blob([outBytes], { type: 'application/pdf' });
            outputFilename = utils.buildDownloadName('NumberedPDF', 'pdf');

            utils.setProgress(progressPanelEl, 100, 'Complete');
            resultPagesEl.textContent = String(pages.length);
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