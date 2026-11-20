/**
 * ScanPro AI - Tool 3: Compress PDF
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const utils = window.ScanProPdfUtils;
    const renderer = window.ScanProPdfRenderer;
    const uploader = window.ScanProUploader;
    const ui = window.ScanProUI;
    if (!utils || !renderer || !uploader || !ui) return;

    const alertContainer = document.getElementById('toolAlertContainer');
    const dropzoneEl = document.getElementById('compressDropzone');
    const fileInputEl = document.getElementById('compressFileInput');
    const browseBtnEl = document.getElementById('compressBrowseBtn');
    const fileMetaEl = document.getElementById('compressFileMeta');
    const fileNameEl = document.getElementById('compressFileName');
    const fileSubEl = document.getElementById('compressFileSub');
    const resetBtn = document.getElementById('compressResetBtn');
    const previewPanelEl = document.getElementById('compressPreviewPanel');
    const previewCanvasEl = document.getElementById('compressPreviewCanvas');

    const modeSelectEl = document.getElementById('compressModeSelect');
    const modeNoteEl = document.getElementById('compressModeNote');
    const executeBtn = document.getElementById('compressExecuteBtn');
    const progressPanelEl = document.getElementById('compressProgressPanel');

    const resultPanelEl = document.getElementById('compressResultPanel');
    const resultIconEl = document.getElementById('compressResultIcon');
    const resultTitleEl = document.getElementById('compressResultTitle');
    const resultMessageEl = document.getElementById('compressResultMessage');
    const originalSizeEl = document.getElementById('compressOriginalSize');
    const newSizeEl = document.getElementById('compressNewSize');
    const downloadBtn = document.getElementById('compressDownloadBtn');
    const downloadLabelEl = document.getElementById('compressDownloadLabel');

    let currentFile = null;
    let currentBytes = null;
    let totalPages = 0;
    let pdfJsDoc = null;
    let outputBlob = null;
    let outputFilename = '';

    const modeDescriptions = {
        structural: 'Repacks PDF objects using compressed object streams while keeping text selectable and vectors sharp. Best for uncompressed text/vector PDFs; scanned PDFs usually see little or no change.',
        balanced: 'Re-encodes PDF pages as optimized JPEG images (1.5x scale, 75% quality). Usually helps scanned PDFs shrink, while text becomes part of the page image.',
        strong: 'Re-encodes PDF pages at lower resolution (1.1x scale, 55% JPEG quality) for smaller output at lower visual sharpness.'
    };

    const syncModeNote = () => {
        if (modeNoteEl) {
            modeNoteEl.textContent = modeDescriptions[modeSelectEl.value] || '';
        }
    };

    modeSelectEl.addEventListener('change', syncModeNote);
    syncModeNote();

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
        currentFile = null;
        currentBytes = null;
        totalPages = 0;
        clearResult();
        utils.hideProgress(progressPanelEl);
        ui.clearAlert(alertContainer);
        previewCanvasEl.width = 0;
        previewCanvasEl.height = 0;
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

            currentFile = file;
            currentBytes = check.bytes;
            pdfJsDoc = await renderer.loadDocument(check.bytes);

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

            await renderer.renderPageToCanvas(pdfJsDoc, 1, previewCanvasEl, { targetWidth: 360 });
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
        if (!currentFile || !currentBytes) {
            ui.showAlert(alertContainer, 'error', 'Please upload a valid PDF first.');
            return;
        }

        const mode = modeSelectEl.value;
        ui.clearAlert(alertContainer);
        clearResult();
        ui.setButtonLoading(executeBtn, true, 'Optimizing...');
        utils.setProgress(progressPanelEl, 8, 'Preparing PDF...');

        try {
            let resultBytes;

            if (mode === 'structural') {
                utils.setProgress(progressPanelEl, 45, 'Repacking PDF object streams...');
                const srcDoc = await window.PDFLib.PDFDocument.load(currentBytes);
                resultBytes = await srcDoc.save({ useObjectStreams: true });
            } else {
                const scale = mode === 'strong' ? 1.1 : 1.5;
                const quality = mode === 'strong' ? 0.55 : 0.75;
                const outDoc = await window.PDFLib.PDFDocument.create();

                for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
                    const pct = 10 + Math.round((pageNum / totalPages) * 82);
                    utils.setProgress(progressPanelEl, pct, `Optimizing page ${pageNum} of ${totalPages}...`);

                    const jpgBlob = await renderer.renderPageToJpegBlob(pdfJsDoc, pageNum, scale, quality);
                    const jpgBytes = new Uint8Array(await jpgBlob.arrayBuffer());
                    const embeddedImg = await outDoc.embedJpg(jpgBytes);

                    const srcPage = await pdfJsDoc.getPage(pageNum);
                    const origViewport = srcPage.getViewport({ scale: 1 });
                    const page = outDoc.addPage([origViewport.width, origViewport.height]);
                    page.drawImage(embeddedImg, {
                        x: 0,
                        y: 0,
                        width: origViewport.width,
                        height: origViewport.height
                    });
                    if (typeof srcPage.cleanup === 'function') {
                        srcPage.cleanup();
                    }
                }

                utils.setProgress(progressPanelEl, 95, 'Saving optimized PDF...');
                resultBytes = await outDoc.save({ useObjectStreams: true });
            }

            outputBlob = new Blob([resultBytes], { type: 'application/pdf' });
            outputFilename = utils.buildDownloadName('CompressedPDF', 'pdf');

            const originalBytes = currentFile.size;
            const resultingBytes = outputBlob.size;

            utils.setProgress(progressPanelEl, 100, 'Complete');
            originalSizeEl.textContent = utils.formatBytes(originalBytes);
            newSizeEl.textContent = utils.formatBytes(resultingBytes);
            downloadLabelEl.textContent = `Download ${outputFilename}`;

            if (resultingBytes < originalBytes) {
                const savedBytes = originalBytes - resultingBytes;
                const savedPct = ((savedBytes / originalBytes) * 100).toFixed(1);
                resultPanelEl.style.borderColor = 'rgba(16, 185, 129, 0.40)';
                resultPanelEl.style.background = 'rgba(16, 185, 129, 0.08)';
                resultIconEl.className = 'fa-solid fa-circle-check';
                resultIconEl.style.color = 'var(--color-success)';
                resultTitleEl.textContent = 'Compression Successful';
                resultMessageEl.textContent = `File size decreased by ${utils.formatBytes(savedBytes)} (${savedPct}%).`;
            } else {
                resultPanelEl.style.borderColor = 'rgba(245, 158, 11, 0.45)';
                resultPanelEl.style.background = 'rgba(245, 158, 11, 0.08)';
                resultIconEl.className = 'fa-solid fa-triangle-exclamation';
                resultIconEl.style.color = 'var(--color-warning)';
                resultTitleEl.textContent = 'File Size Did Not Decrease';
                resultMessageEl.textContent = `This PDF is already compact for the selected mode (result is ${utils.formatBytes(resultingBytes)} vs original ${utils.formatBytes(originalBytes)}). Try another mode or keep your original file.`;
            }

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