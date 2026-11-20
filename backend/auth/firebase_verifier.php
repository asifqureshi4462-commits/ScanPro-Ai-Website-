<?php
declare(strict_types=1);

/**
 * ScanPro AI - Firebase ID token verification (no Admin SDK, no service account needed).
 *
 * Checks: RS256 signature against Google's public certificate selected by "kid",
 * expiry (exp), issue time (iat, auth_time), issuer (iss), audience (aud),
 * subject (sub), sign-in provider and verified email.
 */

require_once __DIR__ . '/../config/firebase.php';

final class FirebaseTokenException extends RuntimeException
{
    private string $userMessage;
    private bool $serviceError;

    public function __construct(
        string $logMessage,
        string $userMessage = 'Google sign-in could not be verified. Please try again.',
        bool $serviceError = false
    ) {
        parent::__construct($logMessage);
        $this->userMessage = $userMessage;
        $this->serviceError = $serviceError;
    }

    public function userMessage(): string
    {
        return $this->userMessage;
    }

    public function isServiceError(): bool
    {
        return $this->serviceError;
    }
}

/**
 * Strict base64url decoder. Returns false on invalid input.
 */
function firebaseBase64UrlDecode(string $input): string|false
{
    if ($input === '' || preg_match('/^[A-Za-z0-9_-]+$/', $input) !== 1) {
        return false;
    }
    $remainder = strlen($input) % 4;
    if ($remainder === 1) {
        return false;
    }
    if ($remainder > 0) {
        $input .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($input, '-_', '+/'), true);
}

/**
 * Downloads Google's public certificates (TLS certificate is verified).
 * Uses cURL when available and falls back to HTTPS streams if needed.
 *
 * @return array{fetched_at: int, expires_at: int, keys: array<string, string>}|null
 */
function firebaseFetchCertsFromGoogle(): ?array
{
    $headersText = '';
    $body = '';

    if (function_exists('curl_init')) {
        $ch = curl_init(FIREBASE_CERTS_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_USERAGENT      => 'ScanProAI/1.0',
        ]);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if (is_string($raw) && $status === 200) {
            $headersText = substr($raw, 0, $headerSize);
            $body = substr($raw, $headerSize);
        } else {
            error_log('[ScanPro Firebase Certs cURL] status=' . $status . ' err=' . $curlErr);
        }
    }

    if ($body === '' && filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 8,
                'header'  => "User-Agent: ScanProAI/1.0\r\n",
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);
        $streamBody = @file_get_contents(FIREBASE_CERTS_URL, false, $context);
        if (is_string($streamBody) && $streamBody !== '') {
            $body = $streamBody;
            if (isset($http_response_header) && is_array($http_response_header)) {
                $headersText = implode("\n", $http_response_header);
            }
        } else {
            error_log('[ScanPro Firebase Certs Stream] Failed to fetch Google public keys.');
        }
    }

    if ($body === '') {
        return null;
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        return null;
    }

    $keys = [];
    foreach ($decoded as $kid => $pem) {
        if (is_string($kid) && is_string($pem) && str_contains($pem, 'BEGIN CERTIFICATE')) {
            $keys[$kid] = $pem;
        }
    }
    if ($keys === []) {
        return null;
    }

    $maxAge = 3600;
    if ($headersText !== '' && preg_match('/^cache-control:.*max-age=(\d+)/mi', $headersText, $m) === 1) {
        $maxAge = max(300, min((int) $m[1], 86400));
    }

    return [
        'fetched_at' => time(),
        'expires_at' => time() + $maxAge,
        'keys'       => $keys,
    ];
}

/**
 * @return array{fetched_at: int, expires_at: int, keys: array<string, string>}|null
 */
function firebaseReadCertCache(): ?array
{
    if (!is_file(FIREBASE_CERTS_CACHE_FILE)) {
        return null;
    }
    $raw = @file_get_contents(FIREBASE_CERTS_CACHE_FILE);
    if (!is_string($raw)) {
        return null;
    }
    $data = json_decode($raw, true);
    if (
        !is_array($data)
        || !isset($data['keys'], $data['expires_at'], $data['fetched_at'])
        || !is_array($data['keys'])
    ) {
        return null;
    }
    return $data;
}

/**
 * @param array{fetched_at: int, expires_at: int, keys: array<string, string>} $data
 */
function firebaseWriteCertCache(array $data): void
{
    $dir = dirname(FIREBASE_CERTS_CACHE_FILE);
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    $tmp = FIREBASE_CERTS_CACHE_FILE . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, json_encode($data), LOCK_EX) === false) {
        @unlink($tmp);
        return;
    }
    if (!@rename($tmp, FIREBASE_CERTS_CACHE_FILE)) {
        @unlink($tmp);
    }
}

/**
 * Returns kid => PEM certificate. Uses the cache; refreshes when expired or when forced.
 *
 * @return array<string, string>
 */
function firebaseGetPublicKeys(bool $forceRefresh = false): array
{
    $cache = firebaseReadCertCache();
    $isFresh = $cache !== null && (int) $cache['expires_at'] > time();

    if ($isFresh && !$forceRefresh) {
        return $cache['keys'];
    }

    // A forced refresh (unknown kid) is allowed at most once per minute, so fake kids cannot hammer Google.
    if ($cache !== null && $forceRefresh && (time() - (int) $cache['fetched_at']) < 60) {
        return $cache['keys'];
    }

    $fresh = firebaseFetchCertsFromGoogle();
    if ($fresh !== null) {
        firebaseWriteCertCache($fresh);
        return $fresh['keys'];
    }

    // Google unreachable: keep working with the last known keys for up to 24 hours.
    if ($cache !== null && (time() - (int) $cache['fetched_at']) < 86400) {
        return $cache['keys'];
    }

    throw new FirebaseTokenException(
        'Could not load Google signing keys.',
        'Google sign-in is temporarily unavailable. Please try again in a moment.',
        true
    );
}

