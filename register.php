<?php
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to role area
if (isLoggedIn()) {
    $user = currentUser();
    if ($user) {
        redirect(get_role_redirect_url($user['role']));
    }
}

$flash = get_flash_message();
$error = '';
$allowed_roles = ['contractor', 'worker', 'client'];
$prefilled_email = sanitize($_GET['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = 'Security validation failed (CSRF token invalid). Please try again.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = strtolower(trim($_POST['role'] ?? 'worker'));
        $location = sanitize($_POST['location'] ?? '');
        $company_name = sanitize($_POST['company_name'] ?? '');
        $trade_title = sanitize($_POST['trade_title'] ?? '');
        $experience_years = (int)($_POST['experience_years'] ?? 0);

        // Validation Checks
        if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password) || empty($role)) {
            $error = 'Please fill out all required fields marked with an asterisk (*).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Password confirmation does not match.';
        } elseif (!in_array($role, $allowed_roles) || $role === 'admin') {
            $error = 'Invalid role selection. Administrator accounts cannot be created via self-registration.';
        } else {
            $db = getDB();

            // Prepared statement to verify unique email
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'This email address is already registered. Please sign in instead.';
            } else {
                // Securely hash password using password_hash()
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $ins = $db->prepare("INSERT INTO users (name, email, password, role, phone, status) VALUES (?, ?, ?, ?, ?, 'active')");
                if ($ins->execute([$name, $email, $hashed_password, $role, $phone])) {
                    $user_id = $db->lastInsertId();

                    // Create associated profile record
                    if ($role === 'worker') {
                        $trade = !empty($trade_title) ? $trade_title : 'General Construction Specialist';
                        $city = !empty($location) ? $location : 'Ahmedabad';
                        $db->prepare("INSERT INTO workers (user_id, trade_title, experience_years, city, verification_status) VALUES (?, ?, ?, ?, 'pending')")
                           ->execute([$user_id, $trade, $experience_years, $city]);
                    } elseif ($role === 'contractor') {
                        $comp = !empty($company_name) ? $company_name : ($name . ' Construction');
                        $city = !empty($location) ? $location : 'Ahmedabad';
                        $db->prepare("INSERT INTO contractors (user_id, company_name, city) VALUES (?, ?, ?)")
                           ->execute([$user_id, $comp, $city]);
                    } elseif ($role === 'client') {
                        $comp = !empty($company_name) ? $company_name : ($name . ' Developments');
                        $city = !empty($location) ? $location : 'Ahmedabad';
                        $db->prepare("INSERT INTO clients (user_id, company_name, city) VALUES (?, ?, ?)")
                           ->execute([$user_id, $comp, $city]);
                    }

                    set_flash_message('Registration successful! Please log in with your credentials.', 'success');
                    if (function_exists('log_activity')) {
                        log_activity($user_id, 'User Registered', "New user registered with role '{$role}'", 'user', $user_id);
                    }
                    redirect('login.php');
                } else {
                    $error = 'Registration failed due to a system error. Please try again.';
                }
            }
        }
    }
}

