<?php
$tool = [
    'title' => 'Delete Pages',
    'desc' => 'Remove unwanted pages from a PDF and download the rest.',
    'icon' => 'fa-trash-can',
    'runLabel' => 'Delete Pages',
    'runIcon' => 'fa-trash-can',
    'slug' => 'remove-pages',
    'accept' => 'application/pdf,.pdf',
    'dropTitle' => 'Drop your PDF here or tap to browse',
    'dropHint' => 'Pick a PDF document from your phone or computer storage.',
];
require __DIR__ . '/../includes/simple-tool-page.php';
