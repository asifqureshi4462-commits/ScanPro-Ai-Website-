/**
 * ScanPro AI - Tool 7: Watermark PDF
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const utils = window.ScanProPdfUtils;
    const renderer = window.ScanProPdfRenderer;
    const uploader = window.ScanProUploader;
    const ui = window.ScanProUI;
    if (!utils || !renderer || !uploader || !ui) return;

    const alertContainer = document.getElementById('toolAlertContainer');
    const dropzoneEl = document.getElementById('wmDropzone');
    const fileInputEl = document.getElementById('wmFileInput');
    const browseBtnEl = document.getElementById('wmBrowseBtn');
    const fileMetaEl = document.getElementById('wmFileMeta');
    const fileNameEl = document.getElementById('wmFileName');
    const fileSubEl = document.getElementById('wmFileSub');
    const resetBtn = document.getElementById('wmResetBtn');
    const previewPanelEl = document.getElementById('wmPreviewPanel');
    const previewCanvasEl = document.getElementById('wmPreviewCanvas');

    const textInputEl = document.getElementById('wmTextInput');
    const textErrorEl = document.getElementById('wmTextError');
    const posGridEl = document.getElementById('wmPositionGrid');
    const colorSelectEl = document.getElementById('wmColorSelect');
    const sizeRangeEl = document.getElementById('wmSizeRange');
    const sizeValueEl = document.getElementById('wmSizeValue');
    const opacityRangeEl = document.getElementById('wmOpacityRange');
    const opacityValueEl = document.getElementById('wmOpacityValue');
    const rotationRangeEl = document.getElementById('wmRotationRange');
    const rotationValueEl = document.getElementById('wmRotationValue');

    const executeBtn = document.getElementById('wmExecuteBtn');
    const progressPanelEl = document.getElementById('wmProgressPanel');
    const resultPanelEl = document.getElementById('wmResultPanel');
    const resultPagesEl = document.getElementById('wmResultPages');
    const resultSizeEl = document.getElementById('wmResultSize');
    const downloadBtn = document.getElementById('wmDownloadBtn');
    const downloadLabelEl = document.getElementById('wmDownloadLabel');

    let currentBytes = null;
    let pdfJsDoc = null;
    let totalPages = 0;
    let selectedPos = 'center';
    let basePreviewCanvas = null;
    let basePagePointWidth = 595;
    let outputBlob = null;
    let outputFilename = '';

    const colorMap = {
        gray:  { rgb: [100, 116, 139], pdf: [100 / 255, 116 / 255, 139 / 255] },
        red:   { rgb: [220, 38, 38],   pdf: [220 / 255, 38 / 255, 38 / 255] },
        blue:  { rgb: [37, 99, 235],   pdf: [37 / 255, 99 / 255, 235 / 255] },
        cyan:  { rgb: [8, 145, 178],   pdf: [8 / 255, 145 / 255, 178 / 255] },
        black: { rgb: [15, 23, 42],    pdf: [15 / 255, 23 / 255, 42 / 255] }
    };

    const isWinAnsiSupported = (text) => /^[\x20-\x7E\xA0-\xFF]+$/.test(text);

    const clearResult = () => {
        outputBlob = null;
        outputFilename = '';
        resultPanelEl.style.display = 'none';
    };

    const getAnchorNormalized = (pos) => {
        const map = {
            'top-left':      [0.22, 0.16],
            'top-center':    [0.50, 0.16],
            'top-right':     [0.78, 0.16],
            'middle-left':   [0.22, 0.50],
            'center':        [0.50, 0.50],
            'middle-right':  [0.78, 0.50],
            'bottom-left':   [0.22, 0.84],
            'bottom-center': [0.50, 0.84],
            'bottom-right':  [0.78, 0.84]
        };
        return map[pos] || map.center;
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

        const text = textInputEl.value.trim();
        if (!text) return;

        const fontPt = parseInt(sizeRangeEl.value, 10) || 46;
        const scaleFactor = w / Math.max(basePagePointWidth, 1);
        const fontPx = Math.max(8, fontPt * scaleFactor);
        const opacity = (parseInt(opacityRangeEl.value, 10) || 30) / 100;
        const angleDeg = parseInt(rotationRangeEl.value, 10) || 0;
        const col = colorMap[colorSelectEl.value] || colorMap.gray;
        const [nx, ny] = getAnchorNormalized(selectedPos);

        ctx.save();
        ctx.translate(w * nx, h * ny);
        ctx.rotate((angleDeg * Math.PI) / 180);
        ctx.font = `700 ${fontPx}px Helvetica, Arial, sans-serif`;
        ctx.fillStyle = `rgba(${col.rgb[0]}, ${col.rgb[1]}, ${col.rgb[2]}, ${opacity})`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(text, 0, 0);
        ctx.restore();
    };

    posGridEl.querySelectorAll('.position-btn[data-pos]').forEach((btn) => {
        btn.addEventListener('click', () => {
            selectedPos = btn.dataset.pos || 'center';
            posGridEl.querySelectorAll('.position-btn').forEach((b) => b.classList.toggle('active', b === btn));
            clearResult();
            drawPreview();
        });
    });

    const syncControlLabels = () => {
        sizeValueEl.textContent = `${sizeRangeEl.value} pt`;
        opacityValueEl.textContent = `${opacityRangeEl.value}%`;
        rotationValueEl.textContent = `${rotationRangeEl.value}°`;
    };

    [textInputEl, colorSelectEl, sizeRangeEl, opacityRangeEl, rotationRangeEl].forEach((el) => {
        el.addEventListener('input', () => {
            if (textErrorEl) textErrorEl.textContent = '';
            syncControlLabels();
            clearResult();
            drawPreview();
        });
        el.addEventListener('change', () => {
            syncControlLabels();
            clearResult();
            drawPreview();
        });
    });
    syncControlLabels();

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
        if (textErrorEl) textErrorEl.textContent = '';
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

        const text = textInputEl.value.trim();
        if (!text) {
            if (textErrorEl) textErrorEl.textContent = 'Please enter watermark text.';
            return;
        }
        if (!isWinAnsiSupported(text)) {
            if (textErrorEl) textErrorEl.textContent = 'Standard PDF watermark font supports English letters, numbers, and basic symbols only.';
            return;
        }

        ui.clearAlert(alertContainer);
        clearResult();
        ui.setButtonLoading(executeBtn, true, 'Watermarking...');
        utils.setProgress(progressPanelEl, 10, 'Loading PDF...');

        try {
            const pdfDoc = await window.PDFLib.PDFDocument.load(currentBytes);
            const font = await pdfDoc.embedFont(window.PDFLib.StandardFonts.HelveticaBold);
            const pages = pdfDoc.getPages();

            const fontSize = parseInt(sizeRangeEl.value, 10) || 46;
            const opacity = (parseInt(opacityRangeEl.value, 10) || 30) / 100;
            const cssAngle = parseInt(rotationRangeEl.value, 10) || 0;
            // Canvas clockwise rotation corresponds to negated angle in PDF counter-clockwise coordinates
            const pdfAngle = -cssAngle;
            const rad = (pdfAngle * Math.PI) / 180;
            const col = (colorMap[colorSelectEl.value] || colorMap.gray).pdf;
            const [nx, ny] = getAnchorNormalized(selectedPos);

            const textWidth = font.widthOfTextAtSize(text, fontSize);
            const textHeight = font.heightAtSize(fontSize);

            pages.forEach((page, idx) => {
                const pct = 15 + Math.round(((idx + 1) / pages.length) * 78);
                utils.setProgress(progressPanelEl, pct, `Stamping page ${idx + 1} of ${pages.length}...`);

                const { width, height } = page.getSize();
                const anchorX = width * nx;
                const anchorY = height * (1 - ny);

                const dx = -textWidth / 2;
                const dy = -textHeight * 0.35;
                const drawX = anchorX + dx * Math.cos(rad) - dy * Math.sin(rad);
                const drawY = anchorY + dx * Math.sin(rad) + dy * Math.cos(rad);

                page.drawText(text, {
                    x: drawX,
                    y: drawY,
                    size: fontSize,
                    font,
                    color: window.PDFLib.rgb(col[0], col[1], col[2]),
                    opacity,
                    rotate: window.PDFLib.degrees(pdfAngle)
                });
            });

            utils.setProgress(progressPanelEl, 96, 'Saving watermarked PDF...');
            const outBytes = await pdfDoc.save();
            outputBlob = new Blob([outBytes], { type: 'application/pdf' });
            outputFilename = utils.buildDownloadName('WatermarkedPDF', 'pdf');

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