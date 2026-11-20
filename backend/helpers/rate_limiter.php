<?php
declare(strict_types=1);

/**
 * ScanPro AI - Database-backed rate limiting (works on shared hosting, no extensions needed).
 * Only FAILED attempts are counted. If the table is missing the limiter fails open (login keeps working).
 */

function rateLimitClientIp(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
}

function rateLimitHash(string $value): string
{
    return hash('sha256', strtolower($value));
}

/**
 * Returns true when too many failures were recorded recently.
 *
 * @param string|null $identity  e.g. "email|ip"; pass null to check the IP only
 */
function rateLimitIsBlocked(PDO $pdo, string $action, ?string $identity, int $maxPerIdentity, int $maxPerIp, int $windowSeconds): bool
{
    try {
        $since = date('Y-m-d H:i:s', time() - $windowSeconds);

        $ipStmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE action = :action AND ip_hash = :ip_hash AND success = 0 AND created_at >= :since'
        );
        $ipStmt->execute(['action' => $action, 'ip_hash' => rateLimitHash(rateLimitClientIp()), 'since' => $since]);
        if ((int) $ipStmt->fetchColumn() >= $maxPerIp) {
            return true;
        }

        if ($identity !== null && $maxPerIdentity > 0) {
            $idStmt = $pdo->prepare(
                'SELECT COUNT(*) FROM login_attempts
                 WHERE action = :action AND identifier_hash = :identifier_hash AND success = 0 AND created_at >= :since'
            );
            $idStmt->execute(['action' => $action, 'identifier_hash' => rateLimitHash($identity), 'since' => $since]);
            if ((int) $idStmt->fetchColumn() >= $maxPerIdentity) {
                return true;
            }
        }
    } catch (Throwable $e) {
        error_log('[ScanPro RateLimit Check] ' . $e->getMessage());
    }
    return false;
}

/**
 * Records one failed attempt.
 */
function rateLimitRecordFailure(PDO $pdo, string $action, ?string $identity): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO login_attempts (action, ip_hash, identifier_hash, success, created_at)
             VALUES (:action, :ip_hash, :identifier_hash, 0, :created_at)'
        );
        $stmt->execute([
            'action'          => $action,
            'ip_hash'         => rateLimitHash(rateLimitClientIp()),
            'identifier_hash' => $identity !== null ? rateLimitHash($identity) : null,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        // Housekeeping: about 1 in 50 failures also deletes rows older than a day.
        if (random_int(1, 50) === 1) {
            $del = $pdo->prepare('DELETE FROM login_attempts WHERE created_at < :cutoff');
            $del->execute(['cutoff' => date('Y-m-d H:i:s', time() - 86400)]);
        }
    } catch (Throwable $e) {
        error_log('[ScanPro RateLimit Record] ' . $e->getMessage());
    }
}
