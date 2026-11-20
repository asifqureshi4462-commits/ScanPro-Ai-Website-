<?php
require_once __DIR__ . '/../../backend/helpers/security.php';

$queuedAlerts = consumeFlashMessages();
if (!empty($inlineAlerts) && is_array($inlineAlerts)) {
    foreach ($inlineAlerts as $item) {
        if (is_array($item) && isset($item['type'], $item['message'])) {
            $queuedAlerts[] = [
                'type'    => (string) $item['type'],
                'message' => (string) $item['message'],
            ];
        }
    }
}

$alertIconMap = [
    'success' => 'fa-circle-check',
    'error'   => 'fa-circle-exclamation',
    'warning' => 'fa-triangle-exclamation',
    'info'    => 'fa-circle-info',
];
?>
<div id="apiAlertContainer" aria-live="polite">
    <?php foreach ($queuedAlerts as $alert): ?>
        <?php
        $type = in_array($alert['type'], ['success', 'error', 'warning', 'info'], true)
            ? $alert['type']
            : 'info';
        $icon = $alertIconMap[$type];
        ?>
        <div class="alert alert--<?= e($type) ?>" role="alert">
            <i class="fa-solid <?= e($icon) ?>" aria-hidden="true" style="margin-top:2px;"></i>
            <div class="alert-body"><?= e($alert['message']) ?></div>
            <button type="button" class="alert-close" aria-label="Dismiss alert">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
    <?php endforeach; ?>
</div>