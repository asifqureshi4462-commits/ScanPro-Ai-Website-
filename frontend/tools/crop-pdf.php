<?php
$tool = [
    'title' => 'Crop PDF',
    'desc' => 'Trim margins from all or selected pages of a PDF.',
    'icon' => 'fa-crop-simple',
    'runLabel' => 'Crop PDF',
    'runIcon' => 'fa-crop-simple',
    'slug' => 'crop-pdf',
    'accept' => 'application/pdf,.pdf',
    'dropTitle' => 'Drop your PDF here or tap to browse',
    'dropHint' => 'Pick a PDF document from your phone or computer storage.',
];
require __DIR__ . '/../includes/simple-tool-page.php';
