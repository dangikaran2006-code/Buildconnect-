<?php
/**
 * BuildConnect — Dual Booking Engine Core Functions
 * Implements Instant Booking & Compare Quotes logic, Haversine geospatial matching,
 * concurrency-safe acceptance transactions, and state machine transitions.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Calculate distance between two GPS coordinates using the Haversine formula (in kilometers).
 */
function calculate_haversine_distance($lat1, $lon1, $lat2, $lon2) {
    if (!is_numeric($lat1) || !is_numeric($lon1) || !is_numeric($lat2) || !is_numeric($lon2)) {
        return null;
    }
    $earth_radius = 6371.0; // km

    $dLat = deg2rad((float)$lat2 - (float)$lat1);
    $dLon = deg2rad((float)$lon2 - (float)$lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad((float)$lat1)) * cos(deg2rad((float)$lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earth_radius * $c, 2);
}

/**
 * Generate a unique human-friendly booking tracking number (e.g. BK-2026-000001).
 */
function generate_booking_number() {
    $db = getDB();
    $year = date('Y');
    do {
        $stmt = $db->query("SELECT MAX(id) as max_id FROM bookings");
        $max_id = (int)$stmt->fetchColumn();
        $next = $max_id + 1;
        $num = 'BK-' . $year . '-' . str_pad($next, 6, '0', STR_PAD_LEFT);

        $chk = $db->prepare("SELECT COUNT(*) FROM bookings WHERE booking_number = ?");
        $chk->execute([$num]);
        $exists = (int)$chk->fetchColumn();
        if (!$exists) {
            return $num;
        }
        $next++;
    } while ($exists);

    return 'BK-' . $year . '-' . str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Provide sensible default recommendation for Booking Mode based on service category.
 * Note: Clients are free to toggle between Instant Booking and Compare Quotes regardless.
 */
function get_recommended_booking_mode($category) {
    $category = strtolower(trim((string)$category));
    $instant_categories = [
        'plumbing_repair', 'electrical_fix', 'ac_service', 'carpentry_repair', 
        'leak_detection', 'door_lock_repair', 'drain_cleaning', 'small_welding', 
        'switchboard_repair', 'tap_faucet_fix', 'emergency_repair', 'handyman'
    ];

    foreach ($instant_categories as $ic) {
        if (strpos($category, $ic) !== false || strpos($ic, $category) !== false) {
            return 'instant';
        }
    }
    return 'quote';
}

/**
 * Return list of standard service categories with display metadata.
 */
function get_service_categories() {
    return [
        'plumbing' => [
            'label' => 'Plumbing & Pipe Repairs',
            'icon' => 'fa-faucet-drip',
            'recommended_mode' => 'instant',
            'description' => 'Leak repairs, tap replacements, drain blocks, toilet fixes'
        ],
        'electrical' => [
            'label' => 'Electrical Wiring & Fixes',
            'icon' => 'fa-bolt',
            'recommended_mode' => 'instant',
            'description' => 'Short circuits, switchboard repairs, lighting, fan install'
        ],
        'carpentry' => [
            'label' => 'Carpentry & Woodwork',
            'icon' => 'fa-hammer',
            'recommended_mode' => 'instant',
            'description' => 'Door fixes, furniture assembly, hinges, lock changes'
        ],
        'painting' => [
            'label' => 'Painting & Wall Coatings',
            'icon' => 'fa-paint-roller',
            'recommended_mode' => 'quote',
            'description' => 'Interior/exterior painting, waterproofing, texture coating'
        ],
        'masonry' => [
            'label' => 'Masonry & Concrete Works',
            'icon' => 'fa-trowel-bricks',
            'recommended_mode' => 'quote',
            'description' => 'Brickwork, plastering, tile laying, boundary walls'
        ],
        'renovation' => [
            'label' => 'Full Home / Commercial Renovation',
            'icon' => 'fa-house-chimney-crack',
            'recommended_mode' => 'quote',
            'description' => 'Kitchen remodels, bathroom revamps, structural renovation'
        ],
        'welding' => [
            'label' => 'Welding & Metal Fabrication',
            'icon' => 'fa-fire-burner',
            'recommended_mode' => 'instant',
            'description' => 'Iron gates, grills, railings, on-site metal fabrication'
        ],
        'flooring' => [
            'label' => 'Flooring & Tiling',
            'icon' => 'fa-border-all',
            'recommended_mode' => 'quote',
            'description' => 'Marble polishing, ceramic tiles, wooden laminate'
        ]
    ];
}

/**
 * Find matching workers for an Instant Booking based on category, skills, radius, and availability.
 */
function get_matching_workers_for_booking($booking_id, $override_radius_km = null) {
    $db = getDB();
    $b_stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
    $b_stmt->execute([(int)$booking_id]);
    $booking = $b_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) return [];

    $b_lat = (float)($booking['location_lat'] ?? DEFAULT_LATITUDE);
    $b_lng = (float)($booking['location_lng'] ?? DEFAULT_LONGITUDE);
    $radius_km = $override_radius_km ? (float)$override_radius_km : (float)($booking['matching_radius_km'] ?? 5);
    $category = strtolower($booking['category']);

    // Retrieve active workers who are marked available
    $sql = "
        SELECT u.id as user_id, u.name, u.email, u.phone, u.avatar,
               w.id as worker_profile_id, w.trade_title, w.experience_years, w.hourly_rate, w.daily_rate,
               w.city, w.state, w.address, w.availability_status, w.verification_status,
               w.rating_avg, w.reviews_count, w.location_lat, w.location_lng, w.service_radius_km
        FROM users u
        JOIN workers w ON w.user_id = u.id
        WHERE u.status = 'active'
          AND w.availability_status IN ('available', 'partially_available')
    ";
    $workers = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $matched = [];
    foreach ($workers as $w) {
        $w_lat = !empty($w['location_lat']) ? (float)$w['location_lat'] : DEFAULT_LATITUDE;
        $w_lng = !empty($w['location_lng']) ? (float)$w['location_lng'] : DEFAULT_LONGITUDE;

        $distance = calculate_haversine_distance($b_lat, $b_lng, $w_lat, $w_lng);

        // Filter by radius
        if ($distance !== null && $distance > $radius_km) {
            continue;
        }

        // Trade/category relevance check
        $trade = strtolower($w['trade_title'] ?? '');
        $is_relevant = false;
        if (strpos($trade, $category) !== false || strpos($category, $trade) !== false) {
            $is_relevant = true;
        }

        // Also check worker's listed skills
        $sk_stmt = $db->prepare("
            SELECT s.name, s.category 
            FROM worker_skills ws 
            JOIN skills s ON ws.skill_id = s.id 
            WHERE ws.worker_id = ?
        ");
        $sk_stmt->execute([(int)$w['worker_profile_id']]);
        $worker_skills = $sk_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($worker_skills as $sk) {
            $s_name = strtolower($sk['name']);
            $s_cat = strtolower($sk['category']);
            if (strpos($s_name, $category) !== false || strpos($category, $s_name) !== false ||
                strpos($s_cat, $category) !== false || strpos($category, $s_cat) !== false) {
                $is_relevant = true;
                break;
            }
        }

        if ($is_relevant) {
            $w['distance_km'] = $distance;
            $matched[] = $w;
        }
    }

    // Sort by proximity first, then rating
    usort($matched, function($a, $b) {
        if ($a['distance_km'] == $b['distance_km']) {
            return ($b['rating_avg'] <=> $a['rating_avg']);
        }
        return ($a['distance_km'] <=> $b['distance_km']);
    });

    return $matched;
}

/**
 * Concurrency-Safe Instant Booking Acceptance.
 * Uses atomic transaction + SELECT ... FOR UPDATE to eliminate double-booking race conditions.
 */
function accept_instant_booking($booking_id, $worker_user_id) {
    $db = getDB();
    $booking_id = (int)$booking_id;
    $worker_user_id = (int)$worker_user_id;

    // Verify worker exists and is active
    $w_stmt = $db->prepare("SELECT id, name, role FROM users WHERE id = ? AND role = 'worker' AND status = 'active'");
    $w_stmt->execute([$worker_user_id]);
    $worker = $w_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$worker) {
        return ['success' => false, 'message' => 'Unauthorized or invalid worker account.'];
    }

    try {
        $db->beginTransaction();

        // Lock row exclusively for this transaction
        $lock_stmt = $db->prepare("SELECT * FROM bookings WHERE id = ? FOR UPDATE");
        $lock_stmt->execute([$booking_id]);
        $booking = $lock_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Booking request not found.'];
        }

        if ($booking['booking_mode'] !== 'instant') {
            $db->rollBack();
            return ['success' => false, 'message' => 'This job requires quotation submission and cannot be claimed instantly.'];
        }

        // Concurrency guard: check if already claimed by another provider
        $is_already_claimed_by_other = (!empty($booking['provider_id']) && (int)$booking['provider_id'] !== $worker_user_id);
        if (!in_array($booking['status'], ['posted', 'matching']) || $is_already_claimed_by_other) {
            $db->rollBack();
            return ['success' => false, 'message' => 'This job has already been accepted by another provider.'];
        }

        // Successfully assign worker to booking
        $up = $db->prepare("
            UPDATE bookings 
            SET provider_id = ?, provider_type = 'worker', status = 'accepted', 
                accepted_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ");
        $up->execute([$worker_user_id, $booking_id]);

        // Create notification for the client
        $c_title = "Instant Booking Accepted";
        $c_msg = "Worker '{$worker['name']}' has accepted your booking #{$booking['booking_number']}.";
        create_notification($booking['client_id'], $c_title, $c_msg, 'BOOKING', "/client/booking-details.php?id={$booking_id}", 'booking', $booking_id);

        // Create activity log
        log_activity($worker_user_id, 'BOOKING_ACCEPTED', "Worker '{$worker['name']}' accepted instant booking #{$booking['booking_number']}", 'booking', $booking_id);

        $db->commit();
        return ['success' => true, 'message' => 'Booking successfully accepted! Proceeding to job view.'];

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("accept_instant_booking error: " . $e->getMessage());
        return ['success' => false, 'message' => 'A database error occurred while processing your acceptance.'];
    }
}

/**
 * Worker Rejection / Decline of an Assigned or Matching Instant Booking.
 * Allows the assigned worker to reject the request, logs the reason, notifies the client,
 * and updates the booking status with cancellation details.
 */
function reject_instant_booking($booking_id, $worker_user_id, $reason = 'Unavailable') {
    $db = getDB();
    $booking_id = (int)$booking_id;
    $worker_user_id = (int)$worker_user_id;
    $reason = trim(sanitize($reason ?: 'Worker unavailable at this time'));

    // Verify worker exists and is active
    $w_stmt = $db->prepare("SELECT id, name, role FROM users WHERE id = ? AND role = 'worker' AND status = 'active'");
    $w_stmt->execute([$worker_user_id]);
    $worker = $w_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$worker) {
        return ['success' => false, 'message' => 'Unauthorized or invalid worker account.'];
    }

    try {
        $db->beginTransaction();

        $lock_stmt = $db->prepare("SELECT * FROM bookings WHERE id = ? FOR UPDATE");
        $lock_stmt->execute([$booking_id]);
        $booking = $lock_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Booking request not found.'];
        }

        // Verify that the worker is authorized to reject:
        // Must either be directly assigned or status is posted/matching/accepted
        $is_assigned = (!empty($booking['provider_id']) && (int)$booking['provider_id'] === $worker_user_id);

        if (!$is_assigned && !in_array($booking['status'], ['posted', 'matching'])) {
            $db->rollBack();
            return ['success' => false, 'message' => 'You are not the assigned worker for this booking or it is no longer pending.'];
        }

        if (in_array($booking['status'], ['work_started', 'completed'])) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Cannot reject a booking after work has already commenced or completed.'];
        }

        $cancellation_text = "Worker {$worker['name']} declined request: " . $reason;

        $up = $db->prepare("
            UPDATE bookings 
            SET status = 'cancelled', cancellation_reason = ?, cancelled_by = ?, 
                updated_at = NOW() 
            WHERE id = ?
        ");
        $up->execute([$cancellation_text, $worker_user_id, $booking_id]);

        // Create notification for the client
        $c_title = "Booking Request Declined";
        $c_msg = "Worker '{$worker['name']}' was unable to accept your booking #{$booking['booking_number']}. Reason: {$reason}";
        create_notification($booking['client_id'], $c_title, $c_msg, 'BOOKING_REJECTED', "/client/booking-details.php?id={$booking_id}", 'booking', $booking_id);

        // Create activity log
        log_activity($worker_user_id, 'BOOKING_REJECTED', "Worker '{$worker['name']}' declined booking #{$booking['booking_number']}: {$reason}", 'booking', $booking_id);

        $db->commit();
        return ['success' => true, 'message' => 'Booking request declined. Client has been notified.'];

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("reject_instant_booking error: " . $e->getMessage());
        return ['success' => false, 'message' => 'A database error occurred while declining the booking.'];
    }
}

