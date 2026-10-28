<?php
/**
 * BuildConnect — Worker Nearby Job Requests
 * Displays Instant Booking requests within worker's radius + Compare Quotes opportunities.
 * Features 1-click atomic acceptance and quotation submission.
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('worker');
require_once __DIR__ . '/../includes/booking-functions.php';

$page_title = "Nearby Job Requests - Worker - BuildConnect";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$user = currentUser();
$db = getDB();
$worker_user_id = (int)$user['id'];

// Fetch Worker Profile & Availability
$w_stmt = $db->prepare("
    SELECT id as worker_profile_id, trade_title, experience_years, availability_status, 
           verification_status, location_lat, location_lng, service_radius_km, city
    FROM workers 
    WHERE user_id = ?
");
$w_stmt->execute([$worker_user_id]);
$worker_profile = $w_stmt->fetch(PDO::FETCH_ASSOC);

$is_available = ($worker_profile && $worker_profile['availability_status'] !== 'not_available');
$w_lat = !empty($worker_profile['location_lat']) ? (float)$worker_profile['location_lat'] : DEFAULT_LATITUDE;
$w_lng = !empty($worker_profile['location_lng']) ? (float)$worker_profile['location_lng'] : DEFAULT_LONGITUDE;
$service_radius = !empty($worker_profile['service_radius_km']) ? (int)$worker_profile['service_radius_km'] : 15;

$flash = get_flash_message();
$error = '';

// Handle 1-Click Instant Acceptance POST
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = "Security validation failed (CSRF token invalid).";
    } else {
        $action = sanitize($_POST['action'] ?? '');
        $booking_id = (int)($_POST['booking_id'] ?? 0);

        if ($action === 'accept_instant') {
            if (!$is_available) {
                set_flash_message("You cannot accept jobs while your status is set to 'Not Available'. Update your availability first.", "warning");
                redirect("worker/nearby-requests.php");
            }

            $res = accept_instant_booking($booking_id, $worker_user_id);
            if ($res['success']) {
                set_flash_message($res['message'], "success");
                redirect("worker/my-bookings.php");
            } else {
                set_flash_message($res['message'], "danger");
                redirect("worker/nearby-requests.php");
            }
        } elseif ($action === 'reject_instant') {
            $reason = trim(sanitize($_POST['decline_reason'] ?? 'Worker unavailable'));
            $res = reject_instant_booking($booking_id, $worker_user_id, $reason);
            if ($res['success']) {
                set_flash_message($res['message'], "info");
            } else {
                set_flash_message($res['message'], "danger");
            }
            redirect("worker/nearby-requests.php");
        }
    }
}

// Fetch open Instant requests and Quotes
$stmt = $db->prepare("
    SELECT b.*, 
           c_user.name as client_name, b.city as client_city
    FROM bookings b
    JOIN users c_user ON b.client_id = c_user.id
    WHERE b.status IN ('posted', 'matching')
    ORDER BY CASE WHEN b.urgency = 'urgent' THEN 1 WHEN b.urgency = 'high' THEN 2 ELSE 3 END, b.id DESC
");
$stmt->execute();
$all_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate distance and filter relevant requests
$instant_requests = [];
$quote_requests = [];

foreach ($all_requests as $req) {
    $b_lat = (float)($req['location_lat'] ?? DEFAULT_LATITUDE);
    $b_lng = (float)($req['location_lng'] ?? DEFAULT_LONGITUDE);
    $dist = calculate_haversine_distance($w_lat, $w_lng, $b_lat, $b_lng);

    $req['distance_km'] = $dist;

    if ($req['booking_mode'] === 'instant') {
        // Must be within worker radius or booking radius
        $max_rad = max((int)$req['matching_radius_km'], $service_radius);
        if ($dist === null || $dist <= $max_rad) {
            $instant_requests[] = $req;
        }
    } else {
        // Compare quotes: wider 25 km threshold
        if ($dist === null || $dist <= 30) {
            $quote_requests[] = $req;
        }
    }
}

// Sort by distance
usort($instant_requests, function($a, $b) {
    return ($a['distance_km'] <=> $b['distance_km']);
});
?>

<div class="bc-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="bc-main-content">
        <div class="container-fluid px-0">
            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h2 fw-bold text-dark mb-1">
                        <i class="fa-solid fa-radar text-warning me-2"></i>Nearby Job Requests
                    </h1>
                    <p class="text-muted small mb-0">Browse real-time instant dispatch repair requests and submit bids on renovation jobs in your area.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge <?= $is_available ? 'bg-success' : 'bg-danger' ?> extra-small fw-bold p-2">
                        <i class="fa-solid <?= $is_available ? 'fa-circle-check' : 'fa-circle-xmark' ?> me-1"></i>
                        <?= $is_available ? 'Available for Jobs' : 'Marked Offline' ?>
                    </span>
                    <a href="<?= BASE_URL ?>/worker/availability.php" class="btn btn-outline-secondary btn-sm extra-small">
                        Change Availability
                    </a>
                </div>
            </div>

            <?php if (!$is_available): ?>
                <div class="alert alert-warning border-warning d-flex align-items-center gap-3 mb-4" role="alert">
                    <i class="fa-solid fa-bell-slash fs-3 text-warning"></i>
                    <div>
                        <strong class="text-dark">You are currently offline:</strong>
                        <span class="text-muted"> Your status is set to 'Not Available'. You can view requests, but must switch to Available in order to accept instant dispatches.</span>
                        <a href="<?= BASE_URL ?>/worker/availability.php" class="alert-link ms-2 fw-bold">Update Availability &rarr;</a>
                    </div>
                </div>
            <?php endif; ?>

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

            <!-- SECTION 1: INSTANT BOOKING REQUESTS -->
            <div class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark fw-bold rounded-pill px-2.5 py-1">MODE A</span>
                        <h2 class="h5 fw-bold text-dark mb-0">
                            Instant Service Dispatches (<?= count($instant_requests) ?> Available)
                        </h2>
                    </div>
                    <span class="extra-small text-muted"><i class="fa-solid fa-bolt text-warning me-1"></i>First to accept claims the booking</span>
                </div>

                <?php if (empty($instant_requests)): ?>
                    <div class="bc-card p-4 text-center">
                        <i class="fa-solid fa-location-crosshairs text-muted fs-2 mb-2"></i>
                        <h3 class="h6 text-dark fw-bold mb-1">No Instant Repair Requests Nearby</h3>
                        <p class="text-muted extra-small mb-0">There are no unassigned instant jobs within your <?= $service_radius ?> km service radius right now. Check back shortly!</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($instant_requests as $req): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="bc-card p-3 h-100 d-flex flex-column justify-content-between hover-card-lift">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <span class="badge bg-warning text-dark extra-small fw-bold text-uppercase">
                                                <i class="fa-solid fa-bolt me-1"></i><?= e($req['category']) ?>
                                            </span>
                                            <span class="badge <?= ($req['urgency'] === 'urgent' || $req['urgency'] === 'high') ? 'bg-danger text-white' : 'bg-secondary' ?> extra-small">
                                                <?= strtoupper(e($req['urgency'])) ?>
                                            </span>
                                        </div>

                                        <h3 class="h6 fw-bold text-dark mb-1"><?= e($req['title']) ?></h3>
                                        <p class="text-muted extra-small mb-3" style="max-height: 48px; overflow: hidden; text-overflow: ellipsis;">
                                            <?= e($req['description']) ?>
                                        </p>

                                        <div class="p-2.5 bg-light rounded border mb-3 extra-small">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted"><i class="fa-solid fa-location-dot text-danger me-1"></i>Proximity:</span>
                                                <span class="text-warning fw-bold"><?= $req['distance_km'] !== null ? $req['distance_km'] . ' km away' : 'Nearby' ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted"><i class="fa-solid fa-calendar me-1 text-info"></i>Date:</span>
                                                <span class="text-dark fw-bold"><?= format_date($req['preferred_date']) ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted"><i class="fa-solid fa-clock me-1 text-info"></i>Window:</span>
                                                <span class="text-dark fw-bold"><?= e($req['preferred_time']) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <div class="text-muted extra-small">Client Proposed Fee</div>
                                                <div class="h5 fw-bold text-warning mb-0"><?= format_currency($req['price']) ?></div>
                                            </div>
                                            <span class="extra-small text-muted font-monospace">#<?= e($req['booking_number']) ?></span>
                                        </div>

                                        <form action="<?= BASE_URL ?>/worker/nearby-requests.php" method="POST" onsubmit="return confirm('Accept this instant booking for <?= format_currency($req['price']) ?>? You will be confirmed as the technician.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="accept_instant">
                                            <input type="hidden" name="booking_id" value="<?= $req['id'] ?>">
                                            <button type="submit" class="btn btn-amber w-100 fw-bold btn-sm py-2" <?= !$is_available ? 'disabled' : '' ?>>
                                                <i class="fa-solid fa-check-double me-1"></i> Accept Job (<?= format_currency($req['price']) ?>)
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-outline-secondary w-100 btn-sm extra-small py-1 mt-1" data-bs-toggle="collapse" data-bs-target="#declineReq_<?= $req['id'] ?>">
                                            <i class="fa-solid fa-xmark me-1"></i> Pass / Decline
                                        </button>
                                        <div class="collapse mt-1" id="declineReq_<?= $req['id'] ?>">
                                            <form action="<?= BASE_URL ?>/worker/nearby-requests.php" method="POST">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="reject_instant">
                                                <input type="hidden" name="booking_id" value="<?= $req['id'] ?>">
                                                <input type="text" name="decline_reason" class="form-control form-control-sm extra-small mb-1" placeholder="Reason (e.g. Schedule conflict)" required>
                                                <button type="submit" class="btn btn-outline-danger btn-sm w-100 extra-small py-1">Confirm Decline</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SECTION 2: COMPARE QUOTES OPPORTUNITIES -->
            <div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-info text-dark fw-bold rounded-pill px-2.5 py-1">MODE B</span>
                        <h2 class="h5 fw-bold text-dark mb-0">
                            Quotation Requests & Bidding (<?= count($quote_requests) ?> Open)
                        </h2>
                    </div>
                    <span class="extra-small text-muted"><i class="fa-solid fa-scale-balanced text-info me-1"></i>Submit your proposed price & scope</span>
                </div>

                <?php if (empty($quote_requests)): ?>
                    <div class="bc-card p-4 text-center">
                        <i class="fa-solid fa-file-invoice-dollar text-muted fs-2 mb-2"></i>
                        <h3 class="h6 text-dark fw-bold mb-1">No Open RFQs in Your Area</h3>
                        <p class="text-muted extra-small mb-0">No open quotation requests matching your trade found right now.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($quote_requests as $qreq): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="bc-card p-3 h-100 d-flex flex-column justify-content-between hover-card-lift">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                            <span class="badge bg-info text-dark extra-small fw-bold text-uppercase">
                                                <?= e($qreq['category']) ?>
                                            </span>
                                            <span class="badge bg-light text-secondary border extra-small">RFQ</span>
                                        </div>

                                        <h3 class="h6 fw-bold text-dark mb-1"><?= e($qreq['title']) ?></h3>
                                        <p class="text-muted extra-small mb-3" style="max-height: 48px; overflow: hidden; text-overflow: ellipsis;">
                                            <?= e($qreq['description']) ?>
                                        </p>

                                        <div class="p-2.5 bg-light rounded border mb-3 extra-small">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted"><i class="fa-solid fa-location-dot text-danger me-1"></i>Location:</span>
                                                <span class="text-dark fw-bold"><?= e($qreq['city']) ?> (<?= $qreq['distance_km'] !== null ? $qreq['distance_km'] . ' km' : 'Nearby' ?>)</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted"><i class="fa-solid fa-indian-rupee-sign text-warning me-1"></i>Client Budget:</span>
                                                <span class="text-warning fw-bold">
                                                    <?= $qreq['budget_min'] ? format_currency($qreq['budget_min']) . ' - ' . format_currency($qreq['budget_max']) : 'Flexible Budget' ?>
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span class="text-muted"><i class="fa-solid fa-clock me-1 text-info"></i>Est. Duration:</span>
                                                <span class="text-dark fw-bold"><?= e($qreq['duration_estimate'] ?: 'Per quote') ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <a href="<?= BASE_URL ?>/worker/submit-quote.php?booking_id=<?= $qreq['id'] ?>" class="btn btn-outline-info w-100 fw-bold btn-sm py-2">
                                            <i class="fa-solid fa-file-signature me-1"></i> Submit Quotation Bid
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
