/**
 * ScanPro AI - Authentication Forms (Login, Register, Forgot Password)
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    if (!window.ScanProUI) return;

    const alertBox = document.getElementById('authFormAlerts');
    const isValidEmail = (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

    const postAuthJson = async (url, payload, csrfToken) => {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(payload)
        });
        const rawText = await response.text();
        let data = null;
        try {
            data = rawText ? JSON.parse(rawText) : null;
        } catch (err) {
            data = null;
        }
        return { response, data, rawText };
    };

    const getErrorMessage = (response, data, rawText, defaultMsg) => {
        if (data && data.message) return data.message;
        const plain = (rawText || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        if (plain) {
            return `Server error (HTTP ${response.status}): ${plain.slice(0, 220)}`;
        }
        return `${defaultMsg} (HTTP ${response.status})`;
    };

    // 1. Login Form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        const emailEl = document.getElementById('loginEmail');
        const passEl = document.getElementById('loginPassword');
        const rememberEl = document.getElementById('rememberMe');
        const submitBtn = document.getElementById('loginSubmitBtn');
        const emailErr = document.getElementById('loginEmailError');
        const passErr = document.getElementById('loginPasswordError');

        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            window.ScanProUI.clearAlert(alertBox);
            if (emailErr) emailErr.textContent = '';
            if (passErr) passErr.textContent = '';

            const email = (emailEl?.value || '').trim();
            const password = passEl?.value || '';
            let valid = true;

            if (!isValidEmail(email)) {
                if (emailErr) emailErr.textContent = 'Please enter a valid email address.';
                valid = false;
            }
            if (password.length < 1) {
                if (passErr) passErr.textContent = 'Please enter your password.';
                valid = false;
            }

            if (!valid) return;

            window.ScanProUI.setButtonLoading(submitBtn, true, 'Signing in...');

            try {
                const csrfToken = loginForm.querySelector('input[name="csrf_token"]')?.value || window.ScanProUI.getCsrfToken();
                const { response, data, rawText } = await postAuthJson(
                    loginForm.getAttribute('action') || '../backend/api/login.php',
                    {
                        csrf_token: csrfToken,
                        email,
                        password,
                        remember_me: Boolean(rememberEl?.checked)
                    },
                    csrfToken
                );

                if (!response.ok || !data || !data.success) {
                    if (data?.errors) {
                        if (data.errors.email && emailErr) emailErr.textContent = data.errors.email;
                        if (data.errors.password && passErr) passErr.textContent = data.errors.password;
                    }
                    window.ScanProUI.showAlert(
                        alertBox,
                        'error',
                        getErrorMessage(response, data, rawText, 'Invalid credentials or login failed.')
                    );
                    return;
                }

                window.ScanProUI.showAlert(alertBox, 'success', data.message || 'Signed in successfully. Redirecting...');
                window.location.href = data.redirect || 'dashboard.php';
            } catch (err) {
                window.ScanProUI.showAlert(alertBox, 'error', 'Unable to reach authentication server.');
            } finally {
                window.ScanProUI.setButtonLoading(submitBtn, false);
            }
        });
    }

    // 2. Register Form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        const nameEl = document.getElementById('registerName');
        const emailEl = document.getElementById('registerEmail');
        const passEl = document.getElementById('registerPassword');
        const confirmEl = document.getElementById('registerConfirmPassword');
        const submitBtn = document.getElementById('registerSubmitBtn');

        const nameErr = document.getElementById('registerNameError');
        const emailErr = document.getElementById('registerEmailError');
        const passErr = document.getElementById('registerPasswordError');
        const confirmErr = document.getElementById('registerConfirmPasswordError');

        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            window.ScanProUI.clearAlert(alertBox);
            [nameErr, emailErr, passErr, confirmErr].forEach((el) => {
                if (el) el.textContent = '';
            });

            const fullName = (nameEl?.value || '').trim();
            const email = (emailEl?.value || '').trim();
            const password = passEl?.value || '';
            const confirmPassword = confirmEl?.value || '';
            let valid = true;

            if (fullName.length < 2) {
                if (nameErr) nameErr.textContent = 'Name must be at least 2 characters.';
                valid = false;
            }
            if (!isValidEmail(email)) {
                if (emailErr) emailErr.textContent = 'Please enter a valid email address.';
                valid = false;
            }
            if (password.length < 8) {
                if (passErr) passErr.textContent = 'Password must be at least 8 characters.';
                valid = false;
            }
            if (confirmPassword !== password) {
                if (confirmErr) confirmErr.textContent = 'Passwords do not match.';
                valid = false;
            }

            if (!valid) return;

            window.ScanProUI.setButtonLoading(submitBtn, true, 'Creating account...');

            try {
                const csrfToken = registerForm.querySelector('input[name="csrf_token"]')?.value || window.ScanProUI.getCsrfToken();
                const { response, data, rawText } = await postAuthJson(
                    registerForm.getAttribute('action') || '../backend/api/register.php',
                    {
                        csrf_token: csrfToken,
                        full_name: fullName,
                        email,
                        password,
                        confirm_password: confirmPassword
                    },
                    csrfToken
                );

                if (!response.ok || !data || !data.success) {
                    if (data?.errors) {
                        if (data.errors.full_name && nameErr) nameErr.textContent = data.errors.full_name;
                        if (data.errors.email && emailErr) emailErr.textContent = data.errors.email;
                        if (data.errors.password && passErr) passErr.textContent = data.errors.password;
                        if (data.errors.confirm_password && confirmErr) confirmErr.textContent = data.errors.confirm_password;
                    }
                    window.ScanProUI.showAlert(
                        alertBox,
                        'error',
                        getErrorMessage(response, data, rawText, 'Registration failed.')
                    );
                    return;
                }

                window.ScanProUI.showAlert(alertBox, 'success', data.message || 'Account created! Redirecting...');
                window.location.href = data.redirect || 'dashboard.php';
            } catch (err) {
                window.ScanProUI.showAlert(alertBox, 'error', 'Unable to reach registration server.');
            } finally {
                window.ScanProUI.setButtonLoading(submitBtn, false);
            }
        });
    }

    // 3. Forgot Password Form
    const forgotForm = document.getElementById('forgotPasswordForm');
    if (forgotForm) {
        const emailEl = document.getElementById('forgotEmail');
        const emailErr = document.getElementById('forgotEmailError');
        const submitBtn = document.getElementById('forgotSubmitBtn');

        forgotForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            window.ScanProUI.clearAlert(alertBox);
            if (emailErr) emailErr.textContent = '';

            const email = (emailEl?.value || '').trim();
            if (!isValidEmail(email)) {
                if (emailErr) emailErr.textContent = 'Please enter a valid email address.';
                return;
            }

            window.ScanProUI.setButtonLoading(submitBtn, true, 'Processing...');

            try {
                const csrfToken = forgotForm.querySelector('input[name="csrf_token"]')?.value || window.ScanProUI.getCsrfToken();
                const { response, data, rawText } = await postAuthJson(
                    forgotForm.getAttribute('action') || '../backend/api/forgot_password.php',
                    {
                        csrf_token: csrfToken,
                        email
                    },
                    csrfToken
                );

                if (!response.ok || !data || !data.success) {
                    if (data?.errors?.email && emailErr) {
                        emailErr.textContent = data.errors.email;
                    }
                    window.ScanProUI.showAlert(
                        alertBox,
                        'error',
                        getErrorMessage(response, data, rawText, 'Could not process password reset request.')
                    );
                    return;
                }

                const alertType = data.alertType || 'info';
                window.ScanProUI.showAlert(alertBox, alertType, data.message);
                forgotForm.reset();
            } catch (err) {
                window.ScanProUI.showAlert(alertBox, 'error', 'Unable to reach server. Please try again later.');
            } finally {
                window.ScanProUI.setButtonLoading(submitBtn, false);
            }
        });
    }
});