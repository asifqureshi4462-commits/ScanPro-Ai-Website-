/**
 * ScanPro AI - Reusable Drag-and-Drop & Mobile File Picker Component
 */
'use strict';

(function () {
    const ScanProUploader = {
        /**
         * Binds dropzone, browse button, and hidden <input type="file"> seamlessly.
         *
         * @param {Object} config
         * @param {HTMLElement} config.dropzoneEl
         * @param {HTMLInputElement} config.inputEl
         * @param {HTMLElement} [config.browseBtnEl]
         * @param {boolean} [config.multiple=false]
         * @param {(files: File[]) => void} config.onFilesSelected
         */
        bind(config) {
            const { dropzoneEl, inputEl, browseBtnEl, multiple = false, onFilesSelected } = config || {};
            if (!dropzoneEl || !inputEl || typeof onFilesSelected !== 'function') {
                return;
            }

            inputEl.multiple = Boolean(multiple);

            const emitFiles = (fileList) => {
                if (!fileList || fileList.length === 0) return;
                const files = Array.from(fileList);
                inputEl.value = '';
                onFilesSelected(files);
            };

            inputEl.addEventListener('change', (e) => {
                emitFiles(e.target.files);
            });

            if (browseBtnEl) {
                browseBtnEl.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    inputEl.click();
                });
            }

            dropzoneEl.addEventListener('click', (e) => {
                if (e.target.closest('button, a, input')) return;
                inputEl.click();
            });

            dropzoneEl.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    inputEl.click();
                }
            });

            ['dragenter', 'dragover'].forEach((evtName) => {
                dropzoneEl.addEventListener(evtName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzoneEl.classList.add('is-dragover');
                });
            });

            ['dragleave', 'dragend'].forEach((evtName) => {
                dropzoneEl.addEventListener(evtName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzoneEl.classList.remove('is-dragover');
                });
            });

            dropzoneEl.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropzoneEl.classList.remove('is-dragover');
                const dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    emitFiles(multiple ? dt.files : [dt.files[0]]);
                }
            });
        }
    };

    window.ScanProUploader = ScanProUploader;
})();