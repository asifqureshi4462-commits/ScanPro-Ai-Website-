<?php
/**
 * ScanPro AI - Input Extraction & Validation Helpers
 */

require_once __DIR__ . '/../config/app.php';

/**
 * Reads request payload from JSON body when Content-Type is application/json,
 * or falls back to $_POST.
 *
 * @return array<string, mixed>
 */
function getRequestPayload(): array
{
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

    if (str_contains($contentType, 'application/json')) {
        $rawBody = file_get_contents('php://input');
        if (is_string($rawBody) && trim($rawBody) !== '') {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    return $_POST;
}

/**
 * Trims whitespace and strips null bytes from user text.
 */
function cleanText(mixed $value): string
{
    if (!is_string($value) && !is_numeric($value)) {
        return '';
    }
    $str = str_replace("\0", '', (string) $value);
    return trim($str);
}

/**
 * Normalizes an email address to trimmed lowercase form.
 */
function normalizeEmail(mixed $value): string
{
    return strtolower(cleanText($value));
}

/**
 * Checks whether an email address is syntactically valid and within length bounds.
 */
function isValidEmailAddress(string $email): bool
{
    if ($email === '' || mb_strlen($email, 'UTF-8') > 190) {
        return false;
    }
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Checks multibyte string length bounds.
 */
function isLengthBetween(string $value, int $min, int $max): bool
{
    $len = mb_strlen($value, 'UTF-8');
    return $len >= $min && $len <= $max;
}

/**
 * Validates password length rules and returns an error string or null if valid.
 */
function validatePasswordPolicy(string $password): ?string
{
    $len = mb_strlen($password, 'UTF-8');
    if ($len < PASSWORD_MIN_LENGTH) {
        return 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    }
    if ($len > 128) {
        return 'Password must not exceed 128 characters.';
    }
    return null;
}