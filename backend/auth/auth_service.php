<?php
declare(strict_types=1);

/**
 * ScanPro AI - Authentication & User Account Service
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/auth_guard.php';

/**
 * Finds a user record by normalized email address.
 *
 * @return array<string, mixed>|null
 */
function findUserByEmail(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, full_name, email, password_hash, role, status, last_login_at, created_at
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

/**
 * Registers a new user, initializes their profile, and assigns the default Free plan.
 *
 * @return array{ok: bool, message: string, user_id?: int, errors?: array<string, string>}
 */
function registerNewUser(PDO $pdo, string $fullName, string $email, string $password): array
{
    $existing = findUserByEmail($pdo, $email);
    if ($existing !== null) {
        return [
            'ok'      => false,
            'message' => 'An account with this email address already exists.',
            'errors'  => ['email' => 'This email address is already registered.'],
        ];
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        $userStmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, password_hash, role, status, created_at, updated_at)
             VALUES (:full_name, :email, :password_hash, :role, :status, NOW(), NOW())'
        );
        $userStmt->execute([
            'full_name'     => $fullName,
            'email'         => $email,
            'password_hash' => $passwordHash,
            'role'          => 'user',
            'status'        => 'active',
        ]);

        $userId = (int) $pdo->lastInsertId();

        $profileStmt = $pdo->prepare(
            'INSERT INTO user_profiles (user_id, preferred_language, preferred_theme, created_at, updated_at)
             VALUES (:user_id, :preferred_language, :preferred_theme, NOW(), NOW())'
        );
        $profileStmt->execute([
            'user_id'            => $userId,
            'preferred_language' => 'English',
            'preferred_theme'    => 'dark',
        ]);

        $planStmt = $pdo->prepare('SELECT id FROM plans WHERE slug = :slug AND is_active = 1 LIMIT 1');
        $planStmt->execute(['slug' => 'free']);
        $freePlan = $planStmt->fetch();

        if ($freePlan && !empty($freePlan['id'])) {
            $subStmt = $pdo->prepare(
                'INSERT INTO user_subscriptions (user_id, plan_id, billing_cycle, status, started_at, created_at, updated_at)
                 VALUES (:user_id, :plan_id, :billing_cycle, :status, NOW(), NOW(), NOW())'
            );
            $subStmt->execute([
                'user_id'       => $userId,
                'plan_id'       => (int) $freePlan['id'],
                'billing_cycle' => 'monthly',
                'status'        => 'active',
            ]);
        }

        $pdo->commit();

        regenerateUserSession();
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $fullName;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'user';

        return [
            'ok'      => true,
            'message' => 'Account created successfully.',
            'user_id' => $userId,
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[ScanPro Register Error] ' . $e->getMessage());
        return [
            'ok'      => false,
            'message' => 'Unable to create your account right now. Please try again.',
        ];
    }
}

/**
 * Authenticates a user with email and password and sets up session + optional Remember-Me token.
 *
 * @return array{ok: bool, message: string}
 */
