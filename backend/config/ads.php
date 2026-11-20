<?php
declare(strict_types=1);

/**
 * ScanPro AI - Advertising settings (one place to switch ads on or off).
 */

// Master switch. Set to false to remove ALL ads from the whole website.
define('ADS_ENABLED', true);

// Logged-in users never see ads (a small thank-you for creating an account).
define('ADS_HIDE_FOR_LOGGED_IN', true);

// Set to false if you ever want PDF tool pages to be completely ad-free.
define('ADS_ON_TOOL_PAGES', true);

// Pages that may show ONE ad. Login, register, forgot password, dashboard, pricing,
// contact, workspace and every popup are intentionally NOT in this list.
define('ADS_PLACEMENTS', ['home', 'features', 'pdf-tools', 'tool']);

// Inline native/banner zone (script + container). Only this one format is used.
define('ADS_NATIVE_SCRIPT_SRC', 'https://bauval.org/21/bc212c0c99169247b033ff18c20a0ee1');
define('ADS_NATIVE_CONTAINER_ID', 'container-bc212c0c99169247b033ff18c20a0ee1');
