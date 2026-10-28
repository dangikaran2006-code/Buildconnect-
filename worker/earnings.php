<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(ROLE_WORKER);

$page_title = "My Earnings - Worker - BuildConnect";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$user = get_logged_user();
$db = getDB();

$stmt = $db->prepare("SELECT hourly_rate, daily_rate FROM workers WHERE user_id = ?");
$stmt->execute([$user['id']]);
$worker_profile = $stmt->fetch();
$rate = (float)($worker_profile['hourly_rate'] ?? 500.00);

$stmt = $db->prepare("SELECT COALESCE(SUM(hours_worked), 0) as total_hours FROM attendance WHERE worker_id = ?");
$stmt->execute([$user['id']]);
$attendance = $stmt->fetch();
$total_hours = (float)($attendance['total_hours'] ?? 0);
$total_earnings = $total_hours * $rate;

// Fetch verified attendance records
$stmt = $db->prepare("
    SELECT a.*, p.title as project_title 
    FROM attendance a 
    LEFT JOIN projects p ON a.project_id = p.id 
    WHERE a.worker_id = ? 
    ORDER BY a.attendance_date DESC LIMIT 10
");
$stmt->execute([$user['id']]);
$recent_attendance = $stmt->fetchAll();
?>

<div class="bc-layout">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="bc-main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 fw-bold text-white mb-1"><i class="fa-solid fa-wallet text-warning me-2"></i>My Trade Earnings & Working Hours</h1>
                <p class="text-muted small mb-0">Track verified QR working hours, hourly rate breakdown, and payouts.</p>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="bc-card stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted extra-small uppercase fw-semibold">Total Earnings</div>
                            <div class="stat-value text-emerald"><?= format_currency($total_earnings) ?></div>
                            <div class="text-emerald extra-small"><i class="fa-solid fa-check-double me-1"></i>Verified log payout</div>
                        </div>
                        <div class="stat-icon emerald"><i class="fa-solid fa-money-bill-wave"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="bc-card stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted extra-small uppercase fw-semibold">Total On-Site Hours</div>
                            <div class="stat-value text-white"><?= number_format($total_hours, 1) ?> hrs</div>
                            <div class="text-info extra-small"><i class="fa-solid fa-clock me-1"></i>QR Verified</div>
                        </div>
                        <div class="stat-icon blue"><i class="fa-solid fa-user-clock"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="bc-card stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-muted extra-small uppercase fw-semibold">Hourly Trade Rate</div>
                            <div class="stat-value text-warning"><?= format_currency($rate) ?>/hr</div>
                            <div class="text-muted extra-small">Base agreement rate</div>
                        </div>
                        <div class="stat-icon amber"><i class="fa-solid fa-tag"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bc-card p-4">
            <h3 class="h5 fw-bold text-white mb-3"><i class="fa-solid fa-list-check text-warning me-2"></i>Verified Shift Logs</h3>
            <?php if (empty($recent_attendance)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fa-solid fa-clock fs-2 mb-2 d-block"></i>
                    No attendance logs recorded yet. Scan QR code on-site to record shifts.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead class="text-muted extra-small uppercase">
                            <tr>
                                <th>Date</th>
                                <th>Project</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Hours</th>
                                <th>Payout Calculation</th>
                                <th>Verification</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_attendance as $att): ?>
                                <tr>
                                    <td class="fw-semibold text-white"><?= date('M d, Y', strtotime($att['attendance_date'])) ?></td>
                                    <td><?= e($att['project_title'] ?: 'General Site Assignment') ?></td>
                                    <td class="text-muted small"><?= date('h:i A', strtotime($att['check_in_time'])) ?></td>
                                    <td class="text-muted small"><?= $att['check_out_time'] ? date('h:i A', strtotime($att['check_out_time'])) : '<span class="badge bg-warning text-dark">Active</span>' ?></td>
                                    <td class="fw-bold text-info"><?= number_format((float)$att['hours_worked'], 1) ?> hrs</td>
                                    <td class="fw-bold text-emerald"><?= format_currency((float)$att['hours_worked'] * $rate) ?></td>
                                    <td>
                                        <?php if ($att['verified_by_qr']): ?>
                                            <span class="badge bg-success bg-opacity-25 text-success border border-success"><i class="fa-solid fa-qrcode me-1"></i>QR Verified</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Manual Log</span>
                                        <?php endif; ?>
                                    </td>
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
