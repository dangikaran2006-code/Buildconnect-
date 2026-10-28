<?php
/**
 * BuildConnect Centralized Collapsible Dropdown Sidebar Navigation System
 * Unified navigation component across Admin, Worker, Client, and Contractor dashboards.
 */

$user = get_logged_user();
$current_page = str_replace('\\', '/', $_SERVER['PHP_SELF']);

if (!function_exists('is_active')) {
    function is_active($path, $current_page) {
        return (strpos($current_page, $path) !== false) ? 'active' : '';
    }
}

if (!function_exists('is_group_active')) {
    function is_group_active($paths, $current_page) {
        foreach ($paths as $path) {
            if (strpos($current_page, $path) !== false) {
                return 'is-open active-group';
            }
        }
        return '';
    }
}

$is_worker = ($user && isset($user['role']) && $user['role'] === ROLE_WORKER);
$is_admin  = ($user && isset($user['role']) && $user['role'] === ROLE_ADMIN);
$is_client = ($user && isset($user['role']) && $user['role'] === ROLE_CLIENT);
$is_contractor = ($user && isset($user['role']) && $user['role'] === ROLE_CONTRACTOR);

$sidebar_class = 'bc-sidebar';
$sidebar_id = '';

if ($is_worker) {
    $sidebar_class .= ' bc-sidebar-worker';
    $sidebar_id = 'id="bc-worker-sidebar"';
} elseif ($is_admin) {
    $sidebar_class .= ' bc-sidebar-admin';
    $sidebar_id = 'id="bc-admin-sidebar"';
} elseif ($is_client) {
    $sidebar_class .= ' bc-sidebar-client';
    $sidebar_id = 'id="bc-client-sidebar"';
} elseif ($is_contractor) {
    $sidebar_class .= ' bc-sidebar-contractor';
    $sidebar_id = 'id="bc-contractor-sidebar"';
}

