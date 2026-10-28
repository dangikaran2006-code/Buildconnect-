<?php
/**
 * BuildConnect — Worker My Bookings & Submitted Quotations
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('worker');
require_once __DIR__ . '/../includes/booking-functions.php';

$page_title = "My Bookings & Dispatches - Worker - BuildConnect";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$user = currentUser();
$db = getDB();
$worker_user_id = (int)$user['id'];

$flash = get_flash_message();
$error = '';

// Handle Status Updates (e.g. On the way, Work started)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = "Security validation failed (CSRF token invalid).";
    } else {
        $action = sanitize($_POST['action'] ?? '');
        $booking_id = (int)($_POST['booking_id'] ?? 0);

        if ($action === 'accept_job') {
            $res = accept_instant_booking($booking_id, $worker_user_id);
            if ($res['success']) {
                set_flash_message($res['message'], "success");
            } else {
                set_flash_message($res['message'], "danger");
            }
            redirect("worker/my-bookings.php");

        } elseif ($action === 'reject_job') {
            $reason = trim(sanitize($_POST['decline_reason'] ?? 'Worker unavailable'));
            $res = reject_instant_booking($booking_id, $worker_user_id, $reason);
            if ($res['success']) {
                set_flash_message($res['message'], "info");
            } else {
                set_flash_message($res['message'], "danger");
            }
            redirect("worker/my-bookings.php");

        } elseif ($action === 'set_on_the_way') {
            $res = update_booking_status($booking_id, 'on_the_way', $worker_user_id);
            if ($res['success']) {
                set_flash_message("Status updated to 'On The Way'. Client notified!", "success");
            } else {
                set_flash_message($res['message'], "danger");
            }
            redirect("worker/my-bookings.php");

        } elseif ($action === 'set_work_started') {
            $res = update_booking_status($booking_id, 'work_started', $worker_user_id);
            if ($res['success']) {
                set_flash_message("Work started! Status updated.", "success");
            } else {
                set_flash_message($res['message'], "danger");
            }
            redirect("worker/my-bookings.php");
        }
    }
}

// 1. Fetch Assigned Bookings
$b_stmt = $db->prepare("
    SELECT b.*, 
           c_user.name as client_name, c_user.phone as client_phone, c_user.email as client_email, c_user.avatar as client_avatar
    FROM bookings b
    JOIN users c_user ON b.client_id = c_user.id
    WHERE b.provider_id = ?
    ORDER BY b.id DESC
");
$b_stmt->execute([$worker_user_id]);
$assigned_bookings = $b_stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch Submitted Quotes
$q_stmt = $db->prepare("
    SELECT bq.*, 
           b.booking_number, b.title as booking_title, b.category as booking_category, 
           b.city as booking_city, b.status as booking_status
    FROM booking_quotes bq
    JOIN bookings b ON bq.booking_id = b.id
    WHERE bq.provider_id = ?
    ORDER BY bq.id DESC
");
$q_stmt->execute([$worker_user_id]);
$my_quotes = $q_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bc-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="bc-main-content">
        <div class="container-fluid px-0">
            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h2 fw-bold text-dark mb-1">
                        <i class="fa-solid fa-briefcase text-warning me-2"></i><?= __('booking.worker_title') ?>
                    </h1>
                    <p class="text-muted small mb-0"><?= __('booking.worker_sub') ?></p>
                </div>
                <a href="<?= BASE_URL ?>/worker/nearby-requests.php" class="btn btn-amber fw-bold btn-sm px-3 py-2">
                    <i class="fa-solid fa-radar me-1"></i> <?= __('booking.view_nearby_btn') ?>
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- TABS -->
            <ul class="nav nav-pills mb-4 border-bottom pb-3" id="bookingTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold extra-small" id="assigned-tab" data-bs-toggle="pill" data-bs-target="#assignedPane" type="button" role="tab">
                        <i class="fa-solid fa-clipboard-check text-warning me-1"></i> <?= __('booking.active_assigned_jobs') ?> (<?= count($assigned_bookings) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold extra-small text-secondary" id="quotes-tab" data-bs-toggle="pill" data-bs-target="#quotesPane" type="button" role="tab">
                        <i class="fa-solid fa-scale-balanced text-info me-1"></i> <?= __('booking.my_submitted_quotes') ?> (<?= count($my_quotes) ?>)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="bookingTabsContent">
                <!-- PANE 1: ASSIGNED JOBS -->
                <div class="tab-pane fade show active" id="assignedPane" role="tabpanel">
                    <?php if (empty($assigned_bookings)): ?>
                        <div class="bc-card p-5 text-center">
                            <i class="fa-solid fa-person-digging text-muted fs-1 mb-3"></i>
                            <h3 class="h5 text-dark fw-bold mb-2"><?= __('booking.no_active_assigned') ?></h3>
                            <p class="text-muted small mb-3"><?= __('booking.no_active_assigned_desc') ?></p>
                            <a href="<?= BASE_URL ?>/worker/nearby-requests.php" class="btn btn-outline-warning btn-sm fw-bold">
                                <?= __('booking.find_nearby_btn') ?> &rarr;
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($assigned_bookings as $b): ?>
                                <div class="col-12">
                                    <div class="bc-card p-4 hover-card-lift">
                                        <div class="row align-items-center gy-3">
                                             <!-- Main Info -->
                                            <div class="col-lg-5">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="badge <?= $b['booking_mode'] === 'instant' ? 'bg-warning text-dark' : 'bg-info text-dark' ?> extra-small fw-bold">
                                                        <?= $b['booking_mode'] === 'instant' ? __('booking.instant_bookings') : __('booking.quote') ?>
                                                    </span>
                                                    <span class="badge <?= get_booking_badge_class($b['status']) ?> extra-small">
                                                        <?= e(ucwords(str_replace('_', ' ', $b['status']))) ?>
                                                    </span>
                                                    <span class="text-muted extra-small font-monospace">#<?= e($b['booking_number']) ?></span>
                                                </div>

                                                <h3 class="h5 fw-bold text-dark mb-1"><?= e($b['title']) ?></h3>
                                                <p class="text-muted extra-small mb-2"><?= e($b['description']) ?></p>

                                                <div class="extra-small text-muted">
                                                    <i class="fa-solid fa-calendar me-1 text-info"></i><?= __('booking.scheduled_prefix') ?> <strong class="text-dark"><?= format_date($b['preferred_date']) ?> (<?= e($b['preferred_time']) ?>)</strong>
                                                </div>
                                            </div>

                                            <!-- Client Details & Address (Revealed because accepted) -->
                                            <div class="col-sm-6 col-lg-4">
                                                <div class="p-3 bg-light rounded border extra-small">
                                                    <div class="fw-bold text-dark mb-1">
                                                        <i class="fa-solid fa-user text-warning me-1"></i>Client: <?= e($b['client_name']) ?>
                                                    </div>
                                                    <div class="text-muted mb-1">
                                                        <i class="fa-solid fa-phone text-success me-1"></i>Contact: <a href="tel:<?= e($b['client_phone']) ?>" class="text-success text-decoration-none fw-bold"><?= e($b['client_phone'] ?: 'Phone on file') ?></a>
                                                    </div>
                                                    <div class="text-muted">
                                                        <i class="fa-solid fa-location-dot text-danger me-1"></i>Site Address: <?= e($b['address']) ?>, <?= e($b['city']) ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Fee & Status Action Buttons -->
                                            <div class="col-sm-6 col-lg-3 text-lg-end">
                                                <div class="text-muted extra-small">Agreed Earnings</div>
                                                <div class="h4 fw-bold text-warning mb-2"><?= format_currency($b['price']) ?></div>

                                                <div class="d-flex flex-column gap-1">
                                                    <?php if ($b['status'] === 'posted' || $b['status'] === 'matching'): ?>
                                                        <form action="<?= BASE_URL ?>/worker/my-bookings.php" method="POST" class="mb-1">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="accept_job">
                                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                            <button type="submit" class="btn btn-success btn-sm w-100 fw-bold extra-small py-1.5">
                                                                <i class="fa-solid fa-check me-1"></i> Accept Job
                                                            </button>
                                                        </form>
                                                        <button type="button" class="btn btn-outline-danger btn-sm w-100 extra-small py-1" data-bs-toggle="collapse" data-bs-target="#declineBox_<?= $b['id'] ?>">
                                                            <i class="fa-solid fa-xmark me-1"></i> Decline / Reject
                                                        </button>
                                                        <div class="collapse mt-1" id="declineBox_<?= $b['id'] ?>">
                                                            <form action="<?= BASE_URL ?>/worker/my-bookings.php" method="POST">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="action" value="reject_job">
                                                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                                <input type="text" name="decline_reason" class="form-control form-control-sm extra-small mb-1" placeholder="Reason (e.g. Schedule conflict)" required>
                                                                <button type="submit" class="btn btn-danger btn-sm w-100 extra-small py-1">Confirm Decline</button>
                                                            </form>
                                                        </div>
                                                    <?php elseif ($b['status'] === 'accepted' || $b['status'] === 'confirmed'): ?>
                                                        <form action="<?= BASE_URL ?>/worker/my-bookings.php" method="POST">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="set_on_the_way">
                                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold extra-small py-1.5">
                                                                <i class="fa-solid fa-car-side me-1"></i> I'm On The Way
                                                            </button>
                                                        </form>
                                                        <button type="button" class="btn btn-link text-danger text-decoration-none extra-small py-0" data-bs-toggle="collapse" data-bs-target="#declineBox_<?= $b['id'] ?>">
                                                            Decline Job
                                                        </button>
                                                        <div class="collapse mt-1" id="declineBox_<?= $b['id'] ?>">
                                                            <form action="<?= BASE_URL ?>/worker/my-bookings.php" method="POST">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="action" value="reject_job">
                                                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                                <input type="text" name="decline_reason" class="form-control form-control-sm extra-small mb-1" placeholder="Reason for declining" required>
                                                                <button type="submit" class="btn btn-danger btn-sm w-100 extra-small py-1">Confirm Decline</button>
                                                            </form>
                                                        </div>
                                                    <?php elseif ($b['status'] === 'on_the_way'): ?>
                                                        <form action="<?= BASE_URL ?>/worker/my-bookings.php" method="POST">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="action" value="set_work_started">
                                                            <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                                            <button type="submit" class="btn btn-warning btn-sm w-100 fw-bold extra-small py-1.5 text-dark">
                                                                <i class="fa-solid fa-play me-1"></i> Start Work Now
                                                            </button>
                                                        </form>
                                                    <?php elseif ($b['status'] === 'work_started'): ?>
                                                        <span class="badge bg-primary p-2 w-100 extra-small">
                                                            <i class="fa-solid fa-spinner fa-spin me-1"></i> Work in Progress
                                                        </span>
                                                    <?php elseif ($b['status'] === 'completed'): ?>
                                                        <span class="badge bg-success p-2 w-100 extra-small">
                                                            <i class="fa-solid fa-circle-check me-1"></i> Job Completed
                                                        </span>
                                                    <?php elseif ($b['status'] === 'cancelled'): ?>
                                                        <span class="badge bg-danger p-2 w-100 extra-small">
                                                            <i class="fa-solid fa-ban me-1"></i> Cancelled
                                                        </span>
                                                        <?php if (!empty($b['cancellation_reason'])): ?>
                                                            <div class="extra-small text-danger text-center mt-1"><?= e($b['cancellation_reason']) ?></div>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- PANE 2: MY QUOTES -->
                <div class="tab-pane fade" id="quotesPane" role="tabpanel">
                    <?php if (empty($my_quotes)): ?>
                        <div class="bc-card p-5 text-center">
                            <i class="fa-solid fa-file-invoice text-muted fs-1 mb-3"></i>
                            <h3 class="h5 text-dark fw-bold mb-2">No Quotations Submitted</h3>
                            <p class="text-muted small mb-0">Browse open RFQ requests to submit competitive bids.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($my_quotes as $mq): ?>
                                <div class="col-12">
                                    <div class="bc-card p-3 hover-card-lift">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="badge bg-light text-secondary border extra-small font-monospace">#<?= e($mq['booking_number']) ?></span>
                                                    <span class="badge <?= $mq['status'] === 'accepted' ? 'bg-success' : ($mq['status'] === 'rejected' ? 'bg-secondary' : 'bg-primary') ?> extra-small">
                                                        <?= strtoupper(e($mq['status'])) ?>
                                                    </span>
                                                </div>
                                                <h3 class="h6 fw-bold text-dark mb-0"><?= e($mq['booking_title']) ?></h3>
                                                <div class="text-muted extra-small"><?= e($mq['booking_city']) ?> &bull; Submitted on <?= format_datetime($mq['created_at']) ?></div>
                                            </div>

                                            <div class="text-end">
                                                <div class="h5 fw-bold text-warning mb-0"><?= format_currency($mq['proposed_price']) ?></div>
                                                <div class="extra-small text-muted">Est. <?= e($mq['estimated_duration']) ?></div>
                                            </div>
                                        </div>

                                        <div class="p-2.5 bg-light rounded border extra-small text-secondary mb-2">
                                            <strong>Scope:</strong> <?= e($mq['scope_of_work']) ?>
                                        </div>

                                        <?php if ($mq['status'] === 'submitted' || $mq['status'] === 'revised'): ?>
                                            <div class="text-end">
                                                <a href="<?= BASE_URL ?>/worker/submit-quote.php?booking_id=<?= $mq['booking_id'] ?>" class="btn btn-outline-secondary btn-sm extra-small py-1">
                                                    <i class="fa-solid fa-pen me-1"></i> Revise Quote
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
