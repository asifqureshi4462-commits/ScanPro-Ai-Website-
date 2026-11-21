<?php
declare(strict_types=1);

/**
 * ScanPro AI - Isolated Admin Authentication & Session Guard
 * Completely separated from public user sessions (SCANPRO_SESSID).
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/admin.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/rate_limiter.php';
require_once __DIR__ . '/../helpers/site_runtime.php';
require_once __DIR__ . '/../helpers/admin_logger.php';

/**
 * Resolves the root URL path to /admin safely regardless of whether the script
 * is executed from /admin/*.php or /backend/api/admin/*.php.
 */
function getAdminRootUrlPath(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    // API routes contain /admin/ as a nested directory, so check /backend/
    // first to resolve both API and web requests to the project admin root.
    foreach (['/backend/', '/admin/', '/frontend/'] as $marker) {
        $pos = strpos($scriptName, $marker);
        if ($pos !== false) {
            return substr($scriptName, 0, $pos) . '/admin';
        }
    }
    return '/admin';
}

/**
 * Resolves the cookie path shared by both /admin/* and /backend/api/admin/*.
 */
function getAdminSessionCookiePath(): string
{
    $adminRoot = getAdminRootUrlPath();
    $projectRoot = preg_replace('#/admin$#', '', $adminRoot);
    if ($projectRoot === '' || $projectRoot === '/' || $projectRoot === null) {
        return '/';
    }
    return rtrim($projectRoot, '/') . '/';
}

/**
 * Sends strict defensive headers for Admin UI and APIs (prevents indexing & framing).
 */
function applyAdminSecurityHeaders(): void
{
    applySecurityHeaders();
    if (headers_sent()) {
        return;
    }
    header('X-Frame-Options: DENY');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

/**
 * Reads configured admin idle timeout safely within [300, 86400] seconds.
 */
function getConfiguredAdminIdleTimeout(): int
{
    $raw = (int) getSiteSetting('admin_session_timeout', (string) ADMIN_SESSION_IDLE_TIMEOUT);
    if ($raw < 300 || $raw > 86400) {
        return ADMIN_SESSION_IDLE_TIMEOUT;
    }
    return $raw;
}

/**
 * Starts the isolated Admin PHP session (SCANPRO_ADM_SESSID).
 */
function startAdminSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (session_name() === ADMIN_SESSION_NAME) {
            return;
        }
        session_write_close();
    }

    $isHttps = isHttpsRequest();
    $cookiePath = getAdminSessionCookiePath();

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');

    session_name(ADMIN_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => $cookiePath,
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    $idleLimit = getConfiguredAdminIdleTimeout();
    if (
        !empty($_SESSION['admin_id'])
        && isset($_SESSION['_admin_last_activity'])
        && (time() - (int) $_SESSION['_admin_last_activity']) > $idleLimit
    ) {
        $_SESSION = [];
        $_SESSION['_admin_timed_out'] = true;
        session_regenerate_id(true);
    }

    $_SESSION['_admin_last_activity'] = time();

    if (!isset($_SESSION['_admin_created_at'])) {
        $_SESSION['_admin_created_at'] = time();
    } elseif (time() - (int) $_SESSION['_admin_created_at'] > ADMIN_SESSION_REGENERATE_INTERVAL) {
        session_regenerate_id(true);
        $_SESSION['_admin_created_at'] = time();
    }
}

/**
 * Regenerates the admin session ID after authentication or privilege change.
 */
function regenerateAdminSession(): void
{
    startAdminSession();
    session_regenerate_id(true);
    $_SESSION['_admin_created_at'] = time();
    $_SESSION['_admin_last_activity'] = time();
}

/**
 * Destroys the admin session cleanly.
 */
function destroyAdminSession(): void
{
    startAdminSession();

    $_SESSION = [];
    $sessName = session_name();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        if (!headers_sent()) {
            setcookie(
                $sessName,
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => (bool) $params['secure'],
                    'httponly' => (bool) $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }
    }

    unset($_COOKIE[$sessName]);
    session_destroy();

    if (session_status() === PHP_SESSION_NONE) {
        session_id('');
    }
}

/**
 * Generates or returns the Admin CSRF token.
 */
function getAdminCsrfToken(): string
{
    startAdminSession();

    $token = $_SESSION['_admin_csrf_token'] ?? null;
    $issuedAt = $_SESSION['_admin_csrf_time'] ?? 0;

    if (
        !is_string($token)
        || strlen($token) !== 64
        || (time() - (int) $issuedAt) > ADMIN_CSRF_TOKEN_LIFETIME
    ) {
        $token = bin2hex(random_bytes(32));
        $_SESSION['_admin_csrf_token'] = $token;
        $_SESSION['_admin_csrf_time'] = time();
    }

    return $token;
}

