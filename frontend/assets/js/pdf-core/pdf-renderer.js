/**
 * ScanPro AI - PDF.js Rendering & Thumbnail Wrapper
 */
'use strict';

(function () {
    const getPdfJs = () => {
        const lib = window.pdfjsLib || window['pdfjs-dist/build/pdf'];
        if (!lib) {
            throw new Error('PDF.js library is not loaded.');
        }
        const workerSrc = window.SCANPRO_PDF_LIBS && window.SCANPRO_PDF_LIBS.pdfJsWorkerSrc
            ? window.SCANPRO_PDF_LIBS.pdfJsWorkerSrc
            : 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
        if (lib.GlobalWorkerOptions && lib.GlobalWorkerOptions.workerSrc !== workerSrc) {
            lib.GlobalWorkerOptions.workerSrc = workerSrc;
        }
        return lib;
    };

    const ScanProPdfRenderer = {
        /**
         * Loads a PDF document from Uint8Array using a sliced copy so the caller's
         * ArrayBuffer is never detached when transferred to the PDF.js worker.
         */
        async loadDocument(uint8Array) {
            const pdfjs = getPdfJs();
            const loadingTask = pdfjs.getDocument({
                data: uint8Array.slice(),
                isEvalSupported: false
            });
            return loadingTask.promise;
        },

        async destroyDocument(pdfDoc) {
            if (pdfDoc && typeof pdfDoc.destroy === 'function') {
                try {
                    await pdfDoc.destroy();
                } catch (err) {
                    // Ignore cleanup errors on already-disposed documents
                }
            }
        },

        /**
         * Renders a 1-based page number onto a Canvas fitted to targetWidth.
         */
        async renderPageToCanvas(pdfDoc, pageNumber, canvas, options = {}) {
            const targetWidth = options.targetWidth || 240;
            const extraRotation = options.extraRotation || 0;
            const page = await pdfDoc.getPage(pageNumber);

            const baseViewport = page.getViewport({
                scale: 1,
                rotation: (page.rotate + extraRotation) % 360
            });
            const scale = targetWidth / Math.max(baseViewport.width, 1);
            const viewport = page.getViewport({
                scale,
                rotation: (page.rotate + extraRotation) % 360
            });

            canvas.width = Math.max(1, Math.floor(viewport.width));
            canvas.height = Math.max(1, Math.floor(viewport.height));

            const ctx = canvas.getContext('2d', { alpha: false });
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            await page.render({
                canvasContext: ctx,
                viewport
            }).promise;

            if (typeof page.cleanup === 'function') {
                page.cleanup();
            }

            return { width: canvas.width, height: canvas.height };
        },

        /**
         * Renders a 1-based page at a given scale/DPI and returns a JPEG Blob,
         * releasing the temporary canvas buffer immediately afterward.
         */
        async renderPageToJpegBlob(pdfDoc, pageNumber, scale = 2.0, quality = 0.85) {
            const page = await pdfDoc.getPage(pageNumber);
            const viewport = page.getViewport({ scale });

            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.floor(viewport.width));
            canvas.height = Math.max(1, Math.floor(viewport.height));

            const ctx = canvas.getContext('2d', { alpha: false });
            ctx.fillStyle = '#FFFFFF';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            await page.render({
                canvasContext: ctx,
                viewport
            }).promise;

            const blob = await new Promise((resolve, reject) => {
                canvas.toBlob(
                    (b) => {
                        if (b) resolve(b);
                        else reject(new Error(`Failed to encode Page ${pageNumber} as JPEG.`));
                    },
                    'image/jpeg',
                    quality
                );
            });

            if (typeof page.cleanup === 'function') {
                page.cleanup();
            }
            canvas.width = 0;
            canvas.height = 0;

            return blob;
        }
    };

    window.ScanProPdfRenderer = ScanProPdfRenderer;
})();