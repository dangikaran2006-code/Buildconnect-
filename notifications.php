<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = get_logged_user();
$db = getDB();

// Handle direct link redirection & mark read for specific ID
if (isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$target_id, $user['id']]);
    $notif = $stmt->fetch();

    if ($notif) {
        mark_notification_as_read($target_id, $user['id']);
        if (!empty($notif['link'])) {
            $raw_link = trim($notif['link']);
            $user_role = strtolower(trim($user['role'] ?? ''));
            $clean_link = ltrim(preg_replace('/^https?:\/\/[^\/]+\//', '', $raw_link), '/');

            // Prevent cross-role access denied: if notification link points to another role's directory, resolve to matching route
            $target_role = null;
            if (strpos($clean_link, 'client/') === 0) $target_role = 'client';
            elseif (strpos($clean_link, 'worker/') === 0) $target_role = 'worker';
            elseif (strpos($clean_link, 'contractor/') === 0) $target_role = 'contractor';
            elseif (strpos($clean_link, 'admin/') === 0) $target_role = 'admin';

            if ($target_role && $target_role !== $user_role && $user_role !== 'admin') {
                if (strpos($clean_link, 'booking') !== false || strpos($clean_link, 'quote') !== false) {
                    if ($user_role === 'worker') $clean_link = 'worker/my-bookings.php';
                    elseif ($user_role === 'contractor') $clean_link = 'contractor/quote-requests.php';
                    elseif ($user_role === 'client') $clean_link = 'client/bookings.php';
                } elseif (strpos($clean_link, 'contract') !== false) {
                    if ($user_role === 'contractor') $clean_link = 'contractor/contracts.php';
                    elseif ($user_role === 'worker') $clean_link = 'worker/contracts.php';
                    elseif ($user_role === 'client') $clean_link = 'client/contracts.php';
                } elseif (strpos($clean_link, 'review') !== false) {
                    if ($user_role === 'contractor') $clean_link = 'contractor/reviews.php';
                    elseif ($user_role === 'worker') $clean_link = 'worker/reviews.php';
                    elseif ($user_role === 'client') $clean_link = 'client/reviews.php';
                } else {
                    $clean_link = $user_role . '/index.php';
                }
            }

            $dest = (strpos($raw_link, 'http') === 0 && !isset($target_role)) 
                ? $raw_link 
                : BASE_URL . '/' . ltrim($clean_link, '/');
            redirect($dest);
        }
    }
}

// Handle Form POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';
        if ($action === 'mark_read') {
            $notif_id = (int)($_POST['notification_id'] ?? 0);
            if ($notif_id > 0) {
                mark_notification_as_read($notif_id, $user['id']);
                set_flash_message("Notification marked as read.", "info");
            }
        } elseif ($action === 'mark_all_read') {
            mark_all_notifications_as_read($user['id']);
            set_flash_message("All notifications marked as read.", "success");
        }
    }
    redirect('notifications.php');
}

$page_title = "Notification Center - BuildConnect";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Filtering and Pagination Parameters
$read_filter = sanitize($_GET['read_status'] ?? 'all');
$type_category = sanitize($_GET['category'] ?? 'all');
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Map categories to notification types
$category_type_map = [
    'jobs' => ['JOB_APPLICATION', 'APPLICATION_STATUS', 'JOB_POSTED', 'WORKER_HIRED'],
    'projects' => ['PROJECT_STATUS', 'MILESTONE_COMPLETED'],
    'tasks' => ['TASK_ASSIGNED', 'TASK_UPDATED', 'TASK_COMPLETED'],
    'contracts' => ['CONTRACT'],
    'reviews' => ['REVIEW'],
    'attendance' => ['ATTENDANCE']
];

$where = ["user_id = ?"];
$params = [$user['id']];

if ($read_filter === 'unread') {
    $where[] = "is_read = 0";
} elseif ($read_filter === 'read') {
    $where[] = "is_read = 1";
}

if ($type_category !== 'all' && isset($category_type_map[$type_category])) {
    $in_clause = implode(',', array_fill(0, count($category_type_map[$type_category]), '?'));
    $where[] = "type IN ({$in_clause})";
    foreach ($category_type_map[$type_category] as $t) {
        $params[] = $t;
    }
}

$where_sql = implode(" AND ", $where);

