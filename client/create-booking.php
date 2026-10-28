<?php
/**
 * BuildConnect — Client Booking Creation Form
 * Dual Booking System:
 *   Mode A: Instant Booking (Repairs, Urgent Direct Dispatch)
 *   Mode B: Compare Quotes (Renovations, Major Trades, RFQ Bidding)
 */

require_once __DIR__ . '/../includes/auth.php';
requireRole('client');

require_once __DIR__ . '/../includes/booking-functions.php';

$page_title = "Book a Service / Post Job - Client - BuildConnect";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$user = currentUser();
$db = getDB();
$client_id = (int)$user['id'];

// Fetch client profile defaults if available
$c_stmt = $db->prepare("SELECT address, city, state FROM clients WHERE user_id = ?");
$c_stmt->execute([$client_id]);
$client_profile = $c_stmt->fetch(PDO::FETCH_ASSOC);

$categories = get_service_categories();
$default_mode = ($_GET['mode'] ?? '') === 'quote' ? 'quote' : 'instant';

// Fetch skills list grouped by category
$skills_stmt = $db->query("SELECT id, name, category FROM skills ORDER BY category, name");
$all_skills = $skills_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch available verified workers for live suitable preview
$workers_stmt = $db->query("
    SELECT u.id as user_id, u.name, u.email, u.phone, u.avatar,
           w.id as worker_profile_id, w.trade_title, w.experience_years, w.hourly_rate, w.daily_rate,
           w.city, w.state, w.address, w.availability_status, w.verification_status,
           w.rating_avg, w.reviews_count, w.location_lat, w.location_lng
    FROM users u
    JOIN workers w ON w.user_id = u.id
    WHERE u.status = 'active'
      AND w.availability_status IN ('available', 'partially_available')
    ORDER BY w.rating_avg DESC, w.experience_years DESC
");
$available_workers = $workers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch skills per worker
$worker_skills_lookup = [];
$ws_stmt = $db->query("
    SELECT ws.worker_id, s.name as skill_name, s.category as skill_category
    FROM worker_skills ws
    JOIN skills s ON ws.skill_id = s.id
");
while ($ws = $ws_stmt->fetch(PDO::FETCH_ASSOC)) {
    $worker_skills_lookup[(int)$ws['worker_id']][] = $ws['skill_name'];
}

$error = '';
$flash = get_flash_message();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = "Security validation failed (CSRF token invalid). Please refresh.";
    } else {
        $booking_mode = sanitize($_POST['booking_mode'] ?? 'instant');
        if (!in_array($booking_mode, ['instant', 'quote'], true)) {
            $booking_mode = 'instant';
        }

        $category = sanitize($_POST['category'] ?? '');
        $title = trim(sanitize($_POST['title'] ?? ''));
        $description = trim(sanitize($_POST['description'] ?? ''));
        $address = trim(sanitize($_POST['address'] ?? ''));
        $city = trim(sanitize($_POST['city'] ?? 'Ahmedabad'));
        $state = trim(sanitize($_POST['state'] ?? 'Gujarat'));
        $preferred_date = sanitize($_POST['preferred_date'] ?? date('Y-m-d'));
        $preferred_time = sanitize($_POST['preferred_time'] ?? 'Anytime');
        $urgency = sanitize($_POST['urgency'] ?? 'medium');
        $matching_radius_km = max(1, min(50, (int)($_POST['matching_radius_km'] ?? 5)));
        
        $location_lat = !empty($_POST['location_lat']) ? (float)$_POST['location_lat'] : DEFAULT_LATITUDE;
        $location_lng = !empty($_POST['location_lng']) ? (float)$_POST['location_lng'] : DEFAULT_LONGITUDE;

        // Mode-specific fields
        $price = null;
        $budget_min = null;
        $budget_max = null;
        $duration_estimate = null;
        $materials_included = 0;
        $special_requirements = null;
        $assigned_worker_id = (int)($_POST['assigned_worker_id'] ?? 0);
        $required_skills = isset($_POST['required_skills']) && is_array($_POST['required_skills']) 
            ? array_map('sanitize', $_POST['required_skills']) 
            : [];

        if (!empty($required_skills)) {
            $skills_note = "Required Skills: " . implode(', ', $required_skills);
            $special_requirements = $skills_note;
        }

        if ($booking_mode === 'instant') {
            $price = (float)($_POST['price'] ?? 0);
            if (empty($title)) {
                $cat_label = $categories[$category]['label'] ?? 'Repair Service';
                $title = "Instant Request: {$cat_label}";
            }
        } else {
            $budget_min = !empty($_POST['budget_min']) ? (float)$_POST['budget_min'] : null;
            $budget_max = !empty($_POST['budget_max']) ? (float)$_POST['budget_max'] : null;
            $duration_estimate = trim(sanitize($_POST['duration_estimate'] ?? ''));
            $materials_included = !empty($_POST['materials_included']) ? 1 : 0;
            $spec_req = trim(sanitize($_POST['special_requirements'] ?? ''));
            if ($spec_req) {
                $special_requirements = !empty($special_requirements) ? ($special_requirements . "\n\n" . $spec_req) : $spec_req;
            }
        }

        // Handle Optional Photo Uploads
        $uploaded_photos = [];
        if (!empty($_FILES['photos']['name'][0])) {
            $upload_dir = ROOT_PATH . '/uploads/bookings';
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }

            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
            $file_count = count($_FILES['photos']['name']);

            for ($i = 0; $i < min(5, $file_count); $i++) {
                if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['photos']['tmp_name'][$i];
                    $ext = strtolower(pathinfo($_FILES['photos']['name'][$i], PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed_exts, true) && $_FILES['photos']['size'][$i] <= (5 * 1024 * 1024)) {
                        $new_filename = 'bk_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        $destination = $upload_dir . '/' . $new_filename;
                        if (move_uploaded_file($tmp_name, $destination)) {
                            $uploaded_photos[] = 'uploads/bookings/' . $new_filename;
                        }
                    }
                }
            }
        }
        $photos_json = !empty($uploaded_photos) ? json_encode($uploaded_photos) : null;

        // Validation Rules
        if (empty($category) || !isset($categories[$category])) {
            $error = "Please select a valid service category.";
        } elseif (empty($title)) {
            $error = "Please provide a clear title for the job request.";
        } elseif (empty($description)) {
            $error = "Please describe the work required.";
        } elseif (empty($address)) {
            $error = "Please specify the site or home address.";
        } elseif ($booking_mode === 'instant' && $price <= 0) {
            $error = "Please propose a valid payment price in INR (greater than ₹0).";
        } elseif ($booking_mode === 'quote' && $budget_min !== null && $budget_max !== null && $budget_min > $budget_max) {
            $error = "Minimum budget cannot exceed maximum budget.";
        } else {
            try {
                $db->beginTransaction();

                $booking_number = generate_booking_number();
                $provider_id = null;
                $provider_type = null;
                $initial_status = ($booking_mode === 'instant') ? 'matching' : 'posted';

                if ($booking_mode === 'instant' && $assigned_worker_id > 0) {
                    $chk_w = $db->prepare("SELECT id FROM users WHERE id = ? AND role = 'worker' AND status = 'active'");
                    $chk_w->execute([$assigned_worker_id]);
                    if ($chk_w->fetchColumn()) {
                        $provider_id = $assigned_worker_id;
                        $provider_type = 'worker';
                        $initial_status = 'posted'; // Pending acceptance by assigned worker
                    }
                }

                $stmt = $db->prepare("
                    INSERT INTO bookings (
                        booking_number, client_id, provider_id, provider_type, booking_mode, category, title, description,
                        address, city, state, location_lat, location_lng, matching_radius_km,
                        preferred_date, preferred_time, urgency, price, budget_min, budget_max,
                        duration_estimate, materials_included, special_requirements, photos_json, status, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, NOW()
                    )
                ");

                $stmt->execute([
                    $booking_number, $client_id, $provider_id, $provider_type, $booking_mode, $category, $title, $description,
                    $address, $city, $state, $location_lat, $location_lng, $matching_radius_km,
                    $preferred_date, $preferred_time, $urgency, $price, $budget_min, $budget_max,
                    $duration_estimate, $materials_included, $special_requirements, $photos_json, $initial_status
                ]);

                $booking_id = (int)$db->lastInsertId();

                // Notification dispatch
                if ($booking_mode === 'instant') {
                    if (!empty($provider_id)) {
                        create_notification(
                            $provider_id,
                            "Direct Booking Request ({$urgency})",
                            "Client '{$user['name']}' requested you for {$title} (" . format_currency($price) . ").",
                            'INSTANT_REQUEST',
                            "/worker/my-bookings.php",
                            'booking',
                            $booking_id
                        );
                        log_activity($client_id, 'DIRECT_BOOKING_CREATED', "Sent direct instant booking #{$booking_number} to worker #{$provider_id}", 'booking', $booking_id);
                    } else {
                        // Match workers in radius and notify them
                        $matched_workers = get_matching_workers_for_booking($booking_id, $matching_radius_km);
                        foreach ($matched_workers as $mw) {
                            create_notification(
                                (int)$mw['user_id'],
                                "Nearby Urgent Request ({$urgency})",
                                "New Instant Booking: {$title} (" . format_currency($price) . ") within your service area.",
                                'INSTANT_REQUEST',
                                "/worker/nearby-requests.php",
                                'booking',
                                $booking_id
                            );
                        }
                        log_activity($client_id, 'INSTANT_BOOKING_CREATED', "Posted Instant Booking #{$booking_number} for {$title} (" . count($matched_workers) . " workers notified)", 'booking', $booking_id);
                    }
                } else {
                    // Mode B: Compare Quotes - Notify relevant contractors and workers
                    $c_workers = get_matching_workers_for_booking($booking_id, 25);
                    foreach ($c_workers as $cw) {
                        create_notification(
                            (int)$cw['user_id'],
                            "New Request for Quotation",
                            "Client requested bids for {$title} in {$city}.",
                            'QUOTE_REQUEST',
                            "/worker/nearby-requests.php",
                            'booking',
                            $booking_id
                        );
                    }
                    log_activity($client_id, 'QUOTE_REQUEST_CREATED', "Created Compare Quotes request #{$booking_number} for {$title}", 'booking', $booking_id);
                }

                $db->commit();
                set_flash_message("Booking #{$booking_number} posted successfully!", "success");
                redirect("client/booking-details.php?id={$booking_id}");

            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log("Failed to create booking: " . $e->getMessage());
                $error = "Failed to submit booking due to a database error.";
            }
        }
    }
}
?>

