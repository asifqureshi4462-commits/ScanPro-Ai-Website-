<?php
require_once __DIR__ . '/backend/helpers/security.php';

startSecureSession();
applySecurityHeaders();

$sourceLogoPath = __DIR__ . '/frontend/assets/images/scanpro-logo.png';
$imagesDir = __DIR__ . '/frontend/assets/images';

$targets = [
    ['rel' => 'frontend/assets/images/favicon-96x96.png',     'path' => $imagesDir . '/favicon-96x96.png',     'size' => 96],
    ['rel' => 'frontend/assets/images/favicon-144x144.png',   'path' => $imagesDir . '/favicon-144x144.png',   'size' => 144],
    ['rel' => 'frontend/assets/images/apple-touch-icon.png',  'path' => $imagesDir . '/apple-touch-icon.png',  'size' => 180],
];
$icoTargetRel = 'favicon.ico';
$icoTargetPath = __DIR__ . '/favicon.ico';

$gdAvailable = extension_loaded('gd')
    && function_exists('imagecreatefrompng')
    && function_exists('imagecreatetruecolor')
    && function_exists('imagecopyresampled')
    && function_exists('imagepng');

$sourceExists = is_file($sourceLogoPath) && is_readable($sourceLogoPath);

$results = [];
$errorMessage = null;
$selfDeleted = false;

/**
 * Renders a square transparent-preserving PNG binary string from source GD image.
 */
function buildResizedPngBytes(GdImage $srcImg, int $srcW, int $srcH, int $size): ?string
{
    $dst = imagecreatetruecolor($size, $size);
    if (!$dst) {
        return null;
    }

    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $size, $size, $transparent);

    $ok = imagecopyresampled($dst, $srcImg, 0, 0, 0, 0, $size, $size, $srcW, $srcH);
    if (!$ok) {
        imagedestroy($dst);
        return null;
    }

    ob_start();
    $saved = imagepng($dst, null, 9);
    $bytes = ob_get_clean();
    imagedestroy($dst);

    if (!$saved || !is_string($bytes) || $bytes === '') {
        return null;
    }

    return $bytes;
}

/**
 * Builds a standards-compliant multi-resolution ICO binary wrapping PNG frames.
 *
 * @param array<int, array{size: int, data: string}> $frames
 */
