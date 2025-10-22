<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../middleware/auth.php';

requireRole('admin');

$db = getDB();

// Get user demographics - MODIFIED to not use gender
$stmt = $db->query("
    SELECT 
        role,
        COUNT(*) as count
    FROM users
    WHERE status = 'active' AND role IN ('resident', 'applicant')
    GROUP BY role
");
$gender_stats = $stmt->fetchAll();

// Get age distribution - REMOVED or replace with another metric
// $stmt = $db->query("
//     SELECT 
//         CASE 
//             WHEN YEAR(CURRENT_DATE) - YEAR(date_of_birth) < 20 THEN 'Under 20'
//             WHEN YEAR(CURRENT_DATE) - YEAR(date_of_birth) BETWEEN 20 AND 25 THEN '20-25'
//             WHEN YEAR(CURRENT_DATE) - YEAR(date_of_birth) BETWEEN 26 AND 30 THEN '26-30'
//             WHEN YEAR(CURRENT_DATE) - YEAR(date_of_birth) BETWEEN 31 AND 40 THEN '31-40'
//             ELSE '40+'
//         END as age_group,
//         COUNT(*) as count
//     FROM users
//     WHERE status = 'active' AND date_of_birth IS NOT NULL
//     GROUP BY age_group
//     ORDER BY age_group
// ");
// $age_distribution = $stmt->fetchAll();

// Get application trends (last 12 months)
$stmt = $db->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total_applications,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM applications
    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month
");
$application_trends = $stmt->fetchAll();

// Get occupancy trends
$stmt = $db->query("
    SELECT 
        b.name as building,
        COUNT(a.id) as total_units,
        SUM(CASE WHEN a.status = 'occupied' THEN 1 ELSE 0 END) as occupied,
        ROUND((SUM(CASE WHEN a.status = 'occupied' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as occupancy_rate
    FROM buildings b
    LEFT JOIN apartments a ON b.id = a.building_id
    GROUP BY b.id, b.name
    ORDER BY occupancy_rate DESC
");
$occupancy_by_building = $stmt->fetchAll();

// Get apartment type preferences
$stmt = $db->query("
    SELECT 
        apartment_type_preference as type,
        COUNT(*) as count
    FROM applications
    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
    GROUP BY type
    ORDER BY count DESC
");
$type_preferences = $stmt->fetchAll();

// Get maintenance category distribution - MODIFIED to not use category
$stmt = $db->query("
    SELECT 
        priority as category,
        COUNT(*) as count,
        AVG(CASE 
            WHEN status = 'completed' AND completed_date IS NOT NULL 
            THEN TIMESTAMPDIFF(HOUR, created_at, completed_date) 
            ELSE NULL 
        END) as avg_resolution_hours
    FROM maintenance_requests
    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 3 MONTH)
    GROUP BY priority
    ORDER BY count DESC
");
$maintenance_by_category = $stmt->fetchAll();

// Get payment collection rate
$stmt = $db->query("
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as collected,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending,
        COUNT(*) as total_transactions
    FROM payments
    WHERE payment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month
");
$payment_collection = $stmt->fetchAll();

$page_title = 'Analytics & Insights';
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
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="bi bi-graph-up-arrow me-2"></i>Analytics & Insights</h2>
                <p class="text-muted">Comprehensive data analysis and trends</p>
            </div>
        </div>

        <!-- Demographics Section -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Gender Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="genderChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Age Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="ageChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application Trends -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Application Trends (Last 12 Months)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="applicationTrendChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Occupancy by Building -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Occupancy by Building</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($occupancy_by_building as $building): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold"><?php echo htmlspecialchars($building['building']); ?></span>
                                <span><?php echo $building['occupied']; ?>/<?php echo $building['total_units']; ?> (<?php echo $building['occupancy_rate']; ?>%)</span>
                            </div>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $building['occupancy_rate']; ?>%">
                                    <?php echo $building['occupancy_rate']; ?>%
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Apartment Type Preferences</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="typePreferenceChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Maintenance Analytics -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Maintenance by Category</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Count</th>
                                        <th>Avg. Resolution</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($maintenance_by_category as $cat): ?>
                                    <tr>
                                        <td><?php echo MAINTENANCE_CATEGORIES[$cat['category']] ?? ucfirst($cat['category']); ?></td>
                                        <td><?php echo $cat['count']; ?></td>
                                        <td>
                                            <?php 
                                            if ($cat['avg_resolution_hours']) {
                                                echo round($cat['avg_resolution_hours'], 1) . ' hours';
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Payment Collection Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="paymentCollectionChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gender Distribution Chart
        new Chart(document.getElementById('genderChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map('ucfirst', array_column($gender_stats, 'gender'))); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($gender_stats, 'count')); ?>,
                    backgroundColor: ['#0d6efd', '#dc3545', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });

        // Age Distribution Chart
        new Chart(document.getElementById('ageChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($age_distribution, 'age_group')); ?>,
                datasets: [{
                    label: 'Count',
                    data: <?php echo json_encode(array_column($age_distribution, 'count')); ?>,
                    backgroundColor: 'rgba(13, 110, 253, 0.7)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: { y: { beginAtZero: true } }
            }
        });

        // Application Trend Chart
        new Chart(document.getElementById('applicationTrendChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($application_trends, 'month')); ?>,
                datasets: [{
                    label: 'Total Applications',
                    data: <?php echo json_encode(array_column($application_trends, 'total_applications')); ?>,
                    borderColor: 'rgb(13, 110, 253)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true
                }, {
                    label: 'Approved',
                    data: <?php echo json_encode(array_column($application_trends, 'approved')); ?>,
                    borderColor: 'rgb(25, 135, 84)',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true
                }, {
                    label: 'Rejected',
                    data: <?php echo json_encode(array_column($application_trends, 'rejected')); ?>,
                    borderColor: 'rgb(220, 53, 69)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: { y: { beginAtZero: true } }
            }
        });

        // Type Preference Chart
        new Chart(document.getElementById('typePreferenceChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_map(function($t) { return ucwords(str_replace('_', ' ', $t)); }, array_column($type_preferences, 'type'))); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($type_preferences, 'count')); ?>,
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545', '#0dcaf0']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });

        // Payment Collection Chart
        new Chart(document.getElementById('paymentCollectionChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($payment_collection, 'month')); ?>,
                datasets: [{
                    label: 'Collected',
                    data: <?php echo json_encode(array_column($payment_collection, 'collected')); ?>,
                    backgroundColor: 'rgba(25, 135, 84, 0.7)'
                }, {
                    label: 'Pending',
                    data: <?php echo json_encode(array_column($payment_collection, 'pending')); ?>,
                    backgroundColor: 'rgba(255, 193, 7, 0.7)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
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