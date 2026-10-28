<?php
$page_title = "BuildConnect | Smart Construction Management Platform";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$flash = get_flash_message();
$user = get_logged_user();
?>

<?php if ($flash): ?>
    <div class="container mt-3">
        <div class="alert alert-<?= sanitize($flash['type']) ?> alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-circle-check text-success' : 'fa-circle-info text-warning' ?> me-2"></i>
            <?= sanitize($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<!-- Exact Screenshot Hero & Platform Section -->
<header class="bc-hero-exact">
    <div class="container">
        <!-- Hero Upper Row -->
        <div class="row align-items-center mb-5 pb-lg-4">
            <div class="col-lg-7 text-start position-relative" style="z-index: 3;">
                <span class="badge border border-warning text-warning bg-dark bg-opacity-75 rounded-pill px-3 py-2 fw-semibold small d-inline-flex align-items-center gap-2 mb-3.5">
                    <i class="fa-solid fa-microchip text-warning"></i> <?= __('home.hero_badge') ?>
                </span>
                <h1 class="display-3 fw-bold text-white mb-3 brand-font lh-sm">
                    <?= __('home.hero_title_1') ?><br>
                    <span class="text-warning"><?= __('home.hero_title_2') ?></span>
                </h1>
                <p class="lead text-slate-300 mb-4 me-lg-4" style="max-width: 580px; font-size: 1.05rem; line-height: 1.6;">
                    <?= __('home.hero_desc') ?>
                </p>

                <div class="d-flex flex-wrap align-items-center gap-3 pt-2">
                    <?php if ($user): ?>
                        <a href="<?= BASE_URL ?>/<?= get_role_redirect_url($user['role']) ?>" class="btn btn-amber px-4 py-2.5 fw-bold rounded-3">
                            <i class="fa-solid fa-user-plus me-2"></i> <?= __('home.go_dashboard') ?> <i class="fa-solid fa-arrow-right ms-2"></i>
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/register.php" class="btn btn-amber px-4 py-2.5 fw-bold rounded-3">
                            <i class="fa-solid fa-user-plus me-2"></i> <?= __('home.get_started') ?> <i class="fa-solid fa-arrow-right ms-2"></i>
                        </a>
                    <?php endif; ?>
                    <a href="#platform" class="btn btn-outline-light px-4 py-2.5 fw-semibold rounded-3">
                        <i class="fa-solid fa-circle-play me-2 text-warning"></i> <?= __('home.explore_buildconnect') ?>
                    </a>
                </div>
            </div>

            <!-- Sunset Handwritten Graphic Overlay -->
            <div class="bc-sunset-accent d-none d-xl-block">
                <span class="bc-handwriting"><?= __('home.smarter_construction') ?></span>
                <div class="bc-swoosh"></div>
            </div>
        </div>

        <!-- Platform & Role Cards Side-by-Side Lower Row -->
        <div id="platform" class="pt-4 border-top border-secondary border-opacity-25">
            <div class="row align-items-center g-4">
                <!-- Left Title Column -->
                <div class="col-lg-4 text-start">
                    <span class="badge border border-secondary text-slate-300 bg-dark rounded-pill px-3 py-1.5 extra-small fw-semibold mb-2"><?= __('home.our_platform') ?></span>
                    <h2 class="h1 fw-bold text-white mb-2 brand-font"><?= __('home.platform_title') ?></h2>
                    <p class="text-slate-400 small mb-0" style="max-width: 360px; line-height: 1.6;">
                        <?= __('home.platform_desc') ?>
                    </p>
                </div>

                <!-- Right 3 Role Cards Row -->
                <div class="col-lg-8">
                    <div class="row g-3 text-start">
                        <!-- CONTRACTORS CARD -->
                        <div class="col-md-4">
                            <div class="bc-platform-card">
                                <div>
                                    <div class="stat-icon amber mb-3">
                                        <i class="fa-solid fa-users text-warning fs-5"></i>
                                    </div>
                                    <h3 class="h6 text-white fw-bold mb-1.5 brand-font"><?= __('home.contractors_title') ?></h3>
                                    <p class="text-slate-400 extra-small mb-3" style="line-height: 1.5;">
                                        <?= __('home.contractors_desc') ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <a href="<?= BASE_URL ?>/<?= $user ? get_role_redirect_url($user['role']) : 'register.php?role=contractor' ?>" class="text-warning text-decoration-none fw-bold fs-5 hover-warning">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- WORKERS CARD -->
                        <div class="col-md-4">
                            <div class="bc-platform-card">
                                <div>
                                    <div class="stat-icon blue mb-3">
                                        <i class="fa-solid fa-helmet-safety text-info fs-5"></i>
                                    </div>
                                    <h3 class="h6 text-white fw-bold mb-1.5 brand-font"><?= __('home.workers_title') ?></h3>
                                    <p class="text-slate-400 extra-small mb-3" style="line-height: 1.5;">
                                        <?= __('home.workers_desc') ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <a href="<?= BASE_URL ?>/<?= $user ? get_role_redirect_url($user['role']) : 'register.php?role=worker' ?>" class="text-warning text-decoration-none fw-bold fs-5 hover-warning">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- CLIENTS CARD -->
                        <div class="col-md-4">
                            <div class="bc-platform-card">
                                <div>
                                    <div class="stat-icon purple mb-3">
                                        <i class="fa-solid fa-building text-purple fs-5"></i>
                                    </div>
                                    <h3 class="h6 text-white fw-bold mb-1.5 brand-font"><?= __('home.clients_title') ?></h3>
                                    <p class="text-slate-400 extra-small mb-3" style="line-height: 1.5;">
                                        <?= __('home.clients_desc') ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <a href="<?= BASE_URL ?>/<?= $user ? get_role_redirect_url($user['role']) : 'register.php?role=client' ?>" class="text-warning text-decoration-none fw-bold fs-5 hover-warning">
                                        <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- How It Works Section -->
<section id="how-it-works" class="py-5 border-top border-secondary border-opacity-25" style="background: rgba(15, 23, 42, 0.4);">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="bc-section-title-badge"><?= __('home.how_it_works_badge') ?></span>
            <h2 class="h1 fw-bold text-white mb-2 brand-font"><?= __('home.how_it_works_title') ?></h2>
            <p class="text-slate-400 small mx-auto" style="max-width: 560px;">
                <?= __('home.how_it_works_desc') ?>
            </p>
        </div>

        <div class="row g-4 text-start">
            <div class="col-lg-3 col-sm-6">
                <div class="bc-step-card">
                    <div class="bc-step-number">01</div>
                    <h3 class="h5 text-white fw-bold mb-2 brand-font"><?= __('home.step1_title') ?></h3>
                    <p class="text-slate-400 small mb-0" style="line-height: 1.5;"><?= __('home.step1_desc') ?></p>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="bc-step-card">
                    <div class="bc-step-number">02</div>
                    <h3 class="h5 text-white fw-bold mb-2 brand-font"><?= __('home.step2_title') ?></h3>
                    <p class="text-slate-400 small mb-0" style="line-height: 1.5;"><?= __('home.step2_desc') ?></p>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="bc-step-card">
                    <div class="bc-step-number">03</div>
                    <h3 class="h5 text-white fw-bold mb-2 brand-font"><?= __('home.step3_title') ?></h3>
                    <p class="text-slate-400 small mb-0" style="line-height: 1.5;"><?= __('home.step3_desc') ?></p>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="bc-step-card">
                    <div class="bc-step-number">04</div>
                    <h3 class="h5 text-white fw-bold mb-2 brand-font"><?= __('home.step4_title') ?></h3>
                    <p class="text-slate-400 small mb-0" style="line-height: 1.5;"><?= __('home.step4_desc') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Key Features Section -->
<section id="services" class="py-5 border-top border-secondary border-opacity-25 bg-dark">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="bc-section-title-badge"><?= __('home.why_badge') ?></span>
            <h2 class="h1 fw-bold text-white mb-2 brand-font"><?= __('home.why_title') ?></h2>
            <p class="text-slate-400 small mx-auto" style="max-width: 600px;">
                <?= __('home.why_subtitle') ?>
            </p>
        </div>

        <div class="row g-4 text-start">
            <div class="col-md-6 col-lg-3">
                <div class="bc-feature-card">
                    <i class="fa-solid fa-diagram-project text-warning fs-3 mb-3"></i>
                    <h3 class="h6 text-white fw-bold mb-2 brand-font"><?= __('home.feat_pm_title') ?></h3>
                    <p class="text-slate-400 extra-small mb-0"><?= __('home.feat_pm_desc') ?></p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="bc-feature-card">
                    <i class="fa-solid fa-robot text-warning fs-3 mb-3"></i>
                    <h3 class="h6 text-white fw-bold mb-2 brand-font"><?= __('home.feat_ai_title') ?></h3>
                    <p class="text-slate-400 extra-small mb-0"><?= __('home.feat_ai_desc') ?></p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="bc-feature-card">
                    <i class="fa-solid fa-qrcode text-warning fs-3 mb-3"></i>
                    <h3 class="h6 text-white fw-bold mb-2 brand-font"><?= __('home.feat_qr_title') ?></h3>
                    <p class="text-slate-400 extra-small mb-0"><?= __('home.feat_qr_desc') ?></p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="bc-feature-card">
                    <i class="fa-solid fa-file-contract text-warning fs-3 mb-3"></i>
                    <h3 class="h6 text-white fw-bold mb-2 brand-font"><?= __('home.feat_contracts_title') ?></h3>
                    <p class="text-slate-400 extra-small mb-0"><?= __('home.feat_contracts_desc') ?></p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="bc-feature-card">
                    <i class="fa-solid fa-bell text-warning fs-3 mb-3"></i>
                    <h3 class="h6 text-white fw-bold mb-2 brand-font"><?= __('home.feat_notifs_title') ?></h3>
                    <p class="text-slate-400 extra-small mb-0"><?= __('home.feat_notifs_desc') ?></p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="bc-feature-card">
                    <i class="fa-solid fa-chart-pie text-warning fs-3 mb-3"></i>
                    <h3 class="h6 text-white fw-bold mb-2 brand-font"><?= __('home.feat_analytics_title') ?></h3>
                    <p class="text-slate-400 extra-small mb-0"><?= __('home.feat_analytics_desc') ?></p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="bc-feature-card">
                    <i class="fa-solid fa-map-location-dot text-warning fs-3 mb-3"></i>
                    <h3 class="h6 text-white fw-bold mb-2 brand-font"><?= __('home.feat_maps_title') ?></h3>
                    <p class="text-slate-400 extra-small mb-0"><?= __('home.feat_maps_desc') ?></p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="bc-feature-card">
                    <i class="fa-solid fa-brain text-warning fs-3 mb-3"></i>
                    <h3 class="h6 text-white fw-bold mb-2 brand-font"><?= __('home.feat_insights_title') ?></h3>
                    <p class="text-slate-400 extra-small mb-0"><?= __('home.feat_insights_desc') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Trust / Platform Section -->
<section id="about" class="py-5 border-top border-secondary border-opacity-25" style="background: rgba(15, 23, 42, 0.4);">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="bc-section-title-badge"><?= __('home.built_for_everyone') ?></span>
            <h2 class="h1 fw-bold text-white mb-2 brand-font"><?= __('home.built_title') ?></h2>
            <p class="text-slate-400 small mx-auto" style="max-width: 580px;">
                <?= __('home.built_subtitle') ?>
            </p>
        </div>

        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="bc-card p-4 h-100">
                    <i class="fa-solid fa-building-user text-warning fs-2 mb-3"></i>
                    <h3 class="h5 text-white fw-bold mb-2 brand-font"><?= __('home.for_contractors') ?></h3>
                    <p class="text-slate-400 small mb-0"><?= __('home.for_contractors_desc') ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bc-card p-4 h-100">
                    <i class="fa-solid fa-user-shield text-warning fs-2 mb-3"></i>
                    <h3 class="h5 text-white fw-bold mb-2 brand-font"><?= __('home.for_workers') ?></h3>
                    <p class="text-slate-400 small mb-0"><?= __('home.for_workers_desc') ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bc-card p-4 h-100">
                    <i class="fa-solid fa-chart-line text-warning fs-2 mb-3"></i>
                    <h3 class="h5 text-white fw-bold mb-2 brand-font"><?= __('home.for_clients') ?></h3>
                    <p class="text-slate-400 small mb-0"><?= __('home.for_clients_desc') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final Call to Action Section -->
<section class="py-5 bg-dark">
    <div class="container py-3">
        <div class="bc-cta-banner text-center">
            <h2 class="display-5 fw-bold text-white mb-3 brand-font"><?= __('home.ready_title') ?></h2>
            <p class="lead text-slate-300 mx-auto mb-4" style="max-width: 600px; font-size: 1.1rem;">
                <?= __('home.ready_desc') ?>
            </p>
            <div class="d-flex justify-content-center flex-wrap gap-3">
                <?php if ($user): ?>
                    <a href="<?= BASE_URL ?>/<?= get_role_redirect_url($user['role']) ?>" class="btn btn-amber px-4.5 py-3 fw-bold fs-6 rounded-3">
                        <i class="fa-solid fa-user-plus me-2"></i> <?= __('home.go_dashboard') ?> <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/register.php" class="btn btn-amber px-4.5 py-3 fw-bold fs-6 rounded-3">
                        <i class="fa-solid fa-user-plus me-2"></i> <?= __('home.get_started') ?> <i class="fa-solid fa-arrow-right ms-2"></i>
                    </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/services.php" class="btn btn-outline-light px-4.5 py-3 fw-semibold fs-6 rounded-3">
                    <?= __('home.explore_buildconnect') ?>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer id="contact" class="py-5 border-top border-secondary border-opacity-25" style="background-color: #060910;">
    <div class="container">
        <div class="row g-4 mb-4 text-start">
            <div class="col-lg-4">
                <a href="<?= BASE_URL ?>/index.php" class="bc-brand-logo mb-3">
                    <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="BuildConnect Logo" class="bc-brand-logo-img">
                    <span>Build<span class="text-warning">Connect</span></span>
                </a>

                <p class="text-slate-400 small mt-2" style="max-width: 320px; line-height: 1.6;">
                    <?= __('home.footer_desc') ?>
                </p>
            </div>
            <div class="col-6 col-lg-2">
                <h4 class="text-white small fw-bold text-uppercase mb-3 brand-font"><?= __('home.quick_links') ?></h4>
                <ul class="list-unstyled text-slate-400 small mb-0 d-flex flex-column gap-2">
                    <li><a href="<?= BASE_URL ?>/index.php" class="text-slate-400 text-decoration-none hover-warning"><?= __('nav.home') ?></a></li>
                    <li><a href="<?= BASE_URL ?>/about.php" class="text-slate-400 text-decoration-none hover-warning"><?= __('nav.about') ?></a></li>
                    <li><a href="<?= BASE_URL ?>/services.php" class="text-slate-400 text-decoration-none hover-warning"><?= __('nav.services') ?></a></li>
                    <li><a href="<?= BASE_URL ?>/how-it-works.php" class="text-slate-400 text-decoration-none hover-warning"><?= __('nav.how_it_works') ?></a></li>
                    <li><a href="<?= BASE_URL ?>/index.php#contact" class="text-slate-400 text-decoration-none hover-warning"><?= __('nav.contact') ?></a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h4 class="text-white small fw-bold text-uppercase mb-3 brand-font"><?= __('home.roles_portals') ?></h4>
                <ul class="list-unstyled text-slate-400 small mb-0 d-flex flex-column gap-2">
                    <li><a href="<?= BASE_URL ?>/register.php?role=contractor" class="text-slate-400 text-decoration-none hover-warning"><?= __('home.contractors_portal') ?></a></li>
                    <li><a href="<?= BASE_URL ?>/register.php?role=worker" class="text-slate-400 text-decoration-none hover-warning"><?= __('home.workers_portal') ?></a></li>
                    <li><a href="<?= BASE_URL ?>/register.php?role=client" class="text-slate-400 text-decoration-none hover-warning"><?= __('home.clients_portal') ?></a></li>
                    <li><a href="<?= BASE_URL ?>/login.php" class="text-slate-400 text-decoration-none hover-warning"><?= __('home.admin_access') ?></a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h4 class="text-white small fw-bold text-uppercase mb-3 brand-font"><?= __('home.contact_info') ?></h4>
                <p class="text-slate-400 small mb-2"><i class="fa-solid fa-location-dot me-2 text-warning"></i><?= __('home.location_val') ?></p>
                <p class="text-slate-400 small mb-2"><i class="fa-solid fa-envelope me-2 text-warning"></i>contact@buildconnect.demo</p>
                <p class="text-slate-400 small mb-0"><i class="fa-solid fa-phone me-2 text-warning"></i>+91 98765 43210</p>
            </div>
        </div>

        <div class="pt-4 border-top border-secondary border-opacity-25 text-center text-slate-400 small">
            &copy; <?= date('Y') ?> BuildConnect. <?= __('home.all_rights_reserved') ?>
        </div>
    </div>
</footer>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
