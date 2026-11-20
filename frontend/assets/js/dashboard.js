/**
 * ScanPro AI - Authenticated Dashboard Interactions
 */
'use strict';

document.addEventListener('DOMContentLoaded', () => {
    if (!window.ScanProUI) return;

    const alertBox = document.getElementById('dashboardAlerts');

    // 1. Sidebar Panel Switching
    const navBtns = document.querySelectorAll('.dashboard-nav-btn[data-panel]');
    const panels = document.querySelectorAll('.dashboard-panel');

    navBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.panel;
            if (!targetId) return;

            navBtns.forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');

            panels.forEach((panel) => {
                panel.style.display = panel.id === targetId ? 'block' : 'none';
            });

            window.ScanProUI.clearAlert(alertBox);
        });
    });

    // 2. Profile Update Form
    const profileForm = document.getElementById('profileUpdateForm');
    if (profileForm) {
        const nameEl = document.getElementById('profileFullName');
        const phoneEl = document.getElementById('profilePhone');
        const orgEl = document.getElementById('profileOrg');
        const langEl = document.getElementById('profileLanguage');
        const bioEl = document.getElementById('profileBio');
        const saveBtn = document.getElementById('profileSaveBtn');

        const nameErr = document.getElementById('profileFullNameError');
        const phoneErr = document.getElementById('profilePhoneError');
        const orgErr = document.getElementById('profileOrgError');
        const bioErr = document.getElementById('profileBioError');

        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            window.ScanProUI.clearAlert(alertBox);
            [nameErr, phoneErr, orgErr, bioErr].forEach((el) => {
                if (el) el.textContent = '';
            });

            const fullName = (nameEl?.value || '').trim();
            if (fullName.length < 2) {
                if (nameErr) nameErr.textContent = 'Full name must be at least 2 characters.';
                return;
            }

            window.ScanProUI.setButtonLoading(saveBtn, true, 'Saving...');

            try {
                const csrfToken = profileForm.querySelector('input[name="csrf_token"]')?.value || window.ScanProUI.getCsrfToken();
                const response = await fetch(profileForm.getAttribute('action') || '../backend/api/profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        csrf_token: csrfToken,
                        action: 'update_profile',
                        full_name: fullName,
                        phone: (phoneEl?.value || '').trim(),
                        organization: (orgEl?.value || '').trim(),
                        preferred_language: langEl?.value || 'English',
                        bio: (bioEl?.value || '').trim()
                    })
                });

                const data = await response.json().catch(() => null);

                if (!response.ok || !data || !data.success) {
                    if (data?.errors) {
                        if (data.errors.full_name && nameErr) nameErr.textContent = data.errors.full_name;
                        if (data.errors.phone && phoneErr) phoneErr.textContent = data.errors.phone;
                        if (data.errors.organization && orgErr) orgErr.textContent = data.errors.organization;
                        if (data.errors.bio && bioErr) bioErr.textContent = data.errors.bio;
                    }
                    window.ScanProUI.showAlert(alertBox, 'error', data?.message || 'Could not update profile.');
                    return;
                }

                // Update visible name, initials & language immediately
                const sidebarName = document.getElementById('sidebarUserName');
                const welcomeName = document.getElementById('welcomeUserName');
                const avatarEl = document.getElementById('sidebarAvatarInitials');
                const overviewLang = document.getElementById('overviewLang');

                if (sidebarName) sidebarName.textContent = fullName;
                if (welcomeName) welcomeName.textContent = fullName;
                if (overviewLang && langEl) overviewLang.textContent = langEl.value;
                if (avatarEl) {
                    const parts = fullName.split(/\s+/).filter(Boolean).slice(0, 2);
                    avatarEl.textContent = parts.map((w) => w.charAt(0).toUpperCase()).join('') || 'SP';
                }

                window.ScanProUI.showAlert(alertBox, 'success', data.message || 'Profile updated successfully.');
            } catch (err) {
                window.ScanProUI.showAlert(alertBox, 'error', 'Unable to save profile changes right now.');
            } finally {
                window.ScanProUI.setButtonLoading(saveBtn, false);
            }
        });
    }

    // 3. Password Change Form
    const passwordForm = document.getElementById('passwordUpdateForm');
    if (passwordForm) {
        const currEl = document.getElementById('currentPassword');
        const newEl = document.getElementById('newPassword');
        const confEl = document.getElementById('confirmNewPassword');
        const saveBtn = document.getElementById('passwordSaveBtn');

        const currErr = document.getElementById('currentPasswordError');
        const newErr = document.getElementById('newPasswordError');
        const confErr = document.getElementById('confirmNewPasswordError');

        passwordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            window.ScanProUI.clearAlert(alertBox);
            [currErr, newErr, confErr].forEach((el) => {
                if (el) el.textContent = '';
            });

            const currentPassword = currEl?.value || '';
            const newPassword = newEl?.value || '';
            const confirmNewPassword = confEl?.value || '';
            let valid = true;

            if (!currentPassword) {
                if (currErr) currErr.textContent = 'Please enter your current password.';
                valid = false;
            }
            if (newPassword.length < 8) {
                if (newErr) newErr.textContent = 'New password must be at least 8 characters.';
                valid = false;
            }
            if (confirmNewPassword !== newPassword) {
                if (confErr) confErr.textContent = 'New passwords do not match.';
                valid = false;
            }

            if (!valid) return;

            window.ScanProUI.setButtonLoading(saveBtn, true, 'Updating...');

            try {
                const csrfToken = passwordForm.querySelector('input[name="csrf_token"]')?.value || window.ScanProUI.getCsrfToken();
                const response = await fetch(passwordForm.getAttribute('action') || '../backend/api/profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({
                        csrf_token: csrfToken,
                        action: 'change_password',
                        current_password: currentPassword,
                        new_password: newPassword,
                        confirm_new_password: confirmNewPassword
                    })
                });

                const data = await response.json().catch(() => null);

                if (!response.ok || !data || !data.success) {
                    if (data?.errors) {
                        if (data.errors.current_password && currErr) currErr.textContent = data.errors.current_password;
                        if (data.errors.new_password && newErr) newErr.textContent = data.errors.new_password;
                        if (data.errors.confirm_new_password && confErr) confErr.textContent = data.errors.confirm_new_password;
                    }
                    window.ScanProUI.showAlert(alertBox, 'error', data?.message || 'Password update failed.');
                    return;
                }

                window.ScanProUI.showAlert(alertBox, 'success', data.message || 'Password updated successfully.');
                passwordForm.reset();
            } catch (err) {
                window.ScanProUI.showAlert(alertBox, 'error', 'Unable to update password right now.');
            } finally {
                window.ScanProUI.setButtonLoading(saveBtn, false);
            }
        });
    }
});