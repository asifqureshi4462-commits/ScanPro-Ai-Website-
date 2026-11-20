/**
 * ScanPro AI - Simple PDF tools engine
 * Tools: rotate, numbers, watermark, extract, remove, reorder, crop, totext, img2pdf
 * Runs fully in the browser with pdf-lib and pdf.js.
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('stRoot');
    if (!root) return;

    const utils = window.ScanProPdfUtils, renderer = window.ScanProPdfRenderer, uploader = window.ScanProUploader, ui = window.ScanProUI;
    if (!utils || !renderer || !uploader || !ui) return;

    const $ = (id) => document.getElementById(id);
    const slug = root.dataset.tool;
    const multi = root.dataset.multi === '1';
    const alertBox = $('toolAlertContainer');
    const optionsEl = $('stOptions'), runBtn = $('stRun'), progressEl = $('stProgress'), resultEl = $('stResult');
    const listPanel = $('stListPanel'), listEl = $('stList');

    const state = { bytes: null, name: '', pages: 0, pdfJs: null, order: [], images: [], out: null, thumbs: {} };

    const err = (m) => ui.showAlert(alertBox, 'error', m);
    const clearAlert = () => { alertBox.innerHTML = ''; };
    const fieldId = (k) => 'stf_' + k;
    const val = (k) => ($(fieldId(k)) || {}).value;
    const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    /* ---------- option fields ---------- */
    const F = {
        text: (k, label, o = {}) => ({ k, label, html: `<input type="${o.type || 'text'}" id="${fieldId(k)}" class="form-control" value="${esc(o.value ?? '')}" placeholder="${esc(o.placeholder || '')}"${o.min !== undefined ? ` min="${o.min}"` : ''}${o.max !== undefined ? ` max="${o.max}"` : ''}${o.step ? ` step="${o.step}"` : ''}>`, help: o.help }),
        select: (k, label, opts, help) => ({ k, label, html: `<select id="${fieldId(k)}" class="form-control">${opts.map(([v, t]) => `<option value="${esc(v)}">${esc(t)}</option>`).join('')}</select>`, help })
    };
    const pagesField = (required) => F.text('pages', required ? 'Pages' : 'Pages (leave empty for all)', { placeholder: 'e.g. 1-3, 5, 8-10', help: 'Use commas and dashes. Pages start at 1.' });

    function renderFields(fields) {
        optionsEl.innerHTML = fields.map((f) => `<div class="form-group"><label class="form-label" for="${fieldId(f.k)}">${esc(f.label)}</label>${f.html}${f.help ? `<small class="text-muted" style="display:block;margin-top:6px;font-size:0.8rem;">${esc(f.help)}</small>` : ''}</div>`).join('');
    }

    function pageSet(required) {
        const spec = String(val('pages') || '').trim();
        if (!spec) {
            if (required) return { error: 'Please enter the page numbers.' };
            return { list: Array.from({ length: state.pages }, (_, i) => i + 1) };
        }
        const r = utils.parsePageRanges(spec, state.pages);
        if (!r.ok) return { error: r.error };
        return { list: r.flat };
    }

    const save = async (doc) => ({ data: await doc.save(), mime: 'application/pdf', ext: 'pdf' });
    const loadDoc = () => PDFLib.PDFDocument.load(state.bytes);

    /* ---------- tool definitions ---------- */
    const TOOLS = {
        rotate: {
            dl: 'Rotated', fields: () => [F.select('angle', 'Rotation', [['90', '90° clockwise'], ['180', '180°'], ['270', '90° counter-clockwise']]), pagesField(false)],
            async run() {
                const sel = pageSet(false); if (sel.error) throw new Error(sel.error);
                const doc = await loadDoc(); const angle = parseInt(val('angle'), 10);
                new Set(sel.list).forEach((n) => { const p = doc.getPage(n - 1); p.setRotation(PDFLib.degrees((p.getRotation().angle + angle) % 360)); });
                return { ...(await save(doc)), summary: `Rotated ${new Set(sel.list).size} page(s).` };
            }
        },
        numbers: {
            dl: 'PageNumbers', fields: () => [
                F.select('pos', 'Position', [['bc', 'Bottom center'], ['br', 'Bottom right'], ['bl', 'Bottom left'], ['tc', 'Top center'], ['tr', 'Top right'], ['tl', 'Top left']]),
                F.select('fmt', 'Format', [['n', '1, 2, 3'], ['p', 'Page 1, Page 2'], ['of', '1 / 10, 2 / 10']]),
                F.text('start', 'Start numbering at', { type: 'number', value: 1, min: 0, max: 9999 }),
                F.text('size', 'Font size', { type: 'number', value: 12, min: 6, max: 48 })],
            async run() {
                const doc = await loadDoc(); const font = await doc.embedFont(PDFLib.StandardFonts.Helvetica);
                const start = parseInt(val('start'), 10) || 1, size = Math.max(6, parseInt(val('size'), 10) || 12), pos = val('pos'), fmt = val('fmt');
                const total = doc.getPageCount();
                doc.getPages().forEach((p, i) => {
                    const n = start + i;
                    const label = fmt === 'p' ? `Page ${n}` : fmt === 'of' ? `${n} / ${start + total - 1}` : String(n);
                    const { width, height } = p.getSize(); const w = font.widthOfTextAtSize(label, size); const m = 28;
                    const x = pos[1] === 'l' ? m : pos[1] === 'r' ? width - m - w : (width - w) / 2;
                    const y = pos[0] === 't' ? height - m - size : m;
                    p.drawText(label, { x, y, size, font, color: PDFLib.rgb(0.2, 0.2, 0.2) });
                });
                return { ...(await save(doc)), summary: `Numbered ${total} page(s).` };
            }
        },
        watermark: {
            dl: 'Watermarked', fields: () => [
                F.text('text', 'Watermark text', { value: 'CONFIDENTIAL', help: 'Latin letters, numbers and common symbols only.' }),
                F.text('size', 'Font size', { type: 'number', value: 60, min: 10, max: 200 }),
                F.select('opacity', 'Opacity', [['0.15', 'Light'], ['0.3', 'Medium'], ['0.5', 'Strong']]),
                F.select('angle', 'Angle', [['45', 'Diagonal'], ['0', 'Horizontal']]),
                F.select('color', 'Color', [['gray', 'Gray'], ['red', 'Red'], ['blue', 'Blue'], ['black', 'Black']]), pagesField(false)],
            async run() {
                const text = String(val('text') || '').trim(); if (!text) throw new Error('Please enter the watermark text.');
                const sel = pageSet(false); if (sel.error) throw new Error(sel.error);
                const doc = await loadDoc(); const font = await doc.embedFont(PDFLib.StandardFonts.HelveticaBold);
                const size = Math.max(10, parseInt(val('size'), 10) || 60), opacity = parseFloat(val('opacity')), th = (parseInt(val('angle'), 10) * Math.PI) / 180;
                const col = { gray: [0.5, 0.5, 0.5], red: [0.85, 0.1, 0.1], blue: [0.1, 0.3, 0.85], black: [0, 0, 0] }[val('color')];
                let w; try { w = font.widthOfTextAtSize(text, size); } catch (e) { throw new Error('This text has characters the watermark font does not support. Use Latin letters.'); }
                const h = size * 0.7;
                new Set(sel.list).forEach((n) => {
                    const p = doc.getPage(n - 1); const { width, height } = p.getSize();
                    const x = width / 2 - (w / 2) * Math.cos(th) + (h / 2) * Math.sin(th);
                    const y = height / 2 - (w / 2) * Math.sin(th) - (h / 2) * Math.cos(th);
                    p.drawText(text, { x, y, size, font, opacity, rotate: PDFLib.degrees(th * 180 / Math.PI), color: PDFLib.rgb(...col) });
                });
                return { ...(await save(doc)), summary: `Watermark added to ${new Set(sel.list).size} page(s).` };
            }
        },
        extract: {
            dl: 'ExtractedPages', fields: () => [pagesField(true)],
            async run() {
                const sel = pageSet(true); if (sel.error) throw new Error(sel.error);
                const src = await loadDoc(), out = await PDFLib.PDFDocument.create();
                (await out.copyPages(src, sel.list.map((n) => n - 1))).forEach((p) => out.addPage(p));
                return { ...(await save(out)), summary: `Extracted ${sel.list.length} page(s).` };
            }
        },
        remove: {
            dl: 'PagesDeleted', fields: () => [F.text('pages', 'Pages to delete', { placeholder: 'e.g. 2, 4-6', help: 'These pages will be removed. Pages start at 1.' })],
            async run() {
                const sel = pageSet(true); if (sel.error) throw new Error(sel.error);
                const drop = new Set(sel.list), keep = [];
                for (let i = 1; i <= state.pages; i++) if (!drop.has(i)) keep.push(i - 1);
                if (!keep.length) throw new Error('You cannot delete every page. Keep at least one.');
                const src = await loadDoc(), out = await PDFLib.PDFDocument.create();
                (await out.copyPages(src, keep)).forEach((p) => out.addPage(p));
                return { ...(await save(out)), summary: `Deleted ${drop.size} page(s). ${keep.length} remain.` };
            }
        },
        reorder: {
            dl: 'Rearranged', thumbs: true, fields: () => [],
            async run() {
                if (!state.order.length) throw new Error('Keep at least one page.');
                const src = await loadDoc(), out = await PDFLib.PDFDocument.create();
                (await out.copyPages(src, state.order.map((n) => n - 1))).forEach((p) => out.addPage(p));
                return { ...(await save(out)), summary: `Saved ${state.order.length} page(s) in your order.` };
            }
        },
        crop: {
            dl: 'Cropped', fields: () => [
                F.text('top', 'Trim top (mm)', { type: 'number', value: 10, min: 0, step: 1 }), F.text('bottom', 'Trim bottom (mm)', { type: 'number', value: 10, min: 0, step: 1 }),
                F.text('left', 'Trim left (mm)', { type: 'number', value: 10, min: 0, step: 1 }), F.text('right', 'Trim right (mm)', { type: 'number', value: 10, min: 0, step: 1 }), pagesField(false)],
            async run() {
                const sel = pageSet(false); if (sel.error) throw new Error(sel.error);
                const k = 72 / 25.4, t = (parseFloat(val('top')) || 0) * k, b = (parseFloat(val('bottom')) || 0) * k, l = (parseFloat(val('left')) || 0) * k, r = (parseFloat(val('right')) || 0) * k;
                const doc = await loadDoc();
                for (const n of new Set(sel.list)) {
                    const p = doc.getPage(n - 1), mb = p.getMediaBox(); const w = mb.width - l - r, h = mb.height - t - b;
                    if (w < 36 || h < 36) throw new Error(`The trim is too large for page ${n}. Use smaller values.`);
                    p.setCropBox(mb.x + l, mb.y + b, w, h); p.setMediaBox(mb.x + l, mb.y + b, w, h);
                }
                return { ...(await save(doc)), summary: `Cropped ${new Set(sel.list).size} page(s).` };
            }
        },
        totext: {
            dl: 'Text', needsPdfJs: true, fields: () => [],
            async run(setP) {
                let out = '';
                for (let i = 1; i <= state.pages; i++) {
                    const page = await state.pdfJs.getPage(i), tc = await page.getTextContent(); let line = '', txt = '';
                    tc.items.forEach((it) => { line += it.str; if (it.hasEOL) { txt += line + '\n'; line = ''; } else if (it.str) line += ' '; });
                    out += `--- Page ${i} ---\n${(txt + line).trim()}\n\n`; setP(Math.round(i / state.pages * 100));
                }
                if (!out.replace(/--- Page \d+ ---/g, '').trim()) throw new Error('No selectable text found. This looks like a scanned PDF. Use the Web Workspace and "Extract Text" instead.');
                return { data: new Blob([out], { type: 'text/plain;charset=utf-8' }), mime: 'text/plain', ext: 'txt', summary: `Text extracted from ${state.pages} page(s).` };
            }
        },
        img2pdf: {
            dl: 'ImagesToPDF', fields: () => [
                F.select('size', 'Page size', [['a4', 'A4'], ['letter', 'US Letter'], ['fit', 'Fit to image']]),
                F.select('margin', 'Margin', [['0', 'None'], ['20', 'Small'], ['40', 'Medium']])],
            async run(setP) {
                if (!state.images.length) throw new Error('Add at least one image.');
                const doc = await PDFLib.PDFDocument.create(); const sizes = { a4: [595, 842], letter: [612, 792] };
                const mode = val('size'), m = parseInt(val('margin'), 10) || 0;
                for (let i = 0; i < state.images.length; i++) {
                    const f = state.images[i]; let img;
                    const type = (f.type || '').toLowerCase();
                    if (type === 'image/jpeg' || /\.jpe?g$/i.test(f.name)) img = await doc.embedJpg(await f.arrayBuffer());
                    else if (type === 'image/png' || /\.png$/i.test(f.name)) img = await doc.embedPng(await f.arrayBuffer());
                    else img = await doc.embedJpg(await webpToJpeg(f));
                    let pw, ph;
                    if (mode === 'fit') { const k = Math.min(1, 842 / Math.max(img.width, img.height)); pw = Math.round(img.width * k); ph = Math.round(img.height * k); }
                    else { [pw, ph] = sizes[mode]; if (img.width > img.height) [pw, ph] = [ph, pw]; }
                    const page = doc.addPage([pw, ph]), aw = pw - 2 * m, ah = ph - 2 * m, k = Math.min(aw / img.width, ah / img.height);
                    const w = img.width * k, h = img.height * k;
                    page.drawImage(img, { x: (pw - w) / 2, y: (ph - h) / 2, width: w, height: h });
                    setP(Math.round((i + 1) / state.images.length * 100));
                }
                return { ...(await save(doc)), summary: `Created a PDF with ${state.images.length} page(s).` };
            }
        }
    };
    const SLUG_TO_TOOL = { 'rotate-pdf': 'rotate', 'add-page-numbers': 'numbers', 'watermark-pdf': 'watermark', 'extract-pages': 'extract', 'remove-pages': 'remove', 'reorder-pages': 'reorder', 'crop-pdf': 'crop', 'pdf-to-text': 'totext', 'jpg-to-pdf': 'img2pdf' };
    // Extra tools (advanced-tools.js) plug into the same engine.
    if (typeof window.SCANPRO_EXTRA_TOOLS === 'function') {
        Object.assign(TOOLS, window.SCANPRO_EXTRA_TOOLS({ F, state, val, pageSet, save, loadDoc, utils, renderer, ui, $, esc, err, listPanel, listEl, optionsEl, runBtn }));
    }
    const tool = TOOLS[SLUG_TO_TOOL[slug]] || TOOLS[slug];
    if (!tool) return;

    async function webpToJpeg(file) {
        const url = URL.createObjectURL(file);
        try {
            const img = await new Promise((res, rej) => { const i = new Image(); i.onload = () => res(i); i.onerror = () => rej(new Error('Cannot read ' + file.name)); i.src = url; });
            const c = document.createElement('canvas'); c.width = img.naturalWidth; c.height = img.naturalHeight;
            const x = c.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(0, 0, c.width, c.height); x.drawImage(img, 0, 0);
            return await (await new Promise((r) => c.toBlob(r, 'image/jpeg', 0.92))).arrayBuffer();
        } finally { URL.revokeObjectURL(url); }
    }

    /* ---------- list UI (page order / image order) ---------- */
    function thumbCard(label, canvasSrc, onUp, onDown, onDel, imgSrc) {
        const card = document.createElement('div'); card.className = 'thumb-card';
        const wrap = document.createElement('div'); wrap.className = 'thumb-canvas-wrap';
        if (canvasSrc) { const c = document.createElement('canvas'); c.width = canvasSrc.width; c.height = canvasSrc.height; c.getContext('2d').drawImage(canvasSrc, 0, 0); wrap.appendChild(c); }
        else if (imgSrc) { const im = document.createElement('img'); im.src = imgSrc; im.alt = label; wrap.appendChild(im); }
        const foot = document.createElement('div'); foot.className = 'thumb-footer';
        foot.innerHTML = `<span class="thumb-badge">${esc(label)}</span><span style="display:flex;gap:4px;">
            <button type="button" class="btn btn-outline btn-sm" data-a="up" aria-label="Move earlier"><i class="fa-solid fa-arrow-left"></i></button>
            <button type="button" class="btn btn-outline btn-sm" data-a="down" aria-label="Move later"><i class="fa-solid fa-arrow-right"></i></button>
            <button type="button" class="btn btn-danger btn-sm" data-a="del" aria-label="Remove"><i class="fa-solid fa-xmark"></i></button></span>`;
        foot.querySelector('[data-a=up]').addEventListener('click', onUp);
        foot.querySelector('[data-a=down]').addEventListener('click', onDown);
        foot.querySelector('[data-a=del]').addEventListener('click', onDel);
        card.append(wrap, foot); return card;
    }
    const move = (arr, i, d) => { const j = i + d; if (j < 0 || j >= arr.length) return; [arr[i], arr[j]] = [arr[j], arr[i]]; };

    function renderOrderList() {
        listEl.innerHTML = '';
        state.order.forEach((n, i) => listEl.appendChild(thumbCard(`Page ${n}`, state.thumbs[n], () => { move(state.order, i, -1); renderOrderList(); }, () => { move(state.order, i, 1); renderOrderList(); }, () => { state.order.splice(i, 1); renderOrderList(); })));
        $('stListHint').textContent = `${state.order.length} of ${state.pages} pages kept`;
        runBtn.disabled = state.order.length === 0;
    }
    function renderImageList() {
        listEl.innerHTML = '';
        state.images.forEach((f, i) => {
            if (!f._url) f._url = URL.createObjectURL(f);
            listEl.appendChild(thumbCard(`${i + 1}. ${f.name.slice(0, 14)}`, null, () => { move(state.images, i, -1); renderImageList(); }, () => { move(state.images, i, 1); renderImageList(); }, () => { URL.revokeObjectURL(f._url); state.images.splice(i, 1); if (!state.images.length) reset(); else renderImageList(); }, f._url));
        });
        $('stName').textContent = `${state.images.length} image(s)`;
        $('stSub').textContent = utils.formatBytes(state.images.reduce((s, f) => s + f.size, 0));
        $('stListHint').textContent = 'Use the arrows to set the page order';
    }

    /* ---------- file handling ---------- */
    async function reset() {
        if (state.pdfJs) { try { await renderer.destroyDocument(state.pdfJs); } catch (e) { /* ignore */ } }
        state.images.forEach((f) => f._url && URL.revokeObjectURL(f._url));
        Object.assign(state, { bytes: null, name: '', pages: 0, pdfJs: null, order: [], images: [], out: null, thumbs: {} });
        $('stMeta').style.display = 'none'; $('stDrop').style.display = ''; listPanel.style.display = 'none'; listEl.innerHTML = '';
        resultEl.style.display = 'none'; utils.hideProgress(progressEl); runBtn.disabled = true; optionsEl.innerHTML = ''; clearAlert();
    }

    async function onPdf(files) {
        clearAlert(); resultEl.style.display = 'none';
        const check = await utils.validatePdfFile(files[0]);
        if (!check.ok) { err(check.message); return; }
        try {
            let count = 0;
            try {
                const opts = tool.lenientLoad ? { ignoreEncryption: true, throwOnInvalidObject: false } : undefined;
                count = (await PDFLib.PDFDocument.load(check.bytes, opts)).getPageCount();
            } catch (e) { if (!tool.lenientLoad) throw e; }
            state.bytes = check.bytes; state.name = files[0].name;
            if (tool.thumbs || tool.needsPdfJs || (tool.lenientLoad && !count)) state.pdfJs = await renderer.loadDocument(check.bytes);
            state.pages = count || (state.pdfJs ? state.pdfJs.numPages : 0);
            if (!state.pages) throw new Error('This PDF is too damaged to read.');
        } catch (e) { err(utils.describePdfError(e)); return; }
        const warn = utils.checkLargeFileWarning(files); if (warn) ui.showAlert(alertBox, 'warning', warn);
        $('stName').textContent = state.name; $('stSub').textContent = `${state.pages} pages • ${utils.formatBytes(files[0].size)}`;
        $('stMeta').style.display = 'flex'; $('stDrop').style.display = 'none';
        renderFields(tool.fields()); runBtn.disabled = false;
        if (tool.afterFields) tool.afterFields();
        if (tool.onReady) await tool.onReady();
        if (tool.thumbs) {
            listPanel.style.display = 'block'; $('stListTitle').textContent = 'Page order'; state.order = Array.from({ length: state.pages }, (_, i) => i + 1);
            for (let n = 1; n <= state.pages; n++) { const c = document.createElement('canvas'); await renderer.renderPageToCanvas(state.pdfJs, n, c, { targetWidth: 160 }); state.thumbs[n] = c; }
            renderOrderList();
        }
    }

    function onImages(files) {
        clearAlert(); resultEl.style.display = 'none';
        const ok = files.filter((f) => /^image\/(jpeg|png|webp)$/i.test(f.type) || /\.(jpe?g|png|webp)$/i.test(f.name));
        if (!ok.length) { err('Please choose JPG, PNG or WebP images.'); return; }
        if (ok.length < files.length) ui.showAlert(alertBox, 'warning', 'Some files were skipped because they are not supported images.');
        state.images.push(...ok);
        $('stMeta').style.display = 'flex'; listPanel.style.display = 'block'; $('stListTitle').textContent = 'Images';
        renderFields(tool.fields()); renderImageList(); runBtn.disabled = false;
    }

    uploader.bind({ dropzoneEl: $('stDrop'), inputEl: $('stInput'), browseBtnEl: $('stBrowse'), multiple: multi, onFilesSelected: multi ? onImages : onPdf });
    if (multi) { // allow adding more images after the first batch
        const addBtn = document.createElement('button'); addBtn.type = 'button'; addBtn.className = 'btn btn-secondary btn-sm'; addBtn.innerHTML = '<i class="fa-solid fa-plus"></i><span>Add more</span>';
        addBtn.addEventListener('click', () => $('stInput').click()); $('stReset').before(addBtn);
    }
    $('stReset').addEventListener('click', reset);

    runBtn.addEventListener('click', async () => {
        clearAlert(); resultEl.style.display = 'none'; runBtn.disabled = true;
        utils.setProgress(progressEl, 10, 'Processing...');
        try {
            const setP = (p) => utils.setProgress(progressEl, Math.max(10, p), 'Processing...');
            const out = await tool.run(setP);
            state.out = out; utils.setProgress(progressEl, 100, 'Done');
            setTimeout(() => utils.hideProgress(progressEl), 400);
            $('stResultText').textContent = out.summary || 'Ready';
            resultEl.style.display = 'block';
        } catch (e) {
            utils.hideProgress(progressEl); err(utils.describePdfError(e));
        } finally { runBtn.disabled = false; }
    });

    $('stDownload').addEventListener('click', () => {
        if (!state.out) return;
        utils.triggerDownload(state.out.data, utils.buildDownloadName(tool.dl, state.out.ext), state.out.mime);
    });
});
