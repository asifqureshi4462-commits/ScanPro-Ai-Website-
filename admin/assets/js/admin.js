/**
 * ScanPro AI - Unified Control Center Client Runtime
 * Handles mobile navigation drawer, CSRF JSON requests, toast alerts, and real-data charts.
 */
(function () {
    'use strict';

    const sidebar = document.getElementById('admSidebar');
    const overlay = document.getElementById('admOverlay');
    const openBtn = document.getElementById('admMenuBtn');
    const closeBtn = document.getElementById('admDrawerClose');

    function setDrawer(open) {
        if (!sidebar || !overlay) return;
        sidebar.classList.toggle('open', open);
        overlay.classList.toggle('open', open);
        if (openBtn) {
            openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        document.body.style.overflow = open ? 'hidden' : '';
    }

    if (openBtn) openBtn.addEventListener('click', () => setDrawer(true));
    if (closeBtn) closeBtn.addEventListener('click', () => setDrawer(false));
    if (overlay) overlay.addEventListener('click', () => setDrawer(false));

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
            setDrawer(false);
        }
    });

    if (sidebar) {
        sidebar.querySelectorAll('.adm-nav-link').forEach((link) => {
            link.addEventListener('click', () => setDrawer(false));
        });
    }

    // Dismissible alerts
    document.addEventListener('click', (e) => {
        const close = e.target.closest('.adm-alert-close');
        if (close) {
            const alert = close.closest('.adm-alert');
            if (alert) alert.remove();
        }
    });

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') || '' : '';
    }

    function showToast(type, message) {
        let wrap = document.getElementById('admToastWrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'admToastWrap';
            wrap.className = 'adm-toast-wrap';
            document.body.appendChild(wrap);
        }

        const icons = {
            success: 'fa-circle-check',
            error: 'fa-circle-exclamation',
            warning: 'fa-triangle-exclamation',
            info: 'fa-circle-info'
        };

        const el = document.createElement('div');
        el.className = `adm-alert adm-alert--${type || 'info'}`;
        el.setAttribute('role', 'status');

        const icon = document.createElement('i');
        icon.className = `fa-solid ${icons[type] || icons.info}`;
        icon.setAttribute('aria-hidden', 'true');
        icon.style.marginTop = '2px';

        const text = document.createElement('div');
        text.textContent = message;

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'adm-alert-close';
        btn.setAttribute('aria-label', 'Dismiss');
        btn.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';

        el.append(icon, text, btn);
        wrap.appendChild(el);

        setTimeout(() => {
            if (el.parentNode) el.remove();
        }, 4500);
    }

    async function postJson(url, payload) {
        const csrf = getCsrfToken();
        const body = Object.assign({}, payload || {}, { csrf_token: csrf });
        const response = await fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': csrf
            },
            body: JSON.stringify(body)
        });

        if (response.status === 401) {
            window.location.href = 'login.php';
            throw new Error('Administrator session expired.');
        }

        let data = null;
        try {
            data = await response.json();
        } catch (err) {
            throw new Error('Unexpected server response.');
        }

        if (!response.ok || !data || !data.success) {
            throw new Error((data && data.message) || 'Action failed.');
        }
        return data;
    }

    // Lightweight responsive HiDPI Canvas Bar Chart (zero external dependencies)
    function renderBarChart(canvas, labels, values, colorHex) {
        if (!canvas || !canvas.getContext) return;
        const rect = canvas.parentElement ? canvas.parentElement.getBoundingClientRect() : canvas.getBoundingClientRect();
        const width = Math.max(240, Math.floor(rect.width));
        const height = Math.max(160, Math.floor(rect.height || 210));
        const dpr = Math.min(window.devicePixelRatio || 1, 2);

        canvas.width = width * dpr;
        canvas.height = height * dpr;
        const ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);
        ctx.clearRect(0, 0, width, height);

        const padL = 34;
        const padR = 12;
        const padT = 14;
        const padB = 30;
        const plotW = width - padL - padR;
        const plotH = height - padT - padB;
        const maxVal = Math.max(1, ...values);

        // Horizontal grid lines
        ctx.strokeStyle = 'rgba(255,255,255,0.06)';
        ctx.lineWidth = 1;
        ctx.fillStyle = '#64748B';
        ctx.font = '11px Inter, sans-serif';
        ctx.textAlign = 'right';
        for (let step = 0; step <= 3; step += 1) {
            const y = padT + (plotH * step) / 3;
            ctx.beginPath();
            ctx.moveTo(padL, y);
            ctx.lineTo(width - padR, y);
            ctx.stroke();

            const tickVal = Math.round(maxVal * (1 - step / 3));
            ctx.fillText(String(tickVal), padL - 6, y + 4);
        }

        const n = Math.max(1, labels.length);
        const slotW = plotW / n;
        const barW = Math.max(8, Math.min(34, slotW * 0.56));

        values.forEach((val, idx) => {
            const x = padL + idx * slotW + (slotW - barW) / 2;
            const barH = (val / maxVal) * plotH;
            const y = padT + plotH - barH;

            const grad = ctx.createLinearGradient(0, y, 0, padT + plotH);
            grad.addColorStop(0, colorHex || '#22D3EE');
            grad.addColorStop(1, 'rgba(22, 199, 181, 0.25)');
            ctx.fillStyle = grad;

            if (barH > 0) {
                const r = Math.min(5, barW / 2, barH);
                ctx.beginPath();
                ctx.moveTo(x, y + r);
                ctx.arcTo(x, y, x + barW, y, r);
                ctx.arcTo(x + barW, y, x + barW, y + r, r);
                ctx.lineTo(x + barW, padT + plotH);
                ctx.lineTo(x, padT + plotH);
                ctx.closePath();
                ctx.fill();
            }

            // Label every step (skip alternate labels on dense small screens)
            const skip = n > 8 && width < 420 && idx % 2 === 1;
            if (!skip) {
                ctx.fillStyle = '#94A3B8';
                ctx.font = '10px Inter, sans-serif';
                ctx.textAlign = 'center';
                const lbl = String(labels[idx]).slice(0, 10);
                ctx.fillText(lbl, x + barW / 2, height - 10);
            }
        });
    }

    function initDashboardCharts() {
        const canvases = document.querySelectorAll('canvas[data-chart-labels]');
        if (!canvases.length) return;

        function drawAll() {
            canvases.forEach((canvas) => {
                let labels = [];
                let values = [];
                try {
                    labels = JSON.parse(canvas.getAttribute('data-chart-labels') || '[]');
                    values = JSON.parse(canvas.getAttribute('data-chart-values') || '[]');
                } catch (e) {
                    return;
                }
                const total = values.reduce((a, b) => a + Number(b || 0), 0);
                const emptyBox = canvas.parentElement ? canvas.parentElement.querySelector('.chart-empty') : null;
                if (!labels.length || total === 0) {
                    canvas.style.display = 'none';
                    if (emptyBox) emptyBox.style.display = 'flex';
                    return;
                }
                canvas.style.display = 'block';
                if (emptyBox) emptyBox.style.display = 'none';
                renderBarChart(canvas, labels, values, canvas.getAttribute('data-chart-color') || '#22D3EE');
            });
        }

        drawAll();
        let resizeTimer = null;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(drawAll, 150);
        });
    }

    initDashboardCharts();

    window.AdminUI = {
        getCsrfToken,
        toast: showToast,
        postJson,
        renderBarChart
    };
})();