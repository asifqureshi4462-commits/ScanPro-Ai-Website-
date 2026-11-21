<?php
declare(strict_types=1);

/**
 * ScanPro AI - Admin Activity Audit Logger
 * Never stores passwords, secret keys, or sensitive authentication tokens.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Recursively redacts sensitive keys before serializing audit metadata.
 *
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
function sanitizeAuditDetails(array $data): array
{
    $clean = [];
    foreach ($data as $key => $value) {
        $keyStr = (string) $key;
        if (preg_match('/pass|secret|token|hash|csrf|auth|cookie|credential/i', $keyStr) === 1) {
            $clean[$keyStr] = '[REDACTED]';
            continue;
        }
        if (is_array($value)) {
            $clean[$keyStr] = sanitizeAuditDetails($value);
        } elseif (is_string($value)) {
            $clean[$keyStr] = mb_substr($value, 0, 300, 'UTF-8');
        } elseif (is_scalar($value) || $value === null) {
            $clean[$keyStr] = $value;
        }
    }
    return $clean;
}

/**
 * Writes an entry into admin_activity_logs. Fails gracefully so operational
 * admin actions never crash if logging encounters a transient DB issue.
 *
 * @param array<string, mixed> $details
 */
function logAdminActivity(
    ?PDO $pdo,
    ?int $adminId,
    ?string $adminUsername,
    string $action,
    string $summary,
    ?string $targetType = null,
    ?string $targetId = null,
    array $details = []
): void {
    try {
        $conn = $pdo ?? getDbConnection();
        $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
        $ipHash = hash('sha256', $ip);

        $detailsJson = null;
        if (!empty($details)) {
            $encoded = json_encode(
                sanitizeAuditDetails($details),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
            if (is_string($encoded)) {
                $detailsJson = mb_substr($encoded, 0, 4000, 'UTF-8');
            }
        }

        $stmt = $conn->prepare(
            'INSERT INTO admin_activity_logs
             (admin_id, admin_username, action, target_type, target_id, summary, details_json, ip_hash, created_at)
             VALUES (:admin_id, :admin_username, :action, :target_type, :target_id, :summary, :details_json, :ip_hash, NOW())'
        );
        $stmt->execute([
            'admin_id'       => $adminId,
            'admin_username' => $adminUsername !== null ? mb_substr($adminUsername, 0, 60, 'UTF-8') : null,
            'action'         => mb_substr($action, 0, 80, 'UTF-8'),
            'target_type'    => $targetType !== null ? mb_substr($targetType, 0, 50, 'UTF-8') : null,
            'target_id'      => $targetId !== null ? mb_substr($targetId, 0, 80, 'UTF-8') : null,
            'summary'        => mb_substr($summary, 0, 255, 'UTF-8'),
            'details_json'   => $detailsJson,
            'ip_hash'        => $ipHash,
        ]);
    } catch (Throwable $e) {
        error_log('[ScanPro Admin Audit Log Error] ' . $e->getMessage());
    }
}