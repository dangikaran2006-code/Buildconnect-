<?php
http_response_code(403);
require_once __DIR__ . '/includes/auth.php';

$page_title = __('msg.access_denied') . " - BuildConnect";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$user = currentUser();
$dashboard_url = $user ? get_role_redirect_url($user['role']) : 'index.php';
$flash = get_flash_message();
?>

<div class="container py-5 d-flex justify-content-center align-items-center" style="min-height: 70vh;">
    <div class="bc-card p-4 p-md-5 text-center shadow-lg border-danger" style="max-width: 500px;">
        <div class="text-danger mb-3 display-4">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <h1 class="h3 text-white fw-bold mb-2"><?= __('msg.access_denied') ?></h1>
        <p class="text-muted small mb-4">
            <?= __('msg.access_denied_desc') ?>
        </p>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-warning py-2 px-3 extra-small mb-4 text-start">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= sanitize($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <div class="p-2 mb-4 bg-dark bg-opacity-50 rounded border border-secondary border-opacity-25 extra-small text-muted">
                <?= __('auth.signed_in_as') ?> <strong class="text-white"><?= sanitize($user['name'] ?? 'User') ?></strong> (<span class="text-warning text-capitalize"><?= sanitize(__('role.' . ($user['role'] ?? ''))) ?></span>)
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-center gap-3">
            <a href="<?= BASE_URL ?>/<?= $dashboard_url ?>" class="btn btn-amber btn-sm font-semibold">
                <i class="fa-solid fa-gauge me-1"></i> <?= __('btn.return_dashboard') ?>
            </a>
            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger btn-sm">
                <i class="fa-solid fa-right-from-bracket me-1"></i> <?= __('sidebar.logout') ?>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
