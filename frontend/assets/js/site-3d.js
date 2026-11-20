/**
 * ScanPro AI - Site-wide 3D layer (works together with live-3d.js)
 * 1) 3D scroll reveal  2) floating 3D shapes with parallax  3) pointer glare on cards
 * 4) stacked sheets/orbit on hero scenes  5) touch-drag + gyroscope tilt for phones
 */
'use strict';

(function () {
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const root = document.documentElement;
    const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

    document.addEventListener('DOMContentLoaded', () => {
        /* ---------- 1) Scroll reveal ---------- */
        if (!reduce && 'IntersectionObserver' in window) {
            const sel = '.section-header, .card, .pricing-card, .pdf-hub-card, .faq-item, .hero-stat, .workspace-panel, .auth-card, .dashboard-panel';
            const items = Array.from(document.querySelectorAll(sel)).filter((el) => !el.closest('.sp-modal, .ws-pop, .mobile-drawer, .site-header'));
            const counters = new Map();
            items.forEach((el) => {
                const n = counters.get(el.parentElement) || 0;
                counters.set(el.parentElement, n + 1);
                el.setAttribute('data-reveal', '');
                el.style.setProperty('--rd', Math.min(n, 6) * 70 + 'ms');
            });
            root.classList.add('sp3d-ready');

            const io = new IntersectionObserver((entries) => {
                entries.forEach((en) => {
                    if (!en.isIntersecting) return;
                    const el = en.target;
                    io.unobserve(el);
                    el.classList.add('in-view');
                    setTimeout(() => { el.removeAttribute('data-reveal'); el.classList.remove('in-view'); el.style.removeProperty('--rd'); }, 1300);
                });
            }, { threshold: 0.1, rootMargin: '0px 0px -5% 0px' });
            items.forEach((el) => io.observe(el));
        }

        /* ---------- 2) Floating 3D shapes ---------- */
        const cube = (s) => `<div class="d3-cube" style="--s:${s}px"><i></i><i></i><i></i><i></i><i></i><i></i></div>`;
        const shape = (cls, pos, inner, k, extra) => `<div class="d3-shape ${cls}${extra ? ' ' + extra : ''}" style="${pos};--k:${k}">${inner}</div>`;
        const SETS = [
            ['.hero', [shape('d3-cube-wrap', 'left:47%;top:6%', cube(58), 18, 'hero-cube'), shape('d3-ring-wrap', 'right:4%;bottom:6%', '<div class="d3-ring purple" style="--s:150px"></div>', -14), shape('', 'right:40%;top:7%', '<div class="d3-gem" style="--s:38px"></div>', 22, 'hide-sm')]],
            ['.page-hero, .tool-topbar', [shape('d3-cube-wrap', 'right:6%;top:18%', cube(40), 16, 'hide-sm'), shape('d3-ring-wrap', 'left:-30px;bottom:-40px', '<div class="d3-ring" style="--s:130px"></div>', 10)]],
            ['.section--deep, .section--cyber', [shape('d3-ring-wrap', 'right:-50px;top:14%', '<div class="d3-ring" style="--s:150px"></div>', 12), shape('', 'left:4%;bottom:10%', '<div class="d3-gem" style="--s:34px"></div>', -16, 'hide-sm')]]
        ];
        const holders = [];
        SETS.forEach(([selector, shapes]) => {
            document.querySelectorAll(selector).forEach((host) => {
                if (getComputedStyle(host).position === 'static') host.style.position = 'relative';
                const box = document.createElement('div');
                box.className = 'd3-shapes';
                box.setAttribute('aria-hidden', 'true');
                box.innerHTML = shapes.join('');
                host.insertBefore(box, host.firstChild);
                holders.push({ host, box });
            });
        });

        let pRaf = 0;
        const setPointer = (x, y) => {
            if (pRaf) return;
            pRaf = requestAnimationFrame(() => { pRaf = 0; root.style.setProperty('--px', x.toFixed(3)); root.style.setProperty('--py', y.toFixed(3)); });
        };
        if (!reduce && finePointer) {
            window.addEventListener('pointermove', (e) => setPointer(e.clientX / window.innerWidth - 0.5, e.clientY / window.innerHeight - 0.5), { passive: true });
        }
        let sRaf = 0;
        const onScroll = () => {
            if (sRaf) return;
            sRaf = requestAnimationFrame(() => {
                sRaf = 0;
                const vh = window.innerHeight;
                holders.forEach(({ host, box }) => {
                    const r = host.getBoundingClientRect();
                    if (r.bottom < -100 || r.top > vh + 100) return;
                    box.style.setProperty('--sy', ((r.top + r.height / 2) / vh - 0.5) * -46);
                });
            });
        };
        if (!reduce && holders.length) { window.addEventListener('scroll', onScroll, { passive: true }); onScroll(); }

        /* ---------- 3) Pointer glare on cards ---------- */
        if (!reduce && finePointer) {
            document.addEventListener('pointermove', (e) => {
                const card = e.target.closest && e.target.closest('.card, .pricing-card, .pdf-hub-card');
                if (!card) return;
                const r = card.getBoundingClientRect();
                card.style.setProperty('--mx', ((e.clientX - r.left) / r.width * 100).toFixed(1) + '%');
                card.style.setProperty('--my', ((e.clientY - r.top) / r.height * 100).toFixed(1) + '%');
            }, { passive: true });
        }

        /* ---------- 4) Hero scene depth: orbit ring + stacked sheets ---------- */
        document.querySelectorAll('[data-3d-stage]').forEach((stage) => {
            const orbit = document.createElement('div');
            orbit.className = 'd3-orbit';
            orbit.setAttribute('aria-hidden', 'true');
            stage.insertBefore(orbit, stage.firstChild);

            const center = stage.querySelector('.scene3d-center');
            if (center && center.querySelector('.holo-doc-3d')) {
                ['s1', 's2'].forEach((c) => {
                    const sheet = document.createElement('span');
                    sheet.className = 'd3-sheet ' + c;
                    sheet.setAttribute('aria-hidden', 'true');
                    center.insertBefore(sheet, center.firstChild);
                });
            }
        });

        /* ---------- 5) Touch drag + gyroscope tilt (phones) ---------- */
        if (!reduce) {
            document.querySelectorAll('[data-3d-stage]').forEach((stage) => {
                stage.setAttribute('data-d3-drag', '');
                let startX = null;
                stage.addEventListener('pointerdown', (e) => { if (e.pointerType === 'mouse') return; startX = e.clientX; });
                stage.addEventListener('pointermove', (e) => {
                    if (startX === null || e.pointerType === 'mouse') return;
                    stage.style.transform = `rotateY(${clamp((e.clientX - startX) * 0.28, -26, 26).toFixed(1)}deg)`;
                });
                const end = () => { startX = null; stage.style.transform = ''; };
                stage.addEventListener('pointerup', end);
                stage.addEventListener('pointercancel', end);
            });

            const needsPermission = window.DeviceOrientationEvent && typeof window.DeviceOrientationEvent.requestPermission === 'function';
            if (window.DeviceOrientationEvent && !needsPermission && !finePointer) {
                let gRaf = 0;
                window.addEventListener('deviceorientation', (e) => {
                    if (e.gamma == null || gRaf) return;
                    gRaf = requestAnimationFrame(() => {
                        gRaf = 0;
                        const gx = clamp(e.gamma / 25, -1, 1);
                        root.style.setProperty('--gx', gx.toFixed(3));
                        root.style.setProperty('--px', gx.toFixed(3));
                        root.style.setProperty('--py', clamp(((e.beta || 0) - 50) / 30, -1, 1).toFixed(3));
                    });
                }, { passive: true });
            }
        }
    });
})();