$page_title = "Create Account - BuildConnect";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="bc-auth-wrapper">
    <div class="bc-auth-card bc-register-card">
        <div class="text-center mb-3">
            <div class="bc-auth-icon-wrapper">
                <i class="fa-solid fa-user-plus bc-auth-icon"></i>
            </div>

            <h1 class="bc-auth-title mb-1"><?= __('auth.create_account_brand', ['brand' => '<span class="text-amber">BuildConnect</span>']) ?></h1>
            <p class="bc-auth-subtitle mb-3"><?= __('auth.register_subtitle') ?></p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= sanitize($flash['type']) ?> bc-auth-alert mb-3" role="alert">
                <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check text-success' : 'fa-circle-info text-warning' ?> me-2"></i>
                <?= sanitize($flash['message']) ?>
                <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger bc-auth-alert mb-3" role="alert">
                <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= sanitize($error) ?>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/register.php" method="POST" data-loading="true" id="registerForm">
            <?= csrf_field() ?>

            <!-- Segmented 3-Role Tab Selector (Matching Reference) -->
            <div class="bc-role-tabs mb-3">
                <label class="bc-role-tab <?= (!isset($_POST['role']) || $_POST['role'] === 'worker') ? 'active' : '' ?>" id="tab-worker" onclick="selectRole('worker')">
                    <input type="radio" class="d-none" name="role" id="role-worker" value="worker" <?= (!isset($_POST['role']) || $_POST['role'] === 'worker') ? 'checked' : '' ?>>
                    <i class="fa-solid fa-helmet-safety me-2"></i> <?= __('auth.worker_role') ?>
                </label>
                <label class="bc-role-tab <?= (isset($_POST['role']) && $_POST['role'] === 'contractor') ? 'active' : '' ?>" id="tab-contractor" onclick="selectRole('contractor')">
                    <input type="radio" class="d-none" name="role" id="role-contractor" value="contractor" <?= (isset($_POST['role']) && $_POST['role'] === 'contractor') ? 'checked' : '' ?>>
                    <i class="fa-solid fa-building me-2"></i> <?= __('auth.contractor_role') ?>
                </label>
                <label class="bc-role-tab <?= (isset($_POST['role']) && $_POST['role'] === 'client') ? 'active' : '' ?>" id="tab-client" onclick="selectRole('client')">
                    <input type="radio" class="d-none" name="role" id="role-client" value="client" <?= (isset($_POST['role']) && $_POST['role'] === 'client') ? 'checked' : '' ?>>
                    <i class="fa-solid fa-user me-2"></i> <?= __('auth.client_role') ?>
                </label>
            </div>

            <!-- Full Name -->
            <div class="mb-3">
                <div class="bc-auth-input-group">
                    <span class="bc-auth-input-icon"><i class="fa-solid fa-user"></i></span>
                    <input type="text" class="bc-auth-input" id="name" name="name" required placeholder="<?= e(__('auth.full_name_placeholder')) ?>" value="<?= isset($_POST['name']) ? sanitize($_POST['name']) : '' ?>" autocomplete="name">
                </div>
            </div>

            <!-- Email Address & Phone Number (Side-by-Side) -->
            <div class="row g-2.5 mb-3">
                <div class="col-sm-6">
                    <div class="bc-auth-input-group">
                        <span class="bc-auth-input-icon"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" class="bc-auth-input" id="email" name="email" required placeholder="<?= e(__('auth.email_placeholder')) ?>" value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : ($prefilled_email ? sanitize($prefilled_email) : '') ?>" autocomplete="email">
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="bc-auth-input-group">
                        <span class="bc-auth-input-icon"><i class="fa-solid fa-phone"></i></span>
                        <input type="tel" class="bc-auth-input" id="phone" name="phone" required placeholder="<?= e(__('auth.phone_placeholder')) ?>" value="<?= isset($_POST['phone']) ? sanitize($_POST['phone']) : '' ?>" autocomplete="tel">
                    </div>
                </div>
            </div>

            <!-- Password & Confirm Password (Side-by-Side with Eye Icons) -->
            <div class="row g-2.5 mb-3">
                <div class="col-sm-6">
                    <div class="bc-auth-input-group">
                        <span class="bc-auth-input-icon"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" class="bc-auth-input" id="password" name="password" required placeholder="<?= e(__('auth.password_placeholder')) ?>" autocomplete="new-password">
                        <button class="bc-auth-eye-btn" type="button" onclick="togglePasswordVisibility('password', 'toggleIcon1')" aria-label="Toggle password visibility">
                            <i class="fa-solid fa-eye" id="toggleIcon1"></i>
                        </button>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="bc-auth-input-group">
                        <span class="bc-auth-input-icon"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" class="bc-auth-input" id="confirm_password" name="confirm_password" required placeholder="<?= e(__('auth.confirm_password_placeholder')) ?>" autocomplete="new-password">
                        <button class="bc-auth-eye-btn" type="button" onclick="togglePasswordVisibility('confirm_password', 'toggleIcon2')" aria-label="Toggle confirm password visibility">
                            <i class="fa-solid fa-eye" id="toggleIcon2"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Location -->
            <div class="mb-3">
                <div class="bc-auth-input-group">
                    <span class="bc-auth-input-icon"><i class="fa-solid fa-location-dot"></i></span>
                    <input type="text" class="bc-auth-input" id="location" name="location" placeholder="<?= e(__('auth.location_placeholder')) ?>" value="<?= isset($_POST['location']) ? sanitize($_POST['location']) : '' ?>">
                </div>
            </div>

            <!-- Worker Dynamic Fields: Skill / Trade -->
            <div id="worker-trade-group" class="mb-3 <?= (!isset($_POST['role']) || $_POST['role'] === 'worker') ? '' : 'd-none' ?>">
                <div class="bc-auth-input-group">
                    <span class="bc-auth-input-icon"><i class="fa-solid fa-briefcase"></i></span>
                    <input type="text" class="bc-auth-input" id="trade_title" name="trade_title" placeholder="<?= e(__('auth.trade_placeholder')) ?>" value="<?= isset($_POST['trade_title']) ? sanitize($_POST['trade_title']) : '' ?>">
                </div>
            </div>

            <!-- Worker Dynamic Fields: Experience (Years) -->
            <div id="worker-exp-group" class="mb-3 <?= (!isset($_POST['role']) || $_POST['role'] === 'worker') ? '' : 'd-none' ?>">
                <div class="bc-auth-input-group">
                    <span class="bc-auth-input-icon"><i class="fa-solid fa-calendar"></i></span>
                    <input type="number" class="bc-auth-input" id="experience_years" name="experience_years" min="0" max="50" placeholder="<?= e(__('auth.experience_placeholder')) ?>" value="<?= isset($_POST['experience_years']) ? (int)$_POST['experience_years'] : '' ?>">
                </div>
            </div>

            <!-- Contractor & Client Dynamic Field: Company / Organization Name -->
            <div id="company-field-group" class="mb-3 <?= (isset($_POST['role']) && in_array($_POST['role'], ['contractor', 'client'])) ? '' : 'd-none' ?>">
                <div class="bc-auth-input-group">
                    <span class="bc-auth-input-icon"><i class="fa-solid fa-building"></i></span>
                    <input type="text" class="bc-auth-input" id="company_name" name="company_name" placeholder="<?= e(__('auth.company_placeholder')) ?>" value="<?= isset($_POST['company_name']) ? sanitize($_POST['company_name']) : '' ?>">
                </div>
            </div>

            <!-- Primary Orange Register Account Button -->
            <button type="submit" id="submitBtn" class="bc-auth-btn-primary w-100 mt-2 mb-4">
                <i class="fa-solid fa-user-plus me-2"></i> <?= __('auth.register_account_btn') ?>
            </button>
        </form>

        <div class="bc-auth-footer-divider mb-3">
            <span><?= __('auth.already_have_account') ?></span>
        </div>

        <div class="text-center">
            <a href="<?= BASE_URL ?>/login.php" class="bc-auth-link"><?= __('auth.sign_in') ?></a>
        </div>
    </div>
</div>

<script>
function selectRole(role) {
    document.querySelectorAll('.bc-role-tab').forEach(tab => tab.classList.remove('active'));
    const activeTab = document.getElementById('tab-' + role);
    if (activeTab) {
        activeTab.classList.add('active');
        const radio = activeTab.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
    }

    const tradeGroup = document.getElementById('worker-trade-group');
    const expGroup = document.getElementById('worker-exp-group');
    const companyGroup = document.getElementById('company-field-group');

    if (role === 'worker') {
        tradeGroup?.classList.remove('d-none');
        expGroup?.classList.remove('d-none');
        companyGroup?.classList.add('d-none');
    } else {
        tradeGroup?.classList.add('d-none');
        expGroup?.classList.add('d-none');
        companyGroup?.classList.remove('d-none');
    }
}

function togglePasswordVisibility(fieldId, iconId) {
    const pwd = document.getElementById(fieldId);
    const icon = document.getElementById(iconId);
    if (pwd && icon) {
        if (pwd.type === 'password') {
            pwd.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            pwd.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    }
}

document.getElementById('registerForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    if (btn) {
        btn.disabled = true;
        const regText = window.__ ? window.__('auth.registering_account', 'Registering Account...') : 'Registering Account...';
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> ' + regText;
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

