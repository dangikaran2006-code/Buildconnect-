<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$page_title = __('dash.admin_title') . " - " . __('nav.brand');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$user = currentUser();
$db = getDB();

// Database COUNT Statistics (100% Real Database Queries)
$total_users = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_workers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'worker'")->fetchColumn();
$total_contractors = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'contractor'")->fetchColumn();
$total_clients = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$total_admins = (int)$db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

$total_projects = (int)$db->query("SELECT COUNT(*) FROM projects")->fetchColumn() ?: 0;
$active_projects_count = (int)$db->query("SELECT COUNT(*) FROM projects WHERE status IN ('active', 'in_progress')")->fetchColumn() ?: 0;
$total_budget_sum = (float)$db->query("SELECT COALESCE(SUM(budget), 0) FROM projects")->fetchColumn() ?: 0;
$total_companies = (int)$db->query("SELECT COUNT(*) FROM contractors")->fetchColumn() ?: 0;
$active_jobs = (int)$db->query("SELECT COUNT(*) FROM jobs WHERE status = 'open'")->fetchColumn() ?: 0;
$pending_applications = (int)$db->query("SELECT COUNT(*) FROM job_applications WHERE status = 'pending'")->fetchColumn() ?: 0;
$pending_verifications = (int)$db->query("SELECT COUNT(*) FROM workers WHERE verification_status = 'pending'")->fetchColumn() ?: 0;
$pending_reviews = (int)$db->query("SELECT COUNT(*) FROM review_reports WHERE status = 'pending'")->fetchColumn() ?: 0;

