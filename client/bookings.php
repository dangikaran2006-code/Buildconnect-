<?php
/**
 * BuildConnect — Client My Bookings & Quotes Dashboard
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('client');
require_once __DIR__ . '/../includes/booking-functions.php';

$page_title = "My Service Bookings - Client - BuildConnect";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$user = currentUser();
$db = getDB();
$client_id = (int)$user['id'];

$flash = get_flash_message();

// Filters
$filter_mode = sanitize($_GET['mode'] ?? 'all');
$filter_status = sanitize($_GET['status'] ?? 'all');

$where = ["b.client_id = ?"];
$params = [$client_id];

if (in_array($filter_mode, ['instant', 'quote'], true)) {
    $where[] = "b.booking_mode = ?";
    $params[] = $filter_mode;
}

if ($filter_status === 'active') {
    $where[] = "b.status IN ('posted', 'matching', 'quotes_received', 'accepted', 'confirmed', 'on_the_way', 'work_started')";
} elseif ($filter_status === 'completed') {
    $where[] = "b.status = 'completed'";
} elseif ($filter_status === 'cancelled') {
    $where[] = "b.status = 'cancelled'";
}

$where_sql = implode(" AND ", $where);

$stmt = $db->prepare("
    SELECT b.*, 
           p_user.name as provider_name, p_user.role as provider_role, p_user.phone as provider_phone, p_user.avatar as provider_avatar,
           (SELECT COUNT(*) FROM booking_quotes bq WHERE bq.booking_id = b.id) as quote_count
    FROM bookings b
    LEFT JOIN users p_user ON b.provider_id = p_user.id
    WHERE {$where_sql}
    ORDER BY b.id DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Metrics
$m_stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(CASE WHEN booking_mode = 'instant' THEN 1 ELSE 0 END) as instant_count,
        SUM(CASE WHEN booking_mode = 'quote' THEN 1 ELSE 0 END) as quote_count,
        SUM(CASE WHEN status IN ('posted', 'matching', 'quotes_received', 'accepted', 'confirmed', 'on_the_way', 'work_started') THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count
    FROM bookings 
    WHERE client_id = ?
");
$m_stmt->execute([$client_id]);
$metrics = $m_stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="bc-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="bc-main-content">
        <div class="container-fluid px-0">
            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h2 fw-bold text-dark mb-1">
                        <i class="fa-solid fa-clipboard-list text-warning me-2"></i><?= __('booking.my_bookings_title') ?>
                    </h1>
                    <p class="text-muted small mb-0"><?= __('booking.my_bookings_sub') ?></p>
                </div>
                <a href="<?= BASE_URL ?>/client/create-booking.php" class="btn btn-amber fw-bold btn-sm px-3 py-2">
                    <i class="fa-solid fa-plus me-1"></i> <?= __('booking.book_service_btn') ?>
                </a>
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
                        <div class="text-muted extra-small text-uppercase fw-bold mb-1"><?= __('booking.total_requests') ?></div>
                        <div class="h3 fw-bold text-dark mb-0"><?= (int)($metrics['total_bookings'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bc-card p-3">
                        <div class="text-muted extra-small text-uppercase fw-bold mb-1"><?= __('booking.instant_bookings') ?></div>
                        <div class="h3 fw-bold text-warning mb-0"><?= (int)($metrics['instant_count'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bc-card p-3">
                        <div class="text-muted extra-small text-uppercase fw-bold mb-1"><?= __('booking.compare_quotes_metric') ?></div>
                        <div class="h3 fw-bold text-info mb-0"><?= (int)($metrics['quote_count'] ?? 0) ?></div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="bc-card p-3">
                        <div class="text-muted extra-small text-uppercase fw-bold mb-1"><?= __('booking.active_now') ?></div>
                        <div class="h3 fw-bold text-success mb-0"><?= (int)($metrics['active_count'] ?? 0) ?></div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bc-card p-3 mb-4 d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div class="btn-group btn-group-sm" role="group">
                    <a href="<?= BASE_URL ?>/client/bookings.php" class="btn <?= $filter_mode === 'all' && $filter_status === 'all' ? 'btn-amber' : 'btn-outline-secondary' ?>"><?= __('notif.category_all') ?> (<?= (int)$metrics['total_bookings'] ?>)</a>
                    <a href="<?= BASE_URL ?>/client/bookings.php?mode=instant" class="btn <?= $filter_mode === 'instant' ? 'btn-amber' : 'btn-outline-secondary' ?>"><i class="fa-solid fa-bolt me-1 text-warning"></i><?= __('booking.instant') ?></a>
                    <a href="<?= BASE_URL ?>/client/bookings.php?mode=quote" class="btn <?= $filter_mode === 'quote' ? 'btn-amber' : 'btn-outline-secondary' ?>"><i class="fa-solid fa-scale-balanced me-1 text-info"></i><?= __('booking.quote') ?></a>
                    <a href="<?= BASE_URL ?>/client/bookings.php?status=active" class="btn <?= $filter_status === 'active' ? 'btn-amber' : 'btn-outline-secondary' ?>"><?= __('booking.active_now') ?></a>
                    <a href="<?= BASE_URL ?>/client/bookings.php?status=completed" class="btn <?= $filter_status === 'completed' ? 'btn-amber' : 'btn-outline-secondary' ?>"><?= __('booking.status_completed') ?></a>
                </div>

                <div class="extra-small text-muted">
                    Showing <strong><?= count($bookings) ?></strong> requests
                </div>
            </div>

            <!-- Listing -->
            <?php if (empty($bookings)): ?>
                <div class="bc-card p-5 text-center">
                    <div class="display-6 text-muted mb-3"><i class="fa-solid fa-calendar-xmark"></i></div>
                    <h3 class="h5 text-dark fw-bold mb-2"><?= __('booking.no_bookings_title') ?></h3>
                    <p class="text-muted small mb-4"><?= __('booking.no_bookings_desc') ?></p>
                    <a href="<?= BASE_URL ?>/client/create-booking.php" class="btn btn-amber fw-bold btn-sm px-3 py-2">
                        <i class="fa-solid fa-plus me-1"></i> <?= __('booking.create_first') ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($bookings as $b): 
                        $is_instant = ($b['booking_mode'] === 'instant');
                    ?>
                        <div class="col-12">
                            <div class="bc-card p-3 hover-card-lift">
                                <div class="row align-items-center gy-3">
                                    <!-- Title & Meta -->
                                    <div class="col-lg-5">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge <?= $is_instant ? 'bg-warning text-dark' : 'bg-info text-dark' ?> extra-small fw-bold">
                                                <i class="fa-solid <?= $is_instant ? 'fa-bolt' : 'fa-scale-balanced' ?> me-1"></i>
                                                <?= $is_instant ? 'Instant Booking' : 'Compare Quotes' ?>
                                            </span>
                                            <span class="text-muted font-monospace extra-small">#<?= e($b['booking_number']) ?></span>
                                            <span class="badge <?= get_booking_badge_class($b['status']) ?> extra-small">
                                                <?= e(ucwords(str_replace('_', ' ', $b['status']))) ?>
                                            </span>
                                        </div>
                                        <h3 class="h6 fw-bold mb-1">
                                            <a href="<?= BASE_URL ?>/client/booking-details.php?id=<?= $b['id'] ?>" class="text-dark text-decoration-none hover-amber">
                                                <?= e($b['title']) ?>
                                            </a>
                                        </h3>
                                        <div class="text-muted extra-small">
                                            <i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($b['city']) ?>, <?= e($b['address']) ?>
                                            &bull; <i class="fa-solid fa-calendar me-1 text-info"></i><?= format_date($b['preferred_date']) ?> (<?= e($b['preferred_time']) ?>)
                                        </div>
                                    </div>

                                    <!-- Price / Quotes Received -->
                                    <div class="col-sm-6 col-lg-3">
                                        <?php if ($is_instant): ?>
                                            <div class="text-muted extra-small">Proposed Price</div>
                                            <div class="h5 fw-bold text-warning mb-0"><?= format_currency($b['price']) ?></div>
                                            <div class="extra-small text-muted">Fixed offered rate</div>
                                        <?php else: ?>
                                            <div class="text-muted extra-small">Quotes Received</div>
                                            <div class="h5 fw-bold text-info mb-0">
                                                <?= (int)$b['quote_count'] ?> Quotes
                                            </div>
                                            <div class="extra-small text-muted">
                                                Budget: <?= $b['budget_min'] ? format_currency($b['budget_min']) : 'Flexible' ?> - <?= $b['budget_max'] ? format_currency($b['budget_max']) : '' ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Provider Info -->
                                    <div class="col-sm-6 col-lg-2">
                                        <div class="text-muted extra-small">Assigned Provider</div>
                                        <?php if (!empty($b['provider_name'])): ?>
                                            <div class="fw-bold text-dark small"><?= e($b['provider_name']) ?></div>
                                            <span class="badge bg-light text-secondary border extra-small"><?= ucfirst(e($b['provider_role'])) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic extra-small">
                                                <?= $is_instant ? 'Matching Nearby...' : 'Awaiting Bids...' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Actions -->
                                    <div class="col-lg-2 text-lg-end">
                                        <a href="<?= BASE_URL ?>/client/booking-details.php?id=<?= $b['id'] ?>" class="btn btn-outline-dark btn-sm fw-bold">
                                            <?= __('btn.view_details') ?> <i class="fa-solid fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<style>
.hover-card-lift {
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.hover-card-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
    border-color: #FFAA16 !important;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
