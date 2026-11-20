/**
 * ScanPro AI - Contact Form Validation & Submission
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('contactForm');
    if (!form || !window.ScanProUI) return;

    const alertBox = document.getElementById('contactFormAlerts');
    const submitBtn = document.getElementById('contactSubmitBtn');

    const nameInput = document.getElementById('contactName');
    const emailInput = document.getElementById('contactEmail');
    const subjectInput = document.getElementById('contactSubject');
    const messageInput = document.getElementById('contactMessage');

    const errors = {
        name: document.getElementById('contactNameError'),
        email: document.getElementById('contactEmailError'),
        subject: document.getElementById('contactSubjectError'),
        message: document.getElementById('contactMessageError')
    };

    const clearFieldErrors = () => {
        Object.values(errors).forEach((el) => {
            if (el) el.textContent = '';
        });
    };

    const isValidEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

    const validate = () => {
        clearFieldErrors();
        let valid = true;

        const name = (nameInput?.value || '').trim();
        const email = (emailInput?.value || '').trim();
        const subject = (subjectInput?.value || '').trim();
        const message = (messageInput?.value || '').trim();

        if (name.length < 2) {
            if (errors.name) errors.name.textContent = 'Please enter at least 2 characters for your name.';
            valid = false;
        }

        if (!isValidEmail(email)) {
            if (errors.email) errors.email.textContent = 'Please enter a valid email address.';
            valid = false;
        }

        if (subject.length < 3) {
            if (errors.subject) errors.subject.textContent = 'Subject must be at least 3 characters.';
            valid = false;
        }

        if (message.length < 10) {
            if (errors.message) errors.message.textContent = 'Message must be at least 10 characters.';
            valid = false;
        }

        return valid;
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        window.ScanProUI.clearAlert(alertBox);

        if (!validate()) {
            window.ScanProUI.showAlert(alertBox, 'error', 'Please fix the highlighted fields before sending.');
            return;
        }

        window.ScanProUI.setButtonLoading(submitBtn, true, 'Sending...');

        try {
            const csrfInput = form.querySelector('input[name="csrf_token"]');
            const csrfToken = csrfInput ? csrfInput.value : window.ScanProUI.getCsrfToken();

            const response = await fetch(form.getAttribute('action') || '../backend/api/contact.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    csrf_token: csrfToken,
                    name: nameInput.value.trim(),
                    email: emailInput.value.trim(),
                    subject: subjectInput.value.trim(),
                    message: messageInput.value.trim()
                })
            });

            const data = await response.json().catch(() => null);

            if (!response.ok || !data || !data.success) {
                if (data && data.errors && typeof data.errors === 'object') {
                    Object.entries(data.errors).forEach(([field, msg]) => {
                        if (errors[field]) errors[field].textContent = String(msg);
                    });
                }
                const errMsg = (data && data.message) ? data.message : 'Unable to send your message right now.';
                window.ScanProUI.showAlert(alertBox, 'error', errMsg);
                return;
            }

            window.ScanProUI.showAlert(
                alertBox,
                'success',
                data.message || 'Your message has been received and saved.'
            );
            form.reset();
        } catch (err) {
            window.ScanProUI.showAlert(
                alertBox,
                'error',
                'Network or server error while submitting your message. Please verify your server connection.'
            );
        } finally {
            window.ScanProUI.setButtonLoading(submitBtn, false);
        }
    });
});