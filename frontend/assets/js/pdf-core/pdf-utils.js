/**
 * ScanPro AI - Shared PDF Utilities, Naming, Validation & Cleanup
 */
'use strict';

(function () {
    const LARGE_FILE_BYTES = 25 * 1024 * 1024; // 25 MB warning threshold

    const ScanProPdfUtils = {
        getDateStamp() {
            const now = new Date();
            const yyyy = now.getFullYear();
            const mm = String(now.getMonth() + 1).padStart(2, '0');
            const dd = String(now.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        },

        sanitizeToken(token) {
            return String(token || 'Document')
                .replace(/[^a-zA-Z0-9_-]+/g, '')
                .replace(/^_+|_+$/g, '') || 'Document';
        },

        /**
         * Enforces required naming format: ScanProAI_ToolName_YYYY-MM-DD.ext
         */
        buildDownloadName(toolName, extension) {
            const safeTool = this.sanitizeToken(toolName);
            const safeExt = String(extension || 'pdf').replace(/^\./, '').toLowerCase();
            return `ScanProAI_${safeTool}_${this.getDateStamp()}.${safeExt}`;
        },

        formatBytes(bytes) {
            const val = Number(bytes) || 0;
            if (val < 1024) return `${val} B`;
            if (val < 1024 * 1024) return `${(val / 1024).toFixed(1)} KB`;
            return `${(val / (1024 * 1024)).toFixed(2)} MB`;
        },

        async readFileAsUint8Array(file) {
            const buffer = await file.arrayBuffer();
            return new Uint8Array(buffer);
        },

        hasPdfMagicHeader(uint8Array) {
            if (!uint8Array || uint8Array.length < 5) return false;
            const limit = Math.min(uint8Array.length - 4, 1024);
            for (let i = 0; i <= limit; i++) {
                if (
                    uint8Array[i] === 0x25 && // %
                    uint8Array[i + 1] === 0x50 && // P
                    uint8Array[i + 2] === 0x44 && // D
                    uint8Array[i + 3] === 0x46 && // F
                    uint8Array[i + 4] === 0x2d    // -
                ) {
                    return true;
                }
            }
            return false;
        },

        async validatePdfFile(file) {
            if (!file) {
                return { ok: false, message: 'No file selected.' };
            }
            if (file.size === 0) {
                return { ok: false, message: `"${file.name}" is an empty (0-byte) file.` };
            }
            const nameLower = (file.name || '').toLowerCase();
            const typeLower = (file.type || '').toLowerCase();
            const extOrMimeOk = nameLower.endsWith('.pdf') || typeLower === 'application/pdf' || typeLower === '';
            if (!extOrMimeOk) {
                return { ok: false, message: `"${file.name}" is not a PDF file.` };
            }

            try {
                const bytes = await this.readFileAsUint8Array(file);
                if (!this.hasPdfMagicHeader(bytes)) {
                    return { ok: false, message: `"${file.name}" does not contain a valid PDF header.` };
                }
                return { ok: true, bytes };
            } catch (err) {
                return { ok: false, message: `Unable to read "${file.name}" from device storage.` };
            }
        },

        isSupportedImageFile(file) {
            if (!file || file.size === 0) return false;
            const type = (file.type || '').toLowerCase();
            const name = (file.name || '').toLowerCase();
            return (
                type === 'image/jpeg' ||
                type === 'image/png' ||
                name.endsWith('.jpg') ||
                name.endsWith('.jpeg') ||
                name.endsWith('.png')
            );
        },

        checkLargeFileWarning(files) {
            const list = Array.isArray(files) ? files : [files];
            let total = 0;
            for (const f of list) {
                if (f && typeof f.size === 'number') total += f.size;
            }
            if (total >= LARGE_FILE_BYTES) {
                return `Large file warning (${this.formatBytes(total)}): browser memory limits may slow processing on mobile devices.`;
            }
            return null;
        },

        describePdfError(err) {
            const msg = String((err && err.message) || err || '');
            const lower = msg.toLowerCase();

            if (lower.includes('encrypt') || lower.includes('password')) {
                return 'This PDF is password-protected or encrypted. Browser tools cannot modify encrypted PDFs without unlocking them first.';
            }
            if (lower.includes('invalid pdf') || lower.includes('failed to parse') || lower.includes('no pdf header')) {
                return 'This file appears corrupted or is not a valid PDF document.';
            }
            if (lower.includes('memory') || lower.includes('allocation')) {
                return 'Your browser ran out of memory while processing this document. Try fewer pages or lower resolution.';
            }
            if (msg.trim()) {
                return msg;
            }
            return 'An unexpected error occurred while processing the PDF.';
        },

        /**
         * Parses 1-based ranges like "1-3, 5, 8-10" into groups and flat list.
         */
        parsePageRanges(spec, maxPages) {
            const trimmed = String(spec || '').trim();
            if (!trimmed) {
                return { ok: false, error: 'Please enter page numbers or ranges (for example: 1-3, 5).', groups: [], flat: [] };
            }

            const parts = trimmed.split(/[,;]+/).map((p) => p.trim()).filter(Boolean);
            if (parts.length === 0) {
                return { ok: false, error: 'Please enter valid page numbers.', groups: [], flat: [] };
            }

            const groups = [];
            const seen = new Set();
            const flat = [];

            for (const part of parts) {
                const segs = part.split('-').map((s) => s.trim());
                if (segs.length === 1) {
                    if (!/^\d+$/.test(segs[0])) {
                        return { ok: false, error: `"${part}" is not a valid page number.`, groups: [], flat: [] };
                    }
                    const p = parseInt(segs[0], 10);
                    if (p < 1 || p > maxPages) {
                        return { ok: false, error: `Page ${p} is out of range (document has ${maxPages} pages).`, groups: [], flat: [] };
                    }
                    groups.push([p]);
                    if (!seen.has(p)) {
                        seen.add(p);
                        flat.push(p);
                    }
                } else if (segs.length === 2) {
                    if (!/^\d*$/.test(segs[0]) || !/^\d*$/.test(segs[1]) || (segs[0] === '' && segs[1] === '')) {
                        return { ok: false, error: `"${part}" is not a valid page range.`, groups: [], flat: [] };
                    }
                    const start = segs[0] === '' ? 1 : parseInt(segs[0], 10);
                    const end = segs[1] === '' ? maxPages : parseInt(segs[1], 10);
                    if (start < 1 || end < 1 || start > maxPages || end > maxPages) {
                        return { ok: false, error: `Range "${part}" exceeds document pages (1-${maxPages}).`, groups: [], flat: [] };
                    }
                    const step = start <= end ? 1 : -1;
                    const rangePages = [];
                    for (let cur = start; step > 0 ? cur <= end : cur >= end; cur += step) {
                        rangePages.push(cur);
                        if (!seen.has(cur)) {
                            seen.add(cur);
                            flat.push(cur);
                        }
                    }
                    groups.push(rangePages);
                } else {
                    return { ok: false, error: `"${part}" is not a valid range format.`, groups: [], flat: [] };
                }
            }

            return { ok: true, error: null, groups, flat };
        },

        triggerDownload(data, filename, mimeType = 'application/pdf') {
            const blob = data instanceof Blob ? data : new Blob([data], { type: mimeType });
            const url = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = filename;
            anchor.style.display = 'none';
            document.body.appendChild(anchor);
            anchor.click();
            setTimeout(() => {
                URL.revokeObjectURL(url);
                anchor.remove();
            }, 1500);
        },

        async buildZipBlob(entries, onProgress) {
            if (typeof window.JSZip !== 'function') {
                throw new Error('JSZip library failed to load. Please check your internet connection and reload.');
            }
            const zip = new window.JSZip();
            entries.forEach((entry) => {
                zip.file(entry.name, entry.data);
            });
            return zip.generateAsync(
                { type: 'blob', compression: 'DEFLATE', compressionOptions: { level: 6 } },
                (meta) => {
                    if (typeof onProgress === 'function') {
                        onProgress(meta.percent || 0);
                    }
                }
            );
        },

        setProgress(panelEl, percent, labelText) {
            if (!panelEl) return;
            panelEl.style.display = 'block';
            const fill = panelEl.querySelector('.progress-fill');
            const label = panelEl.querySelector('.progress-label');
            const pct = panelEl.querySelector('.progress-percent');
            const clamped = Math.max(0, Math.min(100, Math.round(percent)));
            if (fill) fill.style.width = `${clamped}%`;
            if (label && labelText) label.textContent = labelText;
            if (pct) pct.textContent = `${clamped}%`;
        },

        hideProgress(panelEl) {
            if (!panelEl) return;
            panelEl.style.display = 'none';
            const fill = panelEl.querySelector('.progress-fill');
            if (fill) fill.style.width = '0%';
        },

        ensurePdfLibraries(requireZip = false) {
            if (!window.PDFLib) {
                throw new Error('PDF-lib library failed to load. Please check your connection and refresh.');
            }
            if (!window.pdfjsLib && !window['pdfjs-dist/build/pdf']) {
                throw new Error('PDF.js library failed to load. Please check your connection and refresh.');
            }
            if (requireZip && typeof window.JSZip !== 'function') {
                throw new Error('JSZip library failed to load. Please check your connection and refresh.');
            }
        }
    };

    window.ScanProPdfUtils = ScanProPdfUtils;
})();