<?php
$tool = [
    'title' => 'PDF to Text',
    'desc' => 'Extract the selectable text from a PDF into a .txt file.',
    'icon' => 'fa-file-lines',
    'runLabel' => 'Extract Text',
    'runIcon' => 'fa-file-lines',
    'slug' => 'pdf-to-text',
    'accept' => 'application/pdf,.pdf',
    'dropTitle' => 'Drop your PDF here or tap to browse',
    'dropHint' => 'Pick a PDF document from your phone or computer storage.',
];
require __DIR__ . '/../includes/simple-tool-page.php';