function loginUserWithCredentials(
    PDO $pdo,
    string $email,
    string $password,
    bool $rememberMe = false
): array {
    $user = findUserByEmail($pdo, $email);

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        return [
            'ok'      => false,
            'message' => 'Invalid email address or password.',
        ];
    }

    if (($user['status'] ?? '') !== 'active') {
        return [
            'ok'      => false,
            'message' => 'This account is currently suspended. Please contact support.',
        ];
    }

    $userId = (int) $user['id'];

    if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $rehashStmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $rehashStmt->execute(['hash' => $newHash, 'id' => $userId]);
    }

    if ($rememberMe) {
        $selector = generateSecureToken(12);
        $validator = generateSecureToken(24);
        $tokenHash = hashToken($validator);
        $expiresAt = date('Y-m-d H:i:s', time() + (REMEMBER_ME_DAYS * 86400));

        $upd = $pdo->prepare(
            'UPDATE users
             SET remember_selector = :selector,
                 remember_token_hash = :token_hash,
                 remember_expires_at = :expires_at,
                 last_login_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id'
        );
        $upd->execute([
            'selector'   => $selector,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'id'         => $userId,
        ]);

        setcookie(
            REMEMBER_COOKIE_NAME,
            $selector . ':' . $validator,
            [
                'expires'  => time() + (REMEMBER_ME_DAYS * 86400),
                'path'     => '/',
                'domain'   => '',
                'secure'   => isHttpsRequest(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    } else {
        $upd = $pdo->prepare(
            'UPDATE users
             SET remember_selector = NULL,
                 remember_token_hash = NULL,
                 remember_expires_at = NULL,
                 last_login_at = NOW(),
                 updated_at = NOW()
             WHERE id = :id'
        );
        $upd->execute(['id' => $userId]);
        clearRememberMeCookie();
    }

    regenerateUserSession();
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = (string) $user['full_name'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['user_role'] = (string) $user['role'];

    return [
        'ok'      => true,
        'message' => 'Signed in successfully.',
    ];
}

/**
 * Logs out the current user, clears DB remember-me token, cookie, and session.
 */
function performUserLogout(): void
{
    startSecureSession();

    if (!empty($_SESSION['user_id'])) {
        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET remember_selector = NULL,
                     remember_token_hash = NULL,
                     remember_expires_at = NULL
                 WHERE id = :id'
            );
            $stmt->execute(['id' => (int) $_SESSION['user_id']]);
        } catch (Throwable $e) {
            error_log('[ScanPro Logout DB Cleanup] ' . $e->getMessage());
        }
    }

    clearRememberMeCookie();
    destroyUserSession();
}

/**
 * Generates and stores a hashed password reset token in password_resets.
 * Never claims email delivery succeeded when MAIL_DELIVERY_ENABLED is false.
 *
 * @return array{ok: bool, alertType: string, message: string}
 */
function createPasswordResetEntry(PDO $pdo, string $email, string $ipAddress): array
{
    $user = findUserByEmail($pdo, $email);

    if ($user && ($user['status'] ?? '') === 'active') {
        $userId = (int) $user['id'];

        $invalidateStmt = $pdo->prepare(
            'UPDATE password_resets SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL'
        );
        $invalidateStmt->execute(['user_id' => $userId]);

        $plainToken = generateSecureToken(32);
        $tokenHash = hashToken($plainToken);
        $expiresAt = date('Y-m-d H:i:s', time() + RESET_TOKEN_EXPIRY);

        $insertStmt = $pdo->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, ip_address, created_at)
             VALUES (:user_id, :token_hash, :expires_at, :ip_address, NOW())'
        );
        $insertStmt->execute([
            'user_id'    => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'ip_address' => substr($ipAddress, 0, 45),
        ]);
    }

    if (!MAIL_DELIVERY_ENABLED) {
        return [
            'ok'        => true,
            'alertType' => 'info',
            'message'   => 'If that email exists in our records, a reset token was stored in the database, but no email was sent because SMTP mail delivery is not configured on this server yet.',
        ];
    }

    return [
        'ok'        => true,
        'alertType' => 'success',
        'message'   => 'If an active account matches that email, password reset instructions have been queued for delivery.',
    ];
}

/**
 * Loads combined user, profile, and current plan data for the Dashboard.
 *
 * @return array<string, mixed>|null
 */
function fetchUserDashboardProfile(PDO $pdo, int $userId): ?array
{
    $sql = "SELECT
            u.id,
            u.full_name,
            u.email,
            u.role,
            u.status,
            u.last_login_at,
            u.created_at,
            %s
            up.phone,
            up.organization,
            up.bio,
            up.preferred_language,
            up.preferred_theme,
            us.billing_cycle,
            us.status AS subscription_status,
            us.started_at AS subscription_started_at,
            p.slug AS plan_slug,
            p.name AS plan_name,
            p.tagline AS plan_tagline,
            p.storage_label,
            p.storage_limit_mb,
            p.price_monthly,
            p.price_yearly
         FROM users u
         LEFT JOIN user_profiles up ON up.user_id = u.id
         LEFT JOIN user_subscriptions us ON us.user_id = u.id AND us.status = 'active'
         LEFT JOIN plans p ON p.id = us.plan_id
         WHERE u.id = :id
         ORDER BY us.id DESC
         LIMIT 1";

    try {
        $stmt = $pdo->prepare(sprintf($sql, 'u.avatar_url, u.auth_provider,'));
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
    } catch (PDOException $e) {
        // Migration 001 not applied yet: fall back to the original columns.
        $stmt = $pdo->prepare(sprintf($sql, ''));
        $stmt->execute(['id' => $userId]);
        $row = $stmt->fetch();
        if (is_array($row)) {
            $row['avatar_url'] = null;
            $row['auth_provider'] = 'password';
        }
    }

    return is_array($row) ? $row : null;
}

/**
 * Updates user name and extended profile settings safely.
 *
 * @return array{ok: bool, message: string}
 */
