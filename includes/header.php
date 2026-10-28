<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$page_title = $page_title ?? 'BuildConnect - Construction Workforce & Project Platform';
$user = get_logged_user();
$script_path = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');

$is_admin_scope = (strpos($script_path, '/admin/') !== false) || ($user && $user['role'] === ROLE_ADMIN && strpos($script_path, '/notifications.php') !== false);
$is_worker_scope = (strpos($script_path, '/worker/') !== false) || ($user && $user['role'] === ROLE_WORKER && strpos($script_path, '/notifications.php') !== false);
$is_client_scope = (strpos($script_path, '/client/') !== false) || ($user && $user['role'] === ROLE_CLIENT && strpos($script_path, '/notifications.php') !== false);
$is_contractor_scope = (strpos($script_path, '/contractor/') !== false) || ($user && $user['role'] === ROLE_CONTRACTOR && strpos($script_path, '/notifications.php') !== false);

$body_app_class = 'bc-body-container';
if ($is_admin_scope) {
    $body_app_class = 'dashboard-app admin-app';
} elseif ($is_worker_scope) {
    $body_app_class = 'dashboard-app worker-app';
} elseif ($is_client_scope) {
    $body_app_class = 'dashboard-app client-app';
} elseif ($is_contractor_scope) {
    $body_app_class = 'dashboard-app contractor-app';
}

$is_auth_scope = (strpos($script_path, 'login.php') !== false || strpos($script_path, 'register.php') !== false);
if ($is_auth_scope) {
    $body_app_class .= ' bc-auth-page-dark';
}
?>
<!DOCTYPE html>
<html lang="<?= current_lang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($page_title) ?></title>
    <meta name="description" content="BuildConnect connects Construction Workers, Contractors, Clients, and Administrators in one unified platform for hiring, project tracking, QR attendance, digital contracts, and AI workforce matching.">
    
    <!-- Google Fonts: Plus Jakarta Sans, Inter, Outfit, Noto Sans Devanagari, Noto Sans Gujarati -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Noto+Sans+Devanagari:wght@400;500;600;700&family=Noto+Sans+Gujarati:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Leaflet CSS (OpenStreetMap) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Default Style CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= time() ?>">

    <!-- Scoped Dashboard CSS System for Admin, Worker, Client, and Contractor -->
    <?php if ($is_admin_scope || $is_worker_scope || $is_client_scope || $is_contractor_scope): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bc-dashboard-core.css?v=<?= time() ?>">
        <?php if ($is_admin_scope): ?>
            <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bc-admin.css?v=<?= time() ?>">
        <?php elseif ($is_worker_scope): ?>
            <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bc-worker.css?v=<?= time() ?>">
        <?php elseif ($is_client_scope): ?>
            <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bc-client.css?v=<?= time() ?>">
        <?php elseif ($is_contractor_scope): ?>
            <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bc-contractor.css?v=<?= time() ?>">
        <?php endif; ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bc-responsive.css?v=<?= time() ?>">
    <?php endif; ?>

    <script>
        window.BASE_URL = '<?= BASE_URL ?>';
        window.BC_LANG = '<?= current_lang() ?>';
        window.BC_TRANSLATIONS = <?= json_encode(get_all_translations(), JSON_UNESCAPED_UNICODE) ?>;
        window.__ = function(key, replacements, fallback) {
            var text = (window.BC_TRANSLATIONS && window.BC_TRANSLATIONS[key]) ? window.BC_TRANSLATIONS[key] : (fallback || key);
            if (replacements && typeof replacements === 'object') {
                for (var placeholder in replacements) {
                    text = text.replace(new RegExp(':' + placeholder, 'g'), replacements[placeholder])
                               .replace(new RegExp('\\{' + placeholder + '\\}', 'g'), replacements[placeholder]);
                }
            }
            return text;
        };
        window.trans = window.__;
    </script>
</head>
<body class="<?= $body_app_class ?>">

<!-- Toast Notification Container -->
<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100;"></div>