<div class="bc-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="bc-main-content">
        <div class="container-fluid px-0">
            <!-- Header Banner -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h1 class="h2 fw-bold text-white mb-1">
                        <i class="fa-solid fa-calendar-plus text-warning me-2"></i>Book a Service or Request Quotes
                    </h1>
                    <p class="text-muted small mb-0">Choose between immediate instant dispatch or multi-quote bidding for larger renovation work.</p>
                </div>
                <a href="<?= BASE_URL ?>/client/bookings.php" class="btn btn-outline-light btn-sm">
                    <i class="fa-solid fa-list-check me-1"></i> View My Bookings
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

            <form action="<?= BASE_URL ?>/client/create-booking.php" method="POST" enctype="multipart/form-data" id="bookingForm" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="booking_mode" id="selectedModeInput" value="instant">

                <!-- STEP 1: CHOOSE BOOKING MODE -->
                <div class="bc-card p-4 mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-amber text-dark fw-bold rounded-pill px-2.5 py-1">STEP 1</span>
                        <h2 class="h5 fw-bold text-dark mb-0">Select Your Booking Mode</h2>
                    </div>

                    <div class="row g-3">
                        <!-- Mode A Card -->
                        <div class="col-md-6">
                            <div class="bc-card border-2 p-3.5 h-100 cursor-pointer mode-card active" id="modeCardInstant" onclick="selectMode('instant')">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="stat-icon bc-metric-icon-orange p-3 rounded-circle">
                                        <i class="fa-solid fa-bolt-lightning fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h3 class="h6 fw-bold text-dark mb-0">Mode A: Instant Booking</h3>
                                            <span class="badge bg-warning text-dark extra-small fw-bold">FAST DISPATCH</span>
                                        </div>
                                        <p class="text-secondary extra-small mb-2">Ideal for quick repairs, leaks, electrical faults, and emergency maintenance. Matched to nearby verified workers within your radius.</p>
                                        <ul class="list-unstyled extra-small text-secondary mb-0 d-flex flex-column gap-1">
                                            <li><i class="fa-solid fa-check text-warning me-1.5"></i> Set your proposed fixed rate (₹ INR)</li>
                                            <li><i class="fa-solid fa-check text-warning me-1.5"></i> Single-worker instant confirmation lock</li>
                                            <li><i class="fa-solid fa-check text-warning me-1.5"></i> Real-time radius proximity dispatch (3–10 km)</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mode B Card -->
                        <div class="col-md-6">
                            <div class="bc-card border p-3.5 h-100 cursor-pointer mode-card" id="modeCardQuote" onclick="selectMode('quote')">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="stat-icon bc-metric-icon-blue p-3 rounded-circle">
                                        <i class="fa-solid fa-scale-balanced fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h3 class="h6 fw-bold text-dark mb-0">Mode B: Compare Quotes</h3>
                                            <span class="badge bg-primary-subtle text-primary extra-small fw-bold">BIDDING / RFQ</span>
                                        </div>
                                        <p class="text-secondary extra-small mb-2">Ideal for home renovations, full painting, masonry, tiling, and complex multi-stage construction projects.</p>
                                        <ul class="list-unstyled extra-small text-secondary mb-0 d-flex flex-column gap-1">
                                            <li><i class="fa-solid fa-check text-primary me-1.5"></i> Receive competitive quotes from workers & contractors</li>
                                            <li><i class="fa-solid fa-check text-primary me-1.5"></i> Compare prices, scopes, materials, ratings, and timelines</li>
                                            <li><i class="fa-solid fa-check text-primary me-1.5"></i> Accept best offer with atomic dispute protection</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: CATEGORY & GENERAL DETAILS -->
                <div class="bc-card p-4 mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-amber text-dark fw-bold rounded-pill px-2.5 py-1">STEP 2</span>
                        <h2 class="h5 text-dark fw-bold mb-0">Service Category & Basic Scope</h2>
                    </div>

                    <div class="row g-3">
                        <!-- Category Selector -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Select Trade / Category <span class="text-danger">*</span></label>
                            <select name="category" id="categorySelect" class="form-select" required onchange="handleCategoryChange(this.value)">
                                <option value="">-- Choose Category --</option>
                                <?php foreach ($categories as $key => $cat): ?>
                                    <option value="<?= e($key) ?>" data-rec="<?= e($cat['recommended_mode']) ?>">
                                        <?= e($cat['label']) ?> (Recommended: <?= ucfirst($cat['recommended_mode']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text extra-small text-muted" id="categoryHint">Selecting a category will auto-highlight the recommended mode, but you may change it anytime.</div>
                        </div>

                        <!-- Title -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Job Title / Summary <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="jobTitleInput" class="form-control" placeholder="e.g. Master Bathroom Shower Leak & Pipe Repair" required>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label small fw-bold">Detailed Description & Symptoms <span class="text-danger">*</span></label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Provide full details of the issue, what tools or materials may be needed, and specific instructions." required></textarea>
                        </div>

                        <!-- Required Skills Multi-select -->
                        <div class="col-12">
                            <label class="form-label small fw-bold">
                                <i class="fa-solid fa-screwdriver-wrench text-warning me-1"></i>Required Skills (Select All That Apply)
                            </label>
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="row g-2">
                                    <?php foreach ($all_skills as $sk): ?>
                                        <div class="col-sm-6 col-md-4 col-lg-3">
                                            <div class="form-check extra-small">
                                                <input class="form-check-input" type="checkbox" name="required_skills[]" value="<?= e($sk['name']) ?>" id="skill_<?= $sk['id'] ?>">
                                                <label class="form-check-label text-dark" for="skill_<?= $sk['id'] ?>">
                                                    <?= e($sk['name']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="form-text extra-small text-muted mt-2">Selecting required skills helps BuildConnect accurately match workers with verified proficiency in these trades.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: LOCATION & SCHEDULE -->
                <div class="bc-card p-4 mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-amber text-dark fw-bold rounded-pill px-2.5 py-1">STEP 3</span>
                        <h2 class="h5 text-dark fw-bold mb-0">Site Address & Schedule</h2>
                    </div>

                    <div class="row g-3">
                        <!-- Address -->
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Street Address / Landmark <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control" value="<?= e($client_profile['address'] ?? '') ?>" placeholder="Flat/House No., Street, Landmark" required>
                        </div>

                        <!-- City -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">City</label>
                            <input type="text" name="city" class="form-control" value="<?= e($client_profile['city'] ?? 'Ahmedabad') ?>">
                        </div>

                        <!-- Date -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Preferred Date <span class="text-danger">*</span></label>
                            <input type="date" name="preferred_date" class="form-control" value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Preferred Time Window -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Preferred Time Window</label>
                            <select name="preferred_time" class="form-select">
                                <option value="Immediately (Within 2 Hours)">Immediately (Within 2 Hours)</option>
                                <option value="Morning (9 AM - 12 PM)" selected>Morning (9 AM - 12 PM)</option>
                                <option value="Afternoon (12 PM - 4 PM)">Afternoon (12 PM - 4 PM)</option>
                                <option value="Evening (4 PM - 8 PM)">Evening (4 PM - 8 PM)</option>
                                <option value="Flexible / Full Day">Flexible / Full Day</option>
                            </select>
                        </div>

                        <!-- Urgency -->
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Urgency Level</label>
                            <select name="urgency" class="form-select">
                                <option value="low">Low (Standard Scheduling)</option>
                                <option value="medium" selected>Medium (Within 24 Hours)</option>
                                <option value="high">High (Same Day Urgent)</option>
                                <option value="urgent">Emergency (Need ASAP)</option>
                            </select>
                        </div>

                        <!-- Geolocation Pinning -->
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Map Coordinates (Optional / Fallback)</label>
                            <div class="input-group">
                                <span class="input-group-text extra-small">Lat</span>
                                <input type="number" step="0.0000001" name="location_lat" id="latInput" value="<?= DEFAULT_LATITUDE ?>" class="form-control">
                                <span class="input-group-text extra-small">Lng</span>
                                <input type="number" step="0.0000001" name="location_lng" id="lngInput" value="<?= DEFAULT_LONGITUDE ?>" class="form-control">
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="detectUserLocation()" title="Use Current Device Location">
                                    <i class="fa-solid fa-location-crosshairs"></i> Detect
                                </button>
                            </div>
                            <div class="form-text extra-small text-muted" id="geoFeedback">Default coordinates set to Ahmedabad city center.</div>
                        </div>

                        <!-- Radius (For Instant Mode) -->
                        <div class="col-md-6" id="radiusGroup">
                            <label class="form-label small fw-bold">Worker Matching Radius</label>
                            <select name="matching_radius_km" class="form-select">
                                <option value="3">3 Kilometers (Tight Neighborhood)</option>
                                <option value="5" selected>5 Kilometers (Standard City Radius)</option>
                                <option value="10">10 Kilometers (Extended Metro Area)</option>
                                <option value="25">25 Kilometers (Whole District)</option>
                            </select>
                            <div class="form-text extra-small text-muted">Radius will expand automatically if no matching worker claims the job.</div>
                        </div>
                    </div>
                </div>

                <!-- STEP 4: MODE-SPECIFIC PRICING & SCOPE -->
                <!-- Mode A: Instant Booking Fields -->
                <div class="bc-card p-4 mb-4" id="instantFieldsGroup">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-amber text-dark fw-bold rounded-pill px-2.5 py-1">STEP 4</span>
                        <h2 class="h5 text-dark fw-bold mb-0">Instant Booking Terms</h2>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Your Proposed Price (₹ INR) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text text-warning fw-bold">₹</span>
                                <input type="number" step="10" name="price" id="instantPriceInput" class="form-control" placeholder="e.g. 500" value="500">
                            </div>
                            <div class="form-text extra-small text-muted">Fixed amount offered to the first worker who accepts this repair booking.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Attach Photos of Damage / Location (Optional)</label>
                            <input type="file" name="photos[]" multiple accept="image/png, image/jpeg, image/webp" class="form-control">
                            <div class="form-text extra-small text-muted">Up to 5 images (JPEG, PNG, WebP, max 5MB each).</div>
                        </div>

                        <!-- Suitable Available Workers Nearby Section -->
                        <div class="col-12 mt-2">
                            <label class="form-label small fw-bold text-dark">
                                <i class="fa-solid fa-users text-warning me-1"></i>Suitable Workers (Based on Skills, Availability & Location)
                            </label>
                            <div class="p-3 bg-light rounded-3 border">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="assigned_worker_id" value="0" id="worker_auto" checked>
                                    <label class="form-check-label fw-bold text-dark small" for="worker_auto">
                                        <i class="fa-solid fa-bolt text-warning me-1"></i> Auto-Match & Broadcast to Nearest Available Workers
                                        <span class="text-muted fw-normal d-block extra-small">Request will be broadcast instantly to all matching verified workers in your radius. The first to claim is booked.</span>
                                    </label>
                                </div>

                                <div class="fw-bold text-muted extra-small text-uppercase mb-2">Or Assign Directly to an Available Verified Worker:</div>
                                <div class="row g-2">
                                    <?php foreach ($available_workers as $aw): 
                                        $skills_list = $worker_skills_lookup[$aw['worker_profile_id']] ?? [];
                                    ?>
                                        <div class="col-md-6">
                                            <div class="bc-card p-2.5 h-100 border">
                                                <div class="form-check d-flex align-items-start gap-2">
                                                    <input class="form-check-input mt-1" type="radio" name="assigned_worker_id" value="<?= $aw['user_id'] ?>" id="worker_<?= $aw['user_id'] ?>">
                                                    <label class="form-check-label w-100 cursor-pointer" for="worker_<?= $aw['user_id'] ?>">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <div class="fw-bold text-dark small"><?= e($aw['name']) ?></div>
                                                                <div class="extra-small text-muted"><?= e($aw['trade_title']) ?> &bull; <?= (int)$aw['experience_years'] ?> yrs exp</div>
                                                            </div>
                                                            <span class="badge bg-success-subtle text-success extra-small fw-bold">Available</span>
                                                        </div>
                                                        <div class="extra-small text-muted my-1">
                                                            <i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($aw['city']) ?>
                                                            &bull; <?= render_star_rating($aw['rating_avg'] ?: 5.0) ?>
                                                        </div>
                                                        <?php if (!empty($skills_list)): ?>
                                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                                <?php foreach (array_slice($skills_list, 0, 3) as $skl): ?>
                                                                    <span class="badge bg-secondary-subtle text-secondary extra-small py-0.5 px-1.5"><?= e($skl) ?></span>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mode B: Compare Quotes Fields -->
                <div class="bc-card p-4 mb-4 d-none" id="quoteFieldsGroup">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-info text-dark fw-bold rounded-pill px-2.5 py-1">STEP 4</span>
                        <h2 class="h5 text-dark fw-bold mb-0">Quotation Request Details</h2>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Budget Min (₹ INR)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="100" name="budget_min" class="form-control" placeholder="e.g. 10000">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Budget Max (₹ INR)</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="100" name="budget_max" class="form-control" placeholder="e.g. 25000">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Expected Project Duration</label>
                            <input type="text" name="duration_estimate" class="form-control" placeholder="e.g. 3-5 days, 2 weeks, 1 month">
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="materials_included" value="1" id="materialsSwitch">
                                <label class="form-check-label small fw-bold" for="materialsSwitch">
                                    Require Provider to Supply All Construction Materials
                                </label>
                            </div>
                            <div class="form-text extra-small text-muted">Leave unchecked if you are providing the paints, tiles, cement, or fixtures yourself.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Attach Site Plans / Architectural Photos (Optional)</label>
                            <input type="file" name="photos[]" multiple accept="image/png, image/jpeg, image/webp" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Special Requirements & Exclusions</label>
                            <textarea name="special_requirements" rows="2" class="form-control" placeholder="e.g. Work must be conducted only between 10 AM and 5 PM; cleanup required daily."></textarea>
                        </div>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="d-flex justify-content-end gap-3 mb-5">
                    <a href="<?= BASE_URL ?>/client/bookings.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-amber fw-bold px-4 py-2.5" id="submitBtn">
                        <i class="fa-solid fa-paper-plane me-2"></i> Publish Job Request
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<style>
.mode-card {
    transition: all 0.25s ease-in-out;
}
.mode-card:hover {
    border-color: #FFAA16 !important;
    transform: translateY(-2px);
}
.mode-card.active {
    border-color: #FFAA16 !important;
    background: rgba(255, 170, 22, 0.05) !important;
}
</style>

<script>
function selectMode(mode) {
    document.getElementById('selectedModeInput').value = mode;

    const cardInstant = document.getElementById('modeCardInstant');
    const cardQuote = document.getElementById('modeCardQuote');
    const instantGroup = document.getElementById('instantFieldsGroup');
    const quoteGroup = document.getElementById('quoteFieldsGroup');
    const submitBtn = document.getElementById('submitBtn');

    if (mode === 'instant') {
        cardInstant.classList.add('active', 'border-warning');
        cardInstant.classList.remove('border-secondary');
        cardQuote.classList.remove('active', 'border-info');
        cardQuote.classList.add('border-secondary');

        instantGroup.classList.remove('d-none');
        quoteGroup.classList.add('d-none');
        document.getElementById('instantPriceInput').setAttribute('required', 'required');
        submitBtn.innerHTML = '<i class="fa-solid fa-bolt me-2"></i> Post Instant Booking (Find Nearby Worker)';
    } else {
        cardQuote.classList.add('active', 'border-info');
        cardQuote.classList.remove('border-secondary');
        cardInstant.classList.remove('active', 'border-warning');
        cardInstant.classList.add('border-secondary');

        instantGroup.classList.add('d-none');
        quoteGroup.classList.remove('d-none');
        document.getElementById('instantPriceInput').removeAttribute('required');
        submitBtn.innerHTML = '<i class="fa-solid fa-scale-balanced me-2"></i> Request Quotations from Providers';
    }
}

function handleCategoryChange(catValue) {
    const sel = document.getElementById('categorySelect');
    const opt = sel.options[sel.selectedIndex];
    const rec = opt ? opt.getAttribute('data-rec') : null;

    if (rec) {
        selectMode(rec);
        const hint = document.getElementById('categoryHint');
        if (hint) {
            hint.innerHTML = '<span class="text-warning fw-bold"><i class="fa-solid fa-wand-magic-sparkles me-1"></i> Auto-recommended: ' + (rec === 'instant' ? 'Mode A (Instant Booking)' : 'Mode B (Compare Quotes)') + '</span> (You can still toggle modes above)';
        }
    }
}

function detectUserLocation() {
    const feedback = document.getElementById('geoFeedback');
    if (!navigator.geolocation) {
        feedback.innerHTML = '<span class="text-danger"><i class="fa-solid fa-circle-exclamation me-1"></i> Geolocation is not supported by your browser.</span>';
        return;
    }

    feedback.innerHTML = '<span class="text-warning"><i class="fa-solid fa-spinner fa-spin me-1"></i> Detecting your coordinates...</span>';

    navigator.geolocation.getCurrentPosition(
        function(pos) {
            document.getElementById('latInput').value = pos.coords.latitude.toFixed(7);
            document.getElementById('lngInput').value = pos.coords.longitude.toFixed(7);
            feedback.innerHTML = '<span class="text-success"><i class="fa-solid fa-check me-1"></i> GPS Location detected: ' + pos.coords.latitude.toFixed(4) + ', ' + pos.coords.longitude.toFixed(4) + '</span>';
        },
        function(err) {
            feedback.innerHTML = '<span class="text-danger"><i class="fa-solid fa-circle-xmark me-1"></i> Geolocation permission denied or unavailable. Using default city coordinates.</span>';
        },
        { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
    );
}

document.addEventListener('DOMContentLoaded', function() {
    selectMode('<?= e($default_mode) ?>');
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
