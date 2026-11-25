<?php
declare(strict_types=1);

/**
 * ScholarLocal Configuration
 * PUP Institute of Technology Research Repository
 */

define('APP_NAME', 'ScholarLocal');
define('APP_SUBTITLE', 'PUP Institute of Technology');
define('APP_VERSION', '1.0.0');

// Base URL path (leave empty '' if deployed at domain root, or '/scholarlocal' if in a subfolder)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = '';
if (strpos($scriptName, '/scholarlocal/') !== false) {
    $basePath = '/scholarlocal';
}
define('BASE_URL', getenv('SCHOLARLOCAL_BASE_URL') ?: $basePath);

// Database configuration: 'mysql' (recommended for cPanel/Hostinger/XAMPP) or 'sqlite' (zero-config)
define('DB_DRIVER', getenv('SCHOLARLOCAL_DB_DRIVER') ?: 'sqlite');

// MySQL / MariaDB credentials
define('DB_HOST', getenv('SCHOLARLOCAL_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('SCHOLARLOCAL_DB_PORT') ?: '3306');
define('DB_NAME', getenv('SCHOLARLOCAL_DB_NAME') ?: 'scholarlocal_db');
define('DB_USER', getenv('SCHOLARLOCAL_DB_USER') ?: 'root');
define('DB_PASS', getenv('SCHOLARLOCAL_DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// SQLite path (used when DB_DRIVER === 'sqlite')
define('SQLITE_PATH', dirname(__DIR__) . '/database/scholarlocal.sqlite');

// Storage paths
define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/studies');
define('MAX_PDF_SIZE_BYTES', 20 * 1024 * 1024); // 20 MB per proposal spec

// Allowed institutional email domains for student/faculty registration
define('ALLOWED_EMAIL_DOMAINS', ['@iskolarngbayan.pup.edu.ph', '@pup.edu.ph']);

// PUP ITECH Diploma Programs
define('ITECH_PROGRAMS', [
    'DIT'   => 'Diploma in Information Technology (DIT)',
    'DCvET' => 'Diploma in Civil Engineering Technology (DCvET)',
    'DCET'  => 'Diploma in Computer Engineering Technology (DCET)',
    'DEET'  => 'Diploma in Electrical Engineering Technology (DEET)',
    'DECET' => 'Diploma in Electronics Engineering Technology (DECET)',
    'DMET'  => 'Diploma in Mechanical Engineering Technology (DMET)',
    'DOMT'  => 'Diploma in Office Management Technology (DOMT)',
    'DRET'  => 'Diploma in Railway Engineering Technology (DRET)',
]);

date_default_timezone_set('Asia/Manila');
