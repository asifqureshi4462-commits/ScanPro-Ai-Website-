<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$admin = requireAdminWebAuth();
$config = getAndroidAppConfig(true);

$isActive = ((int) ($config['is_active'] ?? 1)) === 1;
$isMaint = ((int) ($config['maintenance_mode'] ?? 0)) === 1;

$toDateTimeLocal = static function (?string $dbVal): string {
    if ($dbVal === null || trim($dbVal) === '') {
        return '';
    }
    $ts = strtotime($dbVal);
    return $ts !== false ? date('Y-m-d\TH:i', $ts) : '';
};

$statusBadgeClass = 'badge--ok';
$statusLabel = 'App Active (v' . (string) ($config['latest_version'] ?? '1.0') . ')';
if (!$isActive) {
    $statusBadgeClass = 'badge--danger';
    $statusLabel = 'App Disabled';
} elseif ($isMaint) {
    $statusBadgeClass = 'badge--warn';
    $statusLabel = 'Maintenance Mode ON';
}

renderAdminHeader($admin, 'android', 'Android App Management', 'Remote config, version control & in-app announcements');
?>

<div class="adm-alert adm-alert--info" role="note">
    <i class="fa-brands fa-android" aria-hidden="true" style="margin-top:2px;"></i>
    <div>
        <strong>Remote Config API Ready:</strong> Changes saved here update the live JSON endpoint
        <code>/backend/api/v1/app-config.php</code> immediately. Your Android Kotlin app (<code>com.aistudio.scanproai.app</code>) will honor these controls once integrated to fetch this endpoint.
    </div>
</div>

<div class="adm-card">
    <div class="adm-card-head" style="margin-bottom:8px;">
        <div>
            <h2>Application Remote Status</h2>
            <p class="adm-card-note">Test the public JSON response consumed by the Android app.</p>
        </div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span id="androidStatusBadge" class="adm-badge <?= e($statusBadgeClass) ?>"><?= e($statusLabel) ?></span>
            <a href="../backend/api/v1/app-config.php?version_code=1" target="_blank" rel="noopener noreferrer" class="adm-btn adm-btn-secondary adm-btn-sm">
                <i class="fa-solid fa-code" aria-hidden="true"></i>
                <span>Preview JSON API</span>
            </a>
        </div>
    </div>
</div>