function buildIcoBinaryFromPngFrames(array $frames): string
{
    $count = count($frames);
    $header = pack('vvv', 0, 1, $count);
    $offset = 6 + ($count * 16);
    $directory = '';
    $payload = '';

    foreach ($frames as $frame) {
        $dim = $frame['size'] >= 256 ? 0 : $frame['size'];
        $data = $frame['data'];
        $len = strlen($data);

        $directory .= pack('CCCCvvVV', $dim, $dim, 0, 0, 1, 32, $len, $offset);
        $payload .= $data;
        $offset += $len;
    }

    return $header . $directory . $payload;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $submittedCsrf = extractRequestCsrfToken();

    if (!verifyCsrfToken($submittedCsrf)) {
        $errorMessage = 'Invalid security token. Please refresh and try again.';
    } elseif (!$gdAvailable) {
        $errorMessage = 'PHP GD extension is not enabled on this server.';
    } elseif (!$sourceExists) {
        $errorMessage = 'Source logo frontend/assets/images/scanpro-logo.png was not found.';
    } else {
        $rawLogo = @file_get_contents($sourceLogoPath);
        $srcImg = is_string($rawLogo) ? @imagecreatefromstring($rawLogo) : false;

        if (!$srcImg) {
            $errorMessage = 'Unable to decode source PNG logo image.';
        } else {
            $srcW = imagesx($srcImg);
            $srcH = imagesy($srcImg);

            foreach ($targets as $target) {
                $pngBytes = buildResizedPngBytes($srcImg, $srcW, $srcH, $target['size']);
                if ($pngBytes === null || @file_put_contents($target['path'], $pngBytes) === false) {
                    $results[] = ['file' => $target['rel'], 'ok' => false, 'detail' => 'Write failed'];
                    continue;
                }
                $info = @getimagesize($target['path']);
                $valid = is_array($info)
                    && (int) ($info[0] ?? 0) === $target['size']
                    && (int) ($info[1] ?? 0) === $target['size'];
                $results[] = [
                    'file'   => $target['rel'],
                    'ok'     => $valid,
                    'detail' => $valid ? ($target['size'] . 'x' . $target['size'] . ' PNG (' . strlen($pngBytes) . ' bytes)') : 'Dimension check failed',
                ];
            }

            // Build root favicon.ico containing 48x48, 32x32, and 16x16 frames
            $icoFrames = [];
            foreach ([48, 32, 16] as $icoSize) {
                $frameData = buildResizedPngBytes($srcImg, $srcW, $srcH, $icoSize);
                if ($frameData !== null) {
                    $icoFrames[] = ['size' => $icoSize, 'data' => $frameData];
                }
            }

            if (count($icoFrames) === 3) {
                $icoBinary = buildIcoBinaryFromPngFrames($icoFrames);
                if (@file_put_contents($icoTargetPath, $icoBinary) !== false) {
                    $icoInfo = @getimagesize($icoTargetPath);
                    $icoValid = is_array($icoInfo) && ((int) ($icoInfo[0] ?? 0) > 0);
                    $results[] = [
                        'file'   => $icoTargetRel,
                        'ok'     => $icoValid,
                        'detail' => $icoValid ? ('Multi-size ICO 48/32/16 (' . strlen($icoBinary) . ' bytes)') : 'ICO verification failed',
                    ];
                } else {
                    $results[] = ['file' => $icoTargetRel, 'ok' => false, 'detail' => 'Write failed'];
                }
            } else {
                $results[] = ['file' => $icoTargetRel, 'ok' => false, 'detail' => 'Frame encoding failed'];
            }

            imagedestroy($srcImg);

            $allOk = !empty($results) && !in_array(false, array_column($results, 'ok'), true);
            if ($allOk) {
                $selfDeleted = @unlink(__FILE__);
            } else {
                $errorMessage = 'One or more favicon files failed validation. Check folder write permissions.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>ScanPro AI Favicon Generator</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0F172A; color: #F8FAFC; padding: 24px; max-width: 640px; margin: 0 auto; }
        .box { background: #1E293B; border: 1px solid rgba(34, 211, 238, 0.35); border-radius: 14px; padding: 22px; }
        h1 { font-size: 1.25rem; margin-top: 0; }
        .ok { color: #10B981; }
        .err { color: #EF4444; }
        .btn { background: #22D3EE; color: #061B32; border: none; padding: 12px 20px; border-radius: 10px; font-weight: 700; cursor: pointer; }
        ul { padding-left: 18px; }
        li { margin-bottom: 6px; }
    </style>
</head>
<body>
<div class="box">
    <h1>ScanPro AI – One-Time Favicon Generator</h1>
    <p>Source: <code>frontend/assets/images/scanpro-logo.png</code> (<?= $sourceExists ? '<span class="ok">Found</span>' : '<span class="err">Missing</span>' ?>)</p>
    <p>PHP GD Extension: <?= $gdAvailable ? '<span class="ok">Supported</span>' : '<span class="err">Not Available</span>' ?></p>

    <?php if ($errorMessage !== null): ?>
        <p class="err"><strong><?= e($errorMessage) ?></strong></p>
    <?php endif; ?>

    <?php if (!empty($results)): ?>
        <ul>
            <?php foreach ($results as $row): ?>
                <li class="<?= $row['ok'] ? 'ok' : 'err' ?>">
                    <code><?= e($row['file']) ?></code> — <?= e($row['detail']) ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($selfDeleted): ?>
            <p class="ok"><strong>Success:</strong> All favicons were generated and <code>generate-favicons.php</code> has automatically deleted itself.</p>
        <?php elseif ($errorMessage === null): ?>
            <p class="err"><strong>Action Required:</strong> Automatic deletion failed. Please delete <code>generate-favicons.php</code> manually right now.</p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!$selfDeleted): ?>
        <form method="POST">
            <?= csrfHiddenInput() ?>
            <button type="submit" class="btn">Generate Favicons &amp; Auto-Delete Script</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>