/**
 * Returns a hidden HTML CSRF input for admin forms.
 */
function adminCsrfInput(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(getAdminCsrfToken()) . '">';
}

/**
 * Extracts CSRF token from POST, JSON payload, or X-CSRF-Token header.
 *
 * @param array<string, mixed>|null $payload
 */
function extractAdminCsrfToken(?array $payload = null): ?string
{
    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN']) && is_string($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    if (is_array($payload) && !empty($payload['csrf_token']) && is_string($payload['csrf_token'])) {
        return $payload['csrf_token'];
    }
    if (!empty($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
        return $_POST['csrf_token'];
    }
    return null;
}

/**
 * Verifies an Admin CSRF token in constant time.
 */
function verifyAdminCsrfToken(?string $submittedToken): bool
{
    startAdminSession();
    $expected = $_SESSION['_admin_csrf_token'] ?? null;
    if (!is_string($expected) || !is_string($submittedToken) || $submittedToken === '') {
        return false;
    }
    return hash_equals($expected, $submittedToken);
}

/**
 * Stores a one-time flash message inside the admin session.
 */
function setAdminFlash(string $type, string $message): void
{
    startAdminSession();
    $_SESSION['_admin_flashes'][] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Consumes and clears queued admin flash messages.
 *
 * @return array<int, array{type: string, message: string}>
 */
function consumeAdminFlashes(): array
{
    startAdminSession();
    if (!empty($_SESSION['_admin_timed_out'])) {
        unset($_SESSION['_admin_timed_out']);
        $_SESSION['_admin_flashes'][] = [
            'type'    => 'warning',
            'message' => 'Your admin session expired due to inactivity. Please sign in again.',
        ];
    }
    $items = $_SESSION['_admin_flashes'] ?? [];
    unset($_SESSION['_admin_flashes']);
    return is_array($items) ? $items : [];
}

/**
 * Returns the number of admin accounts currently in the database.
 */
function countAdminAccounts(PDO $pdo): int
{
    $stmt = $pdo->query('SELECT COUNT(*) FROM admins');
    return $stmt ? (int) $stmt->fetchColumn() : 0;
}

/**
 * Fetches the active admin record for the current session (fail-closed).
 *
 * @return array<string, mixed>|null
 */
function getAuthenticatedAdmin(): ?array
{
    startAdminSession();

    $adminId = (int) ($_SESSION['admin_id'] ?? 0);
    if ($adminId <= 0) {
        return null;
    }

    try {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare(
            'SELECT id, username, email, full_name, role, status, last_login_at, created_at
             FROM admins
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $adminId]);
        $admin = $stmt->fetch();

        if (!is_array($admin) || ($admin['status'] ?? '') !== 'active') {
            destroyAdminSession();
            return null;
        }

        $_SESSION['admin_username'] = (string) $admin['username'];
        $_SESSION['admin_email'] = (string) $admin['email'];
        $_SESSION['admin_name'] = (string) $admin['full_name'];
        $_SESSION['admin_role'] = (string) $admin['role'];

        return $admin;
    } catch (Throwable $e) {
        error_log('[ScanPro Admin Auth Guard Error] ' . $e->getMessage());
        return null;
    }
}

/**
 * Protects Admin web pages; redirects unauthenticated requests to /admin/login.php.
 *
 * @return array<string, mixed>
 */
function requireAdminWebAuth(): array
{
    applyAdminSecurityHeaders();
    $admin = getAuthenticatedAdmin();
    if ($admin === null) {
        setAdminFlash('warning', 'Administrator sign-in is required.');
        header('Location: ' . getAdminRootUrlPath() . '/login.php', true, 302);
        exit;
    }
    return $admin;
}

/**
 * Protects Admin JSON API endpoints; returns 401 JSON if unauthorized.
 *
 * @return array<string, mixed>
 */
function requireAdminApiAuth(): array
{
    applyAdminSecurityHeaders();
    $admin = getAuthenticatedAdmin();
    if ($admin === null) {
        jsonError('Administrator authentication required.', 401);
    }
    return $admin;
}

/**
 * Authenticates an admin using username or email + password.
 *
 * @return array{ok: bool, message: string, admin?: array<string, mixed>}
 */
