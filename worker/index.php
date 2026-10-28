<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('worker');

$page_title = __('dash.worker_title') . " - " . __('nav.brand');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$user = currentUser();
$db = getDB();
$user_id = (int)$user['id'];
$flash = get_flash_message();

// Fetch Worker Profile
$stmt = $db->prepare("SELECT * FROM workers WHERE user_id = ?");
$stmt->execute([$user_id]);
$worker = $stmt->fetch();
$worker_profile_id = $worker ? (int)$worker['id'] : 0;

// Fetch Metrics
$stmt_p = $db->prepare("SELECT COUNT(*) FROM project_members WHERE user_id = ?");
$stmt_p->execute([$user_id]);
$assigned_projects = (int)$stmt_p->fetchColumn();

$stmt_ap = $db->prepare("
    SELECT COUNT(*) 
    FROM project_members pm 
    JOIN projects p ON pm.project_id = p.id 
    WHERE pm.user_id = ? AND p.status = 'in_progress'
");
$stmt_ap->execute([$user_id]);
$active_projects = (int)$stmt_ap->fetchColumn();

$stmt_s = $db->prepare("SELECT COUNT(*) FROM worker_skills WHERE worker_id = ?");
$stmt_s->execute([$worker_profile_id]);
$skills_count = (int)$stmt_s->fetchColumn();

$stmt_exp = $db->prepare("SELECT COUNT(*) FROM worker_experience WHERE worker_id = ?");
$stmt_exp->execute([$worker_profile_id]);
$experience_count = (int)$stmt_exp->fetchColumn();

$stmt_d = $db->prepare("SELECT COUNT(*) FROM worker_documents WHERE worker_id = ? AND status = 'pending'");
$stmt_d->execute([$worker_profile_id]);
$pending_documents = (int)$stmt_d->fetchColumn();

$stmt_total_d = $db->prepare("SELECT COUNT(*) FROM worker_documents WHERE worker_id = ?");
$stmt_total_d->execute([$worker_profile_id]);
$total_documents = (int)$stmt_total_d->fetchColumn();

// Fetch Real Attendance Logs Count
$stmt_att = $db->prepare("SELECT COUNT(*) FROM attendance WHERE worker_id = ?");
$stmt_att->execute([$user_id]);
$attendance_shifts = (int)$stmt_att->fetchColumn();

// Profile Completion % Calculation
$completion = 0;
if (!empty($user['name'])) $completion += 10;
if (!empty($user['email'])) $completion += 10;
if (!empty($user['phone'])) $completion += 10;
if ($worker && !empty($worker['bio'])) $completion += 10;
if ($worker && !empty($worker['trade_title'])) $completion += 10;
if ($worker && !empty($worker['city'])) $completion += 10;
if ($skills_count > 0) $completion += 15;
if ($experience_count > 0) $completion += 15;
if ($total_documents > 0) $completion += 10;

// Fetch Recent Assigned Projects
$stmt_rp = $db->prepare("
    SELECT p.*, pm.role_in_project, pm.joined_at 
    FROM project_members pm 
    JOIN projects p ON pm.project_id = p.id 
    WHERE pm.user_id = ? 
    ORDER BY pm.id DESC LIMIT 5
");
$stmt_rp->execute([$user_id]);
$recent_projects = $stmt_rp->fetchAll();

// Greeting helper based on hour of day
$hour = date('H');
$greeting = ($hour < 12) ? __('greeting.morning') : (($hour < 18) ? __('greeting.afternoon') : __('greeting.evening'));
?>

<div class="bc-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="bc-main-content">
        <!-- Dashboard Top Greeting Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h2 fw-bold text-dark mb-1">
                    <?= $greeting ?>, <?= e(explode(' ', $user['name'])[0]) ?> 👋
                </h1>
                <p class="text-muted small mb-0"><?= __('dash.worker_subtitle') ?></p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= BASE_URL ?>/worker/nearby-requests.php" class="btn btn-amber btn-sm fw-bold">
                    <i class="fa-solid fa-radar me-1"></i> <?= __('sidebar.nearby_requests') ?>
                </a>
                <a href="<?= BASE_URL ?>/worker/my-bookings.php" class="btn btn-outline-secondary btn-sm fw-bold">
                    <i class="fa-solid fa-clipboard-check me-1"></i> <?= __('sidebar.my_bookings') ?>
                </a>
                <a href="<?= BASE_URL ?>/worker/profile.php" class="btn btn-outline-dark btn-sm fw-bold">
                    <i class="fa-solid fa-user me-1"></i> <?= __('sidebar.profile') ?>
                </a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= sanitize($flash['type']) ?> alert-dismissible fade show mb-4" role="alert">
                <i class="fa-solid fa-circle-info me-2"></i> <?= sanitize($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Profile Completion Alert Card -->
        <div class="bc-card p-4 mb-4 border-start border-4 border-warning">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <h2 class="h6 fw-bold text-dark mb-0"><?= __('worker.profile_completion') ?>: <span class="font-monospace text-primary"><?= $completion ?>%</span></h2>
                        <?php 
                            $v_st = strtolower($worker['verification_status'] ?? 'pending');
                            $v_bg = ($v_st === 'approved' || $v_st === 'verified') ? 'bg-success' : (($v_st === 'rejected') ? 'bg-danger' : 'bg-warning text-dark');
                        ?>
                        <span class="badge <?= $v_bg ?> text-uppercase font-monospace extra-small">
                            <?= __('common.status') ?>: <?= translate_status($worker['verification_status'] ?? 'pending') ?>
                        </span>
                    </div>
                    <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-amber" style="width: <?= $completion ?>%;"></div>
                    </div>
                    <p class="extra-small text-muted mb-0">
                        <?php if ($completion < 100): ?>
                            <?= __('worker.profile_completion_hint') ?>
                        <?php else: ?>
                            <?= __('worker.profile_completion_complete') ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="<?= BASE_URL ?>/worker/profile.php" class="btn btn-outline-warning btn-sm fw-bold">
                        <i class="fa-solid fa-pen-to-square me-1"></i> <?= __('worker.complete_profile') ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- 5 Metric Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary p-2.5 rounded-3"><i class="fa-solid fa-shield-halved fs-5"></i></div>
                        <span class="extra-small fw-bold text-muted"><?= __('worker.verification') ?></span>
                    </div>
                    <div class="fs-4 fw-bold text-capitalize mb-1 text-dark"><?= translate_status($worker['verification_status'] ?? 'Pending') ?></div>
                    <div class="extra-small text-muted"><?= __('worker.admin_review_status') ?></div>
                </div>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-success bg-opacity-10 text-success p-2.5 rounded-3"><i class="fa-solid fa-building-flag fs-5"></i></div>
                        <span class="extra-small fw-bold text-muted"><?= __('worker.assigned_sites') ?></span>
                    </div>
                    <div class="fs-3 fw-bold mb-1 text-dark"><?= number_format($assigned_projects) ?></div>
                    <div class="extra-small text-muted"><?= __('worker.active_sites_count', ['count' => number_format($active_projects)]) ?></div>
                </div>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-info bg-opacity-10 text-info p-2.5 rounded-3"><i class="fa-solid fa-screwdriver-wrench fs-5"></i></div>
                        <span class="extra-small fw-bold text-muted"><?= __('worker.trade_skills') ?></span>
                    </div>
                    <div class="fs-3 fw-bold mb-1 text-dark"><?= number_format($skills_count) ?></div>
                    <div class="extra-small text-muted"><?= __('worker.specializations') ?></div>
                </div>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning p-2.5 rounded-3"><i class="fa-solid fa-id-card fs-5"></i></div>
                        <span class="extra-small fw-bold text-muted"><?= __('worker.documents') ?></span>
                    </div>
                    <div class="fs-3 fw-bold mb-1 text-dark"><?= number_format($total_documents) ?></div>
                    <div class="extra-small text-muted"><?= __('worker.pending_review_count', ['count' => number_format($pending_documents)]) ?></div>
                </div>
            </div>

            <div class="col-xl col-md-4 col-sm-6">
                <div class="bc-card p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger p-2.5 rounded-3"><i class="fa-solid fa-calendar-check fs-5"></i></div>
                        <span class="extra-small fw-bold text-muted"><?= __('worker.attendance') ?></span>
                    </div>
                    <div class="fs-3 fw-bold mb-1 text-dark"><?= $attendance_shifts ?></div>
                    <div class="extra-small text-muted"><?= __('worker.verified_shifts_logged') ?></div>
                </div>
            </div>
        </div>

        <!-- Main Site Assignments Card Stream & Quick Actions -->
        <div class="row g-4 mb-4">
            <div class="col-xl-9 col-lg-8">
                <div class="bc-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold text-dark mb-0"><i class="fa-solid fa-building-flag text-warning me-2"></i><?= __('worker.active_site_assignments') ?></h2>
                        <a href="<?= BASE_URL ?>/worker/projects.php" class="extra-small fw-bold text-decoration-none hover-amber"><?= __('btn.view_all_projects') ?> <i class="fa-solid fa-arrow-right ms-1"></i></a>
                    </div>

                    <?php if (empty($recent_projects)): ?>
                        <div class="text-center py-4 extra-small text-muted">
                            <?= __('worker.no_site_assignments') ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0 extra-small">
                                <thead>
                                    <tr>
                                        <th><?= __('table.project_name') ?></th>
                                        <th><?= __('table.location') ?></th>
                                        <th><?= __('table.role_in_site') ?></th>
                                        <th><?= __('table.project_status') ?></th>
                                        <th><?= __('table.joined_date') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_projects as $p): ?>
                                        <tr>
                                            <td><strong class="text-dark"><?= e($p['title']) ?></strong></td>
                                            <td class="text-muted"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($p['location']) ?></td>
                                            <td><span class="badge bg-light text-secondary border"><?= e(translate_trade($p['role_in_project'])) ?></span></td>
                                            <td><span class="badge <?= get_status_badge_class($p['status']) ?>"><?= translate_status($p['status']) ?></span></td>
                                            <td class="text-muted"><?= format_date($p['joined_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Worker Quick Actions -->
            <div class="col-xl-3 col-lg-4">
                <div class="bc-card p-4 h-100">
                    <h3 class="h6 fw-bold text-dark mb-3"><i class="fa-solid fa-bolt text-warning me-2"></i><?= __('dash.quick_actions') ?></h3>

                    <div class="d-flex flex-column gap-2">
                        <a href="<?= BASE_URL ?>/worker/nearby-requests.php" class="d-flex align-items-center justify-content-between p-2.5 rounded-3 border bg-light text-decoration-none hover-card-lift">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon bg-warning bg-opacity-10 text-warning p-2 rounded-2"><i class="fa-solid fa-radar fs-6"></i></div>
                                <div>
                                    <div class="fw-bold extra-small text-dark"><?= __('sidebar.nearby_requests') ?></div>
                                    <div class="extra-small text-muted"><?= __('worker.browse_instant_jobs') ?></div>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right extra-small text-muted"></i>
                        </a>

                        <a href="<?= BASE_URL ?>/worker/skills.php" class="d-flex align-items-center justify-content-between p-2.5 rounded-3 border bg-light text-decoration-none hover-card-lift">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon bg-info bg-opacity-10 text-info p-2 rounded-2"><i class="fa-solid fa-screwdriver-wrench fs-6"></i></div>
                                <div>
                                    <div class="fw-bold extra-small text-dark"><?= __('worker.trade_skills') ?></div>
                                    <div class="extra-small text-muted"><?= __('worker.update_specializations') ?></div>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right extra-small text-muted"></i>
                        </a>

                        <a href="<?= BASE_URL ?>/worker/documents.php" class="d-flex align-items-center justify-content-between p-2.5 rounded-3 border bg-light text-decoration-none hover-card-lift">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon bg-primary bg-opacity-10 text-primary p-2 rounded-2"><i class="fa-solid fa-id-card fs-6"></i></div>
                                <div>
                                    <div class="fw-bold extra-small text-dark"><?= __('worker.identity_docs') ?></div>
                                    <div class="extra-small text-muted"><?= __('worker.identity_docs_sub') ?></div>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right extra-small text-muted"></i>
                        </a>

                        <a href="<?= BASE_URL ?>/worker/attendance.php" class="d-flex align-items-center justify-content-between p-2.5 rounded-3 border bg-light text-decoration-none hover-card-lift">
                            <div class="d-flex align-items-center gap-2">
                                <div class="stat-icon bg-success bg-opacity-10 text-success p-2 rounded-2"><i class="fa-solid fa-qrcode fs-6"></i></div>
                                <div>
                                    <div class="fw-bold extra-small text-dark"><?= __('worker.site_qr_checkin') ?></div>
                                    <div class="extra-small text-muted"><?= __('worker.scan_shift_attendance') ?></div>
                                </div>
                            </div>
                            <i class="fa-solid fa-chevron-right extra-small text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
