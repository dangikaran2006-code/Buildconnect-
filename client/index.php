<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/booking-functions.php';
requireRole('client');

$page_title = __('dash.client_title') . " - " . __('nav.brand');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$user = currentUser();
$db = getDB();
$client_user_id = (int)$user['id'];
$flash = get_flash_message();

// DB Statistics isolated strictly to logged-in client
$stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE client_id = ?");
$stmt->execute([$client_user_id]);
$total_projects = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE client_id = ? AND status IN ('in_progress', 'active')");
$stmt->execute([$client_user_id]);
$active_projects = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE client_id = ? AND status = 'completed'");
$stmt->execute([$client_user_id]);
$completed_projects = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE client_id = ? AND status = 'on_hold'");
$stmt->execute([$client_user_id]);
$on_hold_projects = (int)$stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT COUNT(*) 
    FROM milestones m 
    JOIN projects p ON m.project_id = p.id 
    WHERE p.client_id = ? AND m.status IN ('pending', 'in_progress')
");
$stmt->execute([$client_user_id]);
$upcoming_milestones = (int)$stmt->fetchColumn();

// Fetch Client Bookings Count & Recent Bookings
$stmt_bk_cnt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE client_id = ?");
$stmt_bk_cnt->execute([$client_user_id]);
$total_client_bookings = (int)$stmt_bk_cnt->fetchColumn();