/**
 * Fully verifies a Firebase ID token and returns the trusted identity.
 *
 * @return array{uid: string, email: string, name: string, picture: string}
 * @throws FirebaseTokenException
 */
function verifyFirebaseIdToken(string $idToken): array
{
    $idToken = trim($idToken);
    if ($idToken === '' || strlen($idToken) > FIREBASE_MAX_TOKEN_LENGTH) {
        throw new FirebaseTokenException('Token missing or too long.');
    }

    $parts = explode('.', $idToken);
    if (count($parts) !== 3) {
        throw new FirebaseTokenException('Malformed token.');
    }
    [$headerB64, $payloadB64, $signatureB64] = $parts;

    $headerJson = firebaseBase64UrlDecode($headerB64);
    $payloadJson = firebaseBase64UrlDecode($payloadB64);
    $signature = firebaseBase64UrlDecode($signatureB64);
    if ($headerJson === false || $payloadJson === false || $signature === false) {
        throw new FirebaseTokenException('Token is not valid base64url.');
    }

    $header = json_decode($headerJson, true);
    $claims = json_decode($payloadJson, true);
    if (!is_array($header) || !is_array($claims)) {
        throw new FirebaseTokenException('Token header or payload is not JSON.');
    }

    // 1. Algorithm and key id (alg "none" and HS256 tricks are rejected here)
    if (($header['alg'] ?? null) !== 'RS256') {
        throw new FirebaseTokenException('Unsupported algorithm.');
    }
    $kid = $header['kid'] ?? null;
    if (!is_string($kid) || $kid === '' || strlen($kid) > 128) {
        throw new FirebaseTokenException('Missing key id (kid).');
    }

    // 2. Signature
    $keys = firebaseGetPublicKeys();
    if (!isset($keys[$kid])) {
        $keys = firebaseGetPublicKeys(true);
    }
    if (!isset($keys[$kid])) {
        throw new FirebaseTokenException('Unknown signing key (kid).');
    }
    $publicKey = openssl_pkey_get_public($keys[$kid]);
    if ($publicKey === false) {
        throw new FirebaseTokenException('Signing certificate could not be read.', 'Google sign-in is temporarily unavailable. Please try again.', true);
    }
    if (openssl_verify($headerB64 . '.' . $payloadB64, $signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
        throw new FirebaseTokenException('Invalid signature.');
    }

    // 3. Time claims
    $now = time();
    $skew = FIREBASE_CLOCK_SKEW;
    $exp = $claims['exp'] ?? null;
    $iat = $claims['iat'] ?? null;
    $authTime = $claims['auth_time'] ?? null;
    if (!is_int($exp) || !is_int($iat) || !is_int($authTime)) {
        throw new FirebaseTokenException('Missing exp, iat or auth_time.');
    }
    if ($exp < $now - $skew) {
        throw new FirebaseTokenException('Token expired.', 'Your Google sign-in expired. Please try again.');
    }
    if ($iat > $now + $skew || $authTime > $now + $skew || $exp <= $iat) {
        throw new FirebaseTokenException('Token times are not valid.');
    }
    if ($now - $authTime > FIREBASE_MAX_AUTH_AGE) {
        throw new FirebaseTokenException('Sign-in is too old.', 'Your Google sign-in is too old. Please click "Continue with Google" again.');
    }

    // 4. Issuer, audience, subject
    if (($claims['aud'] ?? null) !== FIREBASE_PROJECT_ID) {
        throw new FirebaseTokenException('Wrong audience.');
    }
    if (($claims['iss'] ?? null) !== 'https://securetoken.google.com/' . FIREBASE_PROJECT_ID) {
        throw new FirebaseTokenException('Wrong issuer.');
    }
    $sub = $claims['sub'] ?? null;
    if (!is_string($sub) || $sub === '' || strlen($sub) > 128) {
        throw new FirebaseTokenException('Invalid subject.');
    }

    // 5. Sign-in provider: only Google is accepted on this endpoint
    $firebase = $claims['firebase'] ?? null;
    $provider = is_array($firebase) ? ($firebase['sign_in_provider'] ?? null) : null;
    if ($provider !== 'google.com') {
        throw new FirebaseTokenException('Unsupported sign-in provider.', 'This sign-in method is not supported on the website yet.');
    }

    // 6. Verified email (required for safe account linking)
    $email = $claims['email'] ?? null;
    if (!is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 190) {
        throw new FirebaseTokenException('Missing or invalid email.', 'Your Google account did not provide an email address.');
    }
    if (($claims['email_verified'] ?? false) !== true) {
        throw new FirebaseTokenException('Email not verified.', 'Your Google email address is not verified.');
    }

    return [
        'uid'     => $sub,
        'email'   => strtolower($email),
        'name'    => is_string($claims['name'] ?? null) ? (string) $claims['name'] : '',
        'picture' => is_string($claims['picture'] ?? null) ? (string) $claims['picture'] : '',
    ];
}