// Recent System Activities from Database
$activity_stmt = $db->query("
    SELECT a.*, u.name as user_name, u.email as user_email, u.role as user_role 
    FROM activity_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.id DESC LIMIT 6
");
$recent_activities = $activity_stmt->fetchAll();

// Recent Registrations from Database
$recent_users = $db->query("SELECT id, name, email, role, status, created_at FROM users ORDER BY id DESC LIMIT 5")->fetchAll();

// Real Ongoing Projects from Database
$real_projects = $db->query("
    SELECT p.*, u.name as contractor_name 
    FROM projects p 
    LEFT JOIN users u ON p.contractor_id = u.id 
    ORDER BY p.id DESC LIMIT 4
")->fetchAll();

// Greeting helper based on hour of day
$hour = date('H');
$greeting = ($hour < 12) ? __('greeting.morning') : (($hour < 18) ? __('greeting.afternoon') : __('greeting.evening'));
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="dashboard-content">
        <!-- Dashboard Top Greeting Header Banner -->
        <div class="bc-header-banner mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="h2 fw-extrabold mb-1 bc-greeting-title text-white">
                    <?= $greeting ?>, <span class="bc-greeting-name text-warning"><?= e(explode(' ', $user['name'])[0]) ?></span> <span>👋</span>
                </h1>
                <p class="bc-greeting-subtitle small mb-0 text-light opacity-90"><?= __('dash.admin_subtitle') ?></p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="bc-date-range-control bg-white rounded-3 px-3 py-1.5 shadow-sm small fw-semibold d-flex align-items-center border">
                    <i class="fa-regular fa-calendar text-warning me-2"></i>
                    <span class="text-dark fw-bold"><?= format_date(date('Y-m-d', strtotime('-6 days'))) ?> - <?= format_date(date('Y-m-d')) ?></span>
                </div>
                <a href="<?= BASE_URL ?>/admin/bookings.php" class="btn btn-secondary btn-sm fw-semibold">
                    <i class="fa-solid fa-list-check me-1 text-primary"></i> <?= __('sidebar.bookings_quotes') ?>
                </a>
                <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-amber btn-sm fw-bold">
                    <i class="fa-solid fa-file-export me-1"></i> <?= __('sidebar.reports') ?>
                </a>
            </div>
        </div>

        <!-- 5 Metric KPI Cards Row -->
        <div class="row g-3 mb-4">
            <!-- Metric 1: Total Users -->
            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-metric-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bc-metric-icon-orange">
                            <i class="fa-solid fa-users fs-5"></i>
                        </div>
                        <span class="bc-metric-title"><?= __('dash.total_users') ?></span>
                    </div>
                    <div class="bc-metric-value mb-1"><?= number_format($total_users) ?></div>
                    <div class="bc-metric-footer d-flex align-items-center justify-content-between">
                        <span class="bc-trend-badge-up"><i class="fa-solid fa-arrow-up me-1"></i><?= __('status.active') ?></span>
                        <span class="extra-small text-muted"><?= __('admin.across_4_roles') ?></span>
                    </div>
                    <svg class="mt-2 w-100" height="24" viewBox="0 0 100 25" fill="none">
                        <path d="M0 20 Q 20 15, 40 18 T 80 5 T 100 2" stroke="#f97316" stroke-width="2.5" fill="none"/>
                    </svg>
                </div>
            </div>

            <!-- Metric 2: Total Companies -->
            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-metric-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bc-metric-icon-green">
                            <i class="fa-solid fa-building fs-5"></i>
                        </div>
                        <span class="bc-metric-title"><?= __('dash.total_contractors') ?></span>
                    </div>
                    <div class="bc-metric-value mb-1"><?= number_format($total_companies) ?></div>
                    <div class="bc-metric-footer d-flex align-items-center justify-content-between">
                        <span class="bc-trend-badge-up"><i class="fa-solid fa-circle-check me-1"></i><?= __('status.verified') ?></span>
                        <span class="extra-small text-muted"><?= __('admin.licensed_entities') ?></span>
                    </div>
                    <svg class="mt-2 w-100" height="24" viewBox="0 0 100 25" fill="none">
                        <path d="M0 22 Q 25 10, 50 16 T 80 8 T 100 3" stroke="#10b981" stroke-width="2.5" fill="none"/>
                    </svg>
                </div>
            </div>

            <!-- Metric 3: Total Workers -->
            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-metric-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bc-metric-icon-blue">
                            <i class="fa-solid fa-helmet-safety fs-5"></i>
                        </div>
                        <span class="bc-metric-title"><?= __('dash.total_workers') ?></span>
                    </div>
                    <div class="bc-metric-value mb-1"><?= number_format($total_workers) ?></div>
                    <div class="bc-metric-footer d-flex align-items-center justify-content-between">
                        <span class="bc-trend-badge-up"><i class="fa-solid fa-briefcase me-1"></i><?= __('worker.trade_skills') ?></span>
                        <span class="extra-small text-muted"><?= __('admin.trades_summary') ?></span>
                    </div>
                    <svg class="mt-2 w-100" height="24" viewBox="0 0 100 25" fill="none">
                        <path d="M0 18 Q 30 22, 60 10 T 90 6 T 100 1" stroke="#0284c7" stroke-width="2.5" fill="none"/>
                    </svg>
                </div>
            </div>

            <!-- Metric 4: Total Clients -->
            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-metric-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bc-metric-icon-amber">
                            <i class="fa-solid fa-user-tie fs-5"></i>
                        </div>
                        <span class="bc-metric-title"><?= __('dash.total_clients') ?></span>
                    </div>
                    <div class="bc-metric-value mb-1"><?= number_format($total_clients) ?></div>
                    <div class="bc-metric-footer d-flex align-items-center justify-content-between">
                        <span class="bc-trend-badge-up"><i class="fa-solid fa-chart-line me-1"></i><?= __('sidebar.clients') ?></span>
                        <span class="extra-small text-muted"><?= __('admin.project_owners') ?></span>
                    </div>
                    <svg class="mt-2 w-100" height="24" viewBox="0 0 100 25" fill="none">
                        <path d="M0 24 Q 20 18, 45 12 T 75 14 T 100 2" stroke="#f59e0b" stroke-width="2.5" fill="none"/>
                    </svg>
                </div>
            </div>

            <!-- Metric 5: Pending Verification -->
            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-metric-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bc-metric-icon-red">
                            <i class="fa-solid fa-shield-halved fs-5"></i>
                        </div>
                        <span class="bc-metric-title"><?= __('dash.pending_verifications') ?></span>
                    </div>
                    <div class="bc-metric-value mb-1"><?= number_format($pending_verifications) ?></div>
                    <div class="bc-metric-footer d-flex align-items-center justify-content-between">
                        <span class="<?= $pending_verifications > 0 ? 'bc-trend-badge-down' : 'bc-trend-badge-up' ?>">
                            <i class="fa-solid fa-clock me-1"></i><?= $pending_verifications > 0 ? __('admin.action_required') : __('admin.all_clear') ?>
                        </span>
                        <a href="<?= BASE_URL ?>/admin/verification.php" class="extra-small fw-semibold text-decoration-none" style="color: var(--bc-orange);"><?= __('admin.review_arrow') ?></a>
                    </div>
                    <svg class="mt-2 w-100" height="24" viewBox="0 0 100 25" fill="none">
                        <path d="M0 20 Q 30 15, 50 18 T 85 8 T 100 4" stroke="#ef4444" stroke-width="2.5" fill="none"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Middle Row Grid (User Roles Distribution, Recent Registrations, Quick Actions) -->
        <div class="row g-4 mb-4">
            <!-- User Acquisition Overview Donut Chart -->
            <div class="col-xl-4 col-lg-6">
                <div class="bc-surface-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="h6 fw-bold mb-0 bc-card-title"><i class="fa-solid fa-chart-pie text-warning me-2"></i><?= __('admin.user_roles_distribution') ?></h3>
                            <span class="badge bg-secondary-subtle"><?= __('admin.real_db_data') ?></span>
                        </div>
                        <div style="height: 220px; position: relative;" class="my-2">
                            <canvas id="userRoleDonutChart"></canvas>
                            <!-- Center Donut Labels -->
                            <div class="bc-chart-center-label">
                                <div class="bc-chart-number"><?= $total_users ?></div>
                                <div class="bc-chart-label"><?= __('dash.total_users') ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="border-top pt-3 mt-2 d-flex justify-content-around text-center extra-small">
                        <div>
                            <div class="fw-bold text-dark fs-6"><?= $total_workers ?></div>
                            <div class="text-secondary small"><?= __('sidebar.workers') ?></div>
                        </div>
                        <div>
                            <div class="fw-bold text-dark fs-6"><?= $total_contractors ?></div>
                            <div class="text-secondary small"><?= __('sidebar.contractors') ?></div>
                        </div>
                        <div>
                            <div class="fw-bold text-dark fs-6"><?= $total_clients ?></div>
                            <div class="text-secondary small"><?= __('sidebar.clients') ?></div>
                        </div>
                        <div>
                            <div class="fw-bold text-dark fs-6"><?= $total_admins ?></div>
                            <div class="text-secondary small"><?= __('role.admin') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Registrations Table -->
            <div class="col-xl-5 col-lg-6">
                <div class="bc-surface-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="h6 fw-bold mb-0 bc-card-title"><i class="fa-solid fa-user-plus text-warning me-2"></i><?= __('admin.recent_registrations') ?></h3>
                            <a href="<?= BASE_URL ?>/admin/users.php" class="bc-view-all-link extra-small fw-bold"><?= __('btn.view_all') ?> <i class="fa-solid fa-arrow-right ms-1"></i></a>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 extra-small">
                                <thead>
                                    <tr>
                                        <th style="min-width: 170px;"><?= __('table.name_email') ?></th>
                                        <th class="text-center" style="width: 90px;"><?= __('table.role') ?></th>
                                        <th class="text-center" style="width: 90px;"><?= __('table.status') ?></th>
                                        <th style="width: 95px;"><?= __('table.joined') ?></th>
                                        <th class="text-end" style="width: 45px;"><?= __('table.action') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_users as $ru): 
                                        $initials = strtoupper(substr($ru['name'], 0, 2));
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning fw-bold text-center d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.75rem; flex-shrink: 0;">
                                                        <?= $initials ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark" style="color: #0F172A !important; line-height: 1.25;"><?= e($ru['name']) ?></div>
                                                        <div class="text-secondary" style="font-size: 0.75rem; color: #475569 !important;"><?= e($ru['email']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle text-capitalize fw-semibold"><?= __('role.' . strtolower($ru['role'])) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $ru['status'] === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' ?> fw-semibold"><?= translate_status($ru['status']) ?></span>
                                            </td>
                                            <td class="text-secondary small fw-medium" style="color: #475569 !important; white-space: nowrap;"><?= format_date($ru['created_at']) ?></td>
                                            <td class="text-end">
                                                <a href="<?= BASE_URL ?>/admin/users.php?search=<?= urlencode($ru['email']) ?>" class="btn btn-sm btn-outline-secondary p-1 px-2" title="View in Users" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center;">
                                                    <i class="fa-solid fa-arrow-up-right-from-square text-secondary"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Panel -->
            <div class="col-xl-3 col-lg-12">
                <div class="bc-surface-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="h6 fw-bold mb-0 bc-card-title"><i class="fa-solid fa-bolt text-warning me-2"></i><?= __('dash.quick_actions') ?></h3>
                            <span class="badge bg-secondary-subtle"><?= __('admin.quick_actions_title') ?></span>
                        </div>
                        
                        <a href="#" data-bs-toggle="modal" data-bs-target="#addUserModal" class="bc-quick-action-item">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bc-metric-icon-orange"><i class="fa-solid fa-user-plus fs-6"></i></div>
                                <div>
                                    <div class="fw-bold small"><?= __('sidebar.user_management') ?></div>
                                    <div class="extra-small"><?= __('sidebar.users') ?></div>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right small text-muted"></i>
                        </a>

                        <a href="#" data-bs-toggle="modal" data-bs-target="#createProjectModal" class="bc-quick-action-item">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bc-metric-icon-green"><i class="fa-solid fa-building-circle-check fs-6"></i></div>
                                <div>
                                    <div class="fw-bold small"><?= __('sidebar.create_project') ?></div>
                                    <div class="extra-small"><?= __('sidebar.projects') ?></div>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right small text-muted"></i>
                        </a>

                        <a href="<?= BASE_URL ?>/admin/verification.php" class="bc-quick-action-item">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bc-metric-icon-red"><i class="fa-solid fa-id-card fs-6"></i></div>
                                <div>
                                    <div class="fw-bold small"><?= __('sidebar.verification_queue') ?></div>
                                    <div class="extra-small"><?= __('dash.pending_verifications') ?>: <?= $pending_verifications ?></div>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right small text-muted"></i>
                        </a>

                        <a href="<?= BASE_URL ?>/admin/reports.php" class="bc-quick-action-item mb-0">
                            <div class="d-flex align-items-center gap-3">
                                <div class="stat-icon bc-metric-icon-blue"><i class="fa-solid fa-chart-pie fs-6"></i></div>
                                <div>
                                    <div class="fw-bold small"><?= __('sidebar.reports') ?></div>
                                    <div class="extra-small"><?= __('admin.reports_desc') ?></div>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right small text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add New User Quick Action Modal -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fs-6"><i class="fa-solid fa-user-plus text-warning me-2"></i><?= __('admin.add_user_modal_title') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?= BASE_URL ?>/admin/users.php" method="POST" data-loading="true">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="create_user">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label extra-small fw-bold"><?= __('admin.full_name_label') ?></label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Rajesh Kumar" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label extra-small fw-bold"><?= __('admin.email_address_label') ?></label>
                                <input type="email" name="email" class="form-control" placeholder="user@company.com" required>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label extra-small fw-bold"><?= __('admin.role_label') ?></label>
                                    <select name="role" class="form-select" required>
                                        <option value="worker"><?= __('role.worker') ?></option>
                                        <option value="contractor"><?= __('role.contractor') ?></option>
                                        <option value="client"><?= __('role.client') ?></option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label extra-small fw-bold"><?= __('admin.password_label') ?></label>
                                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= __('btn.cancel') ?></button>
                            <button type="submit" class="btn btn-amber btn-sm"><?= __('admin.create_user_btn') ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Create Project Quick Action Modal -->
        <div class="modal fade" id="createProjectModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fs-6"><i class="fa-solid fa-building-circle-check text-warning me-2"></i><?= __('admin.post_project_modal_title') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="<?= BASE_URL ?>/admin/projects.php" method="POST" data-loading="true">
                        <?= csrf_field() ?>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label extra-small fw-bold"><?= __('admin.project_title_label') ?></label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Metro Line 4 Structural Development" required>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label extra-small fw-bold"><?= __('admin.location_city_label') ?></label>
                                    <input type="text" name="city" class="form-control" placeholder="e.g. Ahmedabad, Gujarat" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label extra-small fw-bold"><?= __('admin.estimated_budget_label') ?></label>
                                    <input type="number" step="0.01" name="budget" class="form-control" placeholder="e.g. 4500000" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label extra-small fw-bold"><?= __('admin.project_desc_label') ?></label>
                                <textarea name="description" class="form-control" rows="3" placeholder="<?= e(__('admin.project_desc_placeholder')) ?>"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><?= __('btn.cancel') ?></button>
                            <button type="submit" class="btn btn-amber btn-sm"><?= __('admin.post_project_btn') ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Project Progress & Quick Insights Row -->
        <div class="row g-4 mb-4">
            <!-- Project Progress Card (Real Database Records) -->
            <div class="col-xl-8 col-lg-7">
                <div class="bc-surface-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h6 fw-bold mb-0 bc-card-title"><i class="fa-solid fa-list-check text-warning me-2"></i><?= __('admin.active_project_progress') ?></h3>
                        <a href="<?= BASE_URL ?>/admin/projects.php" class="bc-view-all-link extra-small fw-bold"><?= __('admin.view_all_n_projects', ['count' => $total_projects]) ?> <i class="fa-solid fa-arrow-right ms-1"></i></a>
                    </div>
                    
                    <div class="d-flex flex-column gap-3">
                        <?php if (empty($real_projects)): ?>
                            <div class="p-4 text-center text-muted"><?= __('contractor.no_projects') ?></div>
                        <?php else: ?>
                            <?php foreach ($real_projects as $proj): 
                                $pct = (int)($proj['progress_percent'] ?? 0);
                                $status_badge_class = 'bg-primary-subtle';
                                if ($proj['status'] === 'completed') $status_badge_class = 'bg-success-subtle';
                                elseif ($proj['status'] === 'on_hold') $status_badge_class = 'bg-danger-subtle';
                            ?>
                                <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border bg-light">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="stat-icon bc-metric-icon-blue">
                                            <i class="fa-solid fa-building"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark small mb-0.5"><?= e($proj['title']) ?></div>
                                            <div class="text-muted extra-small">
                                                <i class="fa-solid fa-location-dot me-1 text-danger"></i><?= e($proj['city'] ?? 'India') ?>
                                                &bull; <?= __('table.budget') ?>: <?= format_currency($proj['budget'] ?? 0) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end" style="min-width: 150px;">
                                        <div class="d-flex justify-content-between extra-small fw-bold mb-1">
                                            <span class="text-muted"><?= __('table.progress') ?></span>
                                            <span class="text-dark fw-extrabold"><?= $pct ?>%</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?= $pct ?>%;"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Insights Card (Real Database Metrics) -->
            <div class="col-xl-4 col-lg-5">
                <div class="bc-surface-card h-100">
                    <h3 class="h6 fw-bold mb-3 bc-card-title"><i class="fa-solid fa-lightbulb text-warning me-2"></i><?= __('admin.operational_insights') ?></h3>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <div class="p-3 rounded-3 text-center border bg-light">
                                <i class="fa-solid fa-building text-warning fs-5 mb-1"></i>
                                <div class="fs-4 fw-extrabold text-dark"><?= $active_projects_count ?></div>
                                <div class="extra-small text-muted"><?= __('admin.active_sites') ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 text-center border bg-light">
                                <i class="fa-solid fa-briefcase text-info fs-5 mb-1"></i>
                                <div class="fs-4 fw-extrabold text-dark"><?= $active_jobs ?></div>
                                <div class="extra-small text-muted"><?= __('admin.open_trade_jobs') ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 text-center border bg-light">
                                <i class="fa-solid fa-user-clock text-danger fs-5 mb-1"></i>
                                <div class="fs-4 fw-extrabold text-dark"><?= $pending_verifications ?></div>
                                <div class="extra-small text-muted"><?= __('admin.pending_verif') ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 text-center border bg-light">
                                <i class="fa-solid fa-indian-rupee-sign text-success fs-5 mb-1"></i>
                                <div class="fs-5 fw-extrabold text-dark text-truncate" title="<?= format_currency($total_budget_sum) ?>"><?= '₹' . number_format($total_budget_sum / 100000, 1) ?>L</div>
                                <div class="extra-small text-muted"><?= __('admin.total_project_volume') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- System Health Indicator -->
                    <div class="border rounded-3 p-2.5 bg-light d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <span class="rounded-circle bg-success d-inline-block" style="width: 10px; height: 10px;"></span>
                            <span class="extra-small fw-bold text-dark"><?= __('admin.db_services') ?></span>
                        </div>
                        <span class="badge bg-success-subtle extra-small"><?= __('admin.operational_100') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Audit Activities Row (Real Database Data) -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="bc-surface-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h6 fw-bold mb-0 bc-card-title"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i><?= __('admin.system_activity_logs') ?></h3>
                        <a href="<?= BASE_URL ?>/admin/audit-logs.php" class="bc-view-all-link extra-small fw-bold"><?= __('admin.view_full_audit') ?> <i class="fa-solid fa-arrow-right ms-1"></i></a>
                    </div>
                    
                    <div class="row g-3">
                        <?php if (empty($recent_activities)): ?>
                            <div class="col-12 text-center text-muted py-3"><?= __('admin.no_activities') ?></div>
                        <?php else: ?>
                            <?php foreach ($recent_activities as $act): ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="d-flex align-items-center gap-3 p-2.5 rounded-3 border bg-light">
                                        <span class="rounded-circle bg-warning p-1 text-white extra-small" style="width: 8px; height: 8px;"></span>
                                        <div class="text-truncate">
                                            <div class="fw-bold extra-small text-dark text-truncate"><?= e(translate_activity_action($act['action'] ?? 'Platform Event')) ?></div>
                                            <div class="extra-small text-muted text-truncate" style="font-size: 0.7rem;">
                                                <?= e($act['user_name'] ?: __('common.system')) ?> &bull; <?= format_datetime($act['created_at']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="d-flex flex-column flex-md-row justify-content-between align-items-center py-3 my-4 border-top text-muted extra-small">
            <div>© <?= date('Y') ?> BuildConnect. <?= __('admin.all_rights_reserved') ?></div>
            <div>
                <?= __('admin.footer_tagline') ?> <i class="fa-solid fa-helmet-safety text-warning ms-1"></i>
            </div>
        </footer>
    </main>
</div>

<!-- Chart.js Donut Visualization Script (Dynamic Real Data) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.getElementById('userRoleDonutChart');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    
    const workerCount = <?= (int)$total_workers ?>;
    const contractorCount = <?= (int)$total_contractors ?>;
    const clientCount = <?= (int)$total_clients ?>;
    const adminCount = <?= (int)$total_admins ?>;
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [
                '<?= addslashes(__('sidebar.workers')) ?> (' + workerCount + ')', 
                '<?= addslashes(__('sidebar.contractors')) ?> (' + contractorCount + ')', 
                '<?= addslashes(__('sidebar.clients')) ?> (' + clientCount + ')', 
                '<?= addslashes(__('role.admin')) ?> (' + adminCount + ')'
            ],
            datasets: [{
                data: [workerCount, contractorCount, clientCount, adminCount],
                backgroundColor: ['#0284c7', '#f97316', '#10b981', '#64748b'],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { 
                        color: '#334155', 
                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 11, weight: '600' },
                        boxWidth: 8,
                        boxHeight: 8,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 12
                    }
                },
                tooltip: {
                    backgroundColor: '#0B192C',
                    titleColor: '#FFFFFF',
                    bodyColor: '#E2E8F0',
                    borderColor: '#1E293B',
                    borderWidth: 1,
                    padding: 10,
                    boxPadding: 4,
                    usePointStyle: true
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
