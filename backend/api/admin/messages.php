<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/../../auth/admin_auth.php';
require_once __DIR__ . '/../../helpers/validator.php';

$admin = requireAdminApiAuth();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$messagesPageUrl = getAdminRootUrlPath() . '/messages.php';

if ($method === 'GET') {
    $q = cleanText($_GET['q'] ?? '');
    $status = cleanText($_GET['status'] ?? 'all');

    try {
        $pdo = getDbConnection();
        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(name LIKE :q1 OR email LIKE :q2 OR subject LIKE :q3 OR message LIKE :q4)';
            $like = '%' . $q . '%';
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }
        if ($status === 'unread') {
            $where[] = "status = 'new'";
        } elseif ($status === 'read') {
            $where[] = "status <> 'new'";
        }

        $whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';
        $stmt = $pdo->prepare(
            "SELECT id, user_id, name, email, subject, message, status, created_at
             FROM contact_messages
             {$whereSql}
             ORDER BY id DESC
             LIMIT 100"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v, PDO::PARAM_STR);
        }
        $stmt->execute();

        $unreadCount = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();

        jsonSuccess('Messages loaded.', [
            'messages'     => $stmt->fetchAll() ?: [],
            'unread_count' => $unreadCount,
        ]);
    } catch (Throwable $e) {
        error_log('[ScanPro Admin Messages GET Error] ' . $e->getMessage());
        jsonError('Unable to load contact messages.', 500);
    }
}

if ($method !== 'POST') {
    if (expectsJsonResponse()) {
        jsonError('Method not allowed.', 405);
    }
    header('Location: ' . $messagesPageUrl, true, 302);
    exit;
}

$payload = getRequestPayload();
$submittedCsrf = extractAdminCsrfToken($payload);

if (!verifyAdminCsrfToken($submittedCsrf)) {
    if (expectsJsonResponse()) {
        jsonError('Security token expired or invalid. Please refresh the page.', 403);
    }
    setAdminFlash('error', 'Security token expired or invalid.');
    header('Location: ' . $messagesPageUrl, true, 302);
    exit;
}

$action = cleanText($payload['action'] ?? '');
$msgId = (int) ($payload['message_id'] ?? 0);

if ($msgId <= 0 || !in_array($action, ['mark_read', 'mark_unread', 'delete'], true)) {
    if (expectsJsonResponse()) {
        jsonError('Invalid message action or ID.', 422);
    }
    setAdminFlash('error', 'Invalid message action.');
    header('Location: ' . $messagesPageUrl, true, 302);
    exit;
}

try {
    $pdo = getDbConnection();
    $check = $pdo->prepare('SELECT id, email, subject, status FROM contact_messages WHERE id = :id LIMIT 1');
    $check->execute(['id' => $msgId]);
    $row = $check->fetch();

    if (!is_array($row)) {
        if (expectsJsonResponse()) {
            jsonError('Message not found.', 404);
        }
        setAdminFlash('error', 'Message not found.');
        header('Location: ' . $messagesPageUrl, true, 302);
        exit;
    }

    $adminId = (int) $admin['id'];
    $adminUser = (string) $admin['username'];

    if ($action === 'delete') {
        $del = $pdo->prepare('DELETE FROM contact_messages WHERE id = :id');
        $del->execute(['id' => $msgId]);
        logAdminActivity(
            $pdo,
            $adminId,
            $adminUser,
            'message.delete',
            'Deleted contact message #' . $msgId . ' (' . (string) $row['email'] . ')',
            'contact_messages',
            (string) $msgId
        );
        $msgText = 'Message deleted.';
        $newStatus = 'deleted';
    } else {
        $newStatus = $action === 'mark_read' ? 'read' : 'new';
        $upd = $pdo->prepare('UPDATE contact_messages SET status = :st WHERE id = :id');
        $upd->execute(['st' => $newStatus, 'id' => $msgId]);
        logAdminActivity(
            $pdo,
            $adminId,
            $adminUser,
            'message.' . $action,
            'Marked contact message #' . $msgId . ' as ' . $newStatus,
            'contact_messages',
            (string) $msgId
        );
        $msgText = $newStatus === 'read' ? 'Message marked as read.' : 'Message marked as unread.';
    }

    $unreadCount = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();

    if (expectsJsonResponse()) {
        jsonSuccess($msgText, [
            'message_id'   => $msgId,
            'status'       => $newStatus,
            'unread_count' => $unreadCount,
        ]);
    }

    setAdminFlash('success', $msgText);
    header('Location: ' . $messagesPageUrl, true, 302);
    exit;
} catch (Throwable $e) {
    error_log('[ScanPro Admin Messages POST Error] ' . $e->getMessage());
    if (expectsJsonResponse()) {
        jsonError('Unable to process message action right now.', 500);
    }
    setAdminFlash('error', 'Unable to process message action.');
    header('Location: ' . $messagesPageUrl, true, 302);
    exit;
}