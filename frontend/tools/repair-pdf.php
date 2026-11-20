<?php
$tool = [
    'title' => 'Repair PDF',
    'desc' => 'Rebuild a damaged or broken PDF into a clean file you can open again.',
    'icon' => 'fa-screwdriver-wrench',
    'runLabel' => 'Repair PDF',
    'runIcon' => 'fa-screwdriver-wrench',
    'slug' => 'repair-pdf',
    'accept' => 'application/pdf,.pdf',
    'dropTitle' => 'Drop your PDF here or tap to browse',
    'dropHint' => 'Pick a PDF document from your phone or computer storage.',
];
require __DIR__ . '/../includes/simple-tool-page.php';
