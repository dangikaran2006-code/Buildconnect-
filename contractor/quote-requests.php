<?php
/**
 * BuildConnect — Contractor Quotation Requests Dashboard
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('contractor');
require_once __DIR__ . '/../includes/booking-functions.php';

$page_title = "Quotation Requests - Contractor - BuildConnect";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$user = currentUser();
$db = getDB();
$contractor_user_id = (int)$user['id'];

$flash = get_flash_message();

// Fetch open Mode B Compare Quotes bookings
$stmt = $db->prepare("
    SELECT b.*, 
           c_user.name as client_name, b.city as client_city,
           (SELECT COUNT(*) FROM booking_quotes bq WHERE bq.booking_id = b.id) as quote_count,
           (SELECT bq.status FROM booking_quotes bq WHERE bq.booking_id = b.id AND bq.provider_id = ?) as my_quote_status,
           (SELECT bq.proposed_price FROM booking_quotes bq WHERE bq.booking_id = b.id AND bq.provider_id = ?) as my_proposed_price
    FROM bookings b
    JOIN users c_user ON b.client_id = c_user.id
    WHERE b.booking_mode = 'quote'
    ORDER BY b.id DESC
");
$stmt->execute([$contractor_user_id, $contractor_user_id]);
$rfq_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="bc-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="bc-main-content">
        <div class="container-fluid px-0">
            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h2 fw-bold text-dark mb-1">
                        <i class="fa-solid fa-file-invoice text-warning me-2"></i>Quotation Requests (RFQs)
                    </h1>
                    <p class="text-muted small mb-0">Browse client requests for renovation, construction, and trade bids, and submit commercial quotations.</p>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($rfq_list)): ?>
                <div class="bc-card p-5 text-center">
                    <i class="fa-solid fa-scale-balanced text-muted fs-1 mb-3"></i>
                    <h3 class="h5 text-dark fw-bold mb-2">No Open Quotation Requests</h3>
                    <p class="text-muted small mb-0">There are currently no active Compare Quotes requests from clients.</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($rfq_list as $rfq): 
                        $has_quoted = !empty($rfq['my_quote_status']);
                    ?>
                        <div class="col-12">
                            <div class="bc-card p-4 hover-card-lift">
                                <div class="row align-items-center gy-3">
                                    <div class="col-lg-6">
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="badge bg-info text-dark extra-small fw-bold text-uppercase">
                                                <?= e($rfq['category']) ?>
                                            </span>
                                            <span class="text-muted extra-small font-monospace">#<?= e($rfq['booking_number']) ?></span>
                                            <span class="badge <?= get_booking_badge_class($rfq['status']) ?> extra-small">
                                                <?= e(ucwords(str_replace('_', ' ', $rfq['status']))) ?>
                                            </span>
                                            <?php if ($has_quoted): ?>
                                                <span class="badge bg-success extra-small">
                                                    <i class="fa-solid fa-check me-1"></i> Quote <?= ucfirst(e($rfq['my_quote_status'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <h3 class="h5 fw-bold text-dark mb-1"><?= e($rfq['title']) ?></h3>
                                        <p class="text-muted small mb-2"><?= e($rfq['description']) ?></p>

                                        <div class="extra-small text-muted">
                                            <i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($rfq['city']) ?>, <?= e($rfq['address']) ?>
                                            &bull; <i class="fa-solid fa-calendar me-1 text-info"></i>Preferred Start: <?= format_date($rfq['preferred_date']) ?>
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-lg-3">
                                        <div class="text-muted extra-small">Client Estimated Budget</div>
                                        <div class="h5 fw-bold text-warning mb-1">
                                            <?= $rfq['budget_min'] ? format_currency($rfq['budget_min']) . ' - ' . format_currency($rfq['budget_max']) : 'Flexible' ?>
                                        </div>
                                        <div class="extra-small text-muted">
                                            <i class="fa-solid fa-users me-1"></i><?= (int)$rfq['quote_count'] ?> Quotes Submitted
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-lg-3 text-lg-end">
                                        <?php if ($has_quoted): ?>
                                            <div class="text-muted extra-small">Your Quoted Price</div>
                                            <div class="h5 fw-bold text-success mb-2"><?= format_currency($rfq['my_proposed_price']) ?></div>
                                            <a href="<?= BASE_URL ?>/worker/submit-quote.php?booking_id=<?= $rfq['id'] ?>" class="btn btn-outline-secondary btn-sm extra-small">
                                                <i class="fa-solid fa-pen me-1"></i> Revise My Quote
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= BASE_URL ?>/worker/submit-quote.php?booking_id=<?= $rfq['id'] ?>" class="btn btn-amber fw-bold btn-sm py-2 px-3">
                                                <i class="fa-solid fa-file-signature me-1"></i> Submit Company Quote
                                            </a>
                                        <?php endif; ?>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
