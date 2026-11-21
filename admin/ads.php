<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$admin = requireAdminWebAuth();
$ads = getAdRuntimeConfig(true);
$isGlobalOn = !empty($ads['ads_enabled']);
$activeCount = count($ads['placements'] ?? []);

renderAdminHeader($admin, 'ads', 'Ads Management', 'Control inline non-intrusive ads across Website & Android');
?>

<div class="adm-card">
    <div class="adm-card-head" style="margin-bottom:8px;">
        <div>
            <h2>Ads System Status</h2>
            <p class="adm-card-note">
                Preserves inline layout only (no popups, popunders, or forced redirects). Login, Register, Dashboard, Pricing, Contact, and Workspace remain ad-free.
            </p>
        </div>
        <span id="adsStatusBadge" class="adm-badge <?= $isGlobalOn ? 'badge--ok' : 'badge--muted' ?>">
            <?= $isGlobalOn ? ('Ads Active (' . $activeCount . ' Web Placements)') : 'Ads Paused Globally' ?>
        </span>
    </div>
</div>

<form id="adsConfigForm" action="../backend/api/admin/ads.php" method="POST" novalidate style="display:flex;flex-direction:column;gap:18px;">
    <?= adminCsrfInput() ?>

    <!-- 1. Global & Audience Controls -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-solid fa-toggle-on" style="color:var(--adm-cyan);margin-right:8px;" aria-hidden="true"></i>Global &amp; Audience Controls</h3>
        </div>

        <div class="adm-form-grid">
            <div class="adm-field adm-field--full">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Global Ads Master Switch</div>
                        <div class="adm-hint">Turns all advertisements ON or OFF across the entire website and Android API.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="adsEnabled" name="ads_enabled" value="1"<?= !empty($ads['ads_enabled']) ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Guest Visitor Ads</div>
                        <div class="adm-hint">Show inline ads to visitors who are not signed in.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="adsGuestEnabled" name="ads_guest_enabled" value="1"<?= !empty($ads['guest_enabled']) ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Logged-in User Ads</div>
                        <div class="adm-hint">Keep OFF to reward signed-in users with an ad-free experience.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="adsLoggedInEnabled" name="ads_logged_in_enabled" value="1"<?= !empty($ads['logged_in_enabled']) ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. Website Page Placements -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-solid fa-layer-group" style="color:var(--adm-teal);margin-right:8px;" aria-hidden="true"></i>Website Ad Placements</h3>
        </div>

        <div class="adm-form-grid">
            <div class="adm-field">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Home Page (<code>index.php</code>)</div>
                        <div class="adm-hint">Single native ad slot between sections.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="adsHomeEnabled" name="ads_home_enabled" value="1"<?= !empty($ads['home_enabled']) ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Features Page (<code>features.php</code>)</div>
                        <div class="adm-hint">Single native ad slot between feature sections.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="adsFeaturesEnabled" name="ads_features_enabled" value="1"<?= !empty($ads['features_enabled']) ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">PDF Tools Hub (<code>pdf-tools.php</code>)</div>
                        <div class="adm-hint">Single slot below the tools grid.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="adsPdfToolsEnabled" name="ads_pdf_tools_enabled" value="1"<?= !empty($ads['pdf_tools_enabled']) ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Individual PDF Tool Pages</div>
                        <div class="adm-hint">Single slot below the tool workspace.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="adsToolPagesEnabled" name="ads_tool_pages_enabled" value="1"<?= !empty($ads['tool_pages_enabled']) ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Ad Slot & Script Configuration + Safe Preview -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-solid fa-code" style="color:var(--adm-blue);margin-right:8px;" aria-hidden="true"></i>Native Ad Zone &amp; Script Configuration</h3>
        </div>

        <div class="adm-form-grid">
            <div class="adm-field">
                <label for="adsScriptSrc" class="adm-label">Native Ad Script URL</label>
                <input type="url" id="adsScriptSrc" name="ads_native_script_src" class="adm-input" maxlength="500" value="<?= e((string) ($ads['native_script_src'] ?? '')) ?>" placeholder="https://.../invoke.js">
                <span class="adm-hint">Must be an <code>http://</code> or <code>https://</code> script URL.</span>
            </div>

            <div class="adm-field">
                <label for="adsContainerId" class="adm-label">Native Container Element ID</label>
                <input type="text" id="adsContainerId" name="ads_native_container_id" class="adm-input" maxlength="120" value="<?= e((string) ($ads['native_container_id'] ?? '')) ?>" placeholder="container-...">
                <span class="adm-hint">Matches the <code>&lt;div id="..."&gt;</code> required by your ad network.</span>
            </div>

            <div class="adm-field adm-field--full">
                <div class="adm-label">Safe Ad Slot Preview (Third-party script is NOT executed inside Admin)</div>
                <div id="adSafePreviewBox" style="background:#0B0B0F;border:1px dashed var(--adm-border-cyan);border-radius:12px;padding:18px;text-align:center;">
                    <div style="font-size:0.68rem;letter-spacing:1.2px;text-transform:uppercase;color:var(--adm-subtle);margin-bottom:8px;">Advertisement (Inline Flow Slot)</div>
                    <div style="padding:14px;border-radius:10px;background:#111117;border:1px solid var(--adm-border);font-size:0.82rem;color:var(--adm-muted);word-break:break-all;">
                        <div><strong>Container ID:</strong> <code id="previewContainerId"><?= e((string) ($ads['native_container_id'] ?? 'Not set')) ?></code></div>
                        <div style="margin-top:4px;"><strong>Script SRC:</strong> <code id="previewScriptSrc"><?= e((string) ($ads['native_script_src'] ?? 'Not set')) ?></code></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. Android App Ads Configuration -->
    <section class="adm-card">
        <div class="adm-card-head">
            <h3><i class="fa-brands fa-android" style="color:var(--adm-teal);margin-right:8px;" aria-hidden="true"></i>Android Application Ads (Remote API)</h3>
        </div>

        <div class="adm-form-grid">
            <div class="adm-field adm-field--full">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Enable Ads in Android App</div>
                        <div class="adm-hint">Exposed in <code>/backend/api/v1/app-config.php</code> as <code>ads.android_enabled</code>. Requires Android app integration to take effect inside the APK.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="adsAndroidEnabled" name="ads_android_enabled" value="1"<?= !empty($ads['android_ads_enabled']) ? ' checked' : '' ?>>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field adm-field--full">
                <label for="adsAndroidBannerId" class="adm-label">Android Banner / Ad Unit ID (Optional)</label>
                <input type="text" id="adsAndroidBannerId" name="ads_android_banner_id" class="adm-input" maxlength="150" value="<?= e((string) ($ads['android_banner_id'] ?? '')) ?>" placeholder="e.g. ca-app-pub-xxxxxxxxxxxxxxxx/xxxxxxxxxx">
            </div>
        </div>

        <div style="margin-top:18px;display:flex;justify-content:flex-end;">
            <button type="submit" id="saveAdsBtn" class="adm-btn adm-btn-primary">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                <span>Save Ads Configuration</span>
            </button>
        </div>
    </section>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('adsConfigForm');
    const saveBtn = document.getElementById('saveAdsBtn');
    const badge = document.getElementById('adsStatusBadge');
    const scriptInput = document.getElementById('adsScriptSrc');
    const containerInput = document.getElementById('adsContainerId');
    const prevScript = document.getElementById('previewScriptSrc');
    const prevContainer = document.getElementById('previewContainerId');

    function syncPreview() {
        if (prevScript) prevScript.textContent = scriptInput.value.trim() || 'Not set';
        if (prevContainer) prevContainer.textContent = containerInput.value.trim() || 'Not set';
    }
    if (scriptInput) scriptInput.addEventListener('input', syncPreview);
    if (containerInput) containerInput.addEventListener('input', syncPreview);

    if (!form) return;
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const orig = saveBtn.innerHTML;
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><span>Saving...</span>';

        const payload = {
            ads_enabled: document.getElementById('adsEnabled').checked,
            ads_guest_enabled: document.getElementById('adsGuestEnabled').checked,
            ads_logged_in_enabled: document.getElementById('adsLoggedInEnabled').checked,
            ads_home_enabled: document.getElementById('adsHomeEnabled').checked,
            ads_features_enabled: document.getElementById('adsFeaturesEnabled').checked,
            ads_pdf_tools_enabled: document.getElementById('adsPdfToolsEnabled').checked,
            ads_tool_pages_enabled: document.getElementById('adsToolPagesEnabled').checked,
            ads_native_script_src: scriptInput.value.trim(),
            ads_native_container_id: containerInput.value.trim(),
            ads_android_enabled: document.getElementById('adsAndroidEnabled').checked,
            ads_android_banner_id: document.getElementById('adsAndroidBannerId').value.trim()
        };

        try {
            const data = await window.AdminUI.postJson(form.getAttribute('action'), payload);
            const a = data.ads || {};
            if (badge) {
                if (a.ads_enabled) {
                    const count = Array.isArray(a.placements) ? a.placements.length : 0;
                    badge.className = 'adm-badge badge--ok';
                    badge.textContent = 'Ads Active (' + count + ' Web Placements)';
                } else {
                    badge.className = 'adm-badge badge--muted';
                    badge.textContent = 'Ads Paused Globally';
                }
            }
            window.AdminUI.toast('success', data.message || 'Ads settings saved.');
        } catch (err) {
            window.AdminUI.toast('error', err.message || 'Could not save ads settings.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = orig;
        }
    });
});
</script>

<?php renderAdminFooter(); ?>