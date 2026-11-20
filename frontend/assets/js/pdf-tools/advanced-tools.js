/**
 * ScanPro AI - Advanced PDF tools (extension for simple-tools.js)
 * Tools: pdf-to-word, pdf-to-markdown, repair-pdf, compare-pdf, sign-pdf, redact-pdf
 * Everything runs in the browser. Files are never uploaded.
 */
'use strict';

window.SCANPRO_EXTRA_TOOLS = function (h) {
    const { F, state, val, save, loadDoc, utils, renderer, esc, listPanel, listEl, runBtn, $ } = h;

    /* ================= text extraction helpers ================= */
    async function pageLines(pdfJs, pageNo) {
        const page = await pdfJs.getPage(pageNo);
        const tc = await page.getTextContent();
        const items = tc.items
            .filter((i) => typeof i.str === 'string' && i.str.trim() !== '')
            .map((i) => ({ s: i.str, x: i.transform[4], y: i.transform[5], size: Math.hypot(i.transform[0], i.transform[1]) || i.height || 10, w: i.width || 0 }));
        items.sort((a, b) => b.y - a.y || a.x - b.x);
        const lines = [];
        for (const it of items) {
            const last = lines[lines.length - 1];
            if (last && Math.abs(last.y - it.y) <= Math.max(2, it.size * 0.5)) last.parts.push(it);
            else lines.push({ y: it.y, parts: [it] });
        }
        return lines.map((l) => {
            l.parts.sort((a, b) => a.x - b.x);
            let text = '', prevEnd = null;
            for (const p of l.parts) {
                if (prevEnd !== null && p.x - prevEnd > p.size * 0.15 && !text.endsWith(' ') && !p.s.startsWith(' ')) text += ' ';
                text += p.s; prevEnd = p.x + p.w;
            }
            return { text: text.replace(/\s+/g, ' ').trim(), size: Math.max(...l.parts.map((p) => p.size)), y: l.y, x: l.parts[0].x, right: Math.max(...l.parts.map((p) => p.x + p.w)) };
        }).filter((l) => l.text);
    }

    async function documentLines(pdfJs, total, onProgress) {
        const pages = [];
        for (let i = 1; i <= total; i++) { pages.push(await pageLines(pdfJs, i)); if (onProgress) onProgress(Math.round(i / total * 100)); }
        return pages;
    }

    const BULLET = /^(?:[\uE000-\uF8FF]\s*|[•●▪◦\-–*]\s+|\d{1,3}[.)]\s+)/;

    function buildBlocks(pages) {
        const all = pages.flat();
        const sizes = all.map((l) => l.size).sort((a, b) => a - b);
        const med = sizes[Math.floor(sizes.length / 2)] || 12;
        // Heading sizes: distinct sizes clearly larger than body text, biggest first -> h1, h2, h3
        const headSizes = [...new Set(all.filter((l) => l.size / med >= 1.15 && l.text.length < 100).map((l) => Math.round(l.size * 2) / 2))].sort((a, b) => b - a).slice(0, 3);
        const blocks = [];
        pages.forEach((lines, pi) => {
            const minX = lines.length ? Math.min(...lines.map((l) => l.x)) : 0;
            const maxR = lines.length ? Math.max(...lines.map((l) => l.right)) : 0;
            let prev = null;
            lines.forEach((l) => {
                let type = 'p';
                const hi = headSizes.indexOf(Math.round(l.size * 2) / 2);
                if (hi !== -1 && l.text.length < 100) type = ['h1', 'h2', 'h3'][hi];
                else if (BULLET.test(l.text) || (l.x - minX > l.size * 1.2 && l.text.length < 90)) type = 'li';
                const gap = prev ? prev.y - l.y : 0;
                // A line continues the previous paragraph only if that line ran to the right margin.
                const prevFull = prev && prev.right >= minX + (maxR - minX) * 0.85;
                if (prev && type === 'p' && prev.type === 'p' && prevFull && gap < l.size * 1.6) blocks[blocks.length - 1].text += ' ' + l.text;
                else blocks.push({ type, text: l.text });
                prev = { y: l.y, type, right: l.right };
            });
            if (pi < pages.length - 1) blocks.push({ type: 'pagebreak' });
        });
        return blocks;
    }

    // List item text: keep "1." style numbers, turn other markers into one bullet.
    const listText = (t, bullet) => (/^\d{1,3}[.)]\s+/.test(t) ? t.replace(/^(\d{1,3})\)/, '$1.') : bullet + t.replace(BULLET, ''));

    const NO_TEXT = 'No selectable text found. This looks like a scanned PDF. Use the Web Workspace "Extract Text" (OCR) instead.';
    const hasText = (pages) => pages.some((p) => p.length > 0);

    /* ================= PDF -> Word (.docx) ================= */
    const xmlEsc = (s) => String(s).replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    async function buildDocx(blocks) {
        const W = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"';
        const head = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        const body = blocks.map((b) => {
            if (b.type === 'pagebreak') return '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
            const style = { h1: 'Heading1', h2: 'Heading2', h3: 'Heading3' }[b.type];
            const text = b.type === 'li' ? listText(b.text, '• ') : b.text;
            return `<w:p>${style ? `<w:pPr><w:pStyle w:val="${style}"/></w:pPr>` : ''}<w:r><w:t xml:space="preserve">${xmlEsc(text)}</w:t></w:r></w:p>`;
        }).join('');
        const heading = (id, name, size) => `<w:style w:type="paragraph" w:styleId="${id}"><w:name w:val="${name}"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/><w:pPr><w:keepNext/><w:spacing w:before="240" w:after="80"/></w:pPr><w:rPr><w:b/><w:sz w:val="${size}"/></w:rPr></w:style>`;
        const zip = new JSZip();
        zip.file('[Content_Types].xml', head + '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>');
        zip.file('_rels/.rels', head + '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        zip.file('word/_rels/document.xml.rels', head + '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        zip.file('word/styles.xml', head + `<w:styles ${W}><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/><w:sz w:val="22"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="120" w:line="264" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults><w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:qFormat/></w:style>${heading('Heading1', 'heading 1', 40)}${heading('Heading2', 'heading 2', 32)}${heading('Heading3', 'heading 3', 26)}</w:styles>`);
        zip.file('word/document.xml', head + `<w:document ${W}><w:body>${body}<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr></w:body></w:document>`);
        return zip.generateAsync({ type: 'uint8array', compression: 'DEFLATE' });
    }

    /* ================= line diff (compare) ================= */
    function diffLines(a, b) {
        const n = a.length, m = b.length, w = m + 1;
        const dp = new Uint16Array((n + 1) * w);
        for (let i = n - 1; i >= 0; i--) for (let j = m - 1; j >= 0; j--) {
            dp[i * w + j] = a[i] === b[j] ? dp[(i + 1) * w + j + 1] + 1 : Math.max(dp[(i + 1) * w + j], dp[i * w + j + 1]);
        }
        const ops = []; let i = 0, j = 0;
        while (i < n && j < m) {
            if (a[i] === b[j]) { ops.push(['=', i, j]); i++; j++; }
            else if (dp[(i + 1) * w + j] >= dp[i * w + j + 1]) { ops.push(['-', i, -1]); i++; }
            else { ops.push(['+', -1, j]); j++; }
        }
        while (i < n) ops.push(['-', i++, -1]);
        while (j < m) ops.push(['+', -1, j++]);
        return ops;
    }

    /* ================= signature helpers ================= */
    const sign = { drawn: false };
    function trimCanvas(src) {
        const ctx = src.getContext('2d'); const { width, height } = src; const d = ctx.getImageData(0, 0, width, height).data;
        let x0 = width, y0 = height, x1 = 0, y1 = 0;
        for (let y = 0; y < height; y++) for (let x = 0; x < width; x++) if (d[(y * width + x) * 4 + 3] > 10) { if (x < x0) x0 = x; if (x > x1) x1 = x; if (y < y0) y0 = y; if (y > y1) y1 = y; }
        if (x1 < x0) return null;
        const pad = 8, c = document.createElement('canvas');
        c.width = x1 - x0 + 1 + pad * 2; c.height = y1 - y0 + 1 + pad * 2;
        c.getContext('2d').drawImage(src, x0, y0, x1 - x0 + 1, y1 - y0 + 1, pad, pad, x1 - x0 + 1, y1 - y0 + 1);
        return c;
    }
    function typedSignature(text) {
        const c = document.createElement('canvas'); c.width = 1400; c.height = 300;
        const ctx = c.getContext('2d'); ctx.fillStyle = '#0a1a5c'; ctx.textBaseline = 'middle';
        ctx.font = 'italic 130px "Brush Script MT","Segoe Script","Lucida Handwriting","Apple Chancery",cursive';
        ctx.fillText(text.slice(0, 40), 30, 150);
        return trimCanvas(c);
    }

    /* ================= redact viewer ================= */
    const rd = { boxes: {}, page: 1 };

    /* ================= tool definitions ================= */
    return {
        'pdf-to-word': {
            dl: 'PDFtoWord', needsPdfJs: true, fields: () => [],
            async run(setP) {
                const pages = await documentLines(state.pdfJs, state.pages, (p) => setP(Math.round(p * 0.8)));
                if (!hasText(pages)) throw new Error(NO_TEXT);
                const data = await buildDocx(buildBlocks(pages));
                return { data, mime: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', ext: 'docx', summary: `Created a Word file from ${state.pages} page(s). Layout is simplified: text, headings and page breaks.` };
            }
        },

        'pdf-to-markdown': {
            dl: 'PDFtoMarkdown', needsPdfJs: true, fields: () => [],
            async run(setP) {
                const pages = await documentLines(state.pdfJs, state.pages, setP);
                if (!hasText(pages)) throw new Error(NO_TEXT);
                const md = buildBlocks(pages).map((b) => {
                    if (b.type === 'h1') return `# ${b.text}\n`;
                    if (b.type === 'h2') return `## ${b.text}\n`;
                    if (b.type === 'h3') return `### ${b.text}\n`;
                    if (b.type === 'li') return listText(b.text, '- ');
                    if (b.type === 'pagebreak') return '\n---\n';
                    return b.text + '\n';
                }).join('\n');
                return { data: new Blob([md], { type: 'text/markdown;charset=utf-8' }), mime: 'text/markdown', ext: 'md', summary: `Converted ${state.pages} page(s) to Markdown.` };
            }
        },

        'repair-pdf': {
            dl: 'Repaired', lenientLoad: true,
            fields: () => [{ k: 'info', label: 'What this does', html: '<p class="text-muted" style="font-size:.86rem;line-height:1.5">Rebuilds the PDF structure page by page into a clean file. If the structure is too damaged, pages are rendered as images instead (text is no longer selectable). Fully corrupted files cannot always be saved.</p>' }],
            async run(setP) {
                try {
                    const src = await PDFLib.PDFDocument.load(state.bytes, { ignoreEncryption: true, throwOnInvalidObject: false, updateMetadata: false });
                    const out = await PDFLib.PDFDocument.create();
                    (await out.copyPages(src, Array.from({ length: src.getPageCount() }, (_, i) => i))).forEach((p) => out.addPage(p));
                    return { ...(await save(out)), summary: `Rebuilt ${out.getPageCount()} page(s) into a clean PDF.` };
                } catch (e) {
                    if (!state.pdfJs) state.pdfJs = await renderer.loadDocument(state.bytes);
                    const out = await PDFLib.PDFDocument.create();
                    for (let i = 1; i <= state.pdfJs.numPages; i++) {
                        const c = document.createElement('canvas');
                        await renderer.renderPageToCanvas(state.pdfJs, i, c, { targetWidth: 1400 });
                        const blob = await new Promise((r) => c.toBlob(r, 'image/jpeg', 0.92));
                        const img = await out.embedJpg(await blob.arrayBuffer());
                        out.addPage([595, Math.round(595 * c.height / c.width)]).drawImage(img, { x: 0, y: 0, width: 595, height: Math.round(595 * c.height / c.width) });
                        setP(Math.round(i / state.pdfJs.numPages * 100));
                    }
                    return { ...(await save(out)), summary: `Structure was too damaged, so ${out.getPageCount()} page(s) were rebuilt as images. Text is not selectable.` };
                }
            }
        },

        'compare-pdf': {
            dl: 'Comparison', needsPdfJs: true,
            fields: () => [{ k: 'second', label: 'Second PDF (to compare with)', html: '<input type="file" id="stf_second" accept="application/pdf,.pdf" class="form-control">', help: 'The file above is the original. The second file is compared against it.' }],
            async run(setP) {
                const f = document.getElementById('stf_second').files[0];
                if (!f) throw new Error('Please choose the second PDF.');
                const check = await utils.validatePdfFile(f);
                if (!check.ok) throw new Error(check.message);
                const doc2 = await renderer.loadDocument(check.bytes);
                const toLines = (pages) => pages.flatMap((p, pi) => p.map((l) => ({ t: l.text, p: pi + 1 })));
                let A = toLines(await documentLines(state.pdfJs, state.pages, (p) => setP(Math.round(p * 0.35))));
                let B = toLines(await documentLines(doc2, doc2.numPages, (p) => setP(35 + Math.round(p * 0.35))));
                if (!A.length && !B.length) throw new Error(NO_TEXT);
                let note = '';
                if (A.length > 4000 || B.length > 4000) { A = A.slice(0, 4000); B = B.slice(0, 4000); note = 'Only the first 4000 text lines of each file were compared.'; }
                const ops = diffLines(A.map((x) => x.t), B.map((x) => x.t));
                const added = ops.filter((o) => o[0] === '+').length, removed = ops.filter((o) => o[0] === '-').length, same = ops.length - added - removed;
                const rows = ops.filter((o, i) => o[0] !== '=' || (ops[i - 1] && ops[i - 1][0] !== '=') || (ops[i + 1] && ops[i + 1][0] !== '='))
                    .map((o) => o[0] === '=' ? `<div class="s">${esc(A[o[1]].t)}</div>` : o[0] === '-' ? `<div class="d">- (page ${A[o[1]].p}) ${esc(A[o[1]].t)}</div>` : `<div class="a">+ (page ${B[o[2]].p}) ${esc(B[o[2]].t)}</div>`).join('');
                const html = `<!doctype html><meta charset="utf-8"><title>PDF comparison</title><style>body{font:15px/1.5 system-ui,sans-serif;max-width:900px;margin:24px auto;padding:0 16px;color:#111}.d{background:#fee2e2;color:#991b1b}.a{background:#dcfce7;color:#166534}.s{color:#6b7280}div{padding:2px 8px;border-radius:4px;white-space:pre-wrap}</style><h1>PDF comparison</h1><p><b>Original:</b> ${esc(state.name)}<br><b>Second:</b> ${esc(f.name)}</p><p>${added} line(s) added, ${removed} line(s) removed, ${same} unchanged. ${esc(note)}</p>${rows || '<p>No text differences found.</p>'}`;
                return { data: new Blob([html], { type: 'text/html;charset=utf-8' }), mime: 'text/html', ext: 'html', summary: added + removed === 0 ? 'No text differences found.' : `${added} added, ${removed} removed, ${same} unchanged. Download the report to see the changes.` };
            }
        },

        'sign-pdf': {
            dl: 'Signed',
            fields: () => [
                F.select('mode', 'Signature type', [['draw', 'Draw with finger or mouse'], ['type', 'Type my name']]),
                { k: 'pad', label: 'Draw your signature', html: '<canvas id="stSignPad" width="640" height="220" class="st-sign-pad" aria-label="Signature pad"></canvas><button type="button" id="stSignClear" class="btn btn-outline btn-sm" style="margin-top:8px">Clear</button>' },
                F.text('typed', 'Type your name', { placeholder: 'Your name' }),
                F.text('page', 'Page number', { type: 'number', value: 1, min: 1 }),
                F.select('pos', 'Position on page', [['br', 'Bottom right'], ['bc', 'Bottom center'], ['bl', 'Bottom left'], ['mr', 'Middle right'], ['mc', 'Center'], ['ml', 'Middle left'], ['tr', 'Top right'], ['tc', 'Top center'], ['tl', 'Top left']]),
                F.select('size', 'Signature size', [['0.18', 'Small'], ['0.28', 'Medium'], ['0.4', 'Large']]),
                { k: 'info', label: 'Note', html: '<p class="text-muted" style="font-size:.8rem;line-height:1.5">This adds a visible signature image. It is not a certified digital signature.</p>' }
            ],
            afterFields() {
                sign.drawn = false;
                $('stf_page').value = state.pages;
                $('stf_page').max = state.pages;
                const pad = $('stSignPad'), ctx = pad.getContext('2d');
                const padGroup = pad.closest('.form-group'), typedGroup = $('stf_typed').closest('.form-group');
                const toggle = () => { const draw = val('mode') === 'draw'; padGroup.style.display = draw ? '' : 'none'; typedGroup.style.display = draw ? 'none' : ''; };
                $('stf_mode').addEventListener('change', toggle); toggle();
                ctx.lineWidth = 5; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#0a1a5c';
                let down = false;
                const pt = (e) => { const r = pad.getBoundingClientRect(); return [(e.clientX - r.left) * pad.width / r.width, (e.clientY - r.top) * pad.height / r.height]; };
                pad.addEventListener('pointerdown', (e) => { down = true; pad.setPointerCapture(e.pointerId); const [x, y] = pt(e); ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(x + 0.01, y); ctx.stroke(); sign.drawn = true; e.preventDefault(); });
                pad.addEventListener('pointermove', (e) => { if (!down) return; const [x, y] = pt(e); ctx.lineTo(x, y); ctx.stroke(); });
                const up = () => { down = false; };
                pad.addEventListener('pointerup', up); pad.addEventListener('pointercancel', up);
                $('stSignClear').addEventListener('click', () => { ctx.clearRect(0, 0, pad.width, pad.height); sign.drawn = false; });
            },
            async run() {
                let sig;
                if (val('mode') === 'draw') {
                    if (!sign.drawn) throw new Error('Please draw your signature first.');
                    sig = trimCanvas($('stSignPad'));
                } else {
                    const t = String(val('typed') || '').trim();
                    if (!t) throw new Error('Please type your name.');
                    sig = typedSignature(t);
                }
                if (!sig) throw new Error('The signature is empty.');
                const pageNo = parseInt(val('page'), 10);
                if (!(pageNo >= 1 && pageNo <= state.pages)) throw new Error(`Page number must be between 1 and ${state.pages}.`);
                const blob = await new Promise((r) => sig.toBlob(r, 'image/png'));
                const doc = await loadDoc();
                const img = await doc.embedPng(await blob.arrayBuffer());
                const page = doc.getPage(pageNo - 1), { width, height } = page.getSize();
                const w = width * parseFloat(val('size')), hh = w * img.height / img.width, m = 36, pos = val('pos');
                const x = pos[1] === 'l' ? m : pos[1] === 'r' ? width - m - w : (width - w) / 2;
                const y = pos[0] === 't' ? height - m - hh : pos[0] === 'm' ? (height - hh) / 2 : m;
                page.drawImage(img, { x, y, width: w, height: hh });
                return { ...(await save(doc)), summary: `Signature added to page ${pageNo}.` };
            }
        },

        'redact-pdf': {
            dl: 'Redacted', needsPdfJs: true,
            fields: () => [{ k: 'info', label: 'How it works', html: '<p class="text-muted" style="font-size:.86rem;line-height:1.5">Drag on the page preview to cover text or images. Redacted pages are converted to images, so the hidden content is permanently removed. Pages without boxes stay unchanged.</p>' }],
            async onReady() {
                rd.boxes = {}; rd.page = 1;
                listPanel.style.display = 'block';
                listEl.classList.remove('thumb-grid');
                $('stListTitle').textContent = 'Select areas to redact';
                $('stListHint').textContent = 'Drag on the page';
                listEl.innerHTML = `<div class="rd-bar"><button type="button" class="btn btn-outline btn-sm" id="rdPrev" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></button><span id="rdLabel"></span><button type="button" class="btn btn-outline btn-sm" id="rdNext" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></button><button type="button" class="btn btn-danger btn-sm" id="rdClear">Clear this page</button></div><div class="rd-wrap" id="rdWrap"><canvas id="rdCanvas"></canvas><div id="rdBoxes"></div></div>`;
                const canvas = $('rdCanvas'), wrap = $('rdWrap'), layer = $('rdBoxes');
                const draw = () => {
                    $('rdLabel').textContent = `Page ${rd.page} / ${state.pages} (${(rd.boxes[rd.page] || []).length} box)`;
                    layer.innerHTML = (rd.boxes[rd.page] || []).map((b) => `<div class="rd-box" style="left:${b.x * 100}%;top:${b.y * 100}%;width:${b.w * 100}%;height:${b.h * 100}%"></div>`).join('');
                };
                const show = async (n) => {
                    rd.page = Math.max(1, Math.min(state.pages, n));
                    await renderer.renderPageToCanvas(state.pdfJs, rd.page, canvas, { targetWidth: Math.min(640, Math.max(240, (listEl.clientWidth || 640) - 8)) });
                    draw();
                };
                $('rdPrev').addEventListener('click', () => show(rd.page - 1));
                $('rdNext').addEventListener('click', () => show(rd.page + 1));
                $('rdClear').addEventListener('click', () => { rd.boxes[rd.page] = []; draw(); });
                let start = null, temp = null;
                const rel = (e) => { const r = canvas.getBoundingClientRect(); return [Math.max(0, Math.min(1, (e.clientX - r.left) / r.width)), Math.max(0, Math.min(1, (e.clientY - r.top) / r.height))]; };
                wrap.addEventListener('pointerdown', (e) => { start = rel(e); wrap.setPointerCapture(e.pointerId); temp = document.createElement('div'); temp.className = 'rd-box'; layer.appendChild(temp); e.preventDefault(); });
                wrap.addEventListener('pointermove', (e) => {
                    if (!start || !temp) return; const [x, y] = rel(e);
                    temp.style.left = Math.min(start[0], x) * 100 + '%'; temp.style.top = Math.min(start[1], y) * 100 + '%';
                    temp.style.width = Math.abs(x - start[0]) * 100 + '%'; temp.style.height = Math.abs(y - start[1]) * 100 + '%';
                });
                const end = (e) => {
                    if (!start) return; const [x, y] = rel(e);
                    const b = { x: Math.min(start[0], x), y: Math.min(start[1], y), w: Math.abs(x - start[0]), h: Math.abs(y - start[1]) };
                    start = null; temp = null;
                    if (b.w > 0.01 && b.h > 0.008) (rd.boxes[rd.page] = rd.boxes[rd.page] || []).push(b);
                    draw();
                };
                wrap.addEventListener('pointerup', end); wrap.addEventListener('pointercancel', () => { start = null; draw(); });
                await show(1);
            },
            async run(setP) {
                const total = Object.values(rd.boxes).reduce((s, a) => s + a.length, 0);
                if (!total) throw new Error('Draw at least one box over the content you want to hide.');
                const src = await loadDoc(), out = await PDFLib.PDFDocument.create();
                for (let i = 1; i <= state.pages; i++) {
                    const bs = rd.boxes[i];
                    if (!bs || !bs.length) { const [p] = await out.copyPages(src, [i - 1]); out.addPage(p); }
                    else {
                        const sp = src.getPage(i - 1), { width, height } = sp.getSize(), rot = sp.getRotation().angle;
                        const [pw, ph] = rot === 90 || rot === 270 ? [height, width] : [width, height];
                        const c = document.createElement('canvas');
                        await renderer.renderPageToCanvas(state.pdfJs, i, c, { targetWidth: Math.min(2000, Math.round(pw * 2.2)) });
                        const ctx = c.getContext('2d'); ctx.fillStyle = '#000';
                        bs.forEach((b) => ctx.fillRect(b.x * c.width, b.y * c.height, b.w * c.width, b.h * c.height));
                        const blob = await new Promise((r) => c.toBlob(r, 'image/jpeg', 0.92));
                        const img = await out.embedJpg(await blob.arrayBuffer());
                        out.addPage([pw, ph]).drawImage(img, { x: 0, y: 0, width: pw, height: ph });
                    }
                    setP(Math.round(i / state.pages * 100));
                }
                return { ...(await save(out)), summary: `Redacted ${total} area(s). Redacted pages are now images.` };
            }
        }
    };
};
