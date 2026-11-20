<?php
require_once __DIR__ . '/../../backend/config/app.php';
require_once __DIR__ . '/../../backend/config/session.php';
require_once __DIR__ . '/../../backend/helpers/security.php';

startSecureSession();
applySecurityHeaders();

$prefix = isset($routePrefix) && is_string($routePrefix) ? $routePrefix : '';

$resolvedTitle = isset($pageTitle) && is_string($pageTitle) && $pageTitle !== ''
    ? $pageTitle . ' | ' . APP_NAME
    : APP_NAME . ' – AI Document Scanner & PDF Suite';

$resolvedDescription = isset($pageDescription) && is_string($pageDescription) && $pageDescription !== ''
    ? $pageDescription
    : APP_TAGLINE;

$csrfToken = getCsrfToken();

// Build stable same-hostname favicon URLs (works both on scanproai.great-site.net and local AWebServer)
$rawHost = preg_replace('/[^a-zA-Z0-9.\-:\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''));
$isProxyHttps = (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
    || (!empty($_SERVER['HTTP_CF_VISITOR']) && str_contains((string) $_SERVER['HTTP_CF_VISITOR'], 'https'))
    || strcasecmp($rawHost, 'scanproai.great-site.net') === 0;
$scheme = (isHttpsRequest() || $isProxyHttps) ? 'https' : 'http';
$projectBase = getProjectBasePath();

if ($rawHost !== '') {
    $siteRootUrl = $scheme . '://' . $rawHost . $projectBase;
    $favicon96Url = $siteRootUrl . '/frontend/assets/images/favicon-96x96.png';
    $favicon144Url = $siteRootUrl . '/frontend/assets/images/favicon-144x144.png';
    $appleTouchUrl = $siteRootUrl . '/frontend/assets/images/apple-touch-icon.png';
    $faviconIcoUrl = $siteRootUrl . '/favicon.ico';
    $faviconSvgUrl = $siteRootUrl . '/frontend/assets/icons/favicon.svg';
} else {
    $favicon96Url = $prefix . 'assets/images/favicon-96x96.png';
    $favicon144Url = $prefix . 'assets/images/favicon-144x144.png';
    $appleTouchUrl = $prefix . 'assets/images/apple-touch-icon.png';
    $faviconIcoUrl = $prefix . '../favicon.ico';
    $faviconSvgUrl = $prefix . 'assets/icons/favicon.svg';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="<?= e($resolvedDescription) ?>">
    <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <meta name="theme-color" content="#0F172A">
    <title><?= e($resolvedTitle) ?></title>

    <link rel="icon" type="image/png" sizes="96x96" href="<?= e($favicon96Url) ?>">
    <link rel="icon" type="image/png" sizes="144x144" href="<?= e($favicon144Url) ?>">
    <link rel="shortcut icon" href="<?= e($faviconIcoUrl) ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e($appleTouchUrl) ?>">
    <link rel="icon" type="image/svg+xml" href="<?= e($faviconSvgUrl) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">

    <link rel="stylesheet" href="<?= e($prefix) ?>assets/css/variables.css?v=2.4">
    <link rel="stylesheet" href="<?= e($prefix) ?>assets/css/style.css?v=2.4">
    <link rel="stylesheet" href="<?= e($prefix) ?>assets/css/components.css?v=2.4">
    <link rel="stylesheet" href="<?= e($prefix) ?>assets/css/responsive.css?v=2.4">
    <link rel="stylesheet" href="<?= e($prefix) ?>assets/css/enhancements.css?v=2.4">
    <link rel="stylesheet" href="<?= e($prefix) ?>assets/css/live-3d.css?v=2.4">
    <link rel="stylesheet" href="<?= e($prefix) ?>assets/css/site-3d.css?v=3.0">
    <?php
    if (!empty($extraStyles) && is_array($extraStyles)) {
        foreach ($extraStyles as $stylePath) {
            if (is_string($stylePath) && $stylePath !== '') {
                $href = preg_match('#^https?://#i', $stylePath) ? $stylePath : ($prefix . $stylePath . '?v=2.4');
                echo '<link rel="stylesheet" href="' . e($href) . '">' . "\n";
            }
        }
    }
    ?>
</head>
<body>