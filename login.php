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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = 'Security validation failed (CSRF token invalid). Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please enter both email address and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $result = attempt_login($email, $password);
            if ($result['success']) {
                set_flash_message('Welcome back, ' . sanitize($result['user']['name']) . '!', 'success');
                redirect($result['redirect']);
            } else {
                $error = $result['message'];
            }
        }
    }
}

$page_title = "Sign In - BuildConnect";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="bc-auth-wrapper">
    <div class="bc-auth-card bc-login-card-size">
        <div class="text-center mb-3">
            <div class="bc-auth-icon-wrapper">
                <i class="fa-solid fa-arrow-right-to-bracket bc-auth-icon"></i>
            </div>

            <h1 class="bc-auth-title mb-1"><?= __('auth.welcome_back_brand', ['brand' => '<span class="text-amber">BuildConnect</span>']) ?></h1>
            <p class="bc-auth-subtitle mb-4"><?= __('auth.login_subtitle') ?></p>
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

        <form action="<?= BASE_URL ?>/login.php" method="POST" data-loading="true" id="loginForm">
            <?= csrf_field() ?>

            <!-- Email Address -->
            <div class="mb-3">
                <div class="bc-auth-input-group">
                    <span class="bc-auth-input-icon"><i class="fa-solid fa-envelope"></i></span>
                    <input type="email" class="bc-auth-input" id="email" name="email" required placeholder="<?= e(__('auth.email_placeholder')) ?>" value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>" autocomplete="email">
                </div>
            </div>

            <!-- Password with Toggle -->
            <div class="mb-3">
                <div class="bc-auth-input-group">
                    <span class="bc-auth-input-icon"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" class="bc-auth-input" id="password" name="password" required placeholder="<?= e(__('auth.password_placeholder')) ?>" autocomplete="current-password">
                    <button class="bc-auth-eye-btn" type="button" onclick="togglePasswordVisibility()" aria-label="Toggle password visibility">
                        <i class="fa-solid fa-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Remember Me -->
            <div class="d-flex align-items-center mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input bc-auth-checkbox" id="remember_me" name="remember_me">
                    <label class="form-check-label small ms-1" for="remember_me" style="color: #94a3b8; cursor: pointer;"><?= __('auth.remember_me') ?></label>
                </div>
            </div>

            <!-- Primary Orange Sign In Button -->
            <button type="submit" id="submitBtn" class="bc-auth-btn-primary w-100 mb-3">
                <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> <?= __('auth.sign_in_btn') ?>
            </button>
        </form>

        <div class="bc-auth-footer-divider my-3">
            <span><?= __('auth.or') ?></span>
        </div>

        <a href="<?= BASE_URL ?>/auth/google-login.php" class="bc-auth-btn-google w-100 py-2.5 mb-4 d-flex align-items-center justify-content-center gap-2">
            <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844a4.14 4.14 0 0 1-1.796 2.716v2.259h2.908c1.702-1.567 2.684-3.875 2.684-6.616z"/>
                <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18z"/>
                <path fill="#FBBC05" d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332z"/>
                <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 7.29C4.672 5.163 6.656 3.58 9 3.58z"/>
            </svg>
            <span><?= __('auth.continue_google') ?></span>
        </a>

        <div class="bc-auth-footer-divider mb-3">
            <span><?= __('auth.no_account') ?></span>
        </div>

        <div class="text-center">
            <a href="<?= BASE_URL ?>/register.php" class="bc-auth-link"><?= __('auth.create_account') ?></a>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility() {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pwd.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

document.getElementById('loginForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    if (btn) {
        btn.disabled = true;
        const signingInText = window.__ ? window.__('auth.signing_in', 'Signing In...') : 'Signing In...';
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> ' + signingInText;
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