$stmt_b = $db->prepare("
    SELECT b.*, u.name as provider_name 
    FROM bookings b 
    LEFT JOIN users u ON b.provider_id = u.id 
    WHERE b.client_id = ? 
    ORDER BY b.id DESC LIMIT 3
");
$stmt_b->execute([$client_user_id]);
$recent_bookings = $stmt_b->fetchAll();

// Fetch Client Projects
$stmt_p = $db->prepare("
    SELECT p.*, u.name as contractor_name, c.company_name as contractor_company 
    FROM projects p 
    LEFT JOIN users u ON p.contractor_id = u.id 
    LEFT JOIN contractors c ON c.user_id = u.id 
    WHERE p.client_id = ? 
    ORDER BY p.id DESC LIMIT 5
");
$stmt_p->execute([$client_user_id]);
$recent_projects = $stmt_p->fetchAll();

// Fetch Upcoming Milestones
$stmt_m = $db->prepare("
    SELECT m.*, p.title as project_title 
    FROM milestones m 
    JOIN projects p ON m.project_id = p.id 
    WHERE p.client_id = ? 
    ORDER BY m.target_date ASC LIMIT 4
");
$stmt_m->execute([$client_user_id]);
$recent_milestones = $stmt_m->fetchAll();

// Greeting helper based on hour of day
$hour = date('H');
$greeting = ($hour < 12) ? __('greeting.morning') : (($hour < 18) ? __('greeting.afternoon') : __('greeting.evening'));
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <!-- Dashboard Top Greeting Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h2 fw-extrabold bc-page-title mb-1">
                    <?= $greeting ?>, <?= e(explode(' ', $user['name'])[0]) ?> 👋
                </h1>
                <p class="text-secondary small mb-0"><?= __('dash.client_subtitle') ?></p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <a href="<?= BASE_URL ?>/client/create-booking.php" class="btn btn-amber btn-sm">
                    <i class="fa-solid fa-calendar-plus me-1"></i> <?= __('sidebar.book_service_post_job') ?>
                </a>
                <a href="<?= BASE_URL ?>/client/bookings.php" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-clipboard-list me-1"></i> <?= __('sidebar.my_bookings') ?> (<?= $total_client_bookings ?>)
                </a>
                <div class="bc-date-range-control px-3 py-1.5 small fw-semibold d-none d-lg-block">
                    <i class="fa-regular fa-calendar text-warning me-2"></i><?= format_date(date('Y-m-d', strtotime('-6 days'))) ?> - <?= format_date(date('Y-m-d')) ?>
                </div>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= sanitize($flash['type']) ?> alert-dismissible fade show mb-4" role="alert">
                <i class="fa-solid fa-circle-info me-2"></i> <?= sanitize($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- DUAL BOOKING LAUNCHPAD: PROMINENT SEPARATION OF INSTANT vs COMPARE QUOTES -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="bc-card p-4 h-100 border-2" style="border-color: #FDBA74 !important; background: linear-gradient(180deg, #FFFFFF 0%, #FFF7ED 100%) !important;">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon bc-metric-icon-orange p-3 rounded-circle">
                            <i class="fa-solid fa-bolt fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h3 class="h5 fw-bold text-dark mb-0"><?= __('booking.instant_title') ?></h3>
                                <span class="badge bg-warning text-dark extra-small fw-bold"><?= __('client.fast_dispatch') ?></span>
                            </div>
                            <p class="text-secondary extra-small mb-3">
                                <?= __('booking.instant_subtitle') ?>
                            </p>
                            <a href="<?= BASE_URL ?>/client/create-booking.php?mode=instant" class="btn btn-amber btn-sm fw-bold">
                                <i class="fa-solid fa-bolt me-1"></i> <?= __('booking.book_now') ?> &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="bc-card p-4 h-100 border-2" style="border-color: #BAE6FD !important; background: linear-gradient(180deg, #FFFFFF 0%, #F0F9FF 100%) !important;">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon bc-metric-icon-blue p-3 rounded-circle">
                            <i class="fa-solid fa-scale-balanced fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h3 class="h5 fw-bold text-dark mb-0"><?= __('booking.quote_title') ?></h3>
                                <span class="badge bg-primary-subtle text-primary extra-small fw-bold"><?= __('client.competitive_bids') ?></span>
                            </div>
                            <p class="text-secondary extra-small mb-3">
                                <?= __('booking.quote_subtitle') ?>
                            </p>
                            <a href="<?= BASE_URL ?>/client/create-booking.php?mode=quote" class="btn btn-secondary btn-sm fw-bold">
                                <i class="fa-solid fa-file-invoice me-1"></i> <?= __('sidebar.quotation_requests') ?> &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5 Metric Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-metric-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bc-metric-badge-blue">
                            <i class="fa-solid fa-building fs-5"></i>
                        </div>
                        <span class="bc-metric-title"><?= __('client.total_projects') ?></span>
                    </div>
                    <div class="bc-metric-value mb-1"><?= number_format($total_projects) ?></div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="bc-trend-badge-up"><i class="fa-solid fa-check me-1"></i><?= __('status.active') ?></span>
                        <span class="extra-small text-muted"><?= __('client.owned_developments') ?></span>
                    </div>
                    <svg class="mt-2 w-100" height="24" viewBox="0 0 100 25" fill="none">
                        <path d="M0 20 Q 20 15, 40 18 T 80 5 T 100 2" stroke="#0284c7" stroke-width="2.5" fill="none"/>
                    </svg>
                </div>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-metric-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bc-metric-icon-green">
                            <i class="fa-solid fa-person-digging fs-5"></i>
                        </div>
                        <span class="bc-metric-title"><?= __('client.active_construction') ?></span>
                    </div>
                    <div class="bc-metric-value mb-1"><?= number_format($active_projects) ?></div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="bc-trend-badge-up"><i class="fa-solid fa-arrow-trend-up me-1"></i><?= __('client.on_track') ?></span>
                        <span class="extra-small text-muted"><?= __('status.in_progress') ?></span>
                    </div>
                    <svg class="mt-2 w-100" height="24" viewBox="0 0 100 25" fill="none">
                        <path d="M0 22 Q 25 10, 50 16 T 80 8 T 100 3" stroke="#10b981" stroke-width="2.5" fill="none"/>
                    </svg>
                </div>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-metric-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bc-metric-icon-amber">
                            <i class="fa-solid fa-flag-checkered fs-5"></i>
                        </div>
                        <span class="bc-metric-title"><?= __('client.pending_milestones') ?></span>
                    </div>
                    <div class="bc-metric-value mb-1"><?= number_format($upcoming_milestones) ?></div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="extra-small text-muted"><?= __('client.on_hold_count', ['count' => number_format($on_hold_projects)]) ?></span>
                        <span class="extra-small text-muted"><?= __('client.upcoming_delivery') ?></span>
                    </div>
                    <svg class="mt-2 w-100" height="24" viewBox="0 0 100 25" fill="none">
                        <path d="M0 24 Q 20 18, 45 12 T 75 14 T 100 2" stroke="#f59e0b" stroke-width="2.5" fill="none"/>
                    </svg>
                </div>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-metric-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bc-metric-icon-orange">
                            <i class="fa-solid fa-clipboard-check fs-5"></i>
                        </div>
                        <span class="bc-metric-title"><?= __('client.service_bookings') ?></span>
                    </div>
                    <div class="bc-metric-value mb-1"><?= number_format($total_client_bookings) ?></div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="bc-trend-badge-up"><i class="fa-solid fa-clock me-1"></i><?= __('client.real_time') ?></span>
                        <a href="<?= BASE_URL ?>/client/bookings.php" class="extra-small fw-semibold text-decoration-none" style="color: var(--bc-orange);"><?= __('btn.view') ?> &rarr;</a>
                    </div>
                    <svg class="mt-2 w-100" height="24" viewBox="0 0 100 25" fill="none">
                        <path d="M0 18 Q 30 22, 60 10 T 90 6 T 100 1" stroke="#f97316" stroke-width="2.5" fill="none"/>
                    </svg>
                </div>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-metric-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bc-metric-badge-blue">
                            <i class="fa-solid fa-circle-check fs-5"></i>
                        </div>
                        <span class="bc-metric-title"><?= __('client.completed_works') ?></span>
                    </div>
                    <div class="bc-metric-value mb-1"><?= number_format($completed_projects) ?></div>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="bc-trend-badge-up"><i class="fa-solid fa-check-double me-1"></i><?= __('status.completed') ?></span>
                        <span class="extra-small text-muted"><?= __('client.delivered_handovers') ?></span>
                    </div>
                    <svg class="mt-2 w-100" height="24" viewBox="0 0 100 25" fill="none">
                        <path d="M0 20 Q 30 15, 50 18 T 85 8 T 100 4" stroke="#0284c7" stroke-width="2.5" fill="none"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Main Construction Projects Card Stream -->
        <div class="row g-4 mb-4">
            <div class="col-xl-8 col-lg-7">
                <div class="bc-surface-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold mb-0 bc-card-title"><i class="fa-solid fa-building text-warning me-2"></i><?= __('client.my_projects') ?></h2>
                        <a href="<?= BASE_URL ?>/client/projects.php" class="bc-view-all-link extra-small fw-bold"><?= __('btn.view_all_projects') ?> <i class="fa-solid fa-arrow-right ms-1"></i></a>
                    </div>

                    <?php if (empty($recent_projects)): ?>
                        <div class="text-center py-5 text-muted extra-small">
                            <i class="fa-solid fa-building-circle-xmark fa-2x mb-2 text-secondary d-block"></i>
                            <?= __('client.no_projects') ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 extra-small">
                                <thead>
                                    <tr>
                                        <th><?= __('table.project_name') ?></th>
                                        <th><?= __('table.contractor') ?></th>
                                        <th><?= __('table.location') ?></th>
                                        <th><?= __('table.budget') ?></th>
                                        <th><?= __('table.status') ?></th>
                                        <th><?= __('table.progress') ?></th>
                                        <th class="text-end"><?= __('table.action') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_projects as $p): ?>
                                        <tr>
                                            <td>
                                                <a href="<?= BASE_URL ?>/client/project-details.php?id=<?= $p['id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                    <?= e($p['title']) ?>
                                                </a>
                                            </td>
                                            <td><span class="text-secondary"><?= e($p['contractor_company'] ?? $p['contractor_name'] ?? __('role.contractor')) ?></span></td>
                                            <td class="text-secondary"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($p['location'] ?: $p['city']) ?></td>
                                            <td class="font-monospace fw-bold text-dark"><?= format_currency($p['budget']) ?></td>
                                            <td>
                                                <span class="badge bg-primary-subtle"><?= e(translate_status($p['status'])) ?></span>
                                            </td>
                                            <td style="min-width: 120px;">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="progress flex-grow-1" style="height: 6px;">
                                                        <div class="progress-bar" style="width: <?= (int)$p['progress_percent'] ?>%;"></div>
                                                    </div>
                                                    <span class="fw-bold text-dark font-monospace"><?= (int)$p['progress_percent'] ?>%</span>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>/client/project-details.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary extra-small"><?= __('btn.inspect') ?></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Bookings & Milestones -->
            <div class="col-xl-4 col-lg-5">
                <div class="bc-surface-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="h6 fw-bold mb-0 bc-card-title"><i class="fa-solid fa-calendar-check text-warning me-2"></i><?= __('client.recent_bookings') ?></h3>
                            <a href="<?= BASE_URL ?>/client/bookings.php" class="bc-view-all-link extra-small fw-bold"><?= __('btn.all_bookings') ?> <i class="fa-solid fa-arrow-right ms-1"></i></a>
                        </div>

                        <?php if (empty($recent_bookings)): ?>
                            <div class="p-4 text-center text-muted extra-small border rounded-3 bg-light mb-3">
                                <?= __('client.no_bookings') ?>
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2 mb-3">
                                <?php foreach ($recent_bookings as $rb): ?>
                                    <div class="p-2.5 rounded-3 border bg-light d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="d-flex align-items-center gap-1.5 mb-0.5">
                                                <span class="badge <?= $rb['booking_mode'] === 'instant' ? 'bg-warning text-dark' : 'bg-info text-dark' ?>" style="font-size: 0.65rem;">
                                                    <?= $rb['booking_mode'] === 'instant' ? __('booking.instant') : __('booking.quote') ?>
                                                </span>
                                                <span class="font-monospace text-muted extra-small">#<?= e($rb['booking_number']) ?></span>
                                            </div>
                                            <div class="fw-bold text-dark small text-truncate" style="max-width: 170px;"><?= e($rb['title']) ?></div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge <?= get_booking_badge_class($rb['status']) ?> extra-small">
                                                <?= e(translate_status($rb['status'])) ?>
                                            </span>
                                            <div class="extra-small text-muted mt-0.5"><?= format_date($rb['created_at']) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Target Delivery Milestones -->
                        <h4 class="h6 fw-bold mb-2 text-dark extra-small text-uppercase"><i class="fa-solid fa-flag-checkered text-warning me-1"></i> <?= __('client.target_milestones') ?></h4>
                        <?php if (empty($recent_milestones)): ?>
                            <div class="p-3 text-center text-muted extra-small border rounded-3 bg-light"><?= __('client.no_milestones') ?></div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($recent_milestones as $ms): ?>
                                    <div class="p-2 rounded-3 border bg-light d-flex align-items-center justify-content-between extra-small">
                                        <div class="text-truncate me-2">
                                            <div class="fw-semibold text-dark text-truncate"><?= e($ms['title']) ?></div>
                                            <div class="text-muted extra-small text-truncate"><?= e($ms['project_title']) ?></div>
                                        </div>
                                        <span class="badge bg-primary-subtle text-nowrap"><?= format_date($ms['target_date']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
