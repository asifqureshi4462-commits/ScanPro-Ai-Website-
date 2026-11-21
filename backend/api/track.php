<?php
declare(strict_types=1);

/**
 * ScanPro AI - Privacy-Conscious Analytics Event Receiver
 * Stores aggregate counts with a daily-rotating salted hash (zero raw IP or PII stored).
 */

require_once __DIR__ . '/../config/admin.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/response.php';

applySecurityHeaders();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    jsonError('Method not allowed.', 405);
}

$rawBody = file_get_contents('php://input');
$payload = [];
if (is_string($rawBody) && trim($rawBody) !== '') {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}
if (empty($payload) && !empty($_POST)) {
    $payload = $_POST;
}

$eventType = cleanText($payload['event_type'] ?? '');
$targetKey = cleanText($payload['target_key'] ?? '');
$platform = cleanText($payload['platform'] ?? 'website');

if (!in_array($eventType, ['page_view', 'tool_run', 'download', 'app_open'], true)) {
    jsonError('Invalid event type.', 422);
}
if (preg_match('/^[a-zA-Z0-9_:\.\-]{1,80}$/', $targetKey) !== 1) {
    jsonError('Invalid target key.', 422);
}
if (!in_array($platform, ['website', 'android'], true)) {
    $platform = 'website';
}

$ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
$deviceCategory = 'unknown';
if ($ua !== '') {
    if (preg_match('/ipad|tablet|playbook|silk|(android(?!.*mobile))/i', $ua) === 1) {
        $deviceCategory = 'tablet';
    } elseif (preg_match('/mobile|iphone|ipod|android|blackberry|iemobile|opera mini/i', $ua) === 1) {
        $deviceCategory = 'mobile';
    } elseif (preg_match('/windows|macintosh|linux|x11|cros/i', $ua) === 1) {
        $deviceCategory = 'desktop';
    }
}

try {
    $today = date('Y-m-d');
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $salt = getPrivacyAnalyticsSalt();
    $visitorHash = hash('sha256', $today . '|' . $ip . '|' . $ua . '|' . $salt);

    $pdo = getDbConnection();
    $stmt = $pdo->prepare(
        'INSERT INTO analytics_events (event_type, target_key, platform, device_category, visitor_hash, event_date, created_at)
         VALUES (:event_type, :target_key, :platform, :device_category, :visitor_hash, :event_date, NOW())'
    );
    $stmt->execute([
        'event_type'      => $eventType,
        'target_key'      => strtolower($targetKey),
        'platform'        => $platform,
        'device_category' => $deviceCategory,
        'visitor_hash'    => $visitorHash,
        'event_date'      => $today,
    ]);

    jsonSuccess('Recorded.');
} catch (Throwable $e) {
    error_log('[ScanPro Analytics Track Error] ' . $e->getMessage());
    jsonSuccess('Skipped.');
}