function loginAdminWithCredentials(PDO $pdo, string $identity, string $password): array
{
    $normalized = strtolower(trim($identity));
    if ($normalized === '' || $password === '') {
        return [
            'ok'      => false,
            'message' => 'Please enter your username/email and password.',
        ];
    }

    $stmt = $pdo->prepare(
        'SELECT id, username, email, full_name, password_hash, role, status
         FROM admins
         WHERE username = :u OR email = :e
         LIMIT 1'
    );
    $stmt->execute([
        'u' => $normalized,
        'e' => $normalized,
    ]);
    $admin = $stmt->fetch();

    if (!is_array($admin) || !password_verify($password, (string) $admin['password_hash'])) {
        logAdminActivity(
            $pdo,
            null,
            mb_substr($normalized, 0, 60, 'UTF-8'),
            'admin.login_failed',
            'Failed admin sign-in attempt'
        );
        return [
            'ok'      => false,
            'message' => 'Invalid administrator credentials.',
        ];
    }

    if (($admin['status'] ?? '') !== 'active') {
        logAdminActivity(
            $pdo,
            (int) $admin['id'],
            (string) $admin['username'],
            'admin.login_blocked',
            'Disabled admin account attempted sign-in'
        );
        return [
            'ok'      => false,
            'message' => 'This administrator account is currently disabled.',
        ];
    }

    $adminId = (int) $admin['id'];

    if (password_needs_rehash((string) $admin['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $rehash = $pdo->prepare('UPDATE admins SET password_hash = :hash WHERE id = :id');
        $rehash->execute(['hash' => $newHash, 'id' => $adminId]);
    }

    $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $upd = $pdo->prepare('UPDATE admins SET last_login_at = NOW(), last_login_ip = :ip, updated_at = NOW() WHERE id = :id');
    $upd->execute([
        'ip' => $ip !== '' ? $ip : null,
        'id' => $adminId,
    ]);

    regenerateAdminSession();
    $_SESSION['admin_id'] = $adminId;
    $_SESSION['admin_username'] = (string) $admin['username'];
    $_SESSION['admin_email'] = (string) $admin['email'];
    $_SESSION['admin_name'] = (string) $admin['full_name'];
    $_SESSION['admin_role'] = (string) $admin['role'];

    logAdminActivity(
        $pdo,
        $adminId,
        (string) $admin['username'],
        'admin.login',
        'Administrator signed in',
        'admin',
        (string) $adminId
    );

    return [
        'ok'      => true,
        'message' => 'Signed in to Control Center.',
        'admin'   => $admin,
    ];
}

/**
 * Logs out the current admin and records the audit entry.
 */
function performAdminLogout(): void
{
    startAdminSession();
    $adminId = !empty($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
    $username = !empty($_SESSION['admin_username']) ? (string) $_SESSION['admin_username'] : null;

    if ($adminId !== null) {
        try {
            $pdo = getDbConnection();
            logAdminActivity(
                $pdo,
                $adminId,
                $username,
                'admin.logout',
                'Administrator signed out',
                'admin',
                (string) $adminId
            );
        } catch (Throwable $e) {
            error_log('[ScanPro Admin Logout Log] ' . $e->getMessage());
        }
    }

    destroyAdminSession();
}

/**
 * Creates the very first super_admin account (only allowed when admins table is empty)
 * and locks future setup on disk.
 *
 * @return array{ok: bool, message: string}
 */
function createInitialSuperAdmin(
    PDO $pdo,
    string $username,
    string $email,
    string $fullName,
    string $password
): array {
    if (isAdminSetupLockedOnDisk()) {
        return [
            'ok'      => false,
            'message' => 'Initial admin setup is already locked.',
        ];
    }

    try {
        $pdo->beginTransaction();

        $countStmt = $pdo->query('SELECT COUNT(*) FROM admins FOR UPDATE');
        $existingCount = $countStmt ? (int) $countStmt->fetchColumn() : 0;

        if ($existingCount > 0) {
            $pdo->rollBack();
            @file_put_contents(ADMIN_SETUP_LOCK_FILE, 'locked:' . date('c') . "\n", LOCK_EX);
            return [
                'ok'      => false,
                'message' => 'An administrator account already exists. Setup is locked.',
            ];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO admins (username, email, full_name, password_hash, role, status, created_at, updated_at)
             VALUES (:username, :email, :full_name, :password_hash, 'super_admin', 'active', NOW(), NOW())"
        );
        $stmt->execute([
            'username'      => strtolower($username),
            'email'         => strtolower($email),
            'full_name'     => $fullName,
            'password_hash' => $hash,
        ]);

        $newId = (int) $pdo->lastInsertId();
        $pdo->commit();

        $storageDir = dirname(ADMIN_SETUP_LOCK_FILE);
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0750, true);
        }
        @file_put_contents(ADMIN_SETUP_LOCK_FILE, 'locked:' . date('c') . "\n", LOCK_EX);

        logAdminActivity(
            $pdo,
            $newId,
            strtolower($username),
            'admin.setup',
            'Initial Super Admin account created via setup',
            'admin',
            (string) $newId
        );

        return [
            'ok'      => true,
            'message' => 'Super Admin account created and setup locked.',
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[ScanPro Admin Setup Error] ' . $e->getMessage());
        return [
            'ok'      => false,
            'message' => 'Unable to create administrator account. Check that the admin migration SQL has been imported.',
        ];
    }
}
