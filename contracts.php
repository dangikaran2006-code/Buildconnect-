<?php
/**
 * BuildConnect Centralized Contracts Router
 * Redirects authenticated users to their corresponding role contract interface.
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$user = get_logged_user();
$role = strtolower(trim($user['role'] ?? ''));

switch ($role) {
    case 'admin':
        redirect('/admin/index.php');
        break;
    case 'contractor':
        redirect('/contractor/contracts.php');
        break;
    case 'worker':
        redirect('/worker/contracts.php');
        break;
    case 'client':
        redirect('/client/contracts.php');
        break;
    default:
        redirect('/login.php');
        break;
}
