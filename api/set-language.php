<?php
/**
 * BuildConnect Set Language API Endpoint
 * Accepts 'lang' parameter via POST, GET, or JSON body,
 * validates against allowlist ['en', 'hi', 'gu'], updates session and cookie,
 * optionally updates users table, and returns structured JSON response.
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

// Support JSON payload
$raw = file_get_contents('php://input');
$json = !empty($raw) ? json_decode($raw, true) : null;

$lang = $_POST['lang'] ?? $_GET['lang'] ?? ($json['lang'] ?? null);
$lang = is_string($lang) ? strtolower(trim($lang)) : '';

$allowed = ['en', 'hi', 'gu'];
if (!in_array($lang, $allowed, true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid language code. Supported: ' . implode(', ', $allowed)
    ]);
    exit();
}

$_SESSION['bc_lang'] = $lang;
$_SESSION['lang'] = $lang;
$cookie_path = '/';
$is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
@setcookie('bc_lang', $lang, time() + (86400 * 30), $cookie_path, '', $is_https, false);
@setcookie('lang', $lang, time() + (86400 * 30), $cookie_path, '', $is_https, false);

// Optional: Persist user preference
if (isset($_SESSION['user_id'])) {
    try {
        $db = getDB();
        $chk = $db->query("SHOW COLUMNS FROM users LIKE 'preferred_lang'")->fetch();
        if ($chk) {
            $up = $db->prepare("UPDATE users SET preferred_lang = ? WHERE id = ?");
            $up->execute([$lang, $_SESSION['user_id']]);
        }
    } catch (Exception $e) {
        // fail gracefully
    }
}

echo json_encode([
    'success' => true,
    'lang' => $lang,
    'info' => current_lang_info()
]);
exit();
