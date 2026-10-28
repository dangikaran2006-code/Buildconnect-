<?php
/**
 * BuildConnect Application Constants
 */

// Application Metainfo
define('APP_NAME', 'BuildConnect');
define('APP_VERSION', '1.0.0');
define('APP_TAGLINE', 'Connect People. Build Projects. Grow Together.');

// Base URL Auto-Detection
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Determine root directory relative to DOCUMENT_ROOT or SCRIPT_NAME (supports symlinks/junctions)
$app_root = str_replace('\\', '/', realpath(dirname(__DIR__)));
$doc_root = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';

$app_root_lower = strtolower($app_root);
$doc_root_lower = strtolower($doc_root);

$web_root = '';
if (!empty($doc_root_lower) && !empty($app_root_lower) && strpos($app_root_lower, $doc_root_lower) === 0) {
    $web_root = substr($app_root, strlen($doc_root));
} elseif (isset($_SERVER['SCRIPT_NAME']) && isset($_SERVER['SCRIPT_FILENAME'])) {
    $script_filename_lower = strtolower(str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']) ?: $_SERVER['SCRIPT_FILENAME']));
    if ($app_root_lower !== '' && strpos($script_filename_lower, $app_root_lower) === 0) {
        $sub_path_lower = substr($script_filename_lower, strlen($app_root_lower));
        $script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $script_name_lower = strtolower($script_name);
        if ($sub_path_lower !== '' && substr($script_name_lower, -strlen($sub_path_lower)) === $sub_path_lower) {
            $web_root = substr($script_name, 0, strlen($script_name) - strlen($sub_path_lower));
        }
    }
}

$base_url = rtrim($protocol . '://' . $host . '/' . ltrim($web_root, '/'), '/');

define('BASE_URL', $base_url);

// Path Constants
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');

// Database Credentials (MySQL via PDO)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'buildconnect');
define('DB_DRIVER', 'mysql');

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_CONTRACTOR', 'contractor');
define('ROLE_WORKER', 'worker');
define('ROLE_CLIENT', 'client');

// Timezone Configuration
date_default_timezone_set('Asia/Kolkata');

// QR Code Attendance Settings
define('QR_EXPIRATION_MINUTES', 5);

// Default Map Position (Ahmedabad, Gujarat, India - Fallback Default)
define('DEFAULT_LATITUDE', 23.0225);
define('DEFAULT_LONGITUDE', 72.5714);
define('DEFAULT_ZOOM', 12);

// Google Maps API Key Configuration (Environment override or fallback)
define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: '');

// AI Engine Configuration Constants
define('AI_ENABLED', getenv('AI_ENABLED') !== 'false');
define('AI_API_KEY', getenv('AI_API_KEY') ?: '');
define('AI_API_URL', getenv('AI_API_URL') ?: 'https://api.openai.com/v1/chat/completions');
define('AI_MODEL', getenv('AI_MODEL') ?: 'gpt-4o-mini');
define('AI_TIMEOUT_SECONDS', 10);
define('AI_CACHE_TTL_HOURS', 24);

// Google OAuth 2.0 Configuration Constants
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '');
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: (rtrim(BASE_URL, '/') . '/auth/google-callback.php'));



