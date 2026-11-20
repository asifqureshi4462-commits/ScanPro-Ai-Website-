<?php
$tool = [
    'title' => 'Redact PDF',
    'desc' => 'Black out sensitive text or images and permanently remove them from the PDF.',
    'icon' => 'fa-eraser',
    'runLabel' => 'Apply Redaction',
    'runIcon' => 'fa-eraser',
    'slug' => 'redact-pdf',
    'accept' => 'application/pdf,.pdf',
    'dropTitle' => 'Drop your PDF here or tap to browse',
    'dropHint' => 'Pick a PDF document from your phone or computer storage.',
];
require __DIR__ . '/../includes/simple-tool-page.php';
