<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$admin = requireAdminWebAuth();
$settings = getSiteSettings(true);
$isMaint = ($settings['maintenance_mode'] ?? '0') === '1';

renderAdminHeader($admin, 'website', 'Website Management', 'Control maintenance mode, notices, identity, and visibility');
?>

<div class="adm-card">
    <div class="adm-card-head">
        <div>
            <h2>Live Website Status</h2>
            <p class="adm-card-note">Changes apply immediately across public pages via <code>site_runtime.php</code>.</p>
        </div>
        <span id="websiteStatusBadge" class="adm-badge <?= $isMaint ? 'badge--warn' : 'badge--ok' ?>">
            <?= $isMaint ? 'Maintenance Mode ON' : 'Website Online' ?>
        </span>
    </div>
</div>

<form id="websiteSettingsForm" action="../backend/api/admin/website.php" method="POST" novalidate style="display:flex;flex-direction:column;gap:18px;">
    <?= adminCsrfInput() ?>

    <!-- 1. Maintenance Mode -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-solid fa-triangle-exclamation" style="color:var(--adm-warning);margin-right:8px;" aria-hidden="true"></i>Maintenance Mode</h3>
        </div>

        <div class="adm-form-grid">
            <div class="adm-field adm-field--full">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Enable Website Maintenance Mode</div>
                        <div class="adm-hint">Public website pages return HTTP 503 Maintenance screen while Admin Panel stays online.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="maintenanceModeToggle" name="maintenance_mode" value="1"<?= $isMaint ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field adm-field--full">
                <label for="maintenanceMessage" class="adm-label">Maintenance Message</label>
                <textarea id="maintenanceMessage" name="maintenance_message" class="adm-textarea" maxlength="500" required><?= e($settings['maintenance_message'] ?? '') ?></textarea>
                <span class="adm-hint">Displayed to visitors when Maintenance Mode is active.</span>
            </div>
        </div>
    </section>

    <!-- 2. Announcements & Homepage Notice -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-solid fa-bullhorn" style="color:var(--adm-cyan);margin-right:8px;" aria-hidden="true"></i>Website Banners &amp; Homepage Notice</h3>
        </div>

        <div class="adm-form-grid">
            <div class="adm-field adm-field--full">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Global Website Announcement Bar</div>
                        <div class="adm-hint">Shows a dismissible banner at the top of public website pages.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="announcementEnabledToggle" name="website_announcement_enabled" value="1"<?= ($settings['website_announcement_enabled'] ?? '0') === '1' ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field adm-field--full">
                <label for="announcementText" class="adm-label">Website Announcement Message</label>
                <input type="text" id="announcementText" name="website_announcement_text" class="adm-input" maxlength="500" value="<?= e($settings['website_announcement_text'] ?? '') ?>" placeholder="e.g. New Files to ZIP/7Z tool is now live!">
            </div>

            <div class="adm-field adm-field--full">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Homepage Notice</div>
                        <div class="adm-hint">Displayed specifically on the Home page (<code>frontend/index.php</code>).</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="homepageNoticeToggle" name="homepage_notice_enabled" value="1"<?= ($settings['homepage_notice_enabled'] ?? '0') === '1' ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field adm-field--full">
                <label for="homepageNoticeText" class="adm-label">Homepage Notice Message</label>
                <input type="text" id="homepageNoticeText" name="homepage_notice_text" class="adm-input" maxlength="500" value="<?= e($settings['homepage_notice_text'] ?? '') ?>" placeholder="Optional notice shown only on the homepage">
            </div>
        </div>
    </section>

    <!-- 3. Website Identity & Contact Info -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-solid fa-globe" style="color:var(--adm-teal);margin-right:8px;" aria-hidden="true"></i>Website Configuration &amp; Contact</h3>
        </div>

        <div class="adm-form-grid">
            <div class="adm-field">
                <label for="siteName" class="adm-label">Website Name</label>
                <input type="text" id="siteName" name="site_name" class="adm-input" maxlength="80" required value="<?= e($settings['site_name'] ?? APP_NAME) ?>">
            </div>

            <div class="adm-field">
                <label for="siteTagline" class="adm-label">Website Tagline</label>
                <input type="text" id="siteTagline" name="site_tagline" class="adm-input" maxlength="160" value="<?= e($settings['site_tagline'] ?? APP_TAGLINE) ?>">
            </div>

            <div class="adm-field adm-field--full">
                <label for="siteDescription" class="adm-label">Default Meta Description</label>
                <textarea id="siteDescription" name="site_description" class="adm-textarea" maxlength="400"><?= e($settings['site_description'] ?? '') ?></textarea>
            </div>

            <div class="adm-field">
                <label for="contactEmail" class="adm-label">Public Support Email</label>
                <input type="email" id="contactEmail" name="contact_email" class="adm-input" maxlength="190" required value="<?= e($settings['contact_email'] ?? APP_SUPPORT_EMAIL) ?>">
                <span class="adm-hint">Shown on maintenance &amp; support references.</span>
            </div>

            <div class="adm-field">
                <label for="contactReceiverEmail" class="adm-label">Contact Form Receiver Email</label>
                <input type="email" id="contactReceiverEmail" name="contact_receiver_email" class="adm-input" maxlength="190" required value="<?= e($settings['contact_receiver_email'] ?? CONTACT_RECEIVER_EMAIL) ?>">
                <span class="adm-hint">Stored in runtime config for contact routing.</span>
            </div>
        </div>
    </section>

    <!-- 4. Section / Feature Visibility -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-solid fa-eye" style="color:var(--adm-blue);margin-right:8px;" aria-hidden="true"></i>Page &amp; Feature Visibility</h3>
        </div>

        <div class="adm-form-grid">
            <div class="adm-field adm-field--full">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Web Document Workspace Access</div>
                        <div class="adm-hint">Controls public access to <code>frontend/workspace.php</code>.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="showWorkspaceToggle" name="show_web_workspace" value="1"<?= ($settings['show_web_workspace'] ?? '1') === '1' ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field adm-field--full">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Pricing Page Access</div>
                        <div class="adm-hint">Controls public access to <code>frontend/pricing.php</code>.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="showPricingToggle" name="show_pricing_section" value="1"<?= ($settings['show_pricing_section'] ?? '1') === '1' ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field adm-field--full">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Android APK Download Section</div>
                        <div class="adm-hint">Controls APK download promotion visibility on <code>ai-scanner.php</code>.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="showAndroidDownloadToggle" name="show_android_download" value="1"<?= ($settings['show_android_download'] ?? '1') === '1' ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>
        </div>

        <div style="margin-top:18px;display:flex;justify-content:flex-end;">
            <button type="submit" id="saveWebsiteBtn" class="adm-btn adm-btn-primary">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                <span>Save Website Settings</span>
            </button>
        </div>
    </section>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('websiteSettingsForm');
    const saveBtn = document.getElementById('saveWebsiteBtn');
    const badge = document.getElementById('websiteStatusBadge');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const origHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><span>Saving...</span>';

        const payload = {
            site_name: document.getElementById('siteName').value.trim(),
            site_tagline: document.getElementById('siteTagline').value.trim(),
            site_description: document.getElementById('siteDescription').value.trim(),
            maintenance_mode: document.getElementById('maintenanceModeToggle').checked,
            maintenance_message: document.getElementById('maintenanceMessage').value.trim(),
            website_announcement_enabled: document.getElementById('announcementEnabledToggle').checked,
            website_announcement_text: document.getElementById('announcementText').value.trim(),
            homepage_notice_enabled: document.getElementById('homepageNoticeToggle').checked,
            homepage_notice_text: document.getElementById('homepageNoticeText').value.trim(),
            contact_email: document.getElementById('contactEmail').value.trim(),
            contact_receiver_email: document.getElementById('contactReceiverEmail').value.trim(),
            show_web_workspace: document.getElementById('showWorkspaceToggle').checked,
            show_pricing_section: document.getElementById('showPricingToggle').checked,
            show_android_download: document.getElementById('showAndroidDownloadToggle').checked
        };

        try {
            const data = await window.AdminUI.postJson(form.getAttribute('action'), payload);
            const isMaint = data.settings && data.settings.maintenance_mode === '1';
            if (badge) {
                badge.className = 'adm-badge ' + (isMaint ? 'badge--warn' : 'badge--ok');
                badge.textContent = isMaint ? 'Maintenance Mode ON' : 'Website Online';
            }
            window.AdminUI.toast('success', data.message || 'Website settings saved.');
        } catch (err) {
            window.AdminUI.toast('error', err.message || 'Could not save website settings.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = origHtml;
        }
    });
});
</script>

<?php renderAdminFooter(); ?>