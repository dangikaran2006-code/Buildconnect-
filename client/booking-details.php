<?php
/**
 * BuildConnect — Client Booking Details & Quote Comparison View
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole(['client', 'admin']);
require_once __DIR__ . '/../includes/booking-functions.php';

$user = currentUser();
$is_admin = (strtolower(trim($user['role'] ?? '')) === 'admin');
$db = getDB();
$client_id = (int)$user['id'];
$booking_id = (int)($_GET['id'] ?? 0);

$flash = get_flash_message();
$error = '';

// Fetch Booking Record
if ($is_admin) {
    $stmt = $db->prepare("
        SELECT b.*, 
               p_user.name as provider_name, p_user.email as provider_email, p_user.phone as provider_phone, 
               p_user.role as provider_role, p_user.avatar as provider_avatar,
               w.rating_avg as worker_rating, w.reviews_count as worker_reviews, w.verification_status as worker_verified,
               c.company_name, c.rating_avg as contractor_rating
        FROM bookings b
        LEFT JOIN users p_user ON b.provider_id = p_user.id
        LEFT JOIN workers w ON w.user_id = p_user.id
        LEFT JOIN contractors c ON c.user_id = p_user.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
} else {
    $stmt = $db->prepare("
        SELECT b.*, 
               p_user.name as provider_name, p_user.email as provider_email, p_user.phone as provider_phone, 
               p_user.role as provider_role, p_user.avatar as provider_avatar,
               w.rating_avg as worker_rating, w.reviews_count as worker_reviews, w.verification_status as worker_verified,
               c.company_name, c.rating_avg as contractor_rating
        FROM bookings b
        LEFT JOIN users p_user ON b.provider_id = p_user.id
        LEFT JOIN workers w ON w.user_id = p_user.id
        LEFT JOIN contractors c ON c.user_id = p_user.id
        WHERE b.id = ? AND b.client_id = ?
    ");
    $stmt->execute([$booking_id, $client_id]);
}
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    set_flash_message("Booking not found or unauthorized.", "danger");
    redirect($is_admin ? "admin/bookings.php" : "client/bookings.php");
}

// Handle Actions (Accept Quote, Update Status, Cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = "Security validation failed (CSRF token invalid).";
    } else {
        $action = sanitize($_POST['action'] ?? '');

        if ($action === 'accept_quote') {
            $quote_id = (int)($_POST['quote_id'] ?? 0);
            $res = accept_booking_quote($quote_id, $client_id);
            if ($res['success']) {
                set_flash_message($res['message'], "success");
            } else {
                set_flash_message($res['message'], "danger");
            }
            redirect("client/booking-details.php?id={$booking_id}");

        } elseif ($action === 'mark_completed') {
            $res = update_booking_status($booking_id, 'completed', $client_id);
            if ($res['success']) {
                set_flash_message("Job marked as Completed! Please leave a review for your service provider.", "success");
            } else {
                set_flash_message($res['message'], "danger");
            }
            redirect("client/booking-details.php?id={$booking_id}");

        } elseif ($action === 'cancel_booking') {
            $reason = trim(sanitize($_POST['cancellation_reason'] ?? 'Cancelled by client'));
            $res = update_booking_status($booking_id, 'cancelled', $client_id, $reason);
            if ($res['success']) {
                set_flash_message("Booking was cancelled.", "info");
            } else {
                set_flash_message($res['message'], "danger");
            }
            redirect("client/booking-details.php?id={$booking_id}");
        }
    }
}

// Fetch Quotes if Mode B
$quotes = [];
if ($booking['booking_mode'] === 'quote') {
    $q_stmt = $db->prepare("
        SELECT bq.*, 
               u.name as provider_name, u.email as provider_email, u.phone as provider_phone, u.avatar as provider_avatar,
               w.trade_title, w.experience_years, w.rating_avg as worker_rating, w.reviews_count as worker_reviews, w.verification_status as worker_verified,
               c.company_name, c.rating_avg as contractor_rating
        FROM booking_quotes bq
        JOIN users u ON bq.provider_id = u.id
        LEFT JOIN workers w ON w.user_id = u.id
        LEFT JOIN contractors c ON c.user_id = u.id
        WHERE bq.booking_id = ?
        ORDER BY CASE WHEN bq.status = 'accepted' THEN 1 ELSE 2 END, bq.proposed_price ASC
    ");
    $q_stmt->execute([$booking_id]);
    $quotes = $q_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$page_title = "Booking #" . $booking['booking_number'] . " - Client - BuildConnect";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$is_instant = ($booking['booking_mode'] === 'instant');
$photos = !empty($booking['photos_json']) ? json_decode($booking['photos_json'], true) : [];
?>

<div class="bc-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="bc-main-content">
        <div class="container-fluid px-0">
            <!-- Breadcrumbs -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb extra-small mb-0">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/<?= $is_admin ? 'admin/index.php' : 'client/index.php' ?>" class="text-muted text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/<?= $is_admin ? 'admin/bookings.php' : 'client/bookings.php' ?>" class="text-muted text-decoration-none"><?= $is_admin ? 'All Bookings' : 'My Bookings' ?></a></li>
                        <li class="breadcrumb-item active text-warning" aria-current="page">#<?= e($booking['booking_number']) ?></li>
                    </ol>
                </nav>
                <a href="<?= BASE_URL ?>/<?= $is_admin ? 'admin/bookings.php' : 'client/bookings.php' ?>" class="btn btn-outline-secondary btn-sm extra-small">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to <?= $is_admin ? 'Bookings' : 'List' ?>
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

            <!-- Header Card -->
            <div class="bc-card p-4 mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge <?= $is_instant ? 'bg-warning text-dark' : 'bg-info text-dark' ?> extra-small fw-bold">
                                <i class="fa-solid <?= $is_instant ? 'fa-bolt' : 'fa-scale-balanced' ?> me-1"></i>
                                <?= $is_instant ? 'Mode A: Instant Booking' : 'Mode B: Compare Quotes' ?>
                            </span>
                            <span class="badge <?= get_booking_badge_class($booking['status']) ?> extra-small">
                                <?= e(ucwords(str_replace('_', ' ', $booking['status']))) ?>
                            </span>
                            <span class="text-muted extra-small">Created on <?= format_datetime($booking['created_at']) ?></span>
                        </div>
                        <h1 class="h3 fw-bold text-dark mb-2"><?= e($booking['title']) ?></h1>
                        <p class="text-muted small mb-0"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($booking['address']) ?>, <?= e($booking['city']) ?></p>
                    </div>

                    <div class="text-end">
                        <div class="text-muted extra-small">Price / Budget</div>
                        <div class="h3 fw-bold text-warning mb-0">
                            <?= $booking['price'] ? format_currency($booking['price']) : ($booking['budget_min'] ? format_currency($booking['budget_min']) . ' - ' . format_currency($booking['budget_max']) : 'Competitive Quotes') ?>
                        </div>
                        <div class="extra-small text-muted"><?= $is_instant ? 'Client Proposed Price' : 'Agreed / Estimated Budget' ?></div>
                    </div>
                </div>

                <?php if ($booking['status'] === 'cancelled'): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-3 mt-3 mb-0" role="alert">
                        <i class="fa-solid fa-ban fs-3 text-danger"></i>
                        <div>
                            <div class="fw-bold">Booking Request Cancelled / Declined</div>
                            <div class="small text-dark"><?= e($booking['cancellation_reason'] ?: 'Request was cancelled.') ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- PROGRESS TIMELINE -->
                <hr class="my-4">
                <div class="booking-timeline">
                    <?php 
                    $steps = ['posted' => 'Job Posted', 'matching' => 'Matching', 'confirmed' => 'Confirmed', 'on_the_way' => 'On The Way', 'work_started' => 'In Progress', 'completed' => 'Completed'];
                    $curr_idx = 0;
                    $step_keys = array_keys($steps);
                    $mapped_status = $booking['status'];
                    if ($mapped_status === 'accepted' || $mapped_status === 'quotes_received') $mapped_status = 'confirmed';
                    $curr_idx = array_search($mapped_status, $step_keys);
                    if ($curr_idx === false) $curr_idx = 0;
                    ?>
                    <div class="row text-center g-2">
                        <?php foreach ($steps as $key => $label): 
                            $step_i = array_search($key, $step_keys);
                            $is_past = ($step_i <= $curr_idx && $booking['status'] !== 'cancelled');
                            $is_current = ($step_i === $curr_idx && $booking['status'] !== 'cancelled');
                        ?>
                            <div class="col">
                                <div class="timeline-step <?= $is_past ? 'step-done' : '' ?> <?= $is_current ? 'step-current' : '' ?>">
                                    <div class="timeline-dot mb-1">
                                        <i class="fa-solid <?= $is_past ? 'fa-check' : 'fa-circle' ?>"></i>
                                    </div>
                                    <div class="timeline-label extra-small fw-bold <?= $is_past ? 'text-dark' : 'text-muted' ?>">
                                        <?= e($label) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Left Column: Details -->
                <div class="col-lg-7">
                    <!-- Scope & Details -->
                    <div class="bc-card p-4 mb-4">
                        <h2 class="h5 fw-bold text-dark mb-3"><i class="fa-solid fa-file-lines text-warning me-2"></i>Scope of Work</h2>
                        <p class="text-secondary small mb-4" style="white-space: pre-line;"><?= e($booking['description']) ?></p>

                        <div class="row g-3 p-3 bg-light rounded-3 border mb-3">
                            <div class="col-sm-6">
                                <div class="text-muted extra-small">Category</div>
                                <div class="text-dark small fw-bold text-capitalize"><?= e(str_replace('_', ' ', $booking['category'])) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted extra-small">Preferred Date & Time</div>
                                <div class="text-dark small fw-bold"><?= format_date($booking['preferred_date']) ?> (<?= e($booking['preferred_time']) ?>)</div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted extra-small">Urgency</div>
                                <div class="text-dark small fw-bold text-uppercase"><?= e($booking['urgency']) ?></div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted extra-small">Materials Supply</div>
                                <div class="text-dark small fw-bold"><?= $booking['materials_included'] ? 'Provider Supplies Materials' : 'Client Supplies Materials' ?></div>
                            </div>
                        </div>

                        <?php if (!empty($booking['special_requirements'])): ?>
                            <div class="p-3 bg-light rounded-3 border mb-3">
                                <div class="text-muted extra-small fw-bold mb-1"><i class="fa-solid fa-list-check text-warning me-1"></i>Special Requirements & Skills</div>
                                <div class="text-dark small" style="white-space: pre-line;"><?= e($booking['special_requirements']) ?></div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($photos)): ?>
                            <div class="mt-3">
                                <div class="text-muted extra-small fw-bold mb-2">Attached Photos</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($photos as $ph): ?>
                                        <a href="<?= BASE_URL ?>/<?= e($ph) ?>" target="_blank" rel="noopener">
                                            <img src="<?= BASE_URL ?>/<?= e($ph) ?>" alt="Photo" class="rounded border" style="width: 80px; height: 80px; object-fit: cover;">
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- COMPARE QUOTES SECTION (If Mode B) -->
                    <?php if (!$is_instant): ?>
                        <div class="bc-card p-4 mb-4" id="quotesSection">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h2 class="h5 fw-bold text-dark mb-0">
                                    <i class="fa-solid fa-scale-balanced text-info me-2"></i>Received Quotations (<?= count($quotes) ?>)
                                </h2>
                                <span class="badge bg-light text-secondary border extra-small">Side-by-Side Comparison</span>
                            </div>

                            <?php if (empty($quotes)): ?>
                                <div class="p-4 text-center text-muted extra-small bg-light rounded border">
                                    <i class="fa-solid fa-spinner fa-spin me-2 text-warning"></i>Waiting for verified workers and contractors to submit quotation proposals...
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-3">
                                    <?php foreach ($quotes as $q): 
                                        $is_accepted = ($q['status'] === 'accepted');
                                        $is_rejected = ($q['status'] === 'rejected');
                                    ?>
                                        <div class="p-3 rounded-3 border <?= $is_accepted ? 'border-warning bg-warning bg-opacity-10' : 'border-slate-200 bg-white' ?>">
                                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                                <div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="h6 fw-bold text-dark mb-0"><?= e($q['company_name'] ?: $q['provider_name']) ?></span>
                                                        <span class="badge bg-light text-secondary border extra-small"><?= ucfirst(e($q['provider_role'])) ?></span>
                                                        <?php if ($q['worker_verified'] === 'approved'): ?>
                                                            <span class="badge bg-success extra-small"><i class="fa-solid fa-check-double me-1"></i>Verified</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="extra-small text-muted">
                                                        <?= render_star_rating($q['contractor_rating'] ?: ($q['worker_rating'] ?: 5.0)) ?>
                                                        &bull; <?= e($q['trade_title'] ?? 'Construction Specialist') ?>
                                                    </div>
                                                </div>

                                                <div class="text-end">
                                                    <div class="h5 fw-bold text-warning mb-0"><?= format_currency($q['proposed_price']) ?></div>
                                                    <span class="badge <?= $is_accepted ? 'bg-success' : ($is_rejected ? 'bg-secondary' : 'bg-primary') ?> extra-small">
                                                        <?= strtoupper(e($q['status'])) ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="p-2.5 bg-light rounded mb-2 extra-small text-secondary border">
                                                <div class="fw-bold text-dark mb-1">Proposed Scope:</div>
                                                <?= e($q['scope_of_work']) ?>
                                                <?php if (!empty($q['exclusions'])): ?>
                                                    <div class="text-danger mt-1"><strong>Exclusions:</strong> <?= e($q['exclusions']) ?></div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 extra-small text-muted">
                                                <div>
                                                    <i class="fa-solid fa-clock me-1 text-info"></i>Est. Duration: <strong class="text-dark"><?= e($q['estimated_duration']) ?></strong>
                                                    &bull; <i class="fa-solid fa-calendar me-1 text-info"></i>Start Date: <strong class="text-dark"><?= format_date($q['proposed_start_date']) ?></strong>
                                                </div>

                                                <?php if (!$is_admin && $booking['status'] !== 'completed' && $booking['status'] !== 'cancelled' && !$is_accepted): ?>
                                                    <form action="<?= BASE_URL ?>/client/booking-details.php?id=<?= $booking_id ?>" method="POST" onsubmit="return confirm('Accept this quotation of <?= format_currency($q['proposed_price']) ?>? This will confirm your booking with <?= e($q['provider_name']) ?>.');">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="action" value="accept_quote">
                                                        <input type="hidden" name="quote_id" value="<?= $q['id'] ?>">
                                                        <button type="submit" class="btn btn-amber btn-sm fw-bold extra-small py-1 px-3">
                                                            <i class="fa-solid fa-check me-1"></i> Accept This Quote
                                                        </button>
                                                    </form>
                                                <?php elseif ($is_accepted): ?>
                                                    <span class="badge bg-success fw-bold p-1.5"><i class="fa-solid fa-check-circle me-1"></i> Accepted Quote</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Provider & Actions -->
                <div class="col-lg-5">
                    <!-- Assigned Provider Card -->
                    <div class="bc-card p-4 mb-4">
                        <h2 class="h5 fw-bold text-dark mb-3"><i class="fa-solid fa-user-shield text-warning me-2"></i>Assigned Provider</h2>

                        <?php if (!empty($booking['provider_id'])): ?>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="<?= BASE_URL ?>/assets/images/<?= e($booking['provider_avatar'] ?: 'default_avatar.png') ?>" alt="Provider" class="rounded-circle border border-warning" style="width: 54px; height: 54px; object-fit: cover;">
                                <div>
                                    <h3 class="h6 fw-bold text-dark mb-0"><?= e($booking['company_name'] ?: $booking['provider_name']) ?></h3>
                                    <div class="extra-small text-muted"><?= ucfirst(e($booking['provider_role'])) ?> &bull; Contact: <?= e($booking['provider_phone'] ?: $booking['provider_email']) ?></div>
                                    <div class="extra-small mt-1">
                                        <?= render_star_rating($booking['contractor_rating'] ?: ($booking['worker_rating'] ?: 5.0)) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded border mb-3 extra-small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted">Accepted At:</span>
                                    <span class="text-dark fw-bold"><?= format_datetime($booking['accepted_at']) ?></span>
                                </div>
                                <?php if ($booking['started_at']): ?>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-muted">Work Started:</span>
                                        <span class="text-dark fw-bold"><?= format_datetime($booking['started_at']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($booking['completed_at']): ?>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Completed:</span>
                                        <span class="text-success fw-bold"><?= format_datetime($booking['completed_at']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-4 text-center bg-light rounded border">
                                <i class="fa-solid fa-radar fa-spin text-warning fs-3 mb-2"></i>
                                <div class="text-dark small fw-bold">Searching & Matching Nearby Providers</div>
                                <div class="text-muted extra-small mt-1">Radius: <?= (int)$booking['matching_radius_km'] ?> km around <?= e($booking['city']) ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- Client Status Actions -->
                        <div class="d-flex flex-column gap-2 mt-3">
                            <?php if ($is_admin): ?>
                                <div class="p-3 bg-light rounded border extra-small text-muted text-center">
                                    <i class="fa-solid fa-shield-halved text-primary mb-1 d-block fs-5"></i>
                                    <div class="fw-bold text-dark">Admin Oversight View</div>
                                    <div class="mt-1">Moderator actions can be managed from <a href="<?= BASE_URL ?>/admin/bookings.php" class="text-decoration-none fw-bold">Admin Bookings</a>.</div>
                                </div>
                            <?php else: ?>
                                <?php if (in_array($booking['status'], ['work_started', 'on_the_way', 'confirmed', 'accepted'])): ?>
                                    <form action="<?= BASE_URL ?>/client/booking-details.php?id=<?= $booking_id ?>" method="POST" onsubmit="return confirm('Confirm that the work is finished to your satisfaction?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="mark_completed">
                                        <button type="submit" class="btn btn-success w-100 fw-bold py-2">
                                            <i class="fa-solid fa-circle-check me-1"></i> Approve & Mark Completed
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($booking['status'] === 'completed'): ?>
                                    <a href="<?= BASE_URL ?>/client/reviews.php?project_id=1&reviewee_id=<?= (int)$booking['provider_id'] ?>" class="btn btn-amber w-100 fw-bold py-2">
                                        <i class="fa-solid fa-star me-1"></i> Rate & Review Provider
                                    </a>
                                <?php endif; ?>

                                <?php if (!in_array($booking['status'], ['completed', 'cancelled'])): ?>
                                    <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#cancelBox">
                                        <i class="fa-solid fa-ban me-1"></i> Cancel Request
                                    </button>
                                    <div class="collapse mt-2" id="cancelBox">
                                        <form action="<?= BASE_URL ?>/client/booking-details.php?id=<?= $booking_id ?>" method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="cancel_booking">
                                            <textarea name="cancellation_reason" rows="2" class="form-control bg-white text-dark border-danger extra-small mb-2" placeholder="Please state the reason for cancellation..." required></textarea>
                                            <button type="submit" class="btn btn-danger btn-sm extra-small w-100 fw-bold">Confirm Cancellation</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Location Coordinates Info -->
                    <div class="bc-card p-4">
                        <h2 class="h6 fw-bold text-dark mb-2"><i class="fa-solid fa-map-location-dot text-warning me-2"></i>Location & Service Radius</h2>
                        <div class="text-muted extra-small mb-2">Latitude: <?= e($booking['location_lat']) ?>, Longitude: <?= e($booking['location_lng']) ?></div>
                        <div class="p-3 bg-light rounded border extra-small text-muted">
                            <i class="fa-solid fa-shield-halved text-success me-1"></i> Privacy Protected: Full address and personal phone contact are only disclosed to the confirmed service provider.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
.booking-timeline .timeline-dot {
    width: 28px;
    height: 28px;
    line-height: 28px;
    margin: 0 auto;
    border-radius: 50%;
    background: #F1F5F9;
    color: #64748b;
    border: 2px solid #CBD5E1;
    font-size: 0.75rem;
}
.booking-timeline .step-done .timeline-dot {
    background: #FFAA16;
    color: #0f172a;
    border-color: #FFAA16;
}
.booking-timeline .step-current .timeline-dot {
    background: #0284c7;
    color: #ffffff;
    border-color: #38bdf8;
    box-shadow: 0 0 10px rgba(56, 189, 248, 0.4);
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
