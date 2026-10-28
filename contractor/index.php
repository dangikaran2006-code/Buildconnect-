<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('contractor');

$page_title = "Contractor Dashboard - BuildConnect";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$user = currentUser();
$db = getDB();
$contractor_id = (int)$user['id'];

$flash = get_flash_message();

// DB statistics isolated strictly to logged-in contractor
$stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE contractor_id = ?");
$stmt->execute([$contractor_id]);
$total_projects = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE contractor_id = ? AND status IN ('in_progress', 'active')");
$stmt->execute([$contractor_id]);
$active_projects = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM projects WHERE contractor_id = ? AND status = 'completed'");
$stmt->execute([$contractor_id]);
$completed_projects = (int)$stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT COUNT(DISTINCT pm.user_id) 
    FROM project_members pm 
    JOIN projects p ON pm.project_id = p.id 
    WHERE p.contractor_id = ? AND pm.user_id != ?
");
$stmt->execute([$contractor_id, $contractor_id]);
$workers_assigned = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM jobs WHERE contractor_id = ? AND status = 'open'");
$stmt->execute([$contractor_id]);
$active_jobs = (int)$stmt->fetchColumn();

$stmt = $db->prepare("
    SELECT COUNT(*) 
    FROM job_applications ja 
    JOIN jobs j ON ja.job_id = j.id 
    WHERE j.contractor_id = ? AND ja.status = 'pending'
");
$stmt->execute([$contractor_id]);
$pending_applications = (int)$stmt->fetchColumn();

// Fetch Recent Projects for this contractor only
$stmt = $db->prepare("SELECT * FROM projects WHERE contractor_id = ? ORDER BY id DESC LIMIT 5");
$stmt->execute([$contractor_id]);
$recent_projects = $stmt->fetchAll();

// Fetch Recent Jobs for this contractor only
$stmt = $db->prepare("
    SELECT j.*, p.title as project_title 
    FROM jobs j 
    JOIN projects p ON j.project_id = p.id 
    WHERE j.contractor_id = ? 
    ORDER BY j.id DESC LIMIT 5
");
$stmt->execute([$contractor_id]);
$recent_jobs = $stmt->fetchAll();
?>

<div class="bc-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="bc-main-content">
        <!-- Top Banner Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 fw-bold text-dark mb-1">
                    <i class="fa-solid fa-gauge-high text-warning me-2"></i><?= __('role.contractor') ?> <?= __('sidebar.dashboard') ?>
                </h1>
                <p class="text-muted small mb-0">
                    <?= __('dash.welcome_back', ['name' => '<strong class="text-dark">' . e($user['name']) . '</strong>']) ?> <?= __('dash.contractor_subtitle') ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/contractor/quote-requests.php" class="btn btn-outline-info btn-sm fw-bold">
                    <i class="fa-solid fa-file-invoice me-1"></i> <?= __('sidebar.quotation_requests') ?>
                </a>
                <a href="<?= BASE_URL ?>/contractor/create-project.php" class="btn btn-amber btn-sm fw-bold">
                    <i class="fa-solid fa-plus me-1"></i> <?= __('sidebar.create_project') ?>
                </a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= sanitize($flash['type']) ?> alert-dismissible fade show mb-4" role="alert">
                <i class="fa-solid fa-circle-info me-2"></i> <?= sanitize($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards Grid -->
        <div class="row g-3 mb-4">
            <div class="col-xl-4 col-md-6">
                <div class="bc-card p-3 h-100 hover-card-lift">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted extra-small text-uppercase fw-semibold"><?= __('contractor.total_projects') ?></span>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning p-2 rounded"><i class="fa-solid fa-building fs-5"></i></div>
                    </div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format($total_projects) ?></div>
                    <div class="text-muted extra-small mt-1"><?= __('contractor.managed_company') ?></div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="bc-card p-3 h-100 hover-card-lift">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted extra-small text-uppercase fw-semibold"><?= __('contractor.active_projects') ?></span>
                        <div class="stat-icon bg-info bg-opacity-10 text-info p-2 rounded"><i class="fa-solid fa-person-digging fs-5"></i></div>
                    </div>
                    <div class="fs-3 fw-bold text-info"><?= number_format($active_projects) ?></div>
                    <div class="text-muted extra-small mt-1"><?= __('contractor.currently_in_progress') ?></div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="bc-card p-3 h-100 hover-card-lift">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted extra-small text-uppercase fw-semibold"><?= __('contractor.completed_projects') ?></span>
                        <div class="stat-icon bg-success bg-opacity-10 text-success p-2 rounded"><i class="fa-solid fa-circle-check fs-5"></i></div>
                    </div>
                    <div class="fs-3 fw-bold text-success"><?= number_format($completed_projects) ?></div>
                    <div class="text-muted extra-small mt-1"><?= __('contractor.successfully_delivered') ?></div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="bc-card p-3 h-100 hover-card-lift">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted extra-small text-uppercase fw-semibold"><?= __('contractor.workforce_assigned') ?></span>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning p-2 rounded"><i class="fa-solid fa-helmet-safety fs-5"></i></div>
                    </div>
                    <div class="fs-3 fw-bold text-warning"><?= number_format($workers_assigned) ?></div>
                    <div class="text-muted extra-small mt-1"><?= __('contractor.active_site_workers') ?></div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="bc-card p-3 h-100 hover-card-lift">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted extra-small text-uppercase fw-semibold"><?= __('contractor.active_jobs') ?></span>
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary p-2 rounded"><i class="fa-solid fa-briefcase fs-5"></i></div>
                    </div>
                    <div class="fs-3 fw-bold text-primary"><?= number_format($active_jobs) ?></div>
                    <div class="text-muted extra-small mt-1"><?= __('contractor.open_recruitment') ?></div>
                </div>
            </div>

            <div class="col-xl-4 col-md-6">
                <div class="bc-card p-3 h-100 hover-card-lift">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted extra-small text-uppercase fw-semibold"><?= __('contractor.pending_applications') ?></span>
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning p-2 rounded"><i class="fa-solid fa-file-signature fs-5"></i></div>
                    </div>
                    <div class="fs-3 fw-bold text-dark"><?= number_format($pending_applications) ?></div>
                    <div class="text-muted extra-small mt-1"><?= __('contractor.applications_to_review') ?></div>
                </div>
            </div>
        </div>

        <!-- Recent Projects Section -->
        <div class="bc-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold text-dark mb-0">
                    <i class="fa-solid fa-list-check text-warning me-2"></i><?= __('contractor.recent_projects') ?>
                </h2>
                <a href="<?= BASE_URL ?>/contractor/projects.php" class="btn btn-outline-secondary btn-sm extra-small">
                    <?= __('btn.view_all_projects') ?> <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <?php if (empty($recent_projects)): ?>
                <div class="bc-empty-state py-4 text-center">
                    <i class="fa-solid fa-building fs-1 text-muted mb-2"></i>
                    <p class="mb-2 text-muted"><?= __('contractor.no_projects') ?></p>
                    <a href="<?= BASE_URL ?>/contractor/create-project.php" class="btn btn-amber btn-sm">
                        <i class="fa-solid fa-plus me-1"></i> <?= __('contractor.create_first_project') ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th><?= __('table.project_name') ?></th>
                                <th><?= __('table.location') ?></th>
                                <th><?= __('table.budget') ?></th>
                                <th><?= __('table.dates') ?></th>
                                <th><?= __('table.status') ?></th>
                                <th><?= __('table.progress') ?></th>
                                <th class="text-end"><?= __('table.action') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_projects as $p): ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>/contractor/project-details.php?id=<?= $p['id'] ?>" class="fw-bold text-dark text-decoration-none hover-amber">
                                            <?= e($p['title']) ?>
                                        </a>
                                        <?php if (!empty($p['project_type'])): ?>
                                            <div class="text-muted extra-small"><?= e($p['project_type']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-muted small"><i class="fa-solid fa-location-dot text-danger me-1"></i><?= e($p['location']) ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-dark font-monospace"><?= format_currency($p['budget']) ?></span>
                                    </td>
                                    <td>
                                        <div class="text-muted extra-small"><?= __('contractor.start_date_label', ['date' => format_date($p['start_date'])]) ?></div>
                                        <div class="text-muted extra-small"><?= __('contractor.end_date_label', ['date' => format_date($p['end_date'])]) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= get_status_badge_class($p['status']) ?>">
                                            <?= e(translate_status($p['status'])) ?>
                                        </span>
                                    </td>
                                    <td style="min-width: 140px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-amber" style="width: <?= (int)$p['progress_percent'] ?>%;"></div>
                                            </div>
                                            <span class="extra-small fw-bold text-dark font-monospace"><?= (int)$p['progress_percent'] ?>%</span>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/contractor/project-details.php?id=<?= $p['id'] ?>" class="btn btn-outline-warning btn-sm extra-small">
                                            <?= __('contractor.view_project') ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Jobs Section -->
        <div class="bc-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold text-dark mb-0">
                    <i class="fa-solid fa-briefcase text-info me-2"></i><?= __('contractor.active_job_postings') ?>
                </h2>
                <a href="<?= BASE_URL ?>/contractor/jobs.php" class="btn btn-outline-secondary btn-sm extra-small">
                    <?= __('btn.view_all_jobs') ?> <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <?php if (empty($recent_jobs)): ?>
                <div class="bc-empty-state py-4 text-center">
                    <i class="fa-solid fa-briefcase fs-1 text-muted mb-2"></i>
                    <p class="mb-0 text-muted"><?= __('contractor.no_jobs') ?></p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th><?= __('table.job_title') ?></th>
                                <th><?= __('table.project') ?></th>
                                <th><?= __('table.trade') ?></th>
                                <th><?= __('table.pay_rate') ?></th>
                                <th><?= __('table.spots') ?></th>
                                <th><?= __('table.status') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_jobs as $j): ?>
                                <tr>
                                    <td><strong class="text-dark"><?= e($j['title']) ?></strong></td>
                                    <td><span class="text-muted small"><?= e($j['project_title']) ?></span></td>
                                    <td><span class="badge bg-light text-secondary border"><?= e(translate_trade($j['trade_required'])) ?></span></td>
                                    <td><span class="text-dark font-monospace fw-bold"><?= format_currency($j['pay_rate']) ?> / <?= e(translate_pay_type($j['pay_type'])) ?></span></td>
                                    <td><span class="text-muted small"><?= (int)$j['spots_filled'] ?> / <?= (int)$j['spots_available'] ?></span></td>
                                    <td><span class="badge <?= get_status_badge_class($j['status']) ?>"><?= e(translate_status($j['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