// Count total matching notifications
$count_stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE {$where_sql}");
$count_stmt->execute($params);
$total_records = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_records / $per_page));

// Fetch paginated notifications
$list_stmt = $db->prepare("
    SELECT * FROM notifications 
    WHERE {$where_sql} 
    ORDER BY id DESC 
    LIMIT {$per_page} OFFSET {$offset}
");
$list_stmt->execute($params);
$notifications = $list_stmt->fetchAll();

$unread_count = get_unread_notification_count($user['id']);
?>

<div class="bc-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="bc-main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 fw-bold text-dark mb-1"><i class="fa-solid fa-bell text-amber me-2"></i><?= __('notif.center_title') ?></h1>
                <p class="text-muted small mb-0"><?= __('notif.center_subtitle') ?></p>
            </div>
            
            <?php if ($unread_count > 0): ?>
                <form action="<?= BASE_URL ?>/notifications.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="btn btn-amber btn-sm fw-bold">
                        <i class="fa-solid fa-check-double me-1"></i> <?= __('notif.mark_all_read') ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($flash = get_flash_message()): ?>
            <div class="alert alert-<?= sanitize($flash['type']) ?> py-2 px-3 small mb-4">
                <i class="fa-solid fa-circle-info me-1"></i> <?= sanitize($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Localhost Web Push Control Panel Card -->
        <div class="bc-card p-3.5 mb-4 border-warning">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2.5 rounded-3 bg-warning-subtle text-warning">
                        <i class="fa-solid fa-tower-cell fs-4"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h5 class="fw-bold text-dark mb-0 fs-6"><?= __('notif.localhost_push') ?></h5>
                            <span id="bc-push-status-badge" class="badge bg-warning text-dark py-1 px-2.5 rounded-pill extra-small">
                                <i class="fa-solid fa-spinner fa-spin me-1"></i> <?= __('notif.checking') ?>
                            </span>
                        </div>
                        <p id="bc-push-status-desc" class="text-muted extra-small mb-0">
                            <?= __('notif.push_desc') ?>
                        </p>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button id="bc-enable-push-btn" type="button" class="btn btn-amber btn-sm fw-bold shadow-sm" onclick="BuildConnectPush.requestPermission()">
                        <i class="fa-solid fa-bell me-1"></i> <?= __('notif.enable_push') ?>
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-sm fw-semibold" onclick="BuildConnectPush.sendTestPush()">
                        <i class="fa-solid fa-paper-plane me-1"></i> <?= __('notif.send_test_push') ?>
                    </button>
                </div>
            </div>
            
            <div class="mt-2.5 pt-2 border-top d-flex justify-content-between align-items-center text-muted extra-small">
                <span><i class="fa-solid fa-laptop-code me-1 text-warning"></i> Push Session Endpoint ID: <code id="bc-push-token-display" class="text-primary bg-light px-2 py-0.5 rounded border">localhost-push-active</code></span>
                <span><i class="fa-solid fa-shield-halved me-1 text-success"></i> W3C Web Push & Service Worker v1.0</span>
            </div>
        </div>

        <!-- Filters Bar -->
        <div class="bc-card p-3 mb-4">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span class="text-muted extra-small me-1"><i class="fa-solid fa-filter me-1"></i><?= __('notif.filter_category') ?></span>
                    <a href="<?= BASE_URL ?>/notifications.php?category=all&read_status=<?= $read_filter ?>" class="btn btn-sm <?= ($type_category === 'all') ? 'btn-amber' : 'btn-outline-secondary' ?>"><?= __('notif.category_all') ?></a>
                    <a href="<?= BASE_URL ?>/notifications.php?category=jobs&read_status=<?= $read_filter ?>" class="btn btn-sm <?= ($type_category === 'jobs') ? 'btn-amber' : 'btn-outline-secondary' ?>"><?= __('notif.jobs_hiring') ?></a>
                    <a href="<?= BASE_URL ?>/notifications.php?category=projects&read_status=<?= $read_filter ?>" class="btn btn-sm <?= ($type_category === 'projects') ? 'btn-amber' : 'btn-outline-secondary' ?>"><?= __('notif.projects') ?></a>
                    <a href="<?= BASE_URL ?>/notifications.php?category=tasks&read_status=<?= $read_filter ?>" class="btn btn-sm <?= ($type_category === 'tasks') ? 'btn-amber' : 'btn-outline-secondary' ?>"><?= __('notif.tasks') ?></a>
                    <a href="<?= BASE_URL ?>/notifications.php?category=contracts&read_status=<?= $read_filter ?>" class="btn btn-sm <?= ($type_category === 'contracts') ? 'btn-amber' : 'btn-outline-secondary' ?>"><?= __('notif.contracts') ?></a>
                    <a href="<?= BASE_URL ?>/notifications.php?category=reviews&read_status=<?= $read_filter ?>" class="btn btn-sm <?= ($type_category === 'reviews') ? 'btn-amber' : 'btn-outline-secondary' ?>"><?= __('notif.reviews') ?></a>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>/notifications.php?read_status=all&category=<?= $type_category ?>" class="btn btn-sm <?= ($read_filter === 'all') ? 'btn-secondary text-white' : 'btn-outline-secondary' ?>"><?= __('notif.all_status') ?></a>
                    <a href="<?= BASE_URL ?>/notifications.php?read_status=unread&category=<?= $type_category ?>" class="btn btn-sm <?= ($read_filter === 'unread') ? 'btn-danger' : 'btn-outline-secondary' ?>"><?= __('notif.unread_only') ?></a>
                </div>
            </div>
        </div>

        <div class="bc-card p-4">
            <?php if (empty($notifications)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fa-solid fa-bell-slash fs-1 opacity-25 mb-3"></i>
                    <p class="mb-0"><?= __('notif.no_notifications_filter') ?></p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush bg-transparent">
                    <?php foreach ($notifications as $notif): ?>
                        <div class="list-group-item bg-transparent text-dark border-light px-0 py-3 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-start gap-3">
                                <div class="mt-1">
                                    <?php if ($notif['is_read']): ?>
                                        <i class="fa-regular fa-envelope-open text-muted fs-5"></i>
                                    <?php else: ?>
                                        <i class="fa-solid fa-envelope text-warning fs-5"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark mb-1">
                                        <?= sanitize(translate_notification_title($notif['title'])) ?>
                                        <span class="badge bg-light border text-secondary extra-small ms-2"><?= sanitize($notif['type']) ?></span>
                                        <?php if (!$notif['is_read']): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle extra-small ms-1"><?= __('notif.unread_badge') ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-secondary small mb-1"><?= sanitize(translate_notification_message($notif['message'])) ?></p>
                                    <span class="text-muted extra-small"><i class="fa-solid fa-clock me-1"></i><?= format_datetime($notif['created_at']) ?></span>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($notif['link'])): ?>
                                    <a href="<?= BASE_URL ?>/notifications.php?id=<?= $notif['id'] ?>" class="btn btn-outline-warning btn-sm extra-small fw-bold">
                                        <?= __('notif.view_resource') ?> <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                <?php endif; ?>

                                <?php if (!$notif['is_read']): ?>
                                    <form action="<?= BASE_URL ?>/notifications.php" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="notification_id" value="<?= $notif['id'] ?>">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm extra-small">
                                            <?= __('notif.mark_read_btn') ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination Navigation -->
                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center pt-4 mt-2 border-top">
                        <span class="text-muted extra-small"><?= __('notif.page_info', ['page' => $page, 'total_pages' => $total_pages, 'total' => $total_records]) ?></span>
                        <div class="btn-group btn-group-sm">
                            <?php if ($page > 1): ?>
                                <a href="<?= BASE_URL ?>/notifications.php?page=<?= $page - 1 ?>&category=<?= $type_category ?>&read_status=<?= $read_filter ?>" class="btn btn-outline-secondary"><?= __('btn.previous') ?></a>
                            <?php endif; ?>
                            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                                <a href="<?= BASE_URL ?>/notifications.php?page=<?= $p ?>&category=<?= $type_category ?>&read_status=<?= $read_filter ?>" class="btn <?= ($p === $page) ? 'btn-amber' : 'btn-outline-secondary' ?>"><?= $p ?></a>
                            <?php endfor; ?>
                            <?php if ($page < $total_pages): ?>
                                <a href="<?= BASE_URL ?>/notifications.php?page=<?= $page + 1 ?>&category=<?= $type_category ?>&read_status=<?= $read_filter ?>" class="btn btn-outline-secondary"><?= __('btn.next') ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
