<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$admin = requireAdminWebAuth();
$features = getAllFeatureFlags(true);

$activeCount = 0;
$maintCount = 0;
$disabledCount = 0;
foreach ($features as $f) {
    $st = (string) ($f['status'] ?? 'active');
    $en = ((int) ($f['is_enabled'] ?? 1)) === 1;
    if ($st === 'maintenance') {
        $maintCount += 1;
    } elseif (!$en || $st === 'disabled') {
        $disabledCount += 1;
    } else {
        $activeCount += 1;
    }
}

$categoryLabels = [
    'core'         => 'Core Suite',
    'pdf_tool'     => 'PDF Tool',
    'archive_tool' => 'Archive (ZIP/7Z)',
    'ai_tool'      => 'AI / OCR',
];

renderAdminHeader($admin, 'features', 'Feature Control Center', 'Enable, disable, or schedule maintenance across Website & Android');
?>

<style>
.feat-toolbar {
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr;
    gap: 12px;
}
.feat-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
}
.feat-card {
    background: var(--adm-card);
    border: 1px solid var(--adm-border);
    border-radius: var(--adm-radius);
    padding: 18px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.feat-card-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
}
.feat-title {
    font-size: 1rem;
    font-weight: 700;
    color: #FFFFFF;
}
.feat-meta {
    font-size: 0.76rem;
    color: var(--adm-muted);
    margin-top: 2px;
}
.feat-controls {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
@media (max-width: 900px) {
    .feat-toolbar,
    .feat-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 480px) {
    .feat-controls {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Summary Strip -->
<section class="adm-grid-3" aria-label="Feature Summary">
    <div class="adm-card">
        <div class="kpi-label">Active Features</div>
        <div class="kpi-value" id="featCountActive" style="margin-top:6px;color:var(--adm-success);"><?= $activeCount ?></div>
    </div>
    <div class="adm-card">
        <div class="kpi-label">Under Maintenance</div>
        <div class="kpi-value" id="featCountMaint" style="margin-top:6px;color:var(--adm-warning);"><?= $maintCount ?></div>
    </div>
    <div class="adm-card">
        <div class="kpi-label">Disabled</div>
        <div class="kpi-value" id="featCountDisabled" style="margin-top:6px;color:var(--adm-danger);"><?= $disabledCount ?></div>
    </div>
</section>

<!-- Filter Toolbar -->
<section class="adm-card">
    <div class="feat-toolbar">
        <div class="adm-field">
            <label for="featSearch" class="adm-label">Search Feature / Tool</label>
            <input type="search" id="featSearch" class="adm-input" placeholder="Search (e.g. zip, merge, ocr, scanner, sign)...">
        </div>
        <div class="adm-field">
            <label for="featCatFilter" class="adm-label">Category</label>
            <select id="featCatFilter" class="adm-select">
                <option value="all">All Categories</option>
                <option value="core">Core Suite</option>
                <option value="pdf_tool">PDF Tools</option>
                <option value="archive_tool">Archive (ZIP / 7Z)</option>
                <option value="ai_tool">AI / OCR</option>
            </select>
        </div>
        <div class="adm-field">
            <label for="featPlatFilter" class="adm-label">Target Platform</label>
            <select id="featPlatFilter" class="adm-select">
                <option value="all">All Platforms</option>
                <option value="website">Website</option>
                <option value="android">Android</option>
                <option value="both">Both</option>
            </select>
        </div>
    </div>
</section>

<!-- Features List -->
<section class="feat-grid" id="featuresListGrid">
    <?php foreach ($features as $feature): ?>
        <?php
        $fkey = (string) $feature['feature_key'];
        $fname = (string) $feature['feature_name'];
        $fcat = (string) ($feature['category'] ?? 'core');
        $fplat = (string) ($feature['platform'] ?? 'both');
        $fstatus = (string) ($feature['status'] ?? 'active');
        $fenabled = ((int) ($feature['is_enabled'] ?? 1)) === 1;
        $fmsg = (string) ($feature['maintenance_message'] ?? '');
        $slug = (string) ($feature['web_tool_slug'] ?? '');

        $badgeClass = 'badge--ok';
        $badgeText = 'Active';
        if ($fstatus === 'maintenance') {
            $badgeClass = 'badge--warn';
            $badgeText = 'Maintenance';
        } elseif (!$fenabled || $fstatus === 'disabled') {
            $badgeClass = 'badge--danger';
            $badgeText = 'Disabled';
        }
        ?>
        <article
            class="feat-card"
            data-feature-key="<?= e($fkey) ?>"
            data-search="<?= e(strtolower($fname . ' ' . $fkey . ' ' . $slug)) ?>"
            data-cat="<?= e($fcat) ?>"
            data-plat="<?= e($fplat) ?>"
        >
            <div class="feat-card-top">
                <div>
                    <div class="feat-title"><?= e($fname) ?></div>
                    <div class="feat-meta">
                        <span><?= e($categoryLabels[$fcat] ?? $fcat) ?></span> •
                        <code><?= e($fkey) ?></code>
                        <?php if ($slug !== ''): ?>
                            • Web: <code><?= e($slug) ?></code>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="adm-badge <?= e($badgeClass) ?> js-feat-badge"><?= e($badgeText) ?></span>
            </div>

            <div class="adm-switch-row" style="padding:10px 12px;">
                <span class="adm-label" style="margin:0;">Feature Enabled</span>
                <label class="adm-switch">
                    <input type="checkbox" class="js-feat-enabled"<?= $fenabled ? ' checked' : '' ?>>
                    <span class="adm-switch-track"></span>
                </label>
            </div>

            <div class="feat-controls">
                <div class="adm-field">
                    <label class="adm-label">Status</label>
                    <select class="adm-select js-feat-status">
                        <option value="active"<?= $fstatus === 'active' ? ' selected' : '' ?>>Active</option>
                        <option value="maintenance"<?= $fstatus === 'maintenance' ? ' selected' : '' ?>>Maintenance</option>
                        <option value="disabled"<?= $fstatus === 'disabled' ? ' selected' : '' ?>>Disabled</option>
                    </select>
                </div>

                <div class="adm-field">
                    <label class="adm-label">Platform</label>
                    <select class="adm-select js-feat-platform">
                        <option value="both"<?= $fplat === 'both' ? ' selected' : '' ?>>Both (Web &amp; App)</option>
                        <option value="website"<?= $fplat === 'website' ? ' selected' : '' ?>>Website Only</option>
                        <option value="android"<?= $fplat === 'android' ? ' selected' : '' ?>>Android Only</option>
                    </select>
                </div>
            </div>

            <div class="adm-field">
                <label class="adm-label">Optional Maintenance / Notice Message</label>
                <input
                    type="text"
                    class="adm-input js-feat-msg"
                    maxlength="300"
                    value="<?= e($fmsg) ?>"
                    placeholder="Shown when disabled or under maintenance"
                >
            </div>

            <div style="display:flex;justify-content:flex-end;">
                <button type="button" class="adm-btn adm-btn-primary adm-btn-sm js-feat-save">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    <span>Save Feature</span>
                </button>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<p id="featEmptyMsg" class="adm-card-note" style="display:none;text-align:center;padding:24px;">
    No features matched your current filter.
</p>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('featSearch');
    const catFilter = document.getElementById('featCatFilter');
    const platFilter = document.getElementById('featPlatFilter');
    const cards = Array.from(document.querySelectorAll('.feat-card'));
    const emptyMsg = document.getElementById('featEmptyMsg');

    function applyFilters() {
        const q = (searchInput.value || '').trim().toLowerCase();
        const cat = catFilter.value;
        const plat = platFilter.value;
        let visible = 0;

        cards.forEach((card) => {
            const matchQ = !q || (card.dataset.search || '').includes(q);
            const matchCat = cat === 'all' || card.dataset.cat === cat;
            const matchPlat = plat === 'all' || card.dataset.plat === plat;
            const show = matchQ && matchCat && matchPlat;
            card.style.display = show ? 'flex' : 'none';
            if (show) visible += 1;
        });

        if (emptyMsg) {
            emptyMsg.style.display = visible === 0 ? 'block' : 'none';
        }
    }

    function recalcCounts() {
        let active = 0, maint = 0, disabled = 0;
        cards.forEach((card) => {
            const en = card.querySelector('.js-feat-enabled').checked;
            const st = card.querySelector('.js-feat-status').value;
            if (st === 'maintenance') maint += 1;
            else if (!en || st === 'disabled') disabled += 1;
            else active += 1;
        });
        document.getElementById('featCountActive').textContent = String(active);
        document.getElementById('featCountMaint').textContent = String(maint);
        document.getElementById('featCountDisabled').textContent = String(disabled);
    }

    [searchInput, catFilter, platFilter].forEach((el) => {
        if (el) el.addEventListener('input', applyFilters);
        if (el) el.addEventListener('change', applyFilters);
    });

    cards.forEach((card) => {
        const enabledEl = card.querySelector('.js-feat-enabled');
        const statusEl = card.querySelector('.js-feat-status');
        const platEl = card.querySelector('.js-feat-platform');
        const msgEl = card.querySelector('.js-feat-msg');
        const badgeEl = card.querySelector('.js-feat-badge');
        const saveBtn = card.querySelector('.js-feat-save');

        enabledEl.addEventListener('change', () => {
            if (!enabledEl.checked && statusEl.value === 'active') {
                statusEl.value = 'disabled';
            } else if (enabledEl.checked && statusEl.value === 'disabled') {
                statusEl.value = 'active';
            }
        });

        statusEl.addEventListener('change', () => {
            if (statusEl.value === 'disabled') {
                enabledEl.checked = false;
            } else if (statusEl.value === 'active') {
                enabledEl.checked = true;
            }
        });

        saveBtn.addEventListener('click', async () => {
            const orig = saveBtn.innerHTML;
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><span>Saving...</span>';

            try {
                const data = await window.AdminUI.postJson('../backend/api/admin/features.php', {
                    action: 'update_feature',
                    feature_key: card.dataset.featureKey,
                    is_enabled: enabledEl.checked,
                    status: statusEl.value,
                    platform: platEl.value,
                    maintenance_message: msgEl.value.trim()
                });

                const f = data.feature || {};
                const st = f.status || statusEl.value;
                const en = Number(f.is_enabled) === 1;
                enabledEl.checked = en;
                statusEl.value = st;
                card.dataset.plat = f.platform || platEl.value;

                if (st === 'maintenance') {
                    badgeEl.className = 'adm-badge badge--warn js-feat-badge';
                    badgeEl.textContent = 'Maintenance';
                } else if (!en || st === 'disabled') {
                    badgeEl.className = 'adm-badge badge--danger js-feat-badge';
                    badgeEl.textContent = 'Disabled';
                } else {
                    badgeEl.className = 'adm-badge badge--ok js-feat-badge';
                    badgeEl.textContent = 'Active';
                }

                recalcCounts();
                applyFilters();
                window.AdminUI.toast('success', data.message || 'Feature updated.');
            } catch (err) {
                window.AdminUI.toast('error', err.message || 'Could not update feature.');
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = orig;
            }
        });
    });
});
</script>

<?php renderAdminFooter(); ?>