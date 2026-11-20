/**
 * ScanPro AI - Tool 10: Reorder PDF Pages
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const utils = window.ScanProPdfUtils;
    const renderer = window.ScanProPdfRenderer;
    const uploader = window.ScanProUploader;
    const ui = window.ScanProUI;
    if (!utils || !renderer || !uploader || !ui) return;

    const alertContainer = document.getElementById('toolAlertContainer');
    const dropzoneEl = document.getElementById('reorderDropzone');
    const fileInputEl = document.getElementById('reorderFileInput');
    const browseBtnEl = document.getElementById('reorderBrowseBtn');
    const fileMetaEl = document.getElementById('reorderFileMeta');
    const fileNameEl = document.getElementById('reorderFileName');
    const fileSubEl = document.getElementById('reorderFileSub');
    const resetBtn = document.getElementById('reorderResetBtn');

    const previewPanelEl = document.getElementById('reorderPreviewPanel');
    const reverseBtn = document.getElementById('reorderReverseBtn');
    const restoreBtn = document.getElementById('reorderRestoreBtn');
    const thumbGridEl = document.getElementById('reorderThumbGrid');

    const orderPreviewBoxEl = document.getElementById('reorderOrderPreviewBox');
    const sequenceTextEl = document.getElementById('reorderSequenceText');
    const executeBtn = document.getElementById('reorderExecuteBtn');
    const progressPanelEl = document.getElementById('reorderProgressPanel');
    const resultPanelEl = document.getElementById('reorderResultPanel');
    const resultPagesEl = document.getElementById('reorderResultPages');
    const resultSizeEl = document.getElementById('reorderResultSize');
    const downloadBtn = document.getElementById('reorderDownloadBtn');
    const downloadLabelEl = document.getElementById('reorderDownloadLabel');

    let currentBytes = null;
    let pdfJsDoc = null;
    let totalPages = 0;
    let pageOrder = [];
    const cachedCanvases = new Map();
    let dragPosition = null;
    let outputBlob = null;
    let outputFilename = '';

    const clearResult = () => {
        outputBlob = null;
        outputFilename = '';
        resultPanelEl.style.display = 'none';
    };

    const clearCachedCanvases = () => {
        cachedCanvases.forEach((canvas) => {
            canvas.width = 0;
            canvas.height = 0;
        });
        cachedCanvases.clear();
    };

    const resetWorkspace = async () => {
        if (pdfJsDoc) {
            await renderer.destroyDocument(pdfJsDoc);
            pdfJsDoc = null;
        }
        clearCachedCanvases();
        currentBytes = null;
        totalPages = 0;
        pageOrder = [];
        clearResult();
        utils.hideProgress(progressPanelEl);
        ui.clearAlert(alertContainer);
        thumbGridEl.innerHTML = '';
        fileMetaEl.style.display = 'none';
        previewPanelEl.style.display = 'none';
        orderPreviewBoxEl.style.display = 'none';
        dropzoneEl.style.display = 'block';
        executeBtn.disabled = true;
    };

    const updateSequencePreview = () => {
        if (pageOrder.length === 0) {
            orderPreviewBoxEl.style.display = 'none';
            executeBtn.disabled = true;
            return;
        }
        orderPreviewBoxEl.style.display = 'flex';
        executeBtn.disabled = false;
        if (pageOrder.length <= 16) {
            sequenceTextEl.textContent = pageOrder.map((p) => `P${p}`).join(' → ');
        } else {
            sequenceTextEl.textContent = `${pageOrder.slice(0, 12).map((p) => `P${p}`).join(' → ')} ... (${pageOrder.length} pages)`;
        }
    };

    const movePage = (fromPos, toPos) => {
        if (toPos < 0 || toPos >= pageOrder.length || fromPos === toPos) return;
        const [moved] = pageOrder.splice(fromPos, 1);
        pageOrder.splice(toPos, 0, moved);
        clearResult();
        renderOrderGrid();
    };

    const renderOrderGrid = () => {
        thumbGridEl.innerHTML = '';

        pageOrder.forEach((origPageNum, positionIdx) => {
            const card = document.createElement('div');
            card.className = 'thumb-card';
            card.draggable = true;
            card.style.cursor = 'grab';

            const canvasWrap = document.createElement('div');
            canvasWrap.className = 'thumb-canvas-wrap';
            const canvas = cachedCanvases.get(origPageNum);
            if (canvas) {
                canvasWrap.appendChild(canvas);
            } else {
                canvasWrap.innerHTML = `<span style="color:#475569;font-size:0.78rem;">Page ${origPageNum}</span>`;
            }

            const footer = document.createElement('div');
            footer.className = 'thumb-footer';
            footer.innerHTML = `<span class="thumb-badge">#${positionIdx + 1}</span><span>Orig P${origPageNum}</span>`;

            const btnRow = document.createElement('div');
            btnRow.style.display = 'flex';
            btnRow.style.justifyContent = 'space-between';
            btnRow.style.gap = '4px';

            const leftBtn = document.createElement('button');
            leftBtn.type = 'button';
            leftBtn.className = 'icon-btn';
            leftBtn.title = 'Move Earlier';
            leftBtn.disabled = positionIdx === 0;
            leftBtn.innerHTML = '<i class="fa-solid fa-arrow-left" aria-hidden="true"></i>';
            leftBtn.addEventListener('click', () => movePage(positionIdx, positionIdx - 1));

            const rightBtn = document.createElement('button');
            rightBtn.type = 'button';
            rightBtn.className = 'icon-btn';
            rightBtn.title = 'Move Later';
            rightBtn.disabled = positionIdx === pageOrder.length - 1;
            rightBtn.innerHTML = '<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>';
            rightBtn.addEventListener('click', () => movePage(positionIdx, positionIdx + 1));

            btnRow.appendChild(leftBtn);
            btnRow.appendChild(rightBtn);

            card.appendChild(canvasWrap);
            card.appendChild(footer);
            card.appendChild(btnRow);

            card.addEventListener('dragstart', () => {
                dragPosition = positionIdx;
                card.classList.add('is-dragging');
            });
            card.addEventListener('dragend', () => {
                dragPosition = null;
                card.classList.remove('is-dragging');
            });
            card.addEventListener('dragover', (e) => {
                e.preventDefault();
                card.classList.add('is-drag-over');
            });
            card.addEventListener('dragleave', () => {
                card.classList.remove('is-drag-over');
            });
            card.addEventListener('drop', (e) => {
                e.preventDefault();
                card.classList.remove('is-drag-over');
                if (dragPosition !== null && dragPosition !== positionIdx) {
                    movePage(dragPosition, positionIdx);
                }
            });

            thumbGridEl.appendChild(card);
        });

        updateSequencePreview();
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
            pageOrder = Array.from({ length: totalPages }, (_, idx) => idx + 1);

            fileNameEl.textContent = file.name;
            fileSubEl.textContent = `${totalPages} page${totalPages !== 1 ? 's' : ''} • ${utils.formatBytes(file.size)}`;
            fileMetaEl.style.display = 'flex';
            dropzoneEl.style.display = 'none';
            previewPanelEl.style.display = 'block';

            const largeWarn = utils.checkLargeFileWarning(file);
            if (largeWarn) {
                ui.showAlert(alertContainer, 'warning', largeWarn);
            }

            renderOrderGrid();

            for (let pageNum = 1; pageNum <= totalPages; pageNum++) {
                const canvas = document.createElement('canvas');
                try {
                    await renderer.renderPageToCanvas(pdfJsDoc, pageNum, canvas, { targetWidth: 180 });
                    cachedCanvases.set(pageNum, canvas);
                } catch (err) {
                    // Fallback placeholder remains if single thumbnail fails
                }
            }
            renderOrderGrid();
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

    reverseBtn.addEventListener('click', () => {
        if (pageOrder.length < 2) return;
        pageOrder.reverse();
        clearResult();
        renderOrderGrid();
    });

    restoreBtn.addEventListener('click', () => {
        if (totalPages < 1) return;
        pageOrder = Array.from({ length: totalPages }, (_, idx) => idx + 1);
        clearResult();
        renderOrderGrid();
    });

    resetBtn.addEventListener('click', resetWorkspace);

    executeBtn.addEventListener('click', async () => {
        if (!currentBytes || pageOrder.length === 0) {
            ui.showAlert(alertContainer, 'error', 'Please upload a valid PDF first.');
            return;
        }

        ui.clearAlert(alertContainer);
        clearResult();
        ui.setButtonLoading(executeBtn, true, 'Reordering...');
        utils.setProgress(progressPanelEl, 15, 'Loading source PDF...');

        try {
            const srcDoc = await window.PDFLib.PDFDocument.load(currentBytes);
            const outDoc = await window.PDFLib.PDFDocument.create();

            utils.setProgress(progressPanelEl, 55, `Rebuilding ${pageOrder.length} pages in new order...`);
            const zeroIndices = pageOrder.map((p) => p - 1);
            const copied = await outDoc.copyPages(srcDoc, zeroIndices);
            copied.forEach((p) => outDoc.addPage(p));

            utils.setProgress(progressPanelEl, 92, 'Saving reordered PDF...');
            const outBytes = await outDoc.save();
            outputBlob = new Blob([outBytes], { type: 'application/pdf' });
            outputFilename = utils.buildDownloadName('ReorderedPDF', 'pdf');

            utils.setProgress(progressPanelEl, 100, 'Complete');
            resultPagesEl.textContent = String(pageOrder.length);
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