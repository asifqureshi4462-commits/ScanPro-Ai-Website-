<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$admin = requireAdminWebAuth();
$announcements = [];

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query(
        'SELECT id, title, message, type, platform, is_active, is_dismissible, start_at, end_at, created_at, updated_at
         FROM announcements
         ORDER BY id DESC
         LIMIT 100'
    );
    $announcements = $stmt ? ($stmt->fetchAll() ?: []) : [];
} catch (Throwable $e) {
    error_log('[ScanPro Admin Announcements Page Error] ' . $e->getMessage());
}

$toDateTimeLocal = static function (?string $val): string {
    if ($val === null || trim($val) === '') {
        return '';
    }
    $ts = strtotime($val);
    return $ts !== false ? date('Y-m-d\TH:i', $ts) : '';
};

renderAdminHeader($admin, 'announcements', 'Announcement Center', 'Publish & schedule notices for Website, Android App, or Both');
?>

<style>
.ann-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.ann-item {
    background: var(--adm-card);
    border: 1px solid var(--adm-border);
    border-radius: var(--adm-radius);
    padding: 18px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.ann-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
.ann-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.ann-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
</style>

<section class="adm-card" id="annFormCard">
    <div class="adm-card-head">
        <h2 id="annFormTitle">Publish New Announcement</h2>
        <button type="button" id="annCancelEditBtn" class="adm-btn adm-btn-secondary adm-btn-sm" style="display:none;">
            Cancel Edit
        </button>
    </div>

    <form id="annForm" novalidate>
        <input type="hidden" id="annId" value="">
        <input type="hidden" id="annAction" value="create">

        <div class="adm-form-grid">
            <div class="adm-field">
                <label for="annTitle" class="adm-label">Announcement Title</label>
                <input type="text" id="annTitle" class="adm-input" maxlength="150" required placeholder="e.g. Scheduled Upgrade / New Tool Live">
            </div>

            <div class="adm-field">
                <label for="annPlatform" class="adm-label">Target Platform</label>
                <select id="annPlatform" class="adm-select">
                    <option value="both" selected>Both (Website &amp; Android App)</option>
                    <option value="website">Website Only</option>
                    <option value="android">Android App Only</option>
                </select>
            </div>

            <div class="adm-field">
                <label for="annType" class="adm-label">Type</label>
                <select id="annType" class="adm-select">
                    <option value="info" selected>Information</option>
                    <option value="update">Update</option>
                    <option value="warning">Warning</option>
                </select>
            </div>

            <div class="adm-field">
                <div class="adm-switch-row" style="height:100%;">
                    <div>
                        <div class="adm-label">Active Immediately</div>
                        <div class="adm-hint">Uncheck to save as inactive draft.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="annIsActive" checked>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>

            <div class="adm-field adm-field--full">
                <label for="annMessage" class="adm-label">Announcement Message</label>
                <textarea id="annMessage" class="adm-textarea" maxlength="2000" required placeholder="Write the announcement text..."></textarea>
            </div>

            <div class="adm-field">
                <label for="annStartAt" class="adm-label">Start Date/Time (Optional)</label>
                <input type="datetime-local" id="annStartAt" class="adm-input">
            </div>

            <div class="adm-field">
                <label for="annEndAt" class="adm-label">End Date/Time (Optional)</label>
                <input type="datetime-local" id="annEndAt" class="adm-input">
            </div>

            <div class="adm-field adm-field--full">
                <div class="adm-switch-row">
                    <div>
                        <div class="adm-label">Allow Users to Dismiss</div>
                        <div class="adm-hint">Shows a close button on the website banner and flags <code>dismissible: true</code> in Android API.</div>
                    </div>
                    <label class="adm-switch">
                        <input type="checkbox" id="annDismissible" checked>
                        <span class="adm-switch-track"></span>
                    </label>
                </div>
            </div>
        </div>

        <div style="margin-top:16px;display:flex;justify-content:flex-end;">
            <button type="submit" id="annSubmitBtn" class="adm-btn adm-btn-primary">
                <i class="fa-solid fa-bullhorn" aria-hidden="true"></i>
                <span>Publish Announcement</span>
            </button>
        </div>
    </form>
</section>

<section class="ann-list" id="announcementsList">
    <?php if (empty($announcements)): ?>
        <div class="adm-card">
            <p class="adm-card-note" style="text-align:center;padding:18px;">No announcements created yet.</p>
        </div>
    <?php else: ?>
        <?php $now = time(); ?>
        <?php foreach ($announcements as $ann): ?>
            <?php
            $aid = (int) $ann['id'];
            $isActive = ((int) $ann['is_active']) === 1;
            $startTs = !empty($ann['start_at']) ? strtotime((string) $ann['start_at']) : null;
            $endTs = !empty($ann['end_at']) ? strtotime((string) $ann['end_at']) : null;

            $statusBadge = 'badge--ok';
            $statusLabel = 'Active';
            if (!$isActive) {
                $statusBadge = 'badge--muted';
                $statusLabel = 'Inactive';
            } elseif ($startTs !== null && $startTs > $now) {
                $statusBadge = 'badge--info';
                $statusLabel = 'Scheduled';
            } elseif ($endTs !== null && $endTs < $now) {
                $statusBadge = 'badge--warn';
                $statusLabel = 'Expired';
            }

            $typeBadge = 'badge--info';
            if ($ann['type'] === 'warning') {
                $typeBadge = 'badge--warn';
            } elseif ($ann['type'] === 'update') {
                $typeBadge = 'badge--ok';
            }

            $editPayload = [
                'id'             => $aid,
                'title'          => (string) $ann['title'],
                'message'        => (string) $ann['message'],
                'type'           => (string) $ann['type'],
                'platform'       => (string) $ann['platform'],
                'is_active'      => $isActive,
                'is_dismissible' => ((int) $ann['is_dismissible']) === 1,
                'start_at'       => $toDateTimeLocal($ann['start_at'] ?? null),
                'end_at'         => $toDateTimeLocal($ann['end_at'] ?? null),
            ];
            ?>
            <article class="ann-item js-ann-card" data-ann-id="<?= $aid ?>">
                <div class="ann-head">
                    <div>
                        <div style="font-size:1rem;font-weight:700;color:#FFFFFF;"><?= e((string) $ann['title']) ?></div>
                        <div class="adm-card-note">
                            Created <?= e(date('M d, Y H:i', strtotime((string) $ann['created_at']))) ?>
                            <?php if (!empty($ann['start_at']) || !empty($ann['end_at'])): ?>
                                • Window:
                                <?= !empty($ann['start_at']) ? e(date('M d H:i', strtotime((string) $ann['start_at']))) : 'Now' ?>
                                &rarr;
                                <?= !empty($ann['end_at']) ? e(date('M d H:i', strtotime((string) $ann['end_at']))) : 'No end' ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ann-badges">
                        <span class="adm-badge <?= e($typeBadge) ?>"><?= e(ucfirst((string) $ann['type'])) ?></span>
                        <span class="adm-badge badge--info"><?= e(ucfirst((string) $ann['platform'])) ?></span>
                        <span class="adm-badge js-ann-status <?= e($statusBadge) ?>"><?= e($statusLabel) ?></span>
                    </div>
                </div>

                <p style="color:#CBD5E1;font-size:0.9rem;white-space:pre-wrap;"><?= e((string) $ann['message']) ?></p>

                <div class="ann-actions">
                    <button
                        type="button"
                        class="adm-btn adm-btn-secondary adm-btn-sm js-ann-edit"
                        data-ann="<?= e(json_encode($editPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>"
                    >
                        <i class="fa-solid fa-pen" aria-hidden="true"></i>
                        <span>Edit</span>
                    </button>

                    <button type="button" class="adm-btn adm-btn-primary adm-btn-sm js-ann-toggle">
                        <i class="fa-solid fa-power-off" aria-hidden="true"></i>
                        <span><?= $isActive ? 'Deactivate' : 'Activate' ?></span>
                    </button>

                    <button type="button" class="adm-btn adm-btn-danger adm-btn-sm js-ann-delete">
                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                        <span>Delete</span>
                    </button>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('annForm');
    const formTitle = document.getElementById('annFormTitle');
    const cancelEditBtn = document.getElementById('annCancelEditBtn');
    const submitBtn = document.getElementById('annSubmitBtn');
    const annIdEl = document.getElementById('annId');
    const annActionEl = document.getElementById('annAction');

    function resetFormMode() {
        form.reset();
        annIdEl.value = '';
        annActionEl.value = 'create';
        document.getElementById('annIsActive').checked = true;
        document.getElementById('annDismissible').checked = true;
        formTitle.textContent = 'Publish New Announcement';
        submitBtn.innerHTML = '<i class="fa-solid fa-bullhorn" aria-hidden="true"></i><span>Publish Announcement</span>';
        cancelEditBtn.style.display = 'none';
    }

    if (cancelEditBtn) cancelEditBtn.addEventListener('click', resetFormMode);

    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            const orig = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><span>Saving...</span>';

            try {
                const data = await window.AdminUI.postJson('../backend/api/admin/announcements.php', {
                    action: annActionEl.value,
                    id: Number(annIdEl.value) || 0,
                    title: document.getElementById('annTitle').value.trim(),
                    message: document.getElementById('annMessage').value.trim(),
                    type: document.getElementById('annType').value,
                    platform: document.getElementById('annPlatform').value,
                    is_active: document.getElementById('annIsActive').checked,
                    is_dismissible: document.getElementById('annDismissible').checked,
                    start_at: document.getElementById('annStartAt').value,
                    end_at: document.getElementById('annEndAt').value
                });
                window.AdminUI.toast('success', data.message || 'Announcement saved.');
                setTimeout(() => window.location.reload(), 450);
            } catch (err) {
                window.AdminUI.toast('error', err.message || 'Could not save announcement.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = orig;
            }
        });
    }

    document.querySelectorAll('.js-ann-card').forEach((card) => {
        const id = Number(card.dataset.annId);
        const editBtn = card.querySelector('.js-ann-edit');
        const toggleBtn = card.querySelector('.js-ann-toggle');
        const deleteBtn = card.querySelector('.js-ann-delete');
        const statusBadge = card.querySelector('.js-ann-status');

        if (editBtn) {
            editBtn.addEventListener('click', () => {
                try {
                    const item = JSON.parse(editBtn.getAttribute('data-ann') || '{}');
                    annIdEl.value = String(item.id || '');
                    annActionEl.value = 'update';
                    document.getElementById('annTitle').value = item.title || '';
                    document.getElementById('annMessage').value = item.message || '';
                    document.getElementById('annType').value = item.type || 'info';
                    document.getElementById('annPlatform').value = item.platform || 'both';
                    document.getElementById('annIsActive').checked = Boolean(item.is_active);
                    document.getElementById('annDismissible').checked = Boolean(item.is_dismissible);
                    document.getElementById('annStartAt').value = item.start_at || '';
                    document.getElementById('annEndAt').value = item.end_at || '';

                    formTitle.textContent = 'Edit Announcement #' + item.id;
                    submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span>Update Announcement</span>';
                    cancelEditBtn.style.display = 'inline-flex';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } catch (e) {}
            });
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', async () => {
                toggleBtn.disabled = true;
                try {
                    const data = await window.AdminUI.postJson('../backend/api/admin/announcements.php', {
                        action: 'toggle',
                        id: id
                    });
                    const active = Number(data.is_active) === 1;
                    statusBadge.className = 'adm-badge js-ann-status ' + (active ? 'badge--ok' : 'badge--muted');
                    statusBadge.textContent = active ? 'Active' : 'Inactive';
                    toggleBtn.innerHTML = '<i class="fa-solid fa-power-off" aria-hidden="true"></i><span>' + (active ? 'Deactivate' : 'Activate') + '</span>';
                    window.AdminUI.toast('success', data.message || 'Updated.');
                } catch (err) {
                    window.AdminUI.toast('error', err.message || 'Action failed.');
                } finally {
                    toggleBtn.disabled = false;
                }
            });
        }

        if (deleteBtn) {
            deleteBtn.addEventListener('click', async () => {
                if (!window.confirm('Delete this announcement?')) return;
                deleteBtn.disabled = true;
                try {
                    const data = await window.AdminUI.postJson('../backend/api/admin/announcements.php', {
                        action: 'delete',
                        id: id
                    });
                    card.remove();
                    window.AdminUI.toast('success', data.message || 'Deleted.');
                } catch (err) {
                    deleteBtn.disabled = false;
                    window.AdminUI.toast('error', err.message || 'Could not delete announcement.');
                }
            });
        }
    });
});
</script>

<?php renderAdminFooter(); ?>