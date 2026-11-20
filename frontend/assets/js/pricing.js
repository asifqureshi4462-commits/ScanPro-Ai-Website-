/**
 * ScanPro AI - Pricing Monthly/Yearly Billing Toggle
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('billingToggleBtn');
    const monthlyLabel = document.getElementById('monthlyLabel');
    const yearlyLabel = document.getElementById('yearlyLabel');
    const cards = document.querySelectorAll('.pricing-card[data-plan-slug]');

    if (!toggleBtn || cards.length === 0) return;

    let isYearly = false;

    const renderPrices = () => {
        toggleBtn.setAttribute('aria-checked', isYearly ? 'true' : 'false');
        if (monthlyLabel) monthlyLabel.classList.toggle('active', !isYearly);
        if (yearlyLabel) yearlyLabel.classList.toggle('active', isYearly);

        cards.forEach((card) => {
            const amountEl = card.querySelector('.pricing-amount');
            const cycleEl = card.querySelector('.pricing-cycle');
            const noteEl = card.querySelector('.pricing-billed-note');

            const monthlyPrice = parseFloat(card.dataset.monthly || '0');
            const yearlyMonthly = parseFloat(card.dataset.yearlyMonthly || '0');
            const yearlyTotal = parseFloat(card.dataset.yearlyTotal || '0');

            if (!amountEl || !cycleEl) return;

            if (monthlyPrice <= 0) {
                amountEl.textContent = '$0.00';
                cycleEl.textContent = '/ month';
                if (noteEl) noteEl.textContent = 'Free forever • No card required';
                return;
            }

            if (isYearly) {
                amountEl.textContent = `$${yearlyMonthly.toFixed(2)}`;
                cycleEl.textContent = '/ month';
                if (noteEl) {
                    noteEl.textContent = `Billed $${yearlyTotal.toFixed(2)} yearly (20% savings)`;
                }
            } else {
                amountEl.textContent = `$${monthlyPrice.toFixed(2)}`;
                cycleEl.textContent = '/ month';
                if (noteEl) {
                    noteEl.textContent = 'Billed monthly';
                }
            }
        });
    };

    toggleBtn.addEventListener('click', () => {
        isYearly = !isYearly;
        renderPrices();
    });

    renderPrices();
});