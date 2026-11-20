/**
 * ScanPro AI - reveals an ad slot only after an ad has really loaded.
 * If nothing loads (ad blocker, no ad available), the slot stays collapsed: no label, no empty box.
 */
'use strict';

(function () {
    document.querySelectorAll('[data-ad-slot]').forEach((slot) => {
        const box = slot.querySelector('[data-ad-box]');
        const container = slot.querySelector('[data-ad-container]');
        if (!box || !container) return;

        const hasAd = () => container.childElementCount > 0
            || Array.from(box.children).some((el) => el !== container && el.tagName !== 'SCRIPT');

        const reveal = () => {
            if (!hasAd()) return false;
            slot.removeAttribute('data-ad-pending');
            return true;
        };

        if (reveal()) return;
        const observer = new MutationObserver(() => { if (reveal()) observer.disconnect(); });
        observer.observe(box, { childList: true, subtree: true });
        setTimeout(() => { if (reveal()) observer.disconnect(); }, 6000);
    });
})();
