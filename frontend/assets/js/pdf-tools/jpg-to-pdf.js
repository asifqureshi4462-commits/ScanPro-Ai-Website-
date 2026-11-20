/**
 * ScanPro AI - Tool 5: JPG / PNG to PDF
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const utils = window.ScanProPdfUtils;
    const uploader = window.ScanProUploader;
    const ui = window.ScanProUI;
    if (!utils || !uploader || !ui) return;

    const alertContainer = document.getElementById('toolAlertContainer');
    const dropzoneEl = document.getElementById('jpg2pdfDropzone');
    const fileInputEl = document.getElementById('jpg2pdfFileInput');
    const browseBtnEl = document.getElementById('jpg2pdfBrowseBtn');
    const addMoreBtn = document.getElementById('jpg2pdfAddMoreBtn');
    const metaEl = document.getElementById('jpg2pdfMeta');
    const summaryTitleEl = document.getElementById('jpg2pdfSummaryTitle');
    const summarySubEl = document.getElementById('jpg2pdfSummarySub');
    const resetBtn = document.getElementById('jpg2pdfResetBtn');
    const previewPanelEl = document.getElementById('jpg2pdfPreviewPanel');
    const thumbGridEl = document.getElementById('jpg2pdfThumbGrid');

    const pageSizeEl = document.getElementById('jpg2pdfPageSize');
    const orientationGroupEl = document.getElementById('jpg2pdfOrientationGroup');
    const orientationEl = document.getElementById('jpg2pdfOrientation');
    const marginEl = document.getElementById('jpg2pdfMargin');
    const executeBtn = document.getElementById('jpg2pdfExecuteBtn');
    const progressPanelEl = document.getElementById('jpg2pdfProgressPanel');

    const resultPanelEl = document.getElementById('jpg2pdfResultPanel');
    const resultPagesEl = document.getElementById('jpg2pdfResultPages');
    const resultSizeEl = document.getElementById('jpg2pdfResultSize');
    const downloadBtn = document.getElementById('jpg2pdfDownloadBtn');
    const downloadLabelEl = document.getElementById('jpg2pdfDownloadLabel');

    let imageItems = [];
    let outputBlob = null;
    let outputFilename = '';
    let dragIdx = null;

    const syncOrientationVisibility = () => {
        orientationGroupEl.style.display = pageSizeEl.value === 'fit' ? 'none' : 'block';
    };

    pageSizeEl.addEventListener('change', syncOrientationVisibility);
    syncOrientationVisibility();

    const clearResult = () => {
        outputBlob = null;
        outputFilename = '';
        resultPanelEl.style.display = 'none';
    };

    const revokeAllPreviews = () => {
        imageItems.forEach((item) => {
            if (item.previewUrl) URL.revokeObjectURL(item.previewUrl);
        });
    };

    const resetWorkspace = () => {
        revokeAllPreviews();
        imageItems = [];
        clearResult();
        utils.hideProgress(progressPanelEl);
        ui.clearAlert(alertContainer);
        renderGrid();
    };

    const moveImage = (fromIdx, toIdx) => {
        if (toIdx < 0 || toIdx >= imageItems.length || fromIdx === toIdx) return;
        const [moved] = imageItems.splice(fromIdx, 1);
        imageItems.splice(toIdx, 0, moved);
        clearResult();
        renderGrid();
    };

    const renderGrid = () => {
        thumbGridEl.innerHTML = '';

        if (imageItems.length === 0) {
            metaEl.style.display = 'none';
            addMoreBtn.style.display = 'none';
            previewPanelEl.style.display = 'none';
            dropzoneEl.style.display = 'block';
            executeBtn.disabled = true;
            return;
        }

        const totalSize = imageItems.reduce((sum, it) => sum + it.file.size, 0);
        summaryTitleEl.textContent = `${imageItems.length} image${imageItems.length > 1 ? 's' : ''} ready`;
        summarySubEl.textContent = `${utils.formatBytes(totalSize)} total`;
        metaEl.style.display = 'flex';
        addMoreBtn.style.display = 'inline-flex';
        previewPanelEl.style.display = 'block';
        dropzoneEl.style.display = 'none';
        executeBtn.disabled = false;

        imageItems.forEach((item, index) => {
            const card = document.createElement('div');
            card.className = 'thumb-card';
            card.draggable = true;

            const imgWrap = document.createElement('div');
            imgWrap.className = 'thumb-canvas-wrap';
            const img = document.createElement('img');
            img.src = item.previewUrl;
            img.alt = item.file.name;
            imgWrap.appendChild(img);

            const footer = document.createElement('div');
            footer.className = 'thumb-footer';
            footer.innerHTML = `<span class="thumb-badge">#${index + 1}</span><span>${item.width}×${item.height}</span>`;

            const controls = document.createElement('div');
            controls.style.display = 'flex';
            controls.style.justifyContent = 'space-between';
            controls.style.gap = '4px';

            const leftBtn = document.createElement('button');
            leftBtn.type = 'button';
            leftBtn.className = 'icon-btn';
            leftBtn.title = 'Move Earlier';
            leftBtn.disabled = index === 0;
            leftBtn.innerHTML = '<i class="fa-solid fa-arrow-left" aria-hidden="true"></i>';
            leftBtn.addEventListener('click', () => moveImage(index, index - 1));

            const rightBtn = document.createElement('button');
            rightBtn.type = 'button';
            rightBtn.className = 'icon-btn';
            rightBtn.title = 'Move Later';
            rightBtn.disabled = index === imageItems.length - 1;
            rightBtn.innerHTML = '<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>';
            rightBtn.addEventListener('click', () => moveImage(index, index + 1));

            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'icon-btn icon-btn--danger';
            delBtn.title = 'Remove Image';
            delBtn.innerHTML = '<i class="fa-solid fa-trash" aria-hidden="true"></i>';
            delBtn.addEventListener('click', () => {
                if (item.previewUrl) URL.revokeObjectURL(item.previewUrl);
                imageItems.splice(index, 1);
                clearResult();
                renderGrid();
            });

            controls.appendChild(leftBtn);
            controls.appendChild(rightBtn);
            controls.appendChild(delBtn);

            card.appendChild(imgWrap);
            card.appendChild(footer);
            card.appendChild(controls);

            card.addEventListener('dragstart', () => {
                dragIdx = index;
                card.classList.add('is-dragging');
            });
            card.addEventListener('dragend', () => {
                dragIdx = null;
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
                if (dragIdx !== null && dragIdx !== index) {
                    moveImage(dragIdx, index);
                }
            });

            thumbGridEl.appendChild(card);
        });
    };

    const loadImageDimensions = (url) =>
        new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve({ img, width: img.naturalWidth, height: img.naturalHeight });
            img.onerror = () => reject(new Error('Invalid or unreadable image file.'));
            img.src = url;
        });

    const handleImagesAdded = async (files) => {
        ui.clearAlert(alertContainer);
        clearResult();

        if (!window.PDFLib) {
            ui.showAlert(alertContainer, 'error', 'PDF-lib library failed to load. Please refresh.');
            return;
        }

        const errors = [];
        for (const file of files) {
            if (!utils.isSupportedImageFile(file)) {
                errors.push(`"${file.name}" is not a supported JPG or PNG image.`);
                continue;
            }
            const url = URL.createObjectURL(file);
            try {
                const { width, height } = await loadImageDimensions(url);
                imageItems.push({ file, previewUrl: url, width, height });
            } catch (err) {
                URL.revokeObjectURL(url);
                errors.push(`"${file.name}" appears corrupted.`);
            }
        }

        const largeWarn = utils.checkLargeFileWarning(imageItems.map((i) => i.file));
        if (errors.length > 0) {
            ui.showAlert(alertContainer, 'error', errors.join(' '));
        } else if (largeWarn) {
            ui.showAlert(alertContainer, 'warning', largeWarn);
        }

        renderGrid();
    };

    uploader.bind({
        dropzoneEl,
        inputEl: fileInputEl,
        browseBtnEl,
        multiple: true,
        onFilesSelected: handleImagesAdded
    });

    addMoreBtn.addEventListener('click', () => fileInputEl.click());
    resetBtn.addEventListener('click', resetWorkspace);

    const normalizeImageBytes = async (item) => {
        const { img, width, height } = await loadImageDimensions(item.previewUrl);
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d', { alpha: false });
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, width, height);
        ctx.drawImage(img, 0, 0, width, height);

        const isPng = (item.file.type || '').toLowerCase() === 'image/png' || item.file.name.toLowerCase().endsWith('.png');
        const mime = isPng ? 'image/png' : 'image/jpeg';
        const blob = await new Promise((resolve, reject) => {
            canvas.toBlob(
                (b) => (b ? resolve(b) : reject(new Error(`Failed to process "${item.file.name}".`))),
                mime,
                0.92
            );
        });
        canvas.width = 0;
        canvas.height = 0;
        return { bytes: new Uint8Array(await blob.arrayBuffer()), isPng };
    };

    executeBtn.addEventListener('click', async () => {
        if (imageItems.length === 0) {
            ui.showAlert(alertContainer, 'error', 'Please select at least 1 JPG or PNG image.');
            return;
        }

        ui.clearAlert(alertContainer);
        clearResult();
        ui.setButtonLoading(executeBtn, true, 'Creating PDF...');
        utils.setProgress(progressPanelEl, 5, 'Preparing PDF document...');

        try {
            const pdfDoc = await window.PDFLib.PDFDocument.create();
            const sizeChoice = pageSizeEl.value;
            const orientChoice = orientationEl.value;
            const margin = parseInt(marginEl.value || '0', 10);

            const pageSizes = {
                a4: [595.28, 841.89],
                letter: [612, 792]
            };

            for (let i = 0; i < imageItems.length; i++) {
                const item = imageItems[i];
                const pct = 10 + Math.round(((i + 1) / imageItems.length) * 82);
                utils.setProgress(progressPanelEl, pct, `Adding image ${i + 1} of ${imageItems.length}...`);

                const normalized = await normalizeImageBytes(item);
                const embedded = normalized.isPng
                    ? await pdfDoc.embedPng(normalized.bytes)
                    : await pdfDoc.embedJpg(normalized.bytes);

                let pageW;
                let pageH;

                if (sizeChoice === 'fit') {
                    pageW = embedded.width + margin * 2;
                    pageH = embedded.height + margin * 2;
                } else {
                    const [baseW, baseH] = pageSizes[sizeChoice] || pageSizes.a4;
                    const isLandscape =
                        orientChoice === 'landscape' ||
                        (orientChoice === 'auto' && embedded.width > embedded.height);
                    pageW = isLandscape ? baseH : baseW;
                    pageH = isLandscape ? baseW : baseH;
                }

                const page = pdfDoc.addPage([pageW, pageH]);
                const availW = Math.max(10, pageW - margin * 2);
                const availH = Math.max(10, pageH - margin * 2);
                const scale = Math.min(availW / embedded.width, availH / embedded.height);
                const drawW = embedded.width * scale;
                const drawH = embedded.height * scale;
                const x = (pageW - drawW) / 2;
                const y = (pageH - drawH) / 2;

                page.drawImage(embedded, { x, y, width: drawW, height: drawH });
            }

            utils.setProgress(progressPanelEl, 96, 'Saving PDF file...');
            const pdfBytes = await pdfDoc.save();
            outputBlob = new Blob([pdfBytes], { type: 'application/pdf' });
            outputFilename = utils.buildDownloadName('ImagesToPDF', 'pdf');

            utils.setProgress(progressPanelEl, 100, 'Complete');
            resultPagesEl.textContent = String(imageItems.length);
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

    window.addEventListener('beforeunload', revokeAllPreviews);
});