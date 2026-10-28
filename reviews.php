<?php
/**
 * BuildConnect Centralized Reviews Router
 * Redirects authenticated users to their corresponding role review interface.
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$user = get_logged_user();
$role = $user['role'] ?? '';

switch ($role) {
    case ROLE_ADMIN:
        redirect('/admin/reviews.php');
        break;
    case ROLE_CONTRACTOR:
        redirect('/contractor/reviews.php');
        break;
    case ROLE_WORKER:
        redirect('/worker/reviews.php');
        break;
    case ROLE_CLIENT:
        redirect('/client/reviews.php');
        break;
    default:
        redirect('/login.php');
        break;
}