function saveUserProfileChanges(
    PDO $pdo,
    int $userId,
    string $fullName,
    string $phone,
    string $organization,
    string $bio,
    string $preferredLanguage
): array {
    try {
        $pdo->beginTransaction();

        $userStmt = $pdo->prepare(
            'UPDATE users SET full_name = :full_name, updated_at = NOW() WHERE id = :id'
        );
        $userStmt->execute([
            'full_name' => $fullName,
            'id'        => $userId,
        ]);

        $checkStmt = $pdo->prepare('SELECT id FROM user_profiles WHERE user_id = :user_id LIMIT 1');
        $checkStmt->execute(['user_id' => $userId]);
        $exists = $checkStmt->fetch();

        if ($exists) {
            $profStmt = $pdo->prepare(
                'UPDATE user_profiles
                 SET phone = :phone,
                     organization = :organization,
                     bio = :bio,
                     preferred_language = :preferred_language,
                     updated_at = NOW()
                 WHERE user_id = :user_id'
            );
        } else {
            $profStmt = $pdo->prepare(
                "INSERT INTO user_profiles (user_id, phone, organization, bio, preferred_language, preferred_theme, created_at, updated_at)
                 VALUES (:user_id, :phone, :organization, :bio, :preferred_language, 'dark', NOW(), NOW())"
            );
        }

        $profStmt->execute([
            'user_id'            => $userId,
            'phone'              => $phone !== '' ? $phone : null,
            'organization'       => $organization !== '' ? $organization : null,
            'bio'                => $bio !== '' ? $bio : null,
            'preferred_language' => $preferredLanguage,
        ]);

        $pdo->commit();
        $_SESSION['user_name'] = $fullName;

        return [
            'ok'      => true,
            'message' => 'Profile settings saved successfully.',
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('[ScanPro Profile Update Error] ' . $e->getMessage());
        return [
            'ok'      => false,
            'message' => 'Unable to save profile changes right now.',
        ];
    }
}

/**
 * Verifies current password and updates the user's password hash.
 *
 * @return array{ok: bool, message: string, errors?: array<string, string>}
 */
function changeUserPassword(
    PDO $pdo,
    int $userId,
    string $currentPassword,
    string $newPassword
): array {
    try {
        $stmt = $pdo->prepare('SELECT password_hash, auth_provider FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
    } catch (PDOException $e) {
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
    }
    $row = $stmt->fetch();

    if ($row && ($row['auth_provider'] ?? 'password') === 'google') {
        return [
            'ok'      => false,
            'message' => 'This account signs in with Google, so it has no password to change.',
        ];
    }

    if (!$row || !password_verify($currentPassword, (string) $row['password_hash'])) {
        return [
            'ok'      => false,
            'message' => 'Current password is incorrect.',
            'errors'  => ['current_password' => 'Current password does not match our records.'],
        ];
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
    $upd->execute([
        'hash' => $newHash,
        'id'   => $userId,
    ]);

    return [
        'ok'      => true,
        'message' => 'Password updated successfully.',
    ];
}


/* ---------------------------------------------------------------------------
 * Google (Firebase) sign-in support
 * ------------------------------------------------------------------------- */

/**
 * Starts the PHP login session for an already authenticated user row.
 *
 * @param array<string, mixed> $user  needs id, full_name, email, role
 */
function establishLoginSession(array $user): void
{
    regenerateUserSession();
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = (string) $user['full_name'];
    $_SESSION['user_email'] = (string) $user['email'];
    $_SESSION['user_role'] = (string) $user['role'];
}

/**
 * Creates a new user from a verified Google identity (profile + free plan, like normal registration).
 * Returns the new user id, or null when the email / Google id was created by another request meanwhile.
 */
function createGoogleUserRecord(PDO $pdo, string $uid, string $email, string $name, ?string $avatar): ?int
{
    // Google accounts have no password: store a random, unusable hash.
    $unusableHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        $userStmt = $pdo->prepare(
            "INSERT INTO users (full_name, email, password_hash, firebase_uid, auth_provider, avatar_url, email_verified, role, status, last_login_at, created_at, updated_at)
             VALUES (:full_name, :email, :password_hash, :firebase_uid, 'google', :avatar_url, 1, 'user', 'active', NOW(), NOW(), NOW())"
        );
        $userStmt->execute([
            'full_name'     => $name,
            'email'         => $email,
            'password_hash' => $unusableHash,
            'firebase_uid'  => $uid,
            'avatar_url'    => $avatar,
        ]);
        $userId = (int) $pdo->lastInsertId();

        $profileStmt = $pdo->prepare(
            'INSERT INTO user_profiles (user_id, preferred_language, preferred_theme, created_at, updated_at)
             VALUES (:user_id, :preferred_language, :preferred_theme, NOW(), NOW())'
        );
        $profileStmt->execute([
            'user_id'            => $userId,
            'preferred_language' => 'English',
            'preferred_theme'    => 'dark',
        ]);

        $planStmt = $pdo->prepare('SELECT id FROM plans WHERE slug = :slug AND is_active = 1 LIMIT 1');
        $planStmt->execute(['slug' => 'free']);
        $freePlan = $planStmt->fetch();
        if ($freePlan && !empty($freePlan['id'])) {
            $subStmt = $pdo->prepare(
                'INSERT INTO user_subscriptions (user_id, plan_id, billing_cycle, status, started_at, created_at, updated_at)
                 VALUES (:user_id, :plan_id, :billing_cycle, :status, NOW(), NOW(), NOW())'
            );
            $subStmt->execute([
                'user_id'       => $userId,
                'plan_id'       => (int) $freePlan['id'],
                'billing_cycle' => 'monthly',
                'status'        => 'active',
            ]);
        }

        $pdo->commit();
        return $userId;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ((string) $e->getCode() === '23000') {
            return null; // duplicate email / firebase_uid created by a parallel request
        }
        throw $e;
    }
}

/**
 * Finds, links or creates the MySQL user for a token that was ALREADY verified by verifyFirebaseIdToken().
 *
 * Linking rule: an existing email/password account is linked to the Google account only because the
 * verified token proves ownership of that email. An account already linked to a different Google id is refused.
 *
 * @param array{uid: string, email: string, name: string, picture: string} $identity
 * @return array{ok: bool, message: string, user?: array<string, mixed>, is_new?: bool, linked?: bool}
 */
function syncFirebaseUser(PDO $pdo, array $identity): array
{
    $uid = $identity['uid'];
    $email = $identity['email'];

    $name = trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $identity['name']));
    if ($name === '') {
        $name = (string) strstr($email, '@', true);
    }
    if ($name === '') {
        $name = 'ScanPro User';
    }
    $name = mb_substr($name, 0, 100, 'UTF-8');

    $picture = $identity['picture'];
    $avatar = (preg_match('#^https://#i', $picture) === 1 && strlen($picture) <= 500) ? $picture : null;

    $columns = 'id, full_name, email, role, status, firebase_uid';
    $suspended = ['ok' => false, 'message' => 'This account is currently suspended. Please contact support.'];
    $conflict = ['ok' => false, 'message' => 'This email is already linked to a different Google account. Please contact support.'];

    try {
        $byUid = $pdo->prepare("SELECT $columns FROM users WHERE firebase_uid = :uid LIMIT 1");
        $byUid->execute(['uid' => $uid]);
        $user = $byUid->fetch();
    } catch (PDOException $e) {
        error_log('[ScanPro Firebase Schema Error] Missing firebase_uid column in users table: ' . $e->getMessage());
        return ['ok' => false, 'message' => 'Google sign-in database columns are not configured yet. Please run the Google auth SQL migration.'];
    }

    $isNew = false;
    $linked = false;

    if (!is_array($user)) {
        $byEmail = $pdo->prepare("SELECT $columns FROM users WHERE email = :email LIMIT 1");
        $byEmail->execute(['email' => $email]);
        $user = $byEmail->fetch();

        if (is_array($user)) {
            if (($user['status'] ?? '') !== 'active') {
                return $suspended;
            }
            if (!empty($user['firebase_uid']) && !hash_equals((string) $user['firebase_uid'], $uid)) {
                return $conflict;
            }

            $link = $pdo->prepare(
                'UPDATE users
                 SET firebase_uid = :uid, email_verified = 1, avatar_url = COALESCE(avatar_url, :avatar), updated_at = NOW()
                 WHERE id = :id AND firebase_uid IS NULL'
            );
            $link->execute(['uid' => $uid, 'avatar' => $avatar, 'id' => (int) $user['id']]);
            if ($link->rowCount() !== 1) {
                return $conflict;
            }
            $linked = true;
        } else {
            $newId = createGoogleUserRecord($pdo, $uid, $email, $name, $avatar);

            if ($newId === null) {
                $byUid->execute(['uid' => $uid]);
                $user = $byUid->fetch();
                if (!is_array($user)) {
                    $byEmail->execute(['email' => $email]);
                    $user = $byEmail->fetch();
                }
                if (!is_array($user) || (!empty($user['firebase_uid']) && !hash_equals((string) $user['firebase_uid'], $uid))) {
                    return $conflict;
                }
            } else {
                $isNew = true;
                $byId = $pdo->prepare("SELECT $columns FROM users WHERE id = :id LIMIT 1");
                $byId->execute(['id' => $newId]);
                $user = $byId->fetch();
            }
        }
    }

    if (!is_array($user)) {
        return ['ok' => false, 'message' => 'Unable to complete Google sign-in right now. Please try again.'];
    }
    if (($user['status'] ?? '') !== 'active') {
        return $suspended;
    }

    $touch = $pdo->prepare(
        'UPDATE users
         SET last_login_at = NOW(), email_verified = 1, avatar_url = COALESCE(:avatar, avatar_url), updated_at = NOW()
         WHERE id = :id'
    );
    $touch->execute(['avatar' => $avatar, 'id' => (int) $user['id']]);

    return [
        'ok'      => true,
        'message' => 'Signed in successfully.',
        'user'    => $user,
        'is_new'  => $isNew,
        'linked'  => $linked,
    ];
}