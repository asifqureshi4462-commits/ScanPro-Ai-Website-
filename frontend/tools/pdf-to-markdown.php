<?php
$tool = [
    'title' => 'PDF to Markdown',
    'desc' => 'Turn a PDF into clean Markdown with headings and lists for notes, docs and AI tools.',
    'icon' => 'fa-hashtag',
    'runLabel' => 'Convert to Markdown',
    'runIcon' => 'fa-hashtag',
    'slug' => 'pdf-to-markdown',
    'accept' => 'application/pdf,.pdf',
    'dropTitle' => 'Drop your PDF here or tap to browse',
    'dropHint' => 'Pick a PDF document from your phone or computer storage.',
];
require __DIR__ . '/../includes/simple-tool-page.php';
