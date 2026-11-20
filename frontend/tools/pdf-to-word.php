<?php
$tool = [
    'title' => 'PDF to Word',
    'desc' => 'Convert a text PDF into an editable Word (.docx) file with headings and paragraphs.',
    'icon' => 'fa-file-word',
    'runLabel' => 'Convert to Word',
    'runIcon' => 'fa-file-word',
    'slug' => 'pdf-to-word',
    'accept' => 'application/pdf,.pdf',
    'dropTitle' => 'Drop your PDF here or tap to browse',
    'dropHint' => 'Pick a PDF document from your phone or computer storage.',
];
require __DIR__ . '/../includes/simple-tool-page.php';