/**
 * Submit or update a quotation for Compare Quotes bookings.
 */
function submit_booking_quote($booking_id, $provider_user_id, $data) {
    $db = getDB();
    $booking_id = (int)$booking_id;
    $provider_user_id = (int)$provider_user_id;

    // Verify provider
    $p_stmt = $db->prepare("SELECT id, name, role FROM users WHERE id = ? AND role IN ('worker', 'contractor') AND status = 'active'");
    $p_stmt->execute([$provider_user_id]);
    $provider = $p_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$provider) {
        return ['success' => false, 'message' => 'Unauthorized provider role.'];
    }

    $b_stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
    $b_stmt->execute([$booking_id]);
    $booking = $b_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        return ['success' => false, 'message' => 'Booking request not found.'];
    }

    if ($booking['booking_mode'] !== 'quote') {
        return ['success' => false, 'message' => 'This booking does not accept quotation bids.'];
    }

    if (!in_array($booking['status'], ['posted', 'quotes_received'])) {
        return ['success' => false, 'message' => 'This request is closed and no longer accepting bids.'];
    }

    // Validate inputs
    $price = (float)($data['proposed_price'] ?? 0);
    $scope = trim($data['scope_of_work'] ?? '');
    $duration = trim($data['estimated_duration'] ?? '');
    $start_date = trim($data['proposed_start_date'] ?? '');
    $materials = sanitize($data['materials_included'] ?? 'labor_only');
    $exclusions = trim($data['exclusions'] ?? '');
    $notes = trim($data['notes'] ?? '');

    if ($price <= 0) {
        return ['success' => false, 'message' => 'Please propose a valid positive price in INR.'];
    }
    if (empty($scope)) {
        return ['success' => false, 'message' => 'Please describe your proposed scope of work.'];
    }
    if (empty($duration)) {
        return ['success' => false, 'message' => 'Please estimate the project duration.'];
    }
    if (empty($start_date)) {
        return ['success' => false, 'message' => 'Please specify your earliest start date.'];
    }

    try {
        $db->beginTransaction();

        // Check if existing quote exists for this provider
        $q_stmt = $db->prepare("SELECT id, status FROM booking_quotes WHERE booking_id = ? AND provider_id = ?");
        $q_stmt->execute([$booking_id, $provider_user_id]);
        $existing = $q_stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ($existing['status'] === 'accepted') {
                $db->rollBack();
                return ['success' => false, 'message' => 'This quote has already been accepted and cannot be modified.'];
            }
            $up = $db->prepare("
                UPDATE booking_quotes 
                SET proposed_price = ?, scope_of_work = ?, exclusions = ?, estimated_duration = ?, 
                    proposed_start_date = ?, materials_included = ?, notes = ?, status = 'revised', updated_at = NOW() 
                WHERE id = ?
            ");
            $up->execute([$price, $scope, $exclusions, $duration, $start_date, $materials, $notes, $existing['id']]);
            $quote_id = $existing['id'];
        } else {
            $ins = $db->prepare("
                INSERT INTO booking_quotes (booking_id, provider_id, provider_role, proposed_price, scope_of_work, exclusions, estimated_duration, proposed_start_date, materials_included, notes, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted', NOW())
            ");
            $ins->execute([$booking_id, $provider_user_id, $provider['role'], $price, $scope, $exclusions, $duration, $start_date, $materials, $notes]);
            $quote_id = $db->lastInsertId();
        }

        // Update booking status to quotes_received
        if ($booking['status'] === 'posted') {
            $db->prepare("UPDATE bookings SET status = 'quotes_received', updated_at = NOW() WHERE id = ?")->execute([$booking_id]);
        }

        // Notify client
        $c_title = "New Quote Received";
        $c_msg = "{$provider['name']} submitted a quote of " . format_currency($price) . " for your request #{$booking['booking_number']}.";
        create_notification($booking['client_id'], $c_title, $c_msg, 'QUOTE', "/client/booking-details.php?id={$booking_id}", 'booking', $booking_id);

        log_activity($provider_user_id, 'QUOTE_SUBMITTED', "Submitted quote of " . format_currency($price) . " on booking #{$booking['booking_number']}", 'booking_quote', $quote_id);

        $db->commit();
        return ['success' => true, 'message' => 'Quotation successfully submitted!'];

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("submit_booking_quote error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error while saving quotation.'];
    }
}

/**
 * Concurrency-Safe Quote Acceptance by Client.
 * Locks the booking and quotes, marks the chosen quote 'accepted', marks other active bids 'rejected',
 * confirms the booking with provider details, and notifies all participants.
 */
function accept_booking_quote($quote_id, $client_user_id) {
    $db = getDB();
    $quote_id = (int)$quote_id;
    $client_user_id = (int)$client_user_id;

    try {
        $db->beginTransaction();

        // Lock quote
        $q_stmt = $db->prepare("SELECT * FROM booking_quotes WHERE id = ? FOR UPDATE");
        $q_stmt->execute([$quote_id]);
        $quote = $q_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$quote) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Quotation record not found.'];
        }

        // Lock booking
        $b_stmt = $db->prepare("SELECT * FROM bookings WHERE id = ? FOR UPDATE");
        $b_stmt->execute([(int)$quote['booking_id']]);
        $booking = $b_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking || (int)$booking['client_id'] !== $client_user_id) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Unauthorized: You are not the owner of this booking.'];
        }

        if ($booking['status'] === 'confirmed' || $booking['status'] === 'completed') {
            $db->rollBack();
            return ['success' => false, 'message' => 'A quotation has already been accepted for this booking.'];
        }

        // 1. Mark this quote accepted
        $db->prepare("UPDATE booking_quotes SET status = 'accepted', updated_at = NOW() WHERE id = ?")->execute([$quote_id]);

        // 2. Mark competing quotes for this booking as rejected (without deleting history)
        $db->prepare("
            UPDATE booking_quotes 
            SET status = 'rejected', updated_at = NOW() 
            WHERE booking_id = ? AND id != ? AND status IN ('submitted', 'revised')
        ")->execute([(int)$quote['booking_id'], $quote_id]);

        // 3. Confirm booking with selected provider and price
        $db->prepare("
            UPDATE bookings 
            SET provider_id = ?, provider_type = ?, price = ?, status = 'confirmed', 
                accepted_at = NOW(), updated_at = NOW() 
            WHERE id = ?
        ")->execute([$quote['provider_id'], $quote['provider_role'], $quote['proposed_price'], (int)$quote['booking_id']]);

        // 3b. If provider is a contractor, synchronize or create construction project in projects table
        $created_project_id = null;
        if ($quote['provider_role'] === 'contractor') {
            $chk_p = $db->prepare("SELECT id FROM projects WHERE contractor_id = ? AND client_id = ? AND title = ?");
            $chk_p->execute([$quote['provider_id'], $client_user_id, $booking['title']]);
            $existing_proj = $chk_p->fetch(PDO::FETCH_ASSOC);

            if ($existing_proj) {
                $created_project_id = (int)$existing_proj['id'];
                $db->prepare("UPDATE projects SET budget = ?, status = 'in_progress', updated_at = NOW() WHERE id = ?")->execute([$quote['proposed_price'], $created_project_id]);
            } else {
                $qr_token = 'BC-PROJ-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
                $proj_loc = !empty($booking['address']) ? ($booking['address'] . ', ' . $booking['city']) : ($booking['city'] . ', Gujarat');
                $p_ins = $db->prepare("
                    INSERT INTO projects (
                        contractor_id, client_id, title, description, location,
                        address, city, state, location_lat, location_lng,
                        budget, start_date, status, progress_percent, qr_code_token, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?,
                        ?, ?, 'in_progress', 0, ?, NOW(), NOW()
                    )
                ");
                $p_ins->execute([
                    $quote['provider_id'],
                    $client_user_id,
                    $booking['title'],
                    !empty($quote['scope_of_work']) ? $quote['scope_of_work'] : $booking['description'],
                    $proj_loc,
                    $booking['address'],
                    $booking['city'] ?? 'Ahmedabad',
                    $booking['state'] ?? 'Gujarat',
                    $booking['location_lat'] ?? DEFAULT_LATITUDE,
                    $booking['location_lng'] ?? DEFAULT_LONGITUDE,
                    $quote['proposed_price'],
                    !empty($quote['proposed_start_date']) ? $quote['proposed_start_date'] : date('Y-m-d'),
                    $qr_token
                ]);
                $created_project_id = (int)$db->lastInsertId();

                // Add project members
                $db->prepare("INSERT INTO project_members (project_id, user_id, role_in_project, status, joined_at) VALUES (?, ?, 'Contractor Manager', 'active', NOW())")->execute([$created_project_id, $quote['provider_id']]);
                $db->prepare("INSERT INTO project_members (project_id, user_id, role_in_project, status, joined_at) VALUES (?, ?, 'Client Representative', 'active', NOW())")->execute([$created_project_id, $client_user_id]);

                log_activity($client_user_id, 'PROJECT_CREATED_FROM_QUOTE', "Construction project #{$created_project_id} ('{$booking['title']}') created from accepted quotation", 'project', $created_project_id);
            }
        }

        // 4. Notify winning provider
        $w_stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
        $w_stmt->execute([$client_user_id]);
        $client_name = $w_stmt->fetchColumn() ?: 'Client';

        create_notification(
            $quote['provider_id'], 
            'Quotation Accepted!', 
            "Congratulations! Your quotation for booking #{$booking['booking_number']} has been accepted by {$client_name}.",
            'QUOTE_ACCEPTED',
            ($quote['provider_role'] === 'worker' ? '/worker/my-bookings.php' : '/contractor/projects.php'),
            'booking',
            (int)$quote['booking_id']
        );

        // 5. Notify rejected providers
        $rej_stmt = $db->prepare("SELECT provider_id, provider_role FROM booking_quotes WHERE booking_id = ? AND id != ?");
        $rej_stmt->execute([(int)$quote['booking_id'], $quote_id]);
        while ($rej = $rej_stmt->fetch(PDO::FETCH_ASSOC)) {
            $dest_url = ($rej['provider_role'] === 'worker') ? '/worker/my-bookings.php' : '/contractor/quote-requests.php';
            create_notification(
                (int)$rej['provider_id'],
                'Quotation Closed',
                "Another quotation was selected for booking #{$booking['booking_number']}. Thank you for your submission.",
                'QUOTE_CLOSED',
                $dest_url,
                'booking',
                (int)$quote['booking_id']
            );
        }

        log_activity($client_user_id, 'QUOTE_ACCEPTED', "Client accepted quote #{$quote_id} from provider #{$quote['provider_id']} for " . format_currency($quote['proposed_price']), 'booking', (int)$quote['booking_id']);

        $db->commit();
        return ['success' => true, 'message' => 'Quotation accepted and booking confirmed!'];

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("accept_booking_quote error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database transaction failed during quotation acceptance.'];
    }
}

/**
 * Validated State Machine Transitions.
 * Restricts transitions to authorized users and validates next states.
 */
function update_booking_status($booking_id, $new_status, $user_id, $reason = null) {
    $db = getDB();
    $booking_id = (int)$booking_id;
    $user_id = (int)$user_id;
    $new_status = sanitize($new_status);

    $u_stmt = $db->prepare("SELECT id, role, name FROM users WHERE id = ?");
    $u_stmt->execute([$user_id]);
    $user = $u_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return ['success' => false, 'message' => 'User not found.'];
    }

    $b_stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
    $b_stmt->execute([$booking_id]);
    $booking = $b_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        return ['success' => false, 'message' => 'Booking not found.'];
    }

    $current_status = $booking['status'];
    $is_client = ((int)$booking['client_id'] === $user_id);
    $is_provider = ((int)$booking['provider_id'] === $user_id);
    $is_admin = ($user['role'] === 'admin');

    if (!$is_client && !$is_provider && !$is_admin) {
        return ['success' => false, 'message' => 'Unauthorized action for this booking.'];
    }

    // Permitted state machine transitions
    $allowed_transitions = [
        'posted' => ['matching', 'quotes_received', 'cancelled'],
        'matching' => ['accepted', 'cancelled'],
        'quotes_received' => ['confirmed', 'cancelled'],
        'accepted' => ['confirmed', 'on_the_way', 'cancelled'],
        'confirmed' => ['on_the_way', 'work_started', 'cancelled'],
        'on_the_way' => ['work_started', 'cancelled'],
        'work_started' => ['completed', 'disputed'],
        'completed' => [],
        'cancelled' => [],
        'disputed' => ['completed', 'cancelled']
    ];

    if (!isset($allowed_transitions[$current_status]) || !in_array($new_status, $allowed_transitions[$current_status])) {
        return ['success' => false, 'message' => "Invalid status transition from '{$current_status}' to '{$new_status}'."];
    }

    // Role-specific transition permissions
    if ($new_status === 'on_the_way' && !$is_provider && !$is_admin) {
        return ['success' => false, 'message' => 'Only the assigned provider can declare they are on the way.'];
    }
    if ($new_status === 'work_started' && !$is_provider && !$is_admin) {
        return ['success' => false, 'message' => 'Only the assigned provider can start the work.'];
    }
    if ($new_status === 'completed' && !$is_client && !$is_admin) {
        return ['success' => false, 'message' => 'Work completion must be confirmed by the client or administrator.'];
    }

    // Execute state transition
    $extra_sql = "";
    $params = [$new_status];

    if ($new_status === 'work_started') {
        $extra_sql = ", started_at = NOW()";
    } elseif ($new_status === 'completed') {
        $extra_sql = ", completed_at = NOW()";
    } elseif ($new_status === 'cancelled') {
        $extra_sql = ", cancellation_reason = ?, cancelled_by = ?";
        $params[] = sanitize($reason ?: 'Cancelled by user');
        $params[] = $user_id;
    }

    $params[] = $booking_id;
    $stmt = $db->prepare("UPDATE bookings SET status = ? {$extra_sql}, updated_at = NOW() WHERE id = ?");
    $stmt->execute($params);

    // Notify parties
    $label = ucwords(str_replace('_', ' ', $new_status));
    $notify_target = $is_client ? $booking['provider_id'] : $booking['client_id'];
    if ($notify_target) {
        $target_role_stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $target_role_stmt->execute([(int)$notify_target]);
        $target_role = strtolower((string)$target_role_stmt->fetchColumn());

        if ($target_role === 'worker') {
            $dest_url = '/worker/my-bookings.php';
        } elseif ($target_role === 'contractor') {
            $dest_url = '/contractor/quote-requests.php';
        } elseif ($target_role === 'admin') {
            $dest_url = '/admin/bookings.php';
        } else {
            $dest_url = "/client/booking-details.php?id={$booking_id}";
        }

        create_notification(
            $notify_target,
            "Booking Status: {$label}",
            "Booking #{$booking['booking_number']} status was updated to '{$label}'.",
            'BOOKING_STATUS',
            $dest_url,
            'booking',
            $booking_id
        );
    }

    log_activity($user_id, 'BOOKING_STATUS_CHANGED', "Updated booking #{$booking['booking_number']} status from '{$current_status}' to '{$new_status}'", 'booking', $booking_id);

    return ['success' => true, 'message' => "Status successfully updated to '{$label}'."];
}

/**
 * Return Bootstrap badge class for booking status.
 */
function get_booking_badge_class($status) {
    switch (strtolower((string)$status)) {
        case 'posted':
        case 'matching':
            return 'bg-info text-dark';
        case 'quotes_received':
            return 'bg-primary text-white';
        case 'accepted':
        case 'confirmed':
            return 'bg-warning text-dark fw-bold';
        case 'on_the_way':
            return 'bg-purple text-white';
        case 'work_started':
            return 'bg-primary text-white';
        case 'completed':
            return 'bg-success text-white';
        case 'cancelled':
            return 'bg-danger text-white';
        case 'disputed':
            return 'bg-danger text-white fw-bold';
        default:
            return 'bg-secondary text-light';
    }
}
