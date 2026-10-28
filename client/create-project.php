<?php
/**
 * BuildConnect — Client Create Construction Project (Compare Quotes)
 * Allows clients to post larger construction projects with scope, location, budget, and timeline,
 * requesting commercial quotations from eligible licensed contractors.
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

require_once __DIR__ . '/../includes/booking-functions.php';

$page_title = "Post Construction Project - Client - BuildConnect";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$user = currentUser();
$db = getDB();
$client_id = (int)$user['id'];

// Fetch client profile defaults
$c_stmt = $db->prepare("SELECT address, city, state FROM clients WHERE user_id = ?");
$c_stmt->execute([$client_id]);
$client_profile = $c_stmt->fetch(PDO::FETCH_ASSOC);

$categories = get_service_categories();
$error = '';
$flash = get_flash_message();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = "Security validation failed (CSRF token invalid). Please refresh.";
    } else {
        $category = sanitize($_POST['category'] ?? 'renovation');
        $title = trim(sanitize($_POST['title'] ?? ''));
        $description = trim(sanitize($_POST['description'] ?? ''));
        $address = trim(sanitize($_POST['address'] ?? ''));
        $city = trim(sanitize($_POST['city'] ?? 'Ahmedabad'));
        $state = trim(sanitize($_POST['state'] ?? 'Gujarat'));
        $preferred_date = sanitize($_POST['preferred_date'] ?? date('Y-m-d'));
        $preferred_time = sanitize($_POST['preferred_time'] ?? 'Flexible / Full Day');
        $urgency = sanitize($_POST['urgency'] ?? 'medium');
        $budget_min = (float)($_POST['budget_min'] ?? 0);
        $budget_max = (float)($_POST['budget_max'] ?? 0);
        $duration_estimate = trim(sanitize($_POST['duration_estimate'] ?? ''));
        $materials_included = !empty($_POST['materials_included']) ? 1 : 0;
        $special_requirements = trim(sanitize($_POST['special_requirements'] ?? ''));
        
        $location_lat = !empty($_POST['location_lat']) ? (float)$_POST['location_lat'] : DEFAULT_LATITUDE;
        $location_lng = !empty($_POST['location_lng']) ? (float)$_POST['location_lng'] : DEFAULT_LONGITUDE;

        // Validation Rules
        if (empty($title)) {
            $error = "Please provide a descriptive project title.";
        } elseif (empty($description)) {
            $error = "Please provide the comprehensive scope of work for this construction project.";
        } elseif (empty($address)) {
            $error = "Please provide the site address.";
        } elseif ($budget_min <= 0 || $budget_max <= 0) {
            $error = "Please specify a valid budget range in INR (greater than ₹0).";
        } elseif ($budget_min > $budget_max) {
            $error = "Minimum budget cannot exceed maximum budget.";
        } elseif (empty($duration_estimate)) {
            $error = "Please specify the expected timeline / duration (e.g. 2 months, 6 months).";
        } else {
            try {
                $db->beginTransaction();

                $booking_number = generate_booking_number();

                $stmt = $db->prepare("
                    INSERT INTO bookings (
                        booking_number, client_id, booking_mode, category, title, description,
                        address, city, state, location_lat, location_lng, matching_radius_km,
                        preferred_date, preferred_time, urgency, budget_min, budget_max,
                        duration_estimate, materials_included, special_requirements, status, created_at
                    ) VALUES (
                        ?, ?, 'quote', ?, ?, ?,
                        ?, ?, ?, ?, ?, 30,
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, 'posted', NOW()
                    )
                ");

                $stmt->execute([
                    $booking_number, $client_id, $category, $title, $description,
                    $address, $city, $state, $location_lat, $location_lng,
                    $preferred_date, $preferred_time, $urgency, $budget_min, $budget_max,
                    $duration_estimate, $materials_included, $special_requirements
                ]);

                $booking_id = (int)$db->lastInsertId();

                // Notify all active contractors in the area
                $contractors_stmt = $db->query("SELECT id FROM users WHERE role = 'contractor' AND status = 'active'");
                $contractor_count = 0;
                while ($c_row = $contractors_stmt->fetch(PDO::FETCH_ASSOC)) {
                    create_notification(
                        (int)$c_row['id'],
                        "New Construction Project Open for Bids",
                        "Client '{$user['name']}' posted project '{$title}' in {$city} (" . format_currency($budget_min) . " - " . format_currency($budget_max) . ").",
                        'QUOTE_REQUEST',
                        "/contractor/quote-requests.php",
                        'booking',
                        $booking_id
                    );
                    $contractor_count++;
                }

                log_activity($client_id, 'PROJECT_RFQ_CREATED', "Posted construction project RFQ #{$booking_number} for '{$title}' ({$contractor_count} contractors notified)", 'booking', $booking_id);

                $db->commit();
                set_flash_message("Construction project '{$title}' posted successfully! Contractors have been invited to submit quotations.", "success");
                redirect("client/booking-details.php?id={$booking_id}");

            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log("Failed to post project: " . $e->getMessage());
                $error = "Failed to submit project request due to a database error.";
            }
        }
    }
}
?>

<div class="bc-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="bc-main-content">
        <div class="container-fluid px-0">
            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h2 fw-bold text-dark mb-1">
                        <i class="fa-solid fa-building text-warning me-2"></i>Post Construction Project (Compare Quotes)
                    </h1>
                    <p class="text-muted small mb-0">Publish large-scale residential or commercial construction projects to receive competitive quotations from licensed contractors.</p>
                </div>
                <a href="<?= BASE_URL ?>/client/projects.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left me-1"></i> Back to Projects
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><?= e($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>/client/create-project.php" method="POST" class="needs-validation" novalidate>
                <?= csrf_field() ?>

                <!-- SECTION 1: PROJECT SCOPE -->
                <div class="bc-card p-4 mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-amber text-dark fw-bold rounded-pill px-2.5 py-1">STEP 1</span>
                        <h2 class="h5 fw-bold text-dark mb-0">Project Scope & Trade Category</h2>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Project Trade Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="renovation" selected>Full Home / Commercial Renovation</option>
                                <option value="masonry">Masonry & Concrete Works</option>
                                <option value="painting">Painting & Wall Coatings</option>
                                <option value="flooring">Flooring & Tiling</option>
                                <option value="electrical">Commercial Electrical Works</option>
                                <option value="plumbing">Major Plumbing Infrastructure</option>
                                <option value="welding">Structural Steel & Metal Works</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Project Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. 3-BHK Villa Renovation & Structural Extension" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Comprehensive Scope of Work & Specifications <span class="text-danger">*</span></label>
                            <textarea name="description" rows="4" class="form-control" placeholder="Detail architectural drawings, demolition, structural additions, electrical conduit work, finishing standards, and project expectations..." required></textarea>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: SITE LOCATION & TIMELINE -->
                <div class="bc-card p-4 mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-amber text-dark fw-bold rounded-pill px-2.5 py-1">STEP 2</span>
                        <h2 class="h5 fw-bold text-dark mb-0">Site Location & Timeline</h2>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Site Address / Plot Landmark <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control" value="<?= e($client_profile['address'] ?? '') ?>" placeholder="Plot No., Scheme, Street, Landmark" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">City</label>
                            <input type="text" name="city" class="form-control" value="<?= e($client_profile['city'] ?? 'Ahmedabad') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Earliest Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="preferred_date" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Expected Completion Duration <span class="text-danger">*</span></label>
                            <input type="text" name="duration_estimate" class="form-control" placeholder="e.g. 45 Days, 3 Months, 6 Months" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Urgency / Timeline Priority</label>
                            <select name="urgency" class="form-select">
                                <option value="low">Flexible Timeline</option>
                                <option value="medium" selected>Standard Construction Timeline</option>
                                <option value="high">Urgent / Priority Execution</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3: BUDGET & TERMS -->
                <div class="bc-card p-4 mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-amber text-dark fw-bold rounded-pill px-2.5 py-1">STEP 3</span>
                        <h2 class="h5 fw-bold text-dark mb-0">Budget in INR (₹) & Contractor Terms</h2>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Estimated Minimum Budget (₹ INR) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text text-warning fw-bold">₹</span>
                                <input type="number" step="500" name="budget_min" class="form-control" placeholder="e.g. 50000" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Estimated Maximum Budget (₹ INR) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text text-warning fw-bold">₹</span>
                                <input type="number" step="500" name="budget_max" class="form-control" placeholder="e.g. 150000" required>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="materials_included" value="1" id="materialsSwitch">
                                <label class="form-check-label small fw-bold" for="materialsSwitch">
                                    Contractor Must Include All Structural & Finishing Materials in Quote
                                </label>
                            </div>
                            <div class="form-text extra-small text-muted">Leave unchecked if you will procure construction materials directly and only require labor & contracting management.</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Special Contract Requirements, Working Hours or Exclusions</label>
                            <textarea name="special_requirements" rows="2" class="form-control" placeholder="e.g. Contractor must be GST registered; payment milestones tied to 25%, 50%, 75% stage audits; site cleanup mandatory weekly."></textarea>
                        </div>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="d-flex justify-content-end gap-3 mb-5">
                    <a href="<?= BASE_URL ?>/client/projects.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-amber fw-bold px-4 py-2.5">
                        <i class="fa-solid fa-paper-plane me-2"></i> Publish Project & Request Contractor Quotations
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