$pending_verif_badge = 0;
$pending_review_badge = 0;
if ($is_admin) {
    try {
        $db_sb = getDB();
        $pending_verif_badge = (int)$db_sb->query("SELECT COUNT(*) FROM workers WHERE verification_status = 'pending'")->fetchColumn();
        $pending_review_badge = (int)$db_sb->query("SELECT COUNT(*) FROM review_reports WHERE status = 'pending'")->fetchColumn();
    } catch (Exception $e) {
        $pending_verif_badge = 0;
        $pending_review_badge = 0;
    }
}
?>
<aside class="<?= $sidebar_class ?>" <?= $sidebar_id ?>>

    <div class="px-2 mb-3">
        <div class="text-uppercase text-muted extra-small fw-bold tracking-wider" style="font-size: 0.68rem; color: var(--bc-text-secondary) !important;">
            <?= $user ? sanitize(strtoupper(__('role.control_center', ['role' => __('role.' . $user['role'])]))) : sanitize(strtoupper(__('common.navigation'))) ?>
        </div>
    </div>
    
    <ul class="bc-sidebar-nav">
        <?php if (!$user): ?>
            <!-- PUBLIC / GUEST MENU -->
            <li class="bc-nav-item <?= is_active('/index.php', $current_page) ?>">
                <a href="<?= BASE_URL ?>/index.php"><i class="fa-solid fa-house"></i> <?= __('sidebar.public_overview') ?></a>
            </li>
            <li class="bc-nav-item <?= is_active('/login.php', $current_page) ?>">
                <a href="<?= BASE_URL ?>/login.php"><i class="fa-solid fa-right-to-bracket"></i> <?= __('nav.login') ?></a>
            </li>
            <li class="bc-nav-item <?= is_active('/register.php', $current_page) ?>">
                <a href="<?= BASE_URL ?>/register.php"><i class="fa-solid fa-user-plus"></i> <?= __('nav.register') ?></a>
            </li>

        <?php elseif ($user['role'] === ROLE_ADMIN): ?>
            <!-- ========================================== -->
            <!-- ADMIN COLLAPSIBLE DROPDOWN NAVIGATION      -->
            <!-- ========================================== -->

            <!-- Direct Link: Dashboard -->
            <li class="bc-nav-item <?= is_active('/admin/index.php', $current_page) ?> mb-2">
                <a href="<?= BASE_URL ?>/admin/index.php">
                    <i class="fa-solid fa-gauge-high text-warning"></i>
                    <span><?= __('sidebar.dashboard') ?></span>
                </a>
            </li>

            <!-- Group 1: Users & Verification -->
            <?php 
                $admin_users_paths = ['/admin/users.php', '/admin/contractors.php', '/admin/clients.php', '/admin/verification.php'];
                $admin_users_state = is_group_active($admin_users_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $admin_users_state ?>" data-group="admin_users">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-users-gear text-info"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.users_verification') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <?php if ($pending_verif_badge > 0): ?>
                            <span class="badge rounded-pill bg-warning text-dark px-1.5 py-0.5 extra-small"><?= $pending_verif_badge ?></span>
                        <?php endif; ?>
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/admin/users.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/admin/users.php">
                            <span><i class="fa-solid fa-users text-info"></i> <?= __('sidebar.users_directory') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/admin/contractors.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/admin/contractors.php">
                            <span><i class="fa-solid fa-building text-warning"></i> <?= __('sidebar.companies_contractors') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/admin/clients.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/admin/clients.php">
                            <span><i class="fa-solid fa-user-tie text-info"></i> <?= __('sidebar.clients_directory') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/admin/verification.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/admin/verification.php">
                            <span><i class="fa-solid fa-id-card text-success"></i> <?= __('sidebar.verification_queue') ?></span>
                            <?php if ($pending_verif_badge > 0): ?>
                                <span class="badge rounded-pill bg-warning text-dark px-1.5 py-0.5 extra-small"><?= $pending_verif_badge ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Group 2: Projects & Operations -->
            <?php 
                $admin_proj_paths = ['/admin/bookings.php', '/admin/projects.php', '/admin/jobs.php', '/admin/applications.php'];
                $admin_proj_state = is_group_active($admin_proj_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $admin_proj_state ?>" data-group="admin_projects">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-layer-group text-warning"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.projects_work') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/admin/bookings.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/admin/bookings.php">
                            <span><i class="fa-solid fa-list-check text-warning"></i> <?= __('sidebar.bookings_quotes') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/admin/projects.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/admin/projects.php">
                            <span><i class="fa-solid fa-building text-info"></i> <?= __('sidebar.all_projects') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/admin/jobs.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/admin/jobs.php">
                            <span><i class="fa-solid fa-briefcase text-warning"></i> <?= __('sidebar.job_postings') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/admin/applications.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/admin/applications.php">
                            <span><i class="fa-solid fa-file-signature text-success"></i> <?= __('sidebar.applications') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Group 3: Monitoring & Audit -->
            <?php 
                $admin_mon_paths = ['/admin/reviews.php', '/admin/analytics.php', '/admin/ai-usage.php', '/admin/audit-logs.php'];
                $admin_mon_state = is_group_active($admin_mon_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $admin_mon_state ?>" data-group="admin_monitoring">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-chart-pie text-success"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.monitoring_audit') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <?php if ($pending_review_badge > 0): ?>
                            <span class="badge rounded-pill bg-danger text-white px-1.5 py-0.5 extra-small"><?= $pending_review_badge ?></span>
                        <?php endif; ?>
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/admin/reviews.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/admin/reviews.php">
                            <span><i class="fa-solid fa-star text-warning"></i> <?= __('sidebar.review_moderation') ?></span>
                            <?php if ($pending_review_badge > 0): ?>
                                <span class="badge rounded-pill bg-danger text-white px-1.5 py-0.5 extra-small"><?= $pending_review_badge ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/admin/analytics.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/admin/analytics.php">
                            <span><i class="fa-solid fa-chart-line text-warning"></i> <?= __('sidebar.platform_analytics') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/admin/ai-usage.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/admin/ai-usage.php">
                            <span><i class="fa-solid fa-brain text-info"></i> <?= __('sidebar.ai_usage_monitor') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/admin/audit-logs.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/admin/audit-logs.php">
                            <span><i class="fa-solid fa-shield-halved text-success"></i> <?= __('sidebar.system_audit_logs') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Group 4: System & Account -->
            <?php 
                $admin_sys_paths = ['/notifications.php', '/admin/settings.php'];
                $admin_sys_state = is_group_active($admin_sys_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $admin_sys_state ?>" data-group="admin_account">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-gear text-info"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.system_account') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/notifications.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/notifications.php">
                            <span><i class="fa-solid fa-bell text-warning"></i> <?= __('sidebar.notifications') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/admin/settings.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/admin/settings.php">
                            <span><i class="fa-solid fa-sliders text-info"></i> <?= __('sidebar.platform_settings') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Bottom: Logout -->
            <li class="bc-nav-item mt-3 pt-2 border-top border-secondary border-opacity-25">
                <a href="<?= BASE_URL ?>/logout.php" class="text-danger">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span><?= __('sidebar.logout') ?></span>
                </a>
            </li>

        <?php elseif ($user['role'] === ROLE_WORKER): ?>
            <!-- ========================================== -->
            <!-- WORKER COLLAPSIBLE DROPDOWN NAVIGATION     -->
            <!-- ========================================== -->

            <!-- Direct Link: Dashboard -->
            <li class="bc-nav-item <?= is_active('/worker/index.php', $current_page) ?> mb-2">
                <a href="<?= BASE_URL ?>/worker/index.php">
                    <i class="fa-solid fa-gauge-high text-warning"></i>
                    <span><?= __('sidebar.dashboard') ?></span>
                </a>
            </li>

            <!-- Group 1: Work & Jobs -->
            <?php 
                $worker_work_paths = ['/worker/nearby-requests.php', '/worker/my-bookings.php', '/worker/projects.php', '/worker/jobs.php', '/worker/job-details.php', '/worker/applications.php', '/worker/apply-job.php', '/worker/submit-quote.php'];
                $worker_work_state = is_group_active($worker_work_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $worker_work_state ?>" data-group="worker_work">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-briefcase text-warning"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.work_jobs') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/worker/nearby-requests.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/nearby-requests.php">
                            <span><i class="fa-solid fa-radar text-warning"></i> <?= __('sidebar.nearby_job_requests') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/worker/my-bookings.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/my-bookings.php">
                            <span><i class="fa-solid fa-clipboard-check text-success"></i> <?= __('sidebar.my_bookings') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/worker/projects.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/projects.php">
                            <span><i class="fa-solid fa-building text-warning"></i> <?= __('sidebar.assigned_projects') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/worker/jobs.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/jobs.php">
                            <span><i class="fa-solid fa-magnifying-glass text-info"></i> <?= __('sidebar.browse_jobs') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/worker/applications.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/applications.php">
                            <span><i class="fa-solid fa-file-signature text-success"></i> <?= __('sidebar.my_applications') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Group 2: Attendance & Earnings -->
            <?php 
                $worker_earn_paths = ['/worker/attendance.php', '/worker/scan-attendance.php', '/worker/earnings.php', '/worker/contracts.php', '/worker/contract-details.php', '/worker/analytics.php', '/worker/tasks.php', '/worker/task-details.php'];
                $worker_earn_state = is_group_active($worker_earn_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $worker_earn_state ?>" data-group="worker_earnings">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-wallet text-success"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.attendance_earnings') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/worker/attendance.php', $current_page) || is_active('/worker/scan-attendance.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/attendance.php">
                            <span><i class="fa-solid fa-qrcode text-warning"></i> <?= __('sidebar.qr_attendance') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/worker/earnings.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/earnings.php">
                            <span><i class="fa-solid fa-wallet text-success"></i> <?= __('sidebar.trade_earnings') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/worker/contracts.php', $current_page) || is_active('/worker/contract-details.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/contracts.php">
                            <span><i class="fa-solid fa-file-contract text-success"></i> <?= __('sidebar.digital_contracts') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/worker/tasks.php', $current_page) || is_active('/worker/task-details.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/tasks.php">
                            <span><i class="fa-solid fa-list-check text-info"></i> <?= __('sidebar.assigned_tasks') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/worker/analytics.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/analytics.php">
                            <span><i class="fa-solid fa-chart-line text-info"></i> <?= __('sidebar.work_analytics') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Group 3: Profile & Credentials -->
            <?php 
                $worker_prof_paths = ['/worker/profile.php', '/worker/edit-profile.php', '/worker/skills.php', '/worker/experience.php', '/worker/availability.php', '/worker/documents.php', '/worker/reviews.php'];
                $worker_prof_state = is_group_active($worker_prof_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $worker_prof_state ?>" data-group="worker_profile">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-id-card text-info"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.profile_skills') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= (is_active('/worker/profile.php', $current_page) || is_active('/worker/edit-profile.php', $current_page)) && !is_active('/worker/skills.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/profile.php">
                            <span><i class="fa-solid fa-user text-info"></i> <?= __('sidebar.trade_profile') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/worker/skills.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/skills.php">
                            <span><i class="fa-solid fa-screwdriver-wrench text-warning"></i> <?= __('sidebar.skills_matrix') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/worker/experience.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/experience.php">
                            <span><i class="fa-solid fa-briefcase text-info"></i> <?= __('sidebar.experience_log') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/worker/availability.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/availability.php">
                            <span><i class="fa-solid fa-calendar-check text-success"></i> <?= __('sidebar.availability') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/worker/documents.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/documents.php">
                            <span><i class="fa-solid fa-file-contract text-primary"></i> <?= __('sidebar.documents') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/worker/reviews.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/reviews.php">
                            <span><i class="fa-solid fa-star text-warning"></i> <?= __('sidebar.client_reviews') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Group 4: Account & Settings -->
            <?php 
                $worker_acc_paths = ['/notifications.php', '/worker/settings.php'];
                $worker_acc_state = is_group_active($worker_acc_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $worker_acc_state ?>" data-group="worker_account">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-sliders text-info"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.account_settings') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/notifications.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/notifications.php">
                            <span><i class="fa-solid fa-bell text-warning"></i> <?= __('sidebar.notifications') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/worker/settings.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/worker/settings.php">
                            <span><i class="fa-solid fa-sliders text-info"></i> <?= __('sidebar.account_settings') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Bottom: Logout -->
            <li class="bc-nav-item mt-3 pt-2 border-top border-secondary border-opacity-25">
                <a href="<?= BASE_URL ?>/logout.php" class="text-danger">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span><?= __('sidebar.logout') ?></span>
                </a>
            </li>

        <?php elseif ($user['role'] === ROLE_CLIENT): ?>
            <!-- ========================================== -->
            <!-- CLIENT COLLAPSIBLE DROPDOWN NAVIGATION     -->
            <!-- ========================================== -->

            <!-- Direct Link: Dashboard -->
            <li class="bc-nav-item <?= is_active('/client/index.php', $current_page) ?> mb-2">
                <a href="<?= BASE_URL ?>/client/index.php">
                    <i class="fa-solid fa-gauge-high text-warning"></i>
                    <span><?= __('sidebar.dashboard') ?></span>
                </a>
            </li>

            <!-- Group 1: Bookings & Services -->
            <?php 
                $client_book_paths = ['/client/create-booking.php', '/client/bookings.php', '/client/projects.php', '/client/project-details.php'];
                $client_book_state = is_group_active($client_book_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $client_book_state ?>" data-group="client_bookings">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-calendar-check text-warning"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.bookings_services') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/client/create-booking.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/client/create-booking.php">
                            <span><i class="fa-solid fa-calendar-plus text-warning"></i> <?= __('sidebar.book_service_post_job') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/client/bookings.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/client/bookings.php">
                            <span><i class="fa-solid fa-clipboard-list text-info"></i> <?= __('sidebar.bookings_quotes') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/client/projects.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/client/projects.php">
                            <span><i class="fa-solid fa-building text-info"></i> <?= __('sidebar.all_projects') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Group 2: Project Oversight -->
            <?php 
                $client_proj_paths = ['/client/milestones.php', '/client/tasks.php', '/client/progress.php'];
                $client_proj_state = is_group_active($client_proj_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $client_proj_state ?>" data-group="client_projects">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-list-check text-info"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.project_oversight') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/client/milestones.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/client/milestones.php">
                            <span><i class="fa-solid fa-flag-checkered text-warning"></i> <?= __('sidebar.project_milestones') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/client/tasks.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/client/tasks.php">
                            <span><i class="fa-solid fa-list-check text-info"></i> <?= __('sidebar.project_tasks') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/client/progress.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/client/progress.php">
                            <span><i class="fa-solid fa-chart-column text-success"></i> <?= __('sidebar.progress_reports') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Group 3: Finance & Reviews -->
            <?php 
                $client_fin_paths = ['/client/payments.php', '/client/contracts.php', '/client/reviews.php'];
                $client_fin_state = is_group_active($client_fin_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $client_fin_state ?>" data-group="client_finance">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-indian-rupee-sign text-success"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.finance_reviews') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/client/payments.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/client/payments.php">
                            <span><i class="fa-solid fa-indian-rupee-sign text-success"></i> <?= __('sidebar.payments_invoices') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/client/contracts.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/client/contracts.php">
                            <span><i class="fa-solid fa-file-contract text-warning"></i> <?= __('sidebar.digital_contracts') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/client/reviews.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/client/reviews.php">
                            <span><i class="fa-solid fa-star text-warning"></i> <?= __('sidebar.ratings_reviews') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Group 4: Account & Settings -->
            <?php 
                $client_acc_paths = ['/client/profile.php', '/notifications.php', '/client/settings.php'];
                $client_acc_state = is_group_active($client_acc_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $client_acc_state ?>" data-group="client_account">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-user-gear text-info"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.account_settings') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/client/profile.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/client/profile.php">
                            <span><i class="fa-solid fa-id-card text-info"></i> <?= __('sidebar.client_profile') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/notifications.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/notifications.php">
                            <span><i class="fa-solid fa-bell text-warning"></i> <?= __('sidebar.notifications') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/client/settings.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/client/settings.php">
                            <span><i class="fa-solid fa-sliders text-info"></i> <?= __('sidebar.account_settings') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Bottom: Logout -->
            <li class="bc-nav-item mt-3 pt-2 border-top border-secondary border-opacity-25">
                <a href="<?= BASE_URL ?>/logout.php" class="text-danger">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span><?= __('sidebar.logout') ?></span>
                </a>
            </li>

        <?php elseif ($user['role'] === ROLE_CONTRACTOR): ?>
            <!-- ========================================== -->
            <!-- CONTRACTOR COLLAPSIBLE DROPDOWN NAVIGATION -->
            <!-- ========================================== -->

            <!-- Direct Link: Dashboard -->
            <li class="bc-nav-item <?= is_active('/contractor/index.php', $current_page) ?> mb-2">
                <a href="<?= BASE_URL ?>/contractor/index.php">
                    <i class="fa-solid fa-gauge-high text-warning"></i>
                    <span><?= __('sidebar.dashboard') ?></span>
                </a>
            </li>

            <!-- Group 1: Projects & Operations -->
            <?php 
                $contractor_proj_paths = ['/contractor/projects.php', '/contractor/create-project.php', '/contractor/edit-project.php', '/contractor/project-details.php', '/contractor/milestones.php', '/contractor/tasks.php', '/contractor/create-task.php', '/contractor/edit-task.php', '/contractor/project-insights.php', '/contractor/analytics.php'];
                $contractor_proj_state = is_group_active($contractor_proj_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $contractor_proj_state ?>" data-group="contractor_projects">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-building text-info"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.projects_planning') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/contractor/projects.php', $current_page) && !is_active('/contractor/create-project.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/projects.php">
                            <span><i class="fa-solid fa-building text-info"></i> <?= __('sidebar.all_projects') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/contractor/create-project.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/create-project.php">
                            <span><i class="fa-solid fa-plus-circle text-success"></i> <?= __('sidebar.create_project') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/contractor/milestones.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/milestones.php">
                            <span><i class="fa-solid fa-flag-checkered text-warning"></i> <?= __('sidebar.project_milestones') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/contractor/tasks.php', $current_page) || is_active('/contractor/create-task.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/tasks.php">
                            <span><i class="fa-solid fa-list-check text-info"></i> <?= __('sidebar.task_management') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/contractor/project-insights.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/project-insights.php">
                            <span><i class="fa-solid fa-brain text-info"></i> <?= __('sidebar.project_health_risk') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/contractor/analytics.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/analytics.php">
                            <span><i class="fa-solid fa-chart-line text-warning"></i> <?= __('sidebar.analytics_engine') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Group 2: Workforce & Hiring -->
            <?php 
                $contractor_wf_paths = ['/contractor/quote-requests.php', '/contractor/workers.php', '/contractor/search-workers.php', '/contractor/job-matches.php', '/contractor/jobs.php', '/contractor/create-job.php', '/contractor/applications.php', '/contractor/contracts.php', '/contractor/create-contract.php'];
                $contractor_wf_state = is_group_active($contractor_wf_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $contractor_wf_state ?>" data-group="contractor_workforce">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-helmet-safety text-warning"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.workforce_hiring') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/contractor/quote-requests.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/quote-requests.php">
                            <span><i class="fa-solid fa-file-invoice text-info"></i> <?= __('sidebar.quotation_requests') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/contractor/workers.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/workers.php">
                            <span><i class="fa-solid fa-helmet-safety text-info"></i> <?= __('sidebar.assigned_workers') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/contractor/search-workers.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/search-workers.php">
                            <span><i class="fa-solid fa-magnifying-glass text-warning"></i> <?= __('sidebar.find_workers') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/contractor/job-matches.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/job-matches.php">
                            <span><i class="fa-solid fa-wand-magic-sparkles text-warning"></i> <?= __('sidebar.ai_worker_matching') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/contractor/jobs.php', $current_page) || is_active('/contractor/create-job.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/jobs.php">
                            <span><i class="fa-solid fa-briefcase text-warning"></i> <?= __('sidebar.trade_jobs') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/contractor/applications.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/applications.php">
                            <span><i class="fa-solid fa-file-signature text-success"></i> <?= __('sidebar.job_applications') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/contractor/contracts.php', $current_page) || is_active('/contractor/create-contract.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/contracts.php">
                            <span><i class="fa-solid fa-file-contract text-warning"></i> <?= __('sidebar.digital_contracts') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Group 3: Site Attendance -->
            <?php 
                $contractor_ops_paths = ['/contractor/attendance.php', '/contractor/generate-attendance.php'];
                $contractor_ops_state = is_group_active($contractor_ops_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $contractor_ops_state ?>" data-group="contractor_operations">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-qrcode text-success"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.site_attendance') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/contractor/generate-attendance.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/generate-attendance.php">
                            <span><i class="fa-solid fa-qrcode text-success"></i> <?= __('sidebar.qr_generator') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/contractor/attendance.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/attendance.php">
                            <span><i class="fa-solid fa-clipboard-user text-info"></i> <?= __('sidebar.attendance_logs') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Group 4: Account & Settings -->
            <?php 
                $contractor_acc_paths = ['/contractor/reviews.php', '/notifications.php', '/contractor/profile.php', '/contractor/settings.php'];
                $contractor_acc_state = is_group_active($contractor_acc_paths, $current_page);
            ?>
            <li class="bc-nav-dropdown <?= $contractor_acc_state ?>" data-group="contractor_account">
                <button type="button" class="bc-dropdown-toggle">
                    <span class="bc-dropdown-toggle-left">
                        <i class="fa-solid fa-user-gear text-info"></i>
                        <span class="bc-dropdown-title"><?= __('sidebar.company_settings') ?></span>
                    </span>
                    <span class="bc-dropdown-toggle-right">
                        <i class="fa-solid fa-chevron-down bc-dropdown-chevron"></i>
                    </span>
                </button>
                <ul class="bc-dropdown-menu">
                    <li class="bc-subnav-item <?= is_active('/contractor/reviews.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/reviews.php">
                            <span><i class="fa-solid fa-star text-warning"></i> <?= __('sidebar.client_reviews') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/notifications.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/notifications.php">
                            <span><i class="fa-solid fa-bell text-warning"></i> <?= __('sidebar.notifications') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/contractor/profile.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/profile.php">
                            <span><i class="fa-solid fa-id-card text-info"></i> <?= __('sidebar.company_profile') ?></span>
                        </a>
                    </li>
                    <li class="bc-subnav-item <?= is_active('/contractor/settings.php', $current_page) ?>">
                        <a href="<?= BASE_URL ?>/contractor/settings.php">
                            <span><i class="fa-solid fa-sliders text-info"></i> <?= __('sidebar.settings') ?></span>
                        </a>
                    </li>
                </ul>
            </li>

            <!-- Bottom: Logout -->
            <li class="bc-nav-item mt-3 pt-2 border-top border-secondary border-opacity-25">
                <a href="<?= BASE_URL ?>/logout.php" class="text-danger">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span><?= __('sidebar.logout') ?></span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</aside>
