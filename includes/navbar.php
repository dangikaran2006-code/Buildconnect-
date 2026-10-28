<?php
$user = get_logged_user();
?>
<nav class="bc-navbar bc-navbar-modern d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-4">
        <a href="<?= BASE_URL ?>/index.php" class="bc-brand-logo flex-shrink-0">
            <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="BuildConnect Logo" class="bc-brand-logo-img">
            <span class="bc-brand-text"><span class="bc-brand-build">Build</span><span class="bc-brand-connect">Connect</span></span>
        </a>

        <!-- Header Navigation Links -->
        <?php $curr_script = basename($_SERVER['PHP_SELF']); ?>
        <div class="d-none d-lg-flex align-items-center gap-3 bc-nav-link-group ms-2">
            <a href="<?= BASE_URL ?>/index.php" class="bc-nav-link <?= ($curr_script === 'index.php' || $curr_script === '') ? 'active' : '' ?>"><?= __('nav.home') ?></a>
            <a href="<?= BASE_URL ?>/about.php" class="bc-nav-link <?= $curr_script === 'about.php' ? 'active' : '' ?>"><?= __('nav.about') ?></a>
            <a href="<?= BASE_URL ?>/services.php" class="bc-nav-link <?= $curr_script === 'services.php' ? 'active' : '' ?>"><?= __('nav.services') ?></a>
            <a href="<?= BASE_URL ?>/how-it-works.php" class="bc-nav-link <?= $curr_script === 'how-it-works.php' ? 'active' : '' ?>"><?= __('nav.how_it_works') ?></a>
            <a href="<?= BASE_URL ?>/index.php#contact" class="bc-nav-link <?= $curr_script === 'contact.php' ? 'active' : '' ?>"><?= __('nav.contact') ?></a>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 gap-md-3">
        <!-- Theme Toggle Icon -->
        <button class="btn btn-sm btn-link text-slate-400 p-1 text-decoration-none d-none d-sm-inline-block bc-theme-toggle-btn" type="button" title="<?= __('nav.toggle_theme') ?>">
            <i class="fa-regular fa-sun fs-5" style="color: #9FB6CF;"></i>
        </button>

        <?php if ($user): 
            $unread_count = get_unread_notification_count($user['id']);
            $nav_notifications = get_user_notifications($user['id'], 5);
            $user_role_label = __('role.' . $user['role']);
            if ($user['role'] === 'admin') $user_role_label = __('role.executive_admin');

            $user_avatar = (!empty($user['avatar']) && file_exists(__DIR__ . '/../assets/images/' . $user['avatar'])) ? $user['avatar'] : 'logo.png';
        ?>
            <!-- Notification Dropdown -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm position-relative text-light border-0" type="button" id="notifDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-bell text-warning fs-5" style="color: #FFAA16 !important;"></i>
                    <?php if ($unread_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                            <?= $unread_count ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end bg-white border border-slate-200 p-0 shadow-lg rounded-3" aria-labelledby="notifDropdown" style="width: 320px; max-height: 420px; overflow-y: auto;">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light rounded-top-3">
                        <span class="fw-bold text-dark small"><i class="fa-solid fa-bell text-warning me-1"></i> <?= __('nav.notifications') ?></span>
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-warning text-dark extra-small fw-bold"><?= $unread_count ?> <?= __('nav.new_badge') ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <?php if (empty($nav_notifications)): ?>
                            <div class="p-4 text-center text-muted extra-small"><?= __('nav.no_notifications') ?></div>
                        <?php else: ?>
                            <?php foreach ($nav_notifications as $n): ?>
                                <a href="<?= BASE_URL ?>/notifications.php?id=<?= $n['id'] ?>" class="list-group-item list-group-item-action bg-white text-dark border-bottom p-2.5 extra-small">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span class="fw-bold text-dark <?= !$n['is_read'] ? 'text-primary' : '' ?>"><?= sanitize(translate_notification_title($n['title'])) ?></span>
                                        <span class="text-muted extra-small" style="font-size: 0.65rem;"><?= format_datetime($n['created_at']) ?></span>
                                    </div>
                                    <p class="text-secondary extra-small mb-0 text-truncate" style="max-width: 280px;"><?= sanitize(translate_notification_message($n['message'])) ?></p>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="p-2 text-center border-top bg-light rounded-bottom-3">
                        <a href="<?= BASE_URL ?>/notifications.php" class="text-decoration-none extra-small fw-bold text-dark">
                            <?= __('nav.view_all_notifications') ?> <i class="fa-solid fa-arrow-right ms-1 text-warning"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- PROMINENT 🌐 Language Dropdown (Positioned Near Profile / Settings Area) -->
        <div class="dropdown bc-lang-dropdown">
            <button class="btn bc-lang-selector-btn" type="button" id="bcLangDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="<?= htmlspecialchars(__('nav.language')) ?>">
                <span class="bc-lang-globe">🌐</span>
                <span class="bc-lang-label"><?= __('nav.language') ?></span>
                <span class="badge bg-warning text-dark bc-lang-badge ms-1"><?= sanitize(current_lang_info()['native']) ?></span>
                <i class="fa-solid fa-chevron-down bc-lang-chevron ms-1"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="bcLangDropdown">
                <li class="dropdown-header text-uppercase px-3 py-1"><?= __('lang.select_language') ?></li>
                <li>
                    <a class="dropdown-item d-flex align-items-center justify-content-between px-3 py-2 <?= current_lang() === 'en' ? 'active' : '' ?>" href="<?= htmlspecialchars(get_lang_switch_url('en')) ?>">
                        <span class="d-flex align-items-center gap-2">
                            <span>🇬🇧</span>
                            <span>English</span>
                        </span>
                        <?php if (current_lang() === 'en'): ?>
                            <i class="fa-solid fa-check text-warning ms-2"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center justify-content-between px-3 py-2 <?= current_lang() === 'hi' ? 'active' : '' ?>" href="<?= htmlspecialchars(get_lang_switch_url('hi')) ?>">
                        <span class="d-flex align-items-center gap-2">
                            <span>🇮🇳</span>
                            <span>हिन्दी (Hindi)</span>
                        </span>
                        <?php if (current_lang() === 'hi'): ?>
                            <i class="fa-solid fa-check text-warning ms-2"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center justify-content-between px-3 py-2 <?= current_lang() === 'gu' ? 'active' : '' ?>" href="<?= htmlspecialchars(get_lang_switch_url('gu')) ?>">
                        <span class="d-flex align-items-center gap-2">
                            <span>🇮🇳</span>
                            <span>ગુજરાતી (Gujarati)</span>
                        </span>
                        <?php if (current_lang() === 'gu'): ?>
                            <i class="fa-solid fa-check text-warning ms-2"></i>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>

        <?php if ($user): ?>
            <!-- User Profile Avatar Pill (Name first, Role underneath) -->
            <div class="d-flex align-items-center gap-2 ps-2 border-start border-secondary border-opacity-50">
                <img src="<?= BASE_URL ?>/assets/images/<?= sanitize($user_avatar) ?>" alt="Avatar" class="rounded-circle border border-2 border-warning" style="width: 36px; height: 36px; object-fit: cover;">
                <div class="profile-info text-start d-flex flex-column justify-content-center ms-1 me-1" style="line-height: 1.2; min-width: 130px; flex-shrink: 0;">
                    <div class="fw-bold profile-name bc-user-name"><?= sanitize($user['name'] ?? 'User') ?></div>
                    <div class="extra-small profile-role bc-user-role"><?= sanitize($user_role_label) ?></div>
                </div>
            </div>

            <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline-danger btn-sm ms-1"><?= __('nav.logout') ?></a>
        <?php else: 
            $curr_page = basename($_SERVER['PHP_SELF']);
        ?>
            <a href="<?= BASE_URL ?>/login.php" class="btn bc-auth-nav-btn bc-auth-nav-login <?= $curr_page === 'login.php' ? 'active' : '' ?> btn-sm"><i class="fa-solid fa-user me-1.5"></i><?= __('nav.login') ?></a>
            <a href="<?= BASE_URL ?>/register.php" class="btn bc-auth-nav-btn bc-auth-nav-register <?= $curr_page === 'register.php' ? 'active' : '' ?> btn-sm"><i class="fa-solid fa-user-plus me-1.5"></i><?= __('nav.register') ?></a>
        <?php endif; ?>

        <!-- Mobile Toggle Button -->
        <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bc-toggle="navbar" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
</nav>


<!-- Mobile Navigation Drawer -->
<div id="bc-navbar-menu" class="d-none bg-dark border-bottom border-secondary p-3 d-lg-none">
    <div class="d-flex flex-column gap-2 mb-3">
        <a href="<?= BASE_URL ?>/index.php" class="text-light text-decoration-none small py-1"><?= __('nav.home') ?></a>
        <a href="<?= BASE_URL ?>/about.php" class="text-light text-decoration-none small py-1 opacity-75"><?= __('nav.about') ?></a>
        <a href="<?= BASE_URL ?>/services.php" class="text-light text-decoration-none small py-1 opacity-75"><?= __('nav.services') ?></a>
        <a href="<?= BASE_URL ?>/how-it-works.php" class="text-light text-decoration-none small py-1 opacity-75"><?= __('nav.how_it_works') ?></a>
        <a href="<?= BASE_URL ?>/index.php#contact" class="text-light text-decoration-none small py-1 opacity-75"><?= __('nav.contact') ?></a>
    </div>
    <div class="pt-2 border-top border-secondary">
        <div class="extra-small text-muted mb-2 text-uppercase fw-bold">🌐 <?= __('nav.language') ?></div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= htmlspecialchars(get_lang_switch_url('en')) ?>" class="btn btn-sm <?= (current_lang() === 'en') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary text-light' ?> extra-small py-1 px-2.5">
                🇬🇧 English
            </a>
            <a href="<?= htmlspecialchars(get_lang_switch_url('hi')) ?>" class="btn btn-sm <?= (current_lang() === 'hi') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary text-light' ?> extra-small py-1 px-2.5">
                🇮🇳 हिन्दी (Hindi)
            </a>
            <a href="<?= htmlspecialchars(get_lang_switch_url('gu')) ?>" class="btn btn-sm <?= (current_lang() === 'gu') ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary text-light' ?> extra-small py-1 px-2.5">
                🇮🇳 ગુજરાતી (Gujarati)
            </a>
        </div>
    </div>
</div>
