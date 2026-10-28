<?php
/**
 * BuildConnect — Worker / Contractor Quote Submission Form
 */

require_once __DIR__ . '/../includes/auth.php';
require_login();

// Allow either worker or contractor
$user = currentUser();
if (!in_array($user['role'], ['worker', 'contractor'], true)) {
    redirect('unauthorized.php');
}

require_once __DIR__ . '/../includes/booking-functions.php';

$db = getDB();
$provider_id = (int)$user['id'];
$booking_id = (int)($_GET['booking_id'] ?? 0);

// Fetch Booking
$b_stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
$b_stmt->execute([$booking_id]);
$booking = $b_stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking || $booking['booking_mode'] !== 'quote') {
    set_flash_message("Invalid quotation request or booking not found.", "danger");
    redirect($user['role'] === 'worker' ? 'worker/nearby-requests.php' : 'contractor/index.php');
}

// Fetch existing quote by this provider if any
$q_stmt = $db->prepare("SELECT * FROM booking_quotes WHERE booking_id = ? AND provider_id = ?");
$q_stmt->execute([$booking_id, $provider_id]);
$existing_quote = $q_stmt->fetch(PDO::FETCH_ASSOC);

$error = '';
$flash = get_flash_message();

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = "Security validation failed (CSRF token invalid).";
    } else {
        $quote_data = [
            'proposed_price' => (float)($_POST['proposed_price'] ?? 0),
            'scope_of_work' => trim(sanitize($_POST['scope_of_work'] ?? '')),
            'exclusions' => trim(sanitize($_POST['exclusions'] ?? '')),
            'estimated_duration' => trim(sanitize($_POST['estimated_duration'] ?? '')),
            'proposed_start_date' => sanitize($_POST['proposed_start_date'] ?? ''),
            'materials_included' => sanitize($_POST['materials_included'] ?? 'labor_only'),
            'notes' => trim(sanitize($_POST['notes'] ?? ''))
        ];

        $res = submit_booking_quote($booking_id, $provider_id, $quote_data);
        if ($res['success']) {
            set_flash_message($res['message'], "success");
            redirect($user['role'] === 'worker' ? 'worker/my-bookings.php' : 'contractor/quote-requests.php');
        } else {
            $error = $res['message'];
        }
    }
}

