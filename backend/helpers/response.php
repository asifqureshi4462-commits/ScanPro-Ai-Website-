<?php
/**
 * ScanPro AI - JSON API Response Helpers
 */

require_once __DIR__ . '/security.php';

/**
 * Determines whether the client expects a JSON response.
 */
function expectsJsonResponse(): bool
{
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    $requestedWith = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));

    return str_contains($accept, 'application/json')
        || str_contains($contentType, 'application/json')
        || $requestedWith === 'xmlhttprequest';
}

/**
 * Emits a JSON payload with security headers and terminates execution.
 *
 * @param array<string, mixed> $payload
 */
function sendJsonResponse(array $payload, int $statusCode = 200): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    applySecurityHeaders();

    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Emits a standardized success JSON response.
 *
 * @param array<string, mixed> $extra
 */
function jsonSuccess(string $message, array $extra = [], int $statusCode = 200): void
{
    sendJsonResponse(
        array_merge(
            [
                'success' => true,
                'message' => $message,
            ],
            $extra
        ),
        $statusCode
    );
}

/**
 * Emits a standardized error JSON response.
 *
 * @param array<string, string> $errors
 * @param array<string, mixed> $extra
 */
function jsonError(
    string $message,
    int $statusCode = 400,
    array $errors = [],
    array $extra = []
): void {
    $payload = [
        'success' => false,
        'message' => $message,
    ];

    if (!empty($errors)) {
        $payload['errors'] = $errors;
    }

    sendJsonResponse(array_merge($payload, $extra), $statusCode);
}