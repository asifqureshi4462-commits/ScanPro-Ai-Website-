<?php
declare(strict_types=1);

/**
 * ScanPro AI - Firebase settings.
 * Only PUBLIC values live here. Never put a service account JSON or any private key in this project.
 */

// Set to false to hide Google sign-in everywhere and make the API refuse Google logins.
define('FIREBASE_LOGIN_ENABLED', true);

// Same Firebase project as the ScanPro AI Android app.
define('FIREBASE_PROJECT_ID', 'scanpro-ai-117cb');

// Google's public signing certificates for Firebase ID tokens.
define('FIREBASE_CERTS_URL', 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');
define('FIREBASE_CERTS_CACHE_FILE', dirname(__DIR__) . '/storage/google_certs.json');

define('FIREBASE_CLOCK_SKEW', 120);      // seconds of clock difference tolerated
define('FIREBASE_MAX_AUTH_AGE', 600);    // the Google sign-in must have happened in the last 10 minutes
define('FIREBASE_MAX_TOKEN_LENGTH', 4096);
