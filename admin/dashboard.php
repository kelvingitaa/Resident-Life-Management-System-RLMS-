<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../middleware/auth.php';

requireRole('admin');

$db = getDB();

// Get comprehensive statistics
$stats = [];

// User statistics
$stmt = $db->query("SELECT role, COUNT(*) as count FROM users WHERE status = 'active' GROUP BY role");
$user_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stats['applicants'] = $user_stats['applicant'] ?? 0;
$stats['residents'] = $user_stats['resident'] ?? 0;
$stats['staff'] = $user_stats['staff'] ?? 0;
$stats['total_users'] = array_sum($user_stats);

// Apartment statistics
$stmt = $db->query("SELECT status, COUNT(*) as count FROM apartments GROUP BY status");
$apt_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stats['vacant'] = $apt_stats['vacant'] ?? 0;
$stats['occupied'] = $apt_stats['occupied'] ?? 0;
$stats['maintenance_apt'] = $apt_stats['maintenance'] ?? 0;
$stats['total_apartments'] = array_sum($apt_stats);

// Financial statistics
$stmt = $db->query("
    SELECT 
        SUM(CASE WHEN status = 'completed' AND payment_type = 'rent' THEN amount ELSE 0 END) as rent_collected,
        SUM(CASE WHEN status = 'pending' AND payment_type = 'rent' THEN amount ELSE 0 END) as rent_pending,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_collected
    FROM payments
    WHERE MONTH(payment_date) = MONTH(CURRENT_DATE())
    AND YEAR(payment_date) = YEAR(CURRENT_DATE())
");
$financial = $stmt->fetch();

$stats['rent_collected'] = $financial['rent_collected'] ?? 0;
$stats['rent_pending'] = $financial['rent_pending'] ?? 0;
$stats['total_collected'] = $financial['total_collected'] ?? 0;

// Application statistics
$stmt = $db->query("SELECT status, COUNT(*) as count FROM applications GROUP BY status");
$app_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stats['pending_apps'] = $app_stats['pending'] ?? 0;
$stats['approved_apps'] = $app_stats['approved'] ?? 0;

// Contract statistics
$stmt = $db->query("SELECT COUNT(*) as count FROM contracts WHERE status = 'active'");
$stats['active_contracts'] = $stmt->fetch()['count'];

// Maintenance statistics
$stmt = $db->query("SELECT status, COUNT(*) as count FROM maintenance_requests GROUP BY status");
$maint_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$stats['pending_maint'] = $maint_stats['pending'] ?? 0;
$stats['in_progress_maint'] = $maint_stats['in_progress'] ?? 0;

// Monthly revenue trend (last 6 months)
$stmt = $db->query("
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        SUM(amount) as total
    FROM payments
    WHERE status = 'completed'
    AND payment_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month
");
$revenue_trend = $stmt->fetchAll();

// Recent activities
$stmt = $db->query("
    SELECT action, table_name, created_at, u.first_name, u.last_name
    FROM audit_log al
    LEFT JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
");
$recent_activities = $stmt->fetchAll();

$page_title = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . ' - ' . SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid py-4">
        <?php
        $flash = getFlash();
        if ($flash):
        ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-12">
                <h2>Administrator Dashboard</h2>
                <p class="text-muted">Comprehensive system overview and analytics</p>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2">Total Users</h6>
                                <h2 class="mb-0"><?php echo $stats['total_users']; ?></h2>
                                <small class="text-success">
                                    <i class="bi bi-people-fill"></i> Active
                                </small>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-people text-primary fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2">Apartments</h6>
                                <h2 class="mb-0"><?php echo $stats['total_apartments']; ?></h2>
                                <small class="text-success">
                                    <i class="bi bi-door-open"></i> <?php echo $stats['vacant']; ?> Vacant
                                </small>
                            </div>
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="bi bi-building text-info fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2">Monthly Revenue</h6>
                                <h2 class="mb-0"><?php echo formatCurrency($stats['rent_collected']); ?></h2>
                                <small class="text-warning">
                                    <i class="bi bi-clock"></i> <?php echo formatCurrency($stats['rent_pending']); ?> Pending
                                </small>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-cash-stack text-success fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2">Active Contracts</h6>
                                <h2 class="mb-0"><?php echo $stats['active_contracts']; ?></h2>
                                <small class="text-info">
                                    <i class="bi bi-file-text"></i> Current
                                </small>
                            </div>
                            <div class="bg-dark bg-opacity-10 p-3 rounded">
                                <i class="bi bi-file-earmark-text text-dark fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Secondary Metrics -->
        <div class="row g-4 mb-4">
            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="bi bi-person-check text-success fs-2"></i>
                        <h4 class="mt-2 mb-0"><?php echo $stats['residents']; ?></h4>
                        <small class="text-muted">Residents</small>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="bi bi-person-plus text-warning fs-2"></i>
                        <h4 class="mt-2 mb-0"><?php echo $stats['applicants']; ?></h4>
                        <small class="text-muted">Applicants</small>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="bi bi-clipboard-check text-info fs-2"></i>
                        <h4 class="mt-2 mb-0"><?php echo $stats['pending_apps']; ?></h4>
                        <small class="text-muted">Pending Apps</small>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="bi bi-tools text-danger fs-2"></i>
                        <h4 class="mt-2 mb-0"><?php echo $stats['pending_maint']; ?></h4>
                        <small class="text-muted">Maintenance</small>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="bi bi-door-closed text-primary fs-2"></i>
                        <h4 class="mt-2 mb-0"><?php echo $stats['occupied']; ?></h4>
                        <small class="text-muted">Occupied</small>
                    </div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body">
                        <i class="bi bi-person-gear text-secondary fs-2"></i>
                        <h4 class="mt-2 mb-0"><?php echo $stats['staff']; ?></h4>
                        <small class="text-muted">Staff</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Revenue Chart -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Revenue Trend (Last 6 Months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="80"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="users.php" class="btn btn-outline-primary text-start">
                                <i class="bi bi-people me-2"></i>Manage Users
                            </a>
                            <a href="apartments.php" class="btn btn-outline-info text-start">
                                <i class="bi bi-building me-2"></i>View Apartments
                            </a>
                            <a href="payments.php" class="btn btn-outline-success text-start">
                                <i class="bi bi-cash-stack me-2"></i>Financial Reports
                            </a>
                            <a href="analytics.php" class="btn btn-outline-warning text-start">
                                <i class="bi bi-graph-up-arrow me-2"></i>Analytics
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Table</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(($activity['first_name'] ?? 'System') . ' ' . ($activity['last_name'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                            <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($activity['table_name'] ?? 'N/A'); ?></span></td>
                                            <td class="text-muted small"><?php echo formatDate($activity['created_at'], 'd M, H:i'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($revenue_trend, 'month')); ?>,
                datasets: [{
                    label: 'Revenue (KES)',
                    data: <?php echo json_encode(array_column($revenue_trend, 'total')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KES ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>