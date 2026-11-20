<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/security.php';
require_once __DIR__ . '/../helpers/validator.php';
require_once __DIR__ . '/../helpers/response.php';

startSecureSession();
applySecurityHeaders();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    if (expectsJsonResponse()) {
        jsonError('Method not allowed.', 405);
    }
    header('Location: ../../frontend/contact.php', true, 302);
    exit;
}

$payload = getRequestPayload();
$submittedCsrf = extractRequestCsrfToken() ?? (isset($payload['csrf_token']) ? (string) $payload['csrf_token'] : null);

if (!verifyCsrfToken($submittedCsrf)) {
    if (expectsJsonResponse()) {
        jsonError('Security token expired or invalid. Please refresh the page and try again.', 403);
    }
    setFlashMessage('error', 'Security token expired or invalid.');
    header('Location: ../../frontend/contact.php', true, 302);
    exit;
}

$name = cleanText($payload['name'] ?? '');
$email = normalizeEmail($payload['email'] ?? '');
$subject = cleanText($payload['subject'] ?? '');
$message = cleanText($payload['message'] ?? '');

$errors = [];
if (!isLengthBetween($name, 2, 100)) {
    $errors['name'] = 'Name must be between 2 and 100 characters.';
}
if (!isValidEmailAddress($email)) {
    $errors['email'] = 'Please enter a valid email address.';
}
if (!isLengthBetween($subject, 3, 150)) {
    $errors['subject'] = 'Subject must be between 3 and 150 characters.';
}
if (!isLengthBetween($message, 10, 3000)) {
    $errors['message'] = 'Message must be between 10 and 3000 characters.';
}

if (!empty($errors)) {
    if (expectsJsonResponse()) {
        jsonError('Please review the highlighted form fields.', 422, $errors);
    }
    setFlashMessage('error', 'Please complete all required contact fields properly.');
    header('Location: ../../frontend/contact.php', true, 302);
    exit;
}

try {
    $pdo = getDbConnection();
    $userId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $ipAddress = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

    $stmt = $pdo->prepare(
        'INSERT INTO contact_messages (user_id, name, email, subject, message, status, ip_address, user_agent, created_at)
         VALUES (:user_id, :name, :email, :subject, :message, :status, :ip_address, :user_agent, NOW())'
    );
    $stmt->execute([
        'user_id'    => $userId,
        'name'       => $name,
        'email'      => $email,
        'subject'    => $subject,
        'message'    => $message,
        'status'     => 'new',
        'ip_address' => $ipAddress !== '' ? $ipAddress : null,
        'user_agent' => $userAgent !== '' ? $userAgent : null,
    ]);

    // Email the message to the site owner (failure here must not break the form)
    try {
        $host = preg_replace('/^www\./i', '', preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost')));
        $oneLine = static fn (string $v): string => trim(preg_replace('/[\r\n]+/', ' ', $v));
        $mailSubject = '=?UTF-8?B?' . base64_encode('[ScanPro AI Contact] ' . $oneLine($subject)) . '?=';
        $mailBody = "New message from the ScanPro AI contact form\n\n"
            . 'Name: ' . $oneLine($name) . "\n"
            . 'Email: ' . $oneLine($email) . "\n"
            . 'Subject: ' . $oneLine($subject) . "\n"
            . 'IP: ' . $ipAddress . "\n\n"
            . "Message:\n" . $message . "\n";
        $mailHeaders = [
            'From: ScanPro AI <noreply@' . $host . '>',
            'Reply-To: ' . $oneLine($email),
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
        ];
        if (!@mail(CONTACT_RECEIVER_EMAIL, $mailSubject, $mailBody, implode("\r\n", $mailHeaders))) {
            error_log('[ScanPro API Contact] mail() failed; message is saved in the database only.');
        }
    } catch (Throwable $mailError) {
        error_log('[ScanPro API Contact Mail Error] ' . $mailError->getMessage());
    }

    if (expectsJsonResponse()) {
        jsonSuccess('Thank you! Your message has been saved and our team will review it.', [], 201);
    }

    setFlashMessage('success', 'Thank you! Your message has been received.');
    header('Location: ../../frontend/contact.php', true, 302);
    exit;
} catch (Throwable $e) {
    error_log('[ScanPro API Contact Error] ' . $e->getMessage());
    if (expectsJsonResponse()) {
        jsonError('Unable to save your message right now. Please verify your database connection.', 500);
    }
    setFlashMessage('error', 'Unable to save your message right now.');
    header('Location: ../../frontend/contact.php', true, 302);
    exit;
}