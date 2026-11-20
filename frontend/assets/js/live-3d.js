/**
 * ScanPro AI - Full-Website Global 3D Canvas, Site-Wide Card Tilt & Click-to-Open Motion
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const clearNavigationState = () => {
        document.body.classList.remove('sp-navigating');
        document.querySelectorAll('.is-opening').forEach((el) => el.classList.remove('is-opening'));
    };

    window.addEventListener('pageshow', clearNavigationState);
    clearNavigationState();

    // 1. Global Full-Website 3D Cyber Depth Canvas
    if (!document.getElementById('spGlobal3dBg')) {
        const bgCanvas = document.createElement('canvas');
        bgCanvas.id = 'spGlobal3dBg';
        bgCanvas.setAttribute('aria-hidden', 'true');
        document.body.prepend(bgCanvas);

        const ctx = bgCanvas.getContext('2d');
        if (ctx) {
            let w = 0;
            let h = 0;
            let scrollShift = 0;
            let gridPhase = 0;

            const stars = Array.from({ length: 38 }, (_, i) => ({
                x: (Math.random() - 0.5) * 2.2,
                y: (Math.random() - 0.5) * 2.2,
                z: 0.1 + Math.random() * 0.9,
                speed: 0.0014 + (i % 5) * 0.0004,
                color: i % 3 === 0 ? 'rgba(168, 85, 247, 0.55)' : 'rgba(34, 211, 238, 0.60)'
            }));

            const resizeBg = () => {
                const dpr = Math.min(window.devicePixelRatio || 1, 1.75);
                w = Math.max(1, window.innerWidth);
                h = Math.max(1, window.innerHeight);
                bgCanvas.width = w * dpr;
                bgCanvas.height = h * dpr;
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            };

            resizeBg();
            window.addEventListener('resize', resizeBg, { passive: true });
            window.addEventListener('scroll', () => {
                scrollShift = (window.scrollY || 0) * 0.0004;
            }, { passive: true });

            const drawGlobal3d = () => {
                ctx.clearRect(0, 0, w, h);

                const cx = w * 0.5;
                const horizonY = h * 0.58;

                // Subtle 3D floor perspective lines across bottom of viewport
                ctx.strokeStyle = 'rgba(34, 211, 238, 0.08)';
                ctx.lineWidth = 1;
                for (let i = -7; i <= 7; i++) {
                    ctx.beginPath();
                    ctx.moveTo(cx + i * 38, horizonY);
                    ctx.lineTo(cx + i * 190, h);
                    ctx.stroke();
                }

                gridPhase = (gridPhase + 0.006 + scrollShift * 0.04) % 1;
                scrollShift *= 0.92;
                for (let r = 0; r < 7; r++) {
                    const t = (r + gridPhase) / 7;
                    const y = horizonY + Math.pow(t, 1.85) * (h - horizonY);
                    ctx.beginPath();
                    ctx.moveTo(0, y);
                    ctx.lineTo(w, y);
                    ctx.stroke();
                }

                // 3D depth particles flying gently forward
                stars.forEach((p) => {
                    if (!prefersReducedMotion) {
                        p.z -= p.speed;
                        if (p.z <= 0.06) p.z = 1;
                    }
                    const px = cx + (p.x * w * 0.48) / p.z;
                    const py = h * 0.42 + (p.y * h * 0.42) / p.z;
                    const rad = Math.max(0.8, (1 - p.z) * 2.8);

                    if (px >= 0 && px <= w && py >= 0 && py <= h) {
                        ctx.fillStyle = p.color;
                        ctx.beginPath();
                        ctx.arc(px, py, rad, 0, Math.PI * 2);
                        ctx.fill();
                    }
                });

                if (!prefersReducedMotion) {
                    requestAnimationFrame(drawGlobal3d);
                }
            };

            drawGlobal3d();
        }
    }

    // 2. Smooth Animated Open on Tool Cards & Internal Links
    const animateAndNavigate = (href, cardEl) => {
        if (!href) return;
        if (prefersReducedMotion) {
            window.location.href = href;
            return;
        }
        if (cardEl) {
            cardEl.classList.add('is-opening');
        }
        document.body.classList.add('sp-navigating');
        setTimeout(() => {
            window.location.href = href;
        }, 150);
    };

    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
            return;
        }

        const anchor = e.target.closest('a[href]');
        if (anchor) {
            if (anchor.hasAttribute('download') || anchor.getAttribute('target') === '_blank') {
                return;
            }
            const rawHref = anchor.getAttribute('href') || '';
            if (
                rawHref === '' ||
                rawHref.startsWith('#') ||
                rawHref.startsWith('mailto:') ||
                rawHref.startsWith('tel:') ||
                rawHref.startsWith('javascript:')
            ) {
                return;
            }
            let targetUrl;
            try {
                targetUrl = new URL(anchor.href, window.location.href);
            } catch (err) {
                return;
            }
            if (targetUrl.origin !== window.location.origin) {
                return;
            }
            if (
                targetUrl.pathname === window.location.pathname &&
                targetUrl.search === window.location.search &&
                targetUrl.hash
            ) {
                return;
            }

            e.preventDefault();
            const card = anchor.closest('.card, .pdf-hub-card');
            animateAndNavigate(targetUrl.href, card);
            return;
        }

        const clickableCard = e.target.closest('.card--tool, .pdf-hub-card');
        if (clickableCard && !e.target.closest('button, a, input, select, textarea')) {
            const cardLink = clickableCard.querySelector('a[href]');
            if (cardLink) {
                e.preventDefault();
                animateAndNavigate(cardLink.href, clickableCard);
            }
        }
    });

    // 3. Interactive 3D Pointer Tilt across Open 3D Scenes & Website Cards
    if (!prefersReducedMotion) {
        const bindTiltTarget = (containerEl, targetEl, maxTiltDeg, usePerspectivePrefix) => {
            let rafId = null;

            containerEl.addEventListener('pointermove', (e) => {
                if (e.pointerType !== 'mouse' && e.pointerType !== 'pen') return;
                const rect = containerEl.getBoundingClientRect();
                if (!rect.width || !rect.height) return;

                const nx = (e.clientX - rect.left) / rect.width - 0.5;
                const ny = (e.clientY - rect.top) / rect.height - 0.5;
                const ry = (nx * maxTiltDeg * 2).toFixed(2);
                const rx = (-ny * maxTiltDeg * 2).toFixed(2);

                if (rafId) cancelAnimationFrame(rafId);
                rafId = requestAnimationFrame(() => {
                    targetEl.style.transform = usePerspectivePrefix
                        ? `perspective(900px) rotateX(${rx}deg) rotateY(${ry}deg) translateY(-4px)`
                        : `rotateX(${rx}deg) rotateY(${ry}deg)`;
                });
            });

            containerEl.addEventListener('pointerleave', () => {
                if (rafId) cancelAnimationFrame(rafId);
                rafId = requestAnimationFrame(() => {
                    targetEl.style.transform = '';
                });
            });
        };

        document.querySelectorAll('[data-3d-wrapper]').forEach((wrapper) => {
            const stage = wrapper.querySelector('[data-3d-stage]');
            if (stage) bindTiltTarget(wrapper, stage, 10, false);
        });

        document.querySelectorAll('.card, .pdf-hub-card').forEach((card) => {
            bindTiltTarget(card, card, 5, true);
        });
    }

    // 4. Live 3D AI Enhancement Filter Switcher
    document.querySelectorAll('[data-ai-stage]').forEach((stage) => {
        const paper = stage.querySelector('.ai-paper-3d');
        const buttons = stage.querySelectorAll('.ai-filter-btn[data-filter]');
        if (!paper || buttons.length === 0) return;

        buttons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const filterName = btn.dataset.filter || 'magic';
                paper.setAttribute('data-filter', filterName);
                buttons.forEach((b) => b.classList.toggle('active', b === btn));
            });
        });
    });
});