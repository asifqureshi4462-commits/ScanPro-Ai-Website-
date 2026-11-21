<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$admin = requireAdminWebAuth();
$messages = [];
$unreadCount = 0;

try {
    $pdo = getDbConnection();
    $unreadCount = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();
    $stmt = $pdo->query(
        'SELECT id, user_id, name, email, subject, message, status, created_at
         FROM contact_messages
         ORDER BY id DESC
         LIMIT 100'
    );
    $messages = $stmt ? ($stmt->fetchAll() ?: []) : [];
} catch (Throwable $e) {
    error_log('[ScanPro Admin Messages Page Error] ' . $e->getMessage());
}

renderAdminHeader($admin, 'messages', 'Contact Messages', 'Review inquiries submitted via the public Contact form');
?>

<style>
.msg-toolbar {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 12px;
}
.msg-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.msg-card {
    background: var(--adm-card);
    border: 1px solid var(--adm-border);
    border-radius: var(--adm-radius);
    padding: 18px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.msg-card.is-unread {
    border-color: var(--adm-border-cyan);
    background: #1A1C24;
}
.msg-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
.msg-body-preview {
    color: var(--adm-muted);
    font-size: 0.9rem;
    white-space: pre-wrap;
    word-break: break-word;
    background: var(--adm-bg-soft);
    border: 1px solid var(--adm-border);
    border-radius: 10px;
    padding: 12px;
}
.msg-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
@media (max-width: 720px) {
    .msg-toolbar { grid-template-columns: 1fr; }
}
</style>

<section class="adm-card">
    <div class="adm-card-head">
        <div>
            <h2>Inbox Summary</h2>
            <p class="adm-card-note">Messages submitted via <code>frontend/contact.php</code>.</p>
        </div>
        <span class="adm-badge <?= $unreadCount > 0 ? 'badge--warn' : 'badge--ok' ?>" id="inboxUnreadBadge">
            <?= $unreadCount ?> Unread
        </span>
    </div>

    <div class="msg-toolbar">
        <div class="adm-field">
            <label for="msgSearchInput" class="adm-label">Search Messages</label>
            <input type="search" id="msgSearchInput" class="adm-input" placeholder="Search sender, email, subject, or content...">
        </div>
        <div class="adm-field">
            <label for="msgStatusFilter" class="adm-label">Filter Status</label>
            <select id="msgStatusFilter" class="adm-select">
                <option value="all">All Messages</option>
                <option value="unread">Unread Only</option>
                <option value="read">Read Only</option>
            </select>
        </div>
    </div>
</section>

<section class="msg-list" id="messagesList">
    <?php if (empty($messages)): ?>
        <div class="adm-card">
            <p class="adm-card-note" style="text-align:center;padding:18px;">No contact messages found yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($messages as $m): ?>
            <?php
            $mid = (int) $m['id'];
            $isUnread = ((string) ($m['status'] ?? 'new')) === 'new';
            $searchText = strtolower(
                (string) $m['name'] . ' ' . (string) $m['email'] . ' ' . (string) $m['subject'] . ' ' . (string) $m['message']
            );
            ?>
            <article
                class="msg-card js-msg-card<?= $isUnread ? ' is-unread' : '' ?>"
                data-msg-id="<?= $mid ?>"
                data-state="<?= $isUnread ? 'unread' : 'read' ?>"
                data-search="<?= e($searchText) ?>"
            >
                <div class="msg-head">
                    <div>
                        <div style="font-size:1rem;font-weight:700;color:#FFFFFF;"><?= e((string) $m['subject']) ?></div>
                        <div class="adm-card-note">
                            From: <strong style="color:#E2E8F0;"><?= e((string) $m['name']) ?></strong>
                            (&lt;<?= e((string) $m['email']) ?>&gt;) •
                            <?= e(date('M d, Y H:i', strtotime((string) $m['created_at']))) ?>
                        </div>
                    </div>
                    <span class="adm-badge js-msg-badge <?= $isUnread ? 'badge--info' : 'badge--muted' ?>">
                        <?= $isUnread ? 'Unread' : 'Read' ?>
                    </span>
                </div>

                <div class="msg-body-preview"><?= e((string) $m['message']) ?></div>

                <div class="msg-actions">
                    <a
                        href="mailto:<?= e((string) $m['email']) ?>?subject=<?= urlencode('Re: ' . (string) $m['subject']) ?>"
                        class="adm-btn adm-btn-secondary adm-btn-sm"
                    >
                        <i class="fa-solid fa-reply" aria-hidden="true"></i>
                        <span>Reply via Email</span>
                    </a>

                    <button
                        type="button"
                        class="adm-btn adm-btn-primary adm-btn-sm js-msg-toggle-read"
                        data-action="<?= $isUnread ? 'mark_read' : 'mark_unread' ?>"
                    >
                        <i class="fa-solid <?= $isUnread ? 'fa-check' : 'fa-envelope' ?>" aria-hidden="true"></i>
                        <span><?= $isUnread ? 'Mark as Read' : 'Mark as Unread' ?></span>
                    </button>

                    <button type="button" class="adm-btn adm-btn-danger adm-btn-sm js-msg-delete">
                        <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                        <span>Delete</span>
                    </button>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<p id="msgEmptyFilter" class="adm-card-note" style="display:none;text-align:center;padding:24px;">
    No messages matched your search/filter.
</p>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchEl = document.getElementById('msgSearchInput');
    const filterEl = document.getElementById('msgStatusFilter');
    const cards = Array.from(document.querySelectorAll('.js-msg-card'));
    const emptyMsg = document.getElementById('msgEmptyFilter');
    const unreadBadge = document.getElementById('inboxUnreadBadge');

    function applyFilter() {
        const q = (searchEl.value || '').trim().toLowerCase();
        const st = filterEl.value;
        let visible = 0;

        cards.forEach((card) => {
            if (!card.isConnected) return;
            const okQ = !q || (card.dataset.search || '').includes(q);
            const okSt = st === 'all' || card.dataset.state === st;
            const show = okQ && okSt;
            card.style.display = show ? 'flex' : 'none';
            if (show) visible += 1;
        });

        if (emptyMsg) emptyMsg.style.display = visible === 0 && cards.some(c => c.isConnected) ? 'block' : 'none';
    }

    function updateUnreadBadge(count) {
        if (!unreadBadge || typeof count !== 'number') return;
        unreadBadge.textContent = count + ' Unread';
        unreadBadge.className = 'adm-badge ' + (count > 0 ? 'badge--warn' : 'badge--ok');
    }

    [searchEl, filterEl].forEach((el) => {
        if (el) {
            el.addEventListener('input', applyFilter);
            el.addEventListener('change', applyFilter);
        }
    });

    cards.forEach((card) => {
        const toggleBtn = card.querySelector('.js-msg-toggle-read');
        const deleteBtn = card.querySelector('.js-msg-delete');
        const badge = card.querySelector('.js-msg-badge');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', async () => {
                const action = toggleBtn.dataset.action;
                toggleBtn.disabled = true;
                try {
                    const data = await window.AdminUI.postJson('../backend/api/admin/messages.php', {
                        action: action,
                        message_id: Number(card.dataset.msgId)
                    });
                    const isUnread = data.status === 'new';
                    card.dataset.state = isUnread ? 'unread' : 'read';
                    card.classList.toggle('is-unread', isUnread);
                    badge.className = 'adm-badge js-msg-badge ' + (isUnread ? 'badge--info' : 'badge--muted');
                    badge.textContent = isUnread ? 'Unread' : 'Read';

                    toggleBtn.dataset.action = isUnread ? 'mark_read' : 'mark_unread';
                    toggleBtn.innerHTML = '<i class="fa-solid ' + (isUnread ? 'fa-check' : 'fa-envelope') + '" aria-hidden="true"></i><span>' + (isUnread ? 'Mark as Read' : 'Mark as Unread') + '</span>';
                    updateUnreadBadge(data.unread_count);
                    applyFilter();
                    window.AdminUI.toast('success', data.message || 'Updated.');
                } catch (err) {
                    window.AdminUI.toast('error', err.message || 'Could not update message.');
                } finally {
                    toggleBtn.disabled = false;
                }
            });
        }

        if (deleteBtn) {
            deleteBtn.addEventListener('click', async () => {
                if (!window.confirm('Permanently delete this contact message?')) return;
                deleteBtn.disabled = true;
                try {
                    const data = await window.AdminUI.postJson('../backend/api/admin/messages.php', {
                        action: 'delete',
                        message_id: Number(card.dataset.msgId)
                    });
                    card.remove();
                    updateUnreadBadge(data.unread_count);
                    applyFilter();
                    window.AdminUI.toast('success', data.message || 'Deleted.');
                } catch (err) {
                    deleteBtn.disabled = false;
                    window.AdminUI.toast('error', err.message || 'Could not delete message.');
                }
            });
        }
    });
});
</script>

<?php renderAdminFooter(); ?>