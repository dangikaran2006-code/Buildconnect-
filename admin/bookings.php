<?php
/**
 * BuildConnect — Admin Bookings & Quotes Oversight
 */

require_once __DIR__ . '/../includes/auth.php';
require_role(ROLE_ADMIN);
require_once __DIR__ . '/../includes/booking-functions.php';

$page_title = "Bookings & Quotes Management - Admin - BuildConnect";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$user = get_logged_user();
$db = getDB();

$flash = get_flash_message();
$error = '';

// Handle Admin Actions (e.g. override status, cancel, resolve dispute)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = "CSRF verification failed.";
    } else {
        $action = sanitize($_POST['action'] ?? '');
        $booking_id = (int)($_POST['booking_id'] ?? 0);

        if ($action === 'admin_cancel') {
            $reason = trim(sanitize($_POST['reason'] ?? 'Cancelled by Admin Moderation'));
            $res = update_booking_status($booking_id, 'cancelled', $user['id'], $reason);
            if ($res['success']) {
                set_flash_message("Booking #{$booking_id} cancelled by Administrator.", "info");
            } else {
                set_flash_message($res['message'], "danger");
            }
            redirect("admin/bookings.php");
        }
    }
}

// Filters
$filter_mode = sanitize($_GET['mode'] ?? 'all');
$filter_status = sanitize($_GET['status'] ?? 'all');

$where = ["1=1"];
$params = [];

if (in_array($filter_mode, ['instant', 'quote'], true)) {
    $where[] = "b.booking_mode = ?";
    $params[] = $filter_mode;
}

if (!empty($filter_status) && $filter_status !== 'all') {
    $where[] = "b.status = ?";
    $params[] = $filter_status;
}

$where_sql = implode(" AND ", $where);

$stmt = $db->prepare("
    SELECT b.*, 
           c_user.name as client_name, c_user.email as client_email,
           p_user.name as provider_name, p_user.role as provider_role,
           (SELECT COUNT(*) FROM booking_quotes bq WHERE bq.booking_id = b.id) as quote_count
    FROM bookings b
    JOIN users c_user ON b.client_id = c_user.id
    LEFT JOIN users p_user ON b.provider_id = p_user.id
    WHERE {$where_sql}
    ORDER BY b.id DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Metrics
$metrics = $db->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN booking_mode = 'instant' THEN 1 ELSE 0 END) as instant_total,
        SUM(CASE WHEN booking_mode = 'quote' THEN 1 ELSE 0 END) as quote_total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_total,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_total
    FROM bookings
")->fetch(PDO::FETCH_ASSOC);
?>

<div class="bc-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="bc-main-content">
        <div class="container-fluid px-0">
            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h2 fw-bold text-white mb-1">
                        <i class="fa-solid fa-list-check text-warning me-2"></i>Bookings & Quotations Oversight
                    </h1>
                    <p class="text-muted small mb-0">Monitor platform-wide Instant Bookings, quotation bidding processes, and dispute resolutions.</p>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Metric Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="bc-card p-3">
                        <div class="text-muted extra-small text-uppercase fw-bold">Total Platform Bookings</div>
                        <div class="h3 fw-bold text-dark mb-0"><?= (int)($metrics['total'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bc-card p-3">
                        <div class="text-muted extra-small text-uppercase fw-bold">Instant Bookings</div>
                        <div class="h3 fw-bold text-warning mb-0"><?= (int)($metrics['instant_total'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bc-card p-3">
                        <div class="text-muted extra-small text-uppercase fw-bold">Compare Quotes</div>
                        <div class="h3 fw-bold text-info mb-0"><?= (int)($metrics['quote_total'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bc-card p-3">
                        <div class="text-muted extra-small text-uppercase fw-bold">Completed Jobs</div>
                        <div class="h3 fw-bold text-success mb-0"><?= (int)($metrics['completed_total'] ?? 0) ?></div>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bc-card p-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 extra-small">
                        <thead>
                            <tr class="text-muted text-uppercase">
                                <th>Booking #</th>
                                <th>Mode</th>
                                <th>Title & Category</th>
                                <th>Client</th>
                                <th>Assigned Provider</th>
                                <th>Price / Quotes</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">No bookings found in platform database.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $b): ?>
                                    <tr>
                                        <td class="font-monospace fw-bold text-warning">#<?= e($b['booking_number']) ?></td>
                                        <td>
                                            <span class="badge <?= $b['booking_mode'] === 'instant' ? 'bg-warning text-dark' : 'bg-info text-dark' ?>">
                                                <?= ucfirst(e($b['booking_mode'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-dark fw-bold"><?= e($b['title']) ?></div>
                                            <div class="text-muted"><?= e($b['city']) ?> &bull; <?= e($b['category']) ?></div>
                                        </td>
                                        <td>
                                            <div class="text-dark fw-semibold"><?= e($b['client_name']) ?></div>
                                            <div class="text-muted"><?= e($b['client_email']) ?></div>
                                        </td>
                                        <td>
                                            <?php if (!empty($b['provider_name'])): ?>
                                                <div class="text-dark fw-semibold"><?= e($b['provider_name']) ?></div>
                                                <span class="badge bg-primary-subtle"><?= ucfirst(e($b['provider_role'])) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted fst-italic">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($b['price']): ?>
                                                <span class="text-dark fw-bold"><?= format_currency($b['price']) ?></span>
                                            <?php else: ?>
                                                <span class="text-info"><?= (int)$b['quote_count'] ?> Quotes</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?= get_booking_badge_class($b['status']) ?>">
                                                <?= e(ucwords(str_replace('_', ' ', $b['status']))) ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?= format_date($b['created_at']) ?></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= BASE_URL ?>/client/booking-details.php?id=<?= $b['id'] ?>" class="btn btn-outline-secondary btn-sm extra-small" title="Inspect Booking">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <?php if (!in_array($b['status'], ['completed', 'cancelled'])): ?>
                                                    <form action="<?= BASE_URL ?>/admin/bookings.php" method="POST" class="d-inline" onsubmit="return confirm('Moderator: Cancel this booking?');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="admin_cancel">
                                                        <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm extra-small" title="Cancel Booking">
                                                            <i class="fa-solid fa-ban"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
