<?php
$tool = [
    'title' => 'Compare PDF',
    'desc' => 'Compare the text of two PDF versions and get a report of what was added or removed.',
    'icon' => 'fa-code-compare',
    'runLabel' => 'Compare Files',
    'runIcon' => 'fa-code-compare',
    'slug' => 'compare-pdf',
    'accept' => 'application/pdf,.pdf',
    'dropTitle' => 'Drop your PDF here or tap to browse',
    'dropHint' => 'Pick a PDF document from your phone or computer storage.',
];
require __DIR__ . '/../includes/simple-tool-page.php';
