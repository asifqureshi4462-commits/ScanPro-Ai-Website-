/**
 * ScanPro AI - Tool 1: Merge PDF
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const utils = window.ScanProPdfUtils;
    const renderer = window.ScanProPdfRenderer;
    const uploader = window.ScanProUploader;
    const ui = window.ScanProUI;
    if (!utils || !renderer || !uploader || !ui) return;

    const alertContainer = document.getElementById('toolAlertContainer');
    const dropzoneEl = document.getElementById('mergeDropzone');
    const fileInputEl = document.getElementById('mergeFileInput');
    const browseBtnEl = document.getElementById('mergeBrowseBtn');
    const addMoreBtn = document.getElementById('addMoreBtn');
    const queueMetaEl = document.getElementById('mergeQueueMeta');
    const summaryTitleEl = document.getElementById('mergeSummaryTitle');
    const summarySubEl = document.getElementById('mergeSummarySub');
    const clearBtn = document.getElementById('mergeClearBtn');
    const fileListEl = document.getElementById('mergeFileList');
    const previewPanelEl = document.getElementById('mergePreviewPanel');
    const previewGridEl = document.getElementById('mergePreviewGrid');
    const executeBtn = document.getElementById('mergeExecuteBtn');
    const progressPanelEl = document.getElementById('mergeProgressPanel');
    const resultPanelEl = document.getElementById('mergeResultPanel');
    const resultPagesEl = document.getElementById('mergeResultPages');
    const resultSizeEl = document.getElementById('mergeResultSize');
    const downloadBtn = document.getElementById('mergeDownloadBtn');
    const downloadLabelEl = document.getElementById('mergeDownloadLabel');

    let items = [];
    let mergedBlob = null;
    let mergedFilename = '';
    let dragIndex = null;

    const clearResult = () => {
        mergedBlob = null;
        mergedFilename = '';
        if (resultPanelEl) resultPanelEl.style.display = 'none';
    };

    const resetWorkspace = () => {
        items = [];
        clearResult();
        utils.hideProgress(progressPanelEl);
        ui.clearAlert(alertContainer);
        renderQueue();
    };

    const updateSummary = () => {
        const count = items.length;
        const totalPages = items.reduce((sum, item) => sum + item.pageCount, 0);
        const totalBytes = items.reduce((sum, item) => sum + item.file.size, 0);

        if (count === 0) {
            queueMetaEl.style.display = 'none';
            addMoreBtn.style.display = 'none';
            dropzoneEl.style.display = 'block';
            previewPanelEl.style.display = 'none';
            executeBtn.disabled = true;
        } else {
            queueMetaEl.style.display = 'flex';
            addMoreBtn.style.display = 'inline-flex';
            dropzoneEl.style.display = 'none';
            previewPanelEl.style.display = 'block';
            summaryTitleEl.textContent = `${count} PDF file${count > 1 ? 's' : ''} ready`;
            summarySubEl.textContent = `${totalPages} total page${totalPages !== 1 ? 's' : ''} • ${utils.formatBytes(totalBytes)}`;
            executeBtn.disabled = count < 2;
        }
    };

    const renderPreviews = async () => {
        previewGridEl.innerHTML = '';
        for (let i = 0; i < items.length; i++) {
            const item = items[i];
            const card = document.createElement('div');
            card.className = 'thumb-card';

            const canvasWrap = document.createElement('div');
            canvasWrap.className = 'thumb-canvas-wrap';
            const canvas = document.createElement('canvas');
            canvasWrap.appendChild(canvas);

            const footer = document.createElement('div');
            footer.className = 'thumb-footer';
            footer.innerHTML = `<span class="thumb-badge">#${i + 1}</span><span>${item.pageCount} pg</span>`;

            card.appendChild(canvasWrap);
            card.appendChild(footer);
            previewGridEl.appendChild(card);

            try {
                const pdfDoc = await renderer.loadDocument(item.bytes);
                await renderer.renderPageToCanvas(pdfDoc, 1, canvas, { targetWidth: 200 });
                await renderer.destroyDocument(pdfDoc);
            } catch (err) {
                canvasWrap.innerHTML = '<span style="color:#475569;font-size:0.75rem;padding:8px;text-align:center;">Preview unavailable</span>';
            }
        }
    };

    const moveItem = (fromIdx, toIdx) => {
        if (toIdx < 0 || toIdx >= items.length || fromIdx === toIdx) return;
        const [moved] = items.splice(fromIdx, 1);
        items.splice(toIdx, 0, moved);
        clearResult();
        renderQueue();
    };

    const renderQueue = () => {
        fileListEl.innerHTML = '';

        items.forEach((item, index) => {
            const li = document.createElement('li');
            li.className = 'file-queue-item';
            li.draggable = true;
            li.dataset.index = String(index);

            const info = document.createElement('div');
            info.className = 'file-queue-info';

            const idxBadge = document.createElement('span');
            idxBadge.className = 'queue-index';
            idxBadge.textContent = String(index + 1);

            const textWrap = document.createElement('div');
            textWrap.style.minWidth = '0';

            const nameEl = document.createElement('div');
            nameEl.className = 'file-meta-name';
            nameEl.textContent = item.file.name;

            const subEl = document.createElement('div');
            subEl.className = 'file-meta-sub';
            subEl.textContent = `${item.pageCount} page${item.pageCount !== 1 ? 's' : ''} • ${utils.formatBytes(item.file.size)}`;

            textWrap.appendChild(nameEl);
            textWrap.appendChild(subEl);
            info.appendChild(idxBadge);
            info.appendChild(textWrap);

            const actions = document.createElement('div');
            actions.className = 'file-queue-actions';

            const upBtn = document.createElement('button');
            upBtn.type = 'button';
            upBtn.className = 'icon-btn';
            upBtn.title = 'Move Up';
            upBtn.disabled = index === 0;
            upBtn.innerHTML = '<i class="fa-solid fa-arrow-up" aria-hidden="true"></i>';
            upBtn.addEventListener('click', () => moveItem(index, index - 1));

            const downBtn = document.createElement('button');
            downBtn.type = 'button';
            downBtn.className = 'icon-btn';
            downBtn.title = 'Move Down';
            downBtn.disabled = index === items.length - 1;
            downBtn.innerHTML = '<i class="fa-solid fa-arrow-down" aria-hidden="true"></i>';
            downBtn.addEventListener('click', () => moveItem(index, index + 1));

            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'icon-btn icon-btn--danger';
            delBtn.title = 'Remove File';
            delBtn.innerHTML = '<i class="fa-solid fa-trash" aria-hidden="true"></i>';
            delBtn.addEventListener('click', () => {
                items.splice(index, 1);
                clearResult();
                renderQueue();
            });

            actions.appendChild(upBtn);
            actions.appendChild(downBtn);
            actions.appendChild(delBtn);

            li.appendChild(info);
            li.appendChild(actions);

            li.addEventListener('dragstart', () => {
                dragIndex = index;
                li.classList.add('is-dragging');
            });
            li.addEventListener('dragend', () => {
                dragIndex = null;
                li.classList.remove('is-dragging');
            });
            li.addEventListener('dragover', (e) => {
                e.preventDefault();
            });
            li.addEventListener('drop', (e) => {
                e.preventDefault();
                if (dragIndex !== null && dragIndex !== index) {
                    moveItem(dragIndex, index);
                }
            });

            fileListEl.appendChild(li);
        });

        updateSummary();
        if (items.length > 0) {
            renderPreviews();
        }
    };

    const handleFilesAdded = async (files) => {
        ui.clearAlert(alertContainer);
        clearResult();

        try {
            utils.ensurePdfLibraries(false);
        } catch (err) {
            ui.showAlert(alertContainer, 'error', err.message);
            return;
        }

        const errors = [];
        for (const file of files) {
            const check = await utils.validatePdfFile(file);
            if (!check.ok) {
                errors.push(check.message);
                continue;
            }
            try {
                const pdfDoc = await window.PDFLib.PDFDocument.load(check.bytes);
                const pageCount = pdfDoc.getPageCount();
                if (pageCount < 1) {
                    errors.push(`"${file.name}" has no pages.`);
                    continue;
                }
                items.push({ file, bytes: check.bytes, pageCount });
            } catch (err) {
                errors.push(`"${file.name}": ${utils.describePdfError(err)}`);
            }
        }

        const largeWarning = utils.checkLargeFileWarning(items.map((i) => i.file));
        if (errors.length > 0) {
            ui.showAlert(alertContainer, 'error', errors.join(' '));
        } else if (largeWarning) {
            ui.showAlert(alertContainer, 'warning', largeWarning);
        }

        renderQueue();
    };

    uploader.bind({
        dropzoneEl,
        inputEl: fileInputEl,
        browseBtnEl,
        multiple: true,
        onFilesSelected: handleFilesAdded
    });

    addMoreBtn.addEventListener('click', () => fileInputEl.click());
    clearBtn.addEventListener('click', resetWorkspace);

    executeBtn.addEventListener('click', async () => {
        if (items.length < 2) {
            ui.showAlert(alertContainer, 'error', 'Please select at least 2 PDF files to merge.');
            return;
        }

        ui.clearAlert(alertContainer);
        clearResult();
        ui.setButtonLoading(executeBtn, true, 'Merging...');
        utils.setProgress(progressPanelEl, 5, 'Initializing merged PDF...');

        try {
            const mergedPdf = await window.PDFLib.PDFDocument.create();
            let totalPages = 0;

            for (let i = 0; i < items.length; i++) {
                const current = items[i];
                const pct = 10 + Math.round(((i + 1) / items.length) * 80);
                utils.setProgress(progressPanelEl, pct, `Adding "${current.file.name}" (${i + 1}/${items.length})...`);

                const srcDoc = await window.PDFLib.PDFDocument.load(current.bytes);
                const indices = srcDoc.getPageIndices();
                const copiedPages = await mergedPdf.copyPages(srcDoc, indices);
                copiedPages.forEach((p) => mergedPdf.addPage(p));
                totalPages += copiedPages.length;
            }

            utils.setProgress(progressPanelEl, 95, 'Finalizing output PDF...');
            const mergedBytes = await mergedPdf.save();

            mergedBlob = new Blob([mergedBytes], { type: 'application/pdf' });
            mergedFilename = utils.buildDownloadName('MergedPDF', 'pdf');

            utils.setProgress(progressPanelEl, 100, 'Merge complete');
            resultPagesEl.textContent = String(totalPages);
            resultSizeEl.textContent = utils.formatBytes(mergedBlob.size);
            downloadLabelEl.textContent = `Download ${mergedFilename}`;
            resultPanelEl.style.display = 'block';
        } catch (err) {
            utils.hideProgress(progressPanelEl);
            ui.showAlert(alertContainer, 'error', utils.describePdfError(err));
        } finally {
            ui.setButtonLoading(executeBtn, false);
        }
    });

    downloadBtn.addEventListener('click', () => {
        if (!mergedBlob || !mergedFilename) return;
        utils.triggerDownload(mergedBlob, mergedFilename, 'application/pdf');
    });
});