<form id="androidConfigForm" action="../backend/api/admin/android.php" method="POST" novalidate style="display:flex;flex-direction:column;gap:18px;">
    <?= adminCsrfInput() ?>

    <!-- 1. App Status & Maintenance -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-solid fa-power-off" style="color:var(--adm-cyan);margin-right:8px;" aria-hidden="true"></i>App Status &amp; Maintenance</h3>
        </div>

        <div class="adm-form-grid">
            <div class="adm-field adm-field--full">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Application Active</div>
                        <div class="adm-hint">Master switch reported as <code>app.is_active</code> in the remote config API.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="appIsActive" name="is_active" value="1"<?= $isActive ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field adm-field--full">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">App Maintenance Mode</div>
                        <div class="adm-hint">Signals the Android app to show the maintenance notice below.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="appMaintMode" name="maintenance_mode" value="1"<?= $isMaint ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field adm-field--full">
                <label for="appMaintMessage" class="adm-label">Maintenance Message</label>
                <textarea id="appMaintMessage" name="maintenance_message" class="adm-textarea" maxlength="500" required><?= e((string) ($config['maintenance_message'] ?? '')) ?></textarea>
            </div>

            <div class="adm-field adm-field--full">
                <label for="appRestrictedNotice" class="adm-label">Temporary Feature Restriction Notice (Optional)</label>
                <input type="text" id="appRestrictedNotice" name="restricted_notice" class="adm-input" maxlength="500" value="<?= e((string) ($config['restricted_notice'] ?? '')) ?>" placeholder="e.g. Cloud AI OCR is temporarily under maintenance; offline scanning remains active.">
                <span class="adm-hint">Manage individual Android feature toggles in <a href="features.php" style="color:var(--adm-cyan);text-decoration:underline;">Feature Controls</a> (Batch 6).</span>
            </div>
        </div>
    </section>

    <!-- 2. Version & Update Control -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-solid fa-code-branch" style="color:var(--adm-teal);margin-right:8px;" aria-hidden="true"></i>App Version &amp; Update Settings</h3>
        </div>

        <div class="adm-form-grid">
            <div class="adm-field">
                <label for="currentVersion" class="adm-label">Current Release Version</label>
                <input type="text" id="currentVersion" name="current_version" class="adm-input" maxlength="32" required value="<?= e((string) ($config['current_version'] ?? '1.0')) ?>">
            </div>

            <div class="adm-field">
                <label for="latestVersion" class="adm-label">Latest Version Name</label>
                <input type="text" id="latestVersion" name="latest_version" class="adm-input" maxlength="32" required value="<?= e((string) ($config['latest_version'] ?? '1.0')) ?>">
            </div>

            <div class="adm-field">
                <label for="latestVersionCode" class="adm-label">Latest Version Code (Android integer)</label>
                <input type="number" id="latestVersionCode" name="latest_version_code" class="adm-input" min="1" max="9999999" required value="<?= (int) ($config['latest_version_code'] ?? 1) ?>">
            </div>

            <div class="adm-field">
                <label for="minSupportedVersion" class="adm-label">Minimum Supported Version Name</label>
                <input type="text" id="minSupportedVersion" name="min_supported_version" class="adm-input" maxlength="32" required value="<?= e((string) ($config['min_supported_version'] ?? '1.0')) ?>">
            </div>

            <div class="adm-field">
                <label for="minSupportedVersionCode" class="adm-label">Minimum Supported Version Code</label>
                <input type="number" id="minSupportedVersionCode" name="min_supported_version_code" class="adm-input" min="1" max="9999999" required value="<?= (int) ($config['min_supported_version_code'] ?? 1) ?>">
                <span class="adm-hint">Apps with versionCode below this receive <code>force_update_required: true</code>.</span>
            </div>

            <div class="adm-field">
                <label for="updateDownloadUrl" class="adm-label">Update Download URL (APK / Play Store)</label>
                <input type="url" id="updateDownloadUrl" name="update_download_url" class="adm-input" maxlength="500" value="<?= e((string) ($config['update_download_url'] ?? '')) ?>" placeholder="https://github.com/.../app-release.apk">
            </div>

            <div class="adm-field">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Update Available</div>
                        <div class="adm-hint">Notify users on older versions.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="updateAvailable" name="update_available" value="1"<?= ((int) ($config['update_available'] ?? 0)) === 1 ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Mandatory Update</div>
                        <div class="adm-hint">Blocks older builds from continuing.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="mandatoryUpdate" name="mandatory_update" value="1"<?= ((int) ($config['mandatory_update'] ?? 0)) === 1 ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field">
                <label for="updateTitle" class="adm-label">Update Dialog Title</label>
                <input type="text" id="updateTitle" name="update_title" class="adm-input" maxlength="120" value="<?= e((string) ($config['update_title'] ?? 'Update Available')) ?>">
            </div>

            <div class="adm-field">
                <label for="updateAnnouncement" class="adm-label">Update Short Notice</label>
                <input type="text" id="updateAnnouncement" name="update_announcement" class="adm-input" maxlength="500" value="<?= e((string) ($config['update_announcement'] ?? '')) ?>" placeholder="e.g. Update to v1.1 for faster edge detection.">
            </div>

            <div class="adm-field adm-field--full">
                <label for="releaseNotes" class="adm-label">Release Notes</label>
                <textarea id="releaseNotes" name="release_notes" class="adm-textarea" maxlength="3000" placeholder="- Improved OCR accuracy&#10;- Faster batch scanning"><?= e((string) ($config['release_notes'] ?? '')) ?></textarea>
            </div>
        </div>
    </section>

    <!-- 3. App-Specific In-App Announcement -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-solid fa-bullhorn" style="color:var(--adm-blue);margin-right:8px;" aria-hidden="true"></i>In-App Announcement</h3>
        </div>

        <div class="adm-form-grid">
            <div class="adm-field adm-field--full">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Show Announcement Inside Android App</div>
                        <div class="adm-hint">Delivered in the <code>announcements</code> array of <code>v1/app-config.php</code>.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="appAnnEnabled" name="announcement_enabled" value="1"<?= ((int) ($config['announcement_enabled'] ?? 0)) === 1 ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field">
                <label for="appAnnTitle" class="adm-label">Announcement Title</label>
                <input type="text" id="appAnnTitle" name="announcement_title" class="adm-input" maxlength="150" value="<?= e((string) ($config['announcement_title'] ?? '')) ?>" placeholder="e.g. New Gemini OCR Upgrade">
            </div>

            <div class="adm-field">
                <label for="appAnnType" class="adm-label">Announcement Type</label>
                <?php $annType = (string) ($config['announcement_type'] ?? 'info'); ?>
                <select id="appAnnType" name="announcement_type" class="adm-select">
                    <option value="info"<?= $annType === 'info' ? ' selected' : '' ?>>Information</option>
                    <option value="update"<?= $annType === 'update' ? ' selected' : '' ?>>Update</option>
                    <option value="warning"<?= $annType === 'warning' ? ' selected' : '' ?>>Warning</option>
                </select>
            </div>

            <div class="adm-field adm-field--full">
                <label for="appAnnContent" class="adm-label">Announcement Content</label>
                <textarea id="appAnnContent" name="announcement_content" class="adm-textarea" maxlength="2000" placeholder="Write message shown inside the Android app..."><?= e((string) ($config['announcement_content'] ?? '')) ?></textarea>
            </div>

            <div class="adm-field">
                <label for="appAnnStart" class="adm-label">Start Date/Time (Optional)</label>
                <input type="datetime-local" id="appAnnStart" name="announcement_start_at" class="adm-input" value="<?= e($toDateTimeLocal($config['announcement_start_at'] ?? null)) ?>">
            </div>

            <div class="adm-field">
                <label for="appAnnEnd" class="adm-label">End Date/Time (Optional)</label>
                <input type="datetime-local" id="appAnnEnd" name="announcement_end_at" class="adm-input" value="<?= e($toDateTimeLocal($config['announcement_end_at'] ?? null)) ?>">
            </div>
        </div>

        <div style="margin-top:18px;display:flex;justify-content:flex-end;">
            <button type="submit" id="saveAndroidBtn" class="adm-btn adm-btn-primary">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                <span>Save Android Configuration</span>
            </button>
        </div>
    </section>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('androidConfigForm');
    const saveBtn = document.getElementById('saveAndroidBtn');
    const badge = document.getElementById('androidStatusBadge');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const origHtml = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><span>Saving...</span>';

        const payload = {
            is_active: document.getElementById('appIsActive').checked,
            maintenance_mode: document.getElementById('appMaintMode').checked,
            maintenance_message: document.getElementById('appMaintMessage').value.trim(),
            restricted_notice: document.getElementById('appRestrictedNotice').value.trim(),
            current_version: document.getElementById('currentVersion').value.trim(),
            latest_version: document.getElementById('latestVersion').value.trim(),
            latest_version_code: parseInt(document.getElementById('latestVersionCode').value, 10) || 1,
            min_supported_version: document.getElementById('minSupportedVersion').value.trim(),
            min_supported_version_code: parseInt(document.getElementById('minSupportedVersionCode').value, 10) || 1,
            update_download_url: document.getElementById('updateDownloadUrl').value.trim(),
            update_available: document.getElementById('updateAvailable').checked,
            mandatory_update: document.getElementById('mandatoryUpdate').checked,
            update_title: document.getElementById('updateTitle').value.trim(),
            update_announcement: document.getElementById('updateAnnouncement').value.trim(),
            release_notes: document.getElementById('releaseNotes').value.trim(),
            announcement_enabled: document.getElementById('appAnnEnabled').checked,
            announcement_title: document.getElementById('appAnnTitle').value.trim(),
            announcement_type: document.getElementById('appAnnType').value,
            announcement_content: document.getElementById('appAnnContent').value.trim(),
            announcement_start_at: document.getElementById('appAnnStart').value,
            announcement_end_at: document.getElementById('appAnnEnd').value
        };

        try {
            const data = await window.AdminUI.postJson(form.getAttribute('action'), payload);
            const cfg = data.config || {};
            if (badge) {
                if (Number(cfg.is_active) !== 1) {
                    badge.className = 'adm-badge badge--danger';
                    badge.textContent = 'App Disabled';
                } else if (Number(cfg.maintenance_mode) === 1) {
                    badge.className = 'adm-badge badge--warn';
                    badge.textContent = 'Maintenance Mode ON';
                } else {
                    badge.className = 'adm-badge badge--ok';
                    badge.textContent = 'App Active (v' + (cfg.latest_version || '1.0') + ')';
                }
            }
            window.AdminUI.toast('success', data.message || 'Android configuration saved.');
        } catch (err) {
            window.AdminUI.toast('error', err.message || 'Could not save Android configuration.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = origHtml;
        }
    });
});
</script>

<?php renderAdminFooter(); ?>