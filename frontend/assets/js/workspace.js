/**
 * ScanPro AI - Web Document Workspace
 * Everything runs in the browser: upload, edit, OCR, export.
 */
'use strict';

(function () {
    const MAX_DIM = 2600;
    const MAX_PAGES = 30;
    const MAX_UNDO = 5;
    const PREVIEW_DIM = 1100;
    const SKIP_KEY = 'sp_skip_export_promo';
    const $ = (id) => document.getElementById(id);

    const PRESETS = {
        original: { b: 0, c: 0, s: 0, gray: false },
        magic: { b: 8, c: 25, s: 10, gray: false },
        lighten: { b: 20, c: 5, s: 0, gray: false },
        gray: { b: 0, c: 0, s: 0, gray: true },
        bw: { b: 10, c: 60, s: 0, gray: true },
        eco: { b: 25, c: 30, s: 0, gray: true }
    };

    const pages = [];
    let cur = -1;
    let cropMode = false;
    let cropRect = null;
    const HAS_CTX_FILTER = typeof CanvasRenderingContext2D !== 'undefined' && 'filter' in CanvasRenderingContext2D.prototype;

    /* ---------- helpers ---------- */
    function mk(w, h) { const c = document.createElement('canvas'); c.width = Math.max(1, Math.round(w)); c.height = Math.max(1, Math.round(h)); return c; }
    function clone(src) { const c = mk(src.width, src.height); c.getContext('2d').drawImage(src, 0, 0); return c; }
    function scaled(src, max) {
        const k = Math.min(1, max / Math.max(src.width, src.height));
        if (k >= 1) return src;
        const c = mk(src.width * k, src.height * k);
        c.getContext('2d').drawImage(src, 0, 0, c.width, c.height);
        return c;
    }
    function filterStr(a) {
        return `brightness(${1 + a.b / 100}) contrast(${1 + a.c / 100}) saturate(${1 + a.s / 100})${a.gray ? ' grayscale(1)' : ''}`;
    }
    function adjusted(src, a) {
        const out = mk(src.width, src.height);
        const ctx = out.getContext('2d', { willReadFrequently: !HAS_CTX_FILTER });
        if (HAS_CTX_FILTER) {
            ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, out.width, out.height);
            ctx.filter = filterStr(a);
            ctx.drawImage(src, 0, 0);
            return out;
        }
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, out.width, out.height);
        ctx.drawImage(src, 0, 0);
        const img = ctx.getImageData(0, 0, out.width, out.height);
        const d = img.data, bm = 1 + a.b / 100, cm = 1 + a.c / 100, sm = 1 + a.s / 100;
        for (let i = 0; i < d.length; i += 4) {
            let r = d[i] * bm, g = d[i + 1] * bm, bl = d[i + 2] * bm;
            const l = 0.299 * r + 0.587 * g + 0.114 * bl;
            r = l + (r - l) * sm; g = l + (g - l) * sm; bl = l + (bl - l) * sm;
            r = (r - 128) * cm + 128; g = (g - 128) * cm + 128; bl = (bl - 128) * cm + 128;
            if (a.gray) { const y = 0.299 * r + 0.587 * g + 0.114 * bl; r = g = bl = y; }
            d[i] = r < 0 ? 0 : r > 255 ? 255 : r; d[i + 1] = g < 0 ? 0 : g > 255 ? 255 : g; d[i + 2] = bl < 0 ? 0 : bl > 255 ? 255 : bl;
        }
        ctx.putImageData(img, 0, 0);
        return out;
    }
    function toBlob(canvas, type, q) { return new Promise((res) => canvas.toBlob(res, type, q)); }
    function saveBlob(blob, name) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = name;
        document.body.appendChild(a); a.click(); a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 4000);
    }
    const page = () => pages[cur];

    /* ---------- loading files ---------- */
    async function addCanvasPage(canvas, name) {
        if (pages.length >= MAX_PAGES) return false;
        const base = scaled(canvas, MAX_DIM);
        pages.push({ name, base: base === canvas ? clone(canvas) : base, preview: null, undo: [], adj: { ...PRESETS.original }, filter: 'original', text: '' });
        rebuildPreview(pages[pages.length - 1]);
        return true;
    }

    function loadImage(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => { const c = mk(img.naturalWidth, img.naturalHeight); c.getContext('2d').drawImage(img, 0, 0); URL.revokeObjectURL(url); resolve(c); };
            img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Cannot read ' + file.name)); };
            img.src = url;
        });
    }

    async function loadPdf(file) {
        if (!window.pdfjsLib) throw new Error('PDF engine is still loading. Please try again in a moment.');
        if (window.SCANPRO_PDF_LIBS) pdfjsLib.GlobalWorkerOptions.workerSrc = window.SCANPRO_PDF_LIBS.pdfJsWorkerSrc;
        const pdf = await pdfjsLib.getDocument({ data: await file.arrayBuffer() }).promise;
        const n = Math.min(pdf.numPages, MAX_PAGES - pages.length);
        for (let i = 1; i <= n; i++) {
            const p = await pdf.getPage(i);
            const vp1 = p.getViewport({ scale: 1 });
            const vp = p.getViewport({ scale: Math.min(2, MAX_DIM / Math.max(vp1.width, vp1.height)) });
            const c = mk(vp.width, vp.height);
            const ctx = c.getContext('2d');
            ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
            await p.render({ canvasContext: ctx, viewport: vp }).promise;
            await addCanvasPage(c, `${file.name} p${i}`);
        }
        if (pdf.numPages > n) notify(`Only the first ${n} pages were loaded.`);
    }

    async function handleFiles(fileList) {
        const files = Array.from(fileList || []);
        if (!files.length) return;
        const firstNew = pages.length;
        for (const f of files) {
            if (pages.length >= MAX_PAGES) { notify(`Limit of ${MAX_PAGES} pages reached.`); break; }
            try {
                if (f.type === 'application/pdf' || /\.pdf$/i.test(f.name)) await loadPdf(f);
                else if (f.type.startsWith('image/')) await addCanvasPage(await loadImage(f), f.name);
                else notify(`${f.name} is not a supported file.`);
            } catch (err) { notify(err.message || 'Could not open the file.'); }
        }
        if (pages.length > firstNew) { $('wsDrop').hidden = true; $('wsApp').hidden = false; select(firstNew); }
    }

    function notify(msg) { const s = $('wsOcrStatus'); $('wsOcrBox').hidden = false; s.textContent = msg; s.classList.add('err'); }

    /* ---------- rendering ---------- */
    function rebuildPreview(p) { p.preview = scaled(p.base, PREVIEW_DIM); }

    function render() {
        const p = page(); if (!p) return;
        const out = adjusted(p.preview, p.adj);
        const cv = $('wsCanvas');
        cv.width = out.width; cv.height = out.height;
        cv.getContext('2d').drawImage(out, 0, 0);
        const t = document.querySelector(`.ws-thumb[data-i="${cur}"] canvas`);
        if (t) { const tc = t.getContext('2d'); tc.clearRect(0, 0, t.width, t.height); tc.drawImage(out, 0, 0, t.width, t.height); }
    }

    function buildThumbs() {
        const box = $('wsPages'); box.innerHTML = '';
        pages.forEach((p, i) => {
            const d = document.createElement('div');
            d.className = 'ws-thumb' + (i === cur ? ' active' : '');
            d.dataset.i = i;
            const c = document.createElement('canvas'); c.width = 70; c.height = 88;
            const s = document.createElement('span'); s.textContent = i + 1;
            d.append(c, s);
            d.addEventListener('click', () => select(i));
            box.appendChild(d);
            const out = adjusted(p.preview, p.adj);
            const k = Math.min(70 / out.width, 88 / out.height);
            c.getContext('2d').drawImage(out, 0, 0, out.width * k, out.height * k);
        });
    }

    function syncPanel() {
        const p = page(); if (!p) return;
        $('rB').value = p.adj.b; $('rC').value = p.adj.c; $('rS').value = p.adj.s;
        $('oB').textContent = p.adj.b; $('oC').textContent = p.adj.c; $('oS').textContent = p.adj.s;
        document.querySelectorAll('#wsFilters button').forEach((b) => b.classList.toggle('active', b.dataset.f === p.filter));
        $('wsOcrText').value = p.text || '';
    }

    function select(i) {
        saveText();
        cur = Math.max(0, Math.min(i, pages.length - 1));
        cancelCrop();
        buildThumbs(); syncPanel(); render();
    }
    function saveText() { const p = page(); if (p && !$('wsOcrBox').hidden) p.text = $('wsOcrText').value; }

    /* ---------- geometry ---------- */
    function pushUndo(p) { p.undo.push(clone(p.base)); if (p.undo.length > MAX_UNDO) p.undo.shift(); }
    function geometry(fn) {
        const p = page(); if (!p) return;
        pushUndo(p);
        p.base = fn(p.base);
        rebuildPreview(p); render(); buildThumbs();
    }
    function rotate(deg) {
        geometry((src) => {
            const c = mk(src.height, src.width), ctx = c.getContext('2d');
            ctx.translate(c.width / 2, c.height / 2); ctx.rotate(deg * Math.PI / 180);
            ctx.drawImage(src, -src.width / 2, -src.height / 2);
            return c;
        });
    }
    function flip() {
        geometry((src) => { const c = mk(src.width, src.height), ctx = c.getContext('2d'); ctx.translate(c.width, 0); ctx.scale(-1, 1); ctx.drawImage(src, 0, 0); return c; });
    }
    function undo() {
        const p = page(); if (!p || !p.undo.length) return;
        p.base = p.undo.pop(); rebuildPreview(p); render(); buildThumbs();
    }

    /* ---------- crop ---------- */
    function cancelCrop() { cropMode = false; cropRect = null; $('wsCropBox').hidden = true; $('wsCropActions').hidden = true; $('wsCanvasWrap').style.cursor = ''; $('wsCanvasWrap').style.touchAction = ''; }
    function toggleCrop() { if (cropMode) { cancelCrop(); return; } cropMode = true; $('wsCropActions').hidden = false; $('wsCanvasWrap').style.cursor = 'crosshair'; $('wsCanvasWrap').style.touchAction = 'none'; }

    function setupCropDrag() {
        const wrap = $('wsCanvasWrap'), cv = $('wsCanvas'), box = $('wsCropBox');
        let start = null;
        const pt = (e) => { const r = cv.getBoundingClientRect(); return { x: Math.max(0, Math.min(r.width, e.clientX - r.left)), y: Math.max(0, Math.min(r.height, e.clientY - r.top)), r }; };
        wrap.addEventListener('pointerdown', (e) => { if (!cropMode) return; start = pt(e); wrap.setPointerCapture(e.pointerId); e.preventDefault(); });
        wrap.addEventListener('pointermove', (e) => {
            if (!cropMode || !start) return;
            const p = pt(e), w = wrap.getBoundingClientRect(), r = start.r;
            const x = Math.min(start.x, p.x), y = Math.min(start.y, p.y);
            cropRect = { x, y, w: Math.abs(p.x - start.x), h: Math.abs(p.y - start.y), dw: r.width, dh: r.height };
            box.hidden = false;
            box.style.left = (r.left - w.left + x) + 'px'; box.style.top = (r.top - w.top + y) + 'px';
            box.style.width = cropRect.w + 'px'; box.style.height = cropRect.h + 'px';
        });
        const end = () => { start = null; };
        wrap.addEventListener('pointerup', end); wrap.addEventListener('pointercancel', end);
    }
    function applyCrop() {
        if (!cropRect || cropRect.w < 10 || cropRect.h < 10) { notify('Drag on the image first to choose the area.'); return; }
        const cr = cropRect;
        geometry((src) => {
            const k = src.width / cr.dw;
            const c = mk(cr.w * k, cr.h * k);
            c.getContext('2d').drawImage(src, cr.x * k, cr.y * k, cr.w * k, cr.h * k, 0, 0, c.width, c.height);
            return c;
        });
        cancelCrop();
    }

    /* ---------- adjustments ---------- */
    function onSlider() {
        const p = page(); if (!p) return;
        p.adj = { ...p.adj, b: +$('rB').value, c: +$('rC').value, s: +$('rS').value };
        p.filter = '';
        for (const [k, v] of Object.entries(PRESETS)) if (v.b === p.adj.b && v.c === p.adj.c && v.s === p.adj.s && v.gray === p.adj.gray) p.filter = k;
        $('oB').textContent = p.adj.b; $('oC').textContent = p.adj.c; $('oS').textContent = p.adj.s;
        document.querySelectorAll('#wsFilters button').forEach((b) => b.classList.toggle('active', b.dataset.f === p.filter));
        render();
    }
    function applyFilter(name) {
        const p = page(); if (!p) return;
        p.adj = { ...PRESETS[name] }; p.filter = name;
        syncPanel(); render();
    }

    /* ---------- OCR ---------- */
    let tessPromise = null;
    function loadTesseract() {
        if (window.Tesseract) return Promise.resolve();
        if (!tessPromise) tessPromise = new Promise((res, rej) => {
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js';
            s.onload = res; s.onerror = () => { tessPromise = null; rej(new Error('Could not load the text engine. Check your connection.')); };
            document.head.appendChild(s);
        });
        return tessPromise;
    }
    async function runOcr() {
        const p = page(); if (!p) return;
        const box = $('wsOcrBox'), st = $('wsOcrStatus');
        box.hidden = false; st.classList.remove('err'); st.textContent = 'Loading text engine...';
        box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        const btn = $('wsOcr'); btn.disabled = true;
        try {
            await loadTesseract();
            const lang = $('wsLang').value;
            const res = await Tesseract.recognize(adjusted(p.base, p.adj), lang, {
                logger: (m) => { if (m.status) st.textContent = m.status.charAt(0).toUpperCase() + m.status.slice(1) + (m.progress ? ` ${Math.round(m.progress * 100)}%` : ''); }
            });
            p.text = (res.data.text || '').trim();
            $('wsOcrText').value = p.text;
            st.textContent = p.text ? 'Done. You can edit the text below.' : 'No text found on this page.';
        } catch (err) {
            st.classList.add('err'); st.textContent = err.message || 'Text extraction failed.';
        } finally { btn.disabled = false; }
    }

    /* ---------- export ---------- */
    function exportPages() { return $('wsScope').value === 'current' ? [page()] : pages; }
    const stamp = () => 'scanpro-' + new Date().toISOString().slice(0, 10);

    async function doExport() {
        saveText();
        const fmt = $('wsFormat').value, list = exportPages();
        const btn = $('wsDownload'); btn.disabled = true;
        try {
            if (fmt === 'txt') {
                const withText = list.filter((p) => (p.text || '').trim());
                if (!withText.length) { notify('No extracted text yet. Use "Extract Text" first.'); return; }
                const body = list.map((p, i) => `--- Page ${pages.indexOf(p) + 1} ---\n${(p.text || '').trim()}`).join('\n\n');
                saveBlob(new Blob([body], { type: 'text/plain;charset=utf-8' }), stamp() + '.txt');
            } else if (fmt === 'pdf') {
                if (!window.PDFLib) throw new Error('PDF engine is still loading. Please try again.');
                const doc = await PDFLib.PDFDocument.create();
                for (const p of list) {
                    const c = adjusted(p.base, p.adj);
                    const img = await doc.embedJpg(await (await toBlob(c, 'image/jpeg', 0.92)).arrayBuffer());
                    const w = 595, h = Math.round(595 * c.height / c.width);
                    doc.addPage([w, h]).drawImage(img, { x: 0, y: 0, width: w, height: h });
                }
                saveBlob(new Blob([await doc.save()], { type: 'application/pdf' }), stamp() + '.pdf');
            } else {
                const type = fmt === 'png' ? 'image/png' : 'image/jpeg';
                const blobs = [];
                for (const p of list) blobs.push(await toBlob(adjusted(p.base, p.adj), type, 0.92));
                if (blobs.length === 1) saveBlob(blobs[0], `${stamp()}.${fmt}`);
                else if (window.JSZip) {
                    const zip = new JSZip();
                    blobs.forEach((b, i) => zip.file(`page-${i + 1}.${fmt}`, b));
                    saveBlob(await zip.generateAsync({ type: 'blob' }), stamp() + '.zip');
                } else blobs.forEach((b, i) => saveBlob(b, `page-${i + 1}.${fmt}`));
            }
        } catch (err) { notify(err.message || 'Export failed.'); }
        finally { btn.disabled = false; }
    }

    /* ---------- download popup ---------- */
    let pop = null;
    function skipPromo() { try { return localStorage.getItem(SKIP_KEY) === '1'; } catch (e) { return false; } }
    function closePop() { if (pop) pop.classList.remove('open'); }
    function openPop() {
        if (!pop) {
            pop = document.createElement('div');
            pop.className = 'ws-pop-overlay';
            pop.innerHTML = `
                <div class="ws-pop" role="dialog" aria-modal="true" aria-labelledby="wsPopTitle">
                    <button type="button" class="ws-pop-x" id="wsPopX" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                    <div class="ws-pop-ok"><i class="fa-solid fa-circle-check"></i></div>
                    <h3 id="wsPopTitle">Your document is ready</h3>
                    <p class="sub">Download it now, right from your browser.</p>
                    <button type="button" class="btn btn-primary btn-block btn-lg" id="wsPopGo"><i class="fa-solid fa-download"></i><span>Continue to Download on Web</span></button>
                    <div class="ws-pop-promo">
                        <strong><i class="fa-brands fa-android"></i> Scan faster on your phone</strong>
                        <p>The ScanPro AI Android app adds camera scanning with auto edge detection.</p>
                        <a class="btn btn-secondary btn-sm btn-block" id="wsPopApp" href="ai-scanner.php#mobile-app" target="_blank" rel="noopener"><span>Get Android App</span><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                    </div>
                    <div class="ws-pop-foot">
                        <label><input type="checkbox" id="wsPopSkip"> Don't show this again</label>
                        <button type="button" id="wsPopCancel">Cancel</button>
                    </div>
                </div>`;
            document.body.appendChild(pop);
            pop.addEventListener('click', (e) => { if (e.target === pop) closePop(); });
            document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePop(); });
            $('wsPopX').addEventListener('click', closePop);
            $('wsPopCancel').addEventListener('click', closePop);
            $('wsPopGo').addEventListener('click', () => {
                if ($('wsPopSkip').checked) { try { localStorage.setItem(SKIP_KEY, '1'); } catch (e) { /* ignore */ } }
                closePop(); doExport();
            });
        }
        requestAnimationFrame(() => pop.classList.add('open'));
        setTimeout(() => $('wsPopGo').focus(), 60);
    }
    function onDownload() { if (skipPromo()) doExport(); else openPop(); }

    /* ---------- wiring ---------- */
    document.addEventListener('DOMContentLoaded', () => {
        const drop = $('wsDrop'), input = $('wsFile');
        drop.addEventListener('click', () => input.click());
        drop.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); } });
        ['dragenter', 'dragover'].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.add('over'); }));
        ['dragleave', 'drop'].forEach((ev) => drop.addEventListener(ev, (e) => { e.preventDefault(); drop.classList.remove('over'); }));
        drop.addEventListener('drop', (e) => handleFiles(e.dataTransfer.files));
        input.addEventListener('change', () => { handleFiles(input.files); input.value = ''; });

        $('wsAdd').addEventListener('click', () => input.click());
        $('wsRotL').addEventListener('click', () => rotate(-90));
        $('wsRotR').addEventListener('click', () => rotate(90));
        $('wsFlip').addEventListener('click', flip);
        $('wsUndo').addEventListener('click', undo);
        $('wsCrop').addEventListener('click', toggleCrop);
        $('wsCropApply').addEventListener('click', applyCrop);
        $('wsCropCancel').addEventListener('click', cancelCrop);
        $('wsOcr').addEventListener('click', runOcr);
        $('wsDelete').addEventListener('click', () => {
            if (!page()) return;
            pages.splice(cur, 1);
            $('wsOcrText').value = '';
            if (!pages.length) { cur = -1; cancelCrop(); $('wsApp').hidden = true; $('wsDrop').hidden = false; $('wsOcrBox').hidden = true; return; }
            select(Math.min(cur, pages.length - 1));
        });
        ['rB', 'rC', 'rS'].forEach((id) => $(id).addEventListener('input', onSlider));
        document.querySelectorAll('#wsFilters button').forEach((b) => b.addEventListener('click', () => applyFilter(b.dataset.f)));
        $('wsOcrText').addEventListener('input', () => { const p = page(); if (p) p.text = $('wsOcrText').value; });
        $('wsCopy').addEventListener('click', async () => {
            try { await navigator.clipboard.writeText($('wsOcrText').value); $('wsOcrStatus').classList.remove('err'); $('wsOcrStatus').textContent = 'Copied.'; }
            catch (e) { $('wsOcrText').select(); document.execCommand('copy'); }
        });
        $('wsDownload').addEventListener('click', onDownload);
        setupCropDrag();
    });
})();