$page_title = "Submit Quotation - BuildConnect";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="bc-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="bc-main-content">
        <div class="container-fluid px-0">
            <!-- Breadcrumbs -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb extra-small mb-0">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/<?= $user['role'] ?>/index.php" class="text-muted text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/worker/nearby-requests.php" class="text-muted text-decoration-none">Opportunities</a></li>
                        <li class="breadcrumb-item active text-warning" aria-current="page">Submit Quote #<?= e($booking['booking_number']) ?></li>
                    </ol>
                </nav>
                <a href="<?= BASE_URL ?>/worker/nearby-requests.php" class="btn btn-outline-secondary btn-sm extra-small">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Job Summary Card -->
            <div class="bc-card p-4 mb-4 border-secondary">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <span class="badge bg-info text-dark extra-small fw-bold text-uppercase">
                        <i class="fa-solid fa-scale-balanced me-1"></i>Compare Quotes Request
                    </span>
                    <span class="text-muted extra-small font-monospace">#<?= e($booking['booking_number']) ?></span>
                </div>
                <h1 class="h4 fw-bold text-white mb-2"><?= e($booking['title']) ?></h1>
                <p class="text-slate-300 small mb-3"><?= e($booking['description']) ?></p>

                <div class="row g-2 p-3 bg-dark rounded border border-secondary extra-small text-muted">
                    <div class="col-sm-4">
                        <i class="fa-solid fa-location-dot text-danger me-1"></i>City: <strong class="text-light"><?= e($booking['city']) ?></strong>
                    </div>
                    <div class="col-sm-4">
                        <i class="fa-solid fa-indian-rupee-sign text-warning me-1"></i>Client Budget: <strong class="text-warning"><?= $booking['budget_min'] ? format_currency($booking['budget_min']) . ' - ' . format_currency($booking['budget_max']) : 'Flexible' ?></strong>
                    </div>
                    <div class="col-sm-4">
                        <i class="fa-solid fa-calendar me-1 text-info"></i>Preferred Start: <strong class="text-light"><?= format_date($booking['preferred_date']) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Quotation Form -->
            <div class="bc-card p-4 border-secondary">
                <h2 class="h5 fw-bold text-white mb-3">
                    <i class="fa-solid fa-file-signature text-warning me-2"></i><?= $existing_quote ? 'Revise Your Quotation' : 'Enter Your Quotation Terms' ?>
                </h2>

                <form action="<?= BASE_URL ?>/worker/submit-quote.php?booking_id=<?= $booking_id ?>" method="POST">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <!-- Proposed Price -->
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Your Proposed Total Price (₹ INR) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-warning fw-bold">₹</span>
                                <input type="number" step="50" name="proposed_price" class="form-control bg-dark text-light border-secondary" placeholder="e.g. 15000" value="<?= e($existing_quote['proposed_price'] ?? '') ?>" required>
                            </div>
                            <div class="form-text extra-small text-muted">Total fee you are charging for this work.</div>
                        </div>

                        <!-- Estimated Duration -->
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Estimated Work Duration <span class="text-danger">*</span></label>
                            <input type="text" name="estimated_duration" class="form-control bg-dark text-light border-secondary" placeholder="e.g. 3 Days, 1 Week, 10 Working Days" value="<?= e($existing_quote['estimated_duration'] ?? '') ?>" required>
                        </div>

                        <!-- Proposed Start Date -->
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Earliest Available Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="proposed_start_date" class="form-control bg-dark text-light border-secondary" value="<?= e($existing_quote['proposed_start_date'] ?? date('Y-m-d')) ?>" min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Materials Terms -->
                        <div class="col-md-6">
                            <label class="form-label text-white small fw-bold">Materials Arrangement</label>
                            <select name="materials_included" class="form-select bg-dark text-light border-secondary">
                                <option value="labor_only" <?= ($existing_quote['materials_included'] ?? '') === 'labor_only' ? 'selected' : '' ?>>Labor Only (Client supplies all materials)</option>
                                <option value="included" <?= ($existing_quote['materials_included'] ?? '') === 'included' ? 'selected' : '' ?>>Materials Included in Proposed Price</option>
                                <option value="excluded" <?= ($existing_quote['materials_included'] ?? '') === 'excluded' ? 'selected' : '' ?>>Materials Billed Separately with Receipts</option>
                            </select>
                        </div>

                        <!-- Scope of Work -->
                        <div class="col-12">
                            <label class="form-label text-white small fw-bold">Your Proposed Scope of Work & Deliverables <span class="text-danger">*</span></label>
                            <textarea name="scope_of_work" rows="4" class="form-control bg-dark text-light border-secondary" placeholder="Detail exactly what services, steps, and preparation you will perform..." required><?= e($existing_quote['scope_of_work'] ?? '') ?></textarea>
                        </div>

                        <!-- Exclusions -->
                        <div class="col-12">
                            <label class="form-label text-white small fw-bold">Exclusions (What is NOT covered in this quote)</label>
                            <textarea name="exclusions" rows="2" class="form-control bg-dark text-light border-secondary" placeholder="e.g. Debris hauling off-site not included; electrical rewiring beyond switchboard is excluded."><?= e($existing_quote['exclusions'] ?? '') ?></textarea>
                        </div>

                        <!-- Notes -->
                        <div class="col-12">
                            <label class="form-label text-white small fw-bold">Additional Notes / Why Choose You</label>
                            <textarea name="notes" rows="2" class="form-control bg-dark text-light border-secondary" placeholder="Mention your warranty on work, team size, or certifications..."><?= e($existing_quote['notes'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-3 mt-4">
                        <a href="<?= BASE_URL ?>/worker/nearby-requests.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-amber fw-bold px-4 py-2">
                            <i class="fa-solid fa-paper-plane me-1"></i> <?= $existing_quote ? 'Update Quotation' : 'Submit Quotation' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
