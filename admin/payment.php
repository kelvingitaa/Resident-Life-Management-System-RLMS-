<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../middleware/auth.php';

requireRole('admin');

$db = getDB();

// Get date filters
$start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : date('Y-m-t');
$payment_type = isset($_GET['payment_type']) ? sanitize($_GET['payment_type']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Build query
$where_clauses = ["p.payment_date BETWEEN ? AND ?"];
$params = [$start_date, $end_date];

if ($payment_type) {
    $where_clauses[] = "p.payment_type = ?";
    $params[] = $payment_type;
}

if ($status) {
    $where_clauses[] = "p.status = ?";
    $params[] = $status;
}

$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

// Get all payments
$stmt = $db->prepare("
    SELECT p.*, 
           u.first_name, u.last_name, u.email,
           c.contract_number
    FROM payments p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN contracts c ON p.contract_id = c.id
    $where_sql
    ORDER BY p.payment_date DESC
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Get financial summary
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_transactions,
        SUM(amount) as total_amount,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'failed' THEN amount ELSE 0 END) as failed_amount,
        SUM(CASE WHEN payment_type = 'rent' AND status = 'completed' THEN amount ELSE 0 END) as rent_collected,
        SUM(CASE WHEN payment_type = 'deposit' AND status = 'completed' THEN amount ELSE 0 END) as deposits_collected
    FROM payments
    $where_sql
");
$stmt->execute($params);
$summary = $stmt->fetch();

// Get payment type breakdown
$stmt = $db->prepare("
    SELECT 
        payment_type,
        COUNT(*) as count,
        SUM(amount) as total
    FROM payments
    $where_sql
    GROUP BY payment_type
    ORDER BY total DESC
");
$stmt->execute($params);
$type_breakdown = $stmt->fetchAll();

// Get monthly revenue trend
$stmt = $db->query("
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') as month,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as revenue
    FROM payments
    WHERE payment_date >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
    GROUP BY month
    ORDER BY month
");
$revenue_trend = $stmt->fetchAll();

$page_title = 'Payment Management';
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
                <h2><i class="bi bi-cash-stack me-2"></i>Financial Management</h2>
            </div>
        </div>

        <!-- Financial Summary Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Revenue</h6>
                        <h3 class="text-success mb-0"><?php echo formatCurrency($summary['completed_amount']); ?></h3>
                        <small class="text-muted"><?php echo $summary['total_transactions']; ?> transactions</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Rent Collected</h6>
                        <h3 class="text-primary mb-0"><?php echo formatCurrency($summary['rent_collected']); ?></h3>
                        <small class="text-muted">From rent payments</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Pending Payments</h6>
                        <h3 class="text-warning mb-0"><?php echo formatCurrency($summary['pending_amount']); ?></h3>
                        <small class="text-muted">Awaiting payment</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Deposits Collected</h6>
                        <h3 class="text-info mb-0"><?php echo formatCurrency($summary['deposits_collected']); ?></h3>
                        <small class="text-muted">Security deposits</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Monthly Revenue Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="80"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Payment Type Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($type_breakdown as $type): ?>
                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <strong><?php echo ucfirst($type['payment_type']); ?></strong>
                                <br>
                                <small class="text-muted"><?php echo $type['count']; ?> payments</small>
                            </div>
                            <div class="text-end">
                                <strong><?php echo formatCurrency($type['total']); ?></strong>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Payment Type</label>
                        <select name="payment_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="rent" <?php echo $payment_type === 'rent' ? 'selected' : ''; ?>>Rent</option>
                            <option value="deposit" <?php echo $payment_type === 'deposit' ? 'selected' : ''; ?>>Deposit</option>
                            <option value="maintenance" <?php echo $payment_type === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="utility" <?php echo $payment_type === 'utility' ? 'selected' : ''; ?>>Utility</option>
                            <option value="penalty" <?php echo $payment_type === 'penalty' ? 'selected' : ''; ?>>Penalty</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-funnel me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Payment Transactions</h5>
                <button onclick="exportToCSV()" class="btn btn-sm btn-success">
                    <i class="bi bi-download me-2"></i>Export CSV
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="paymentsTable">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Date</th>
                                <th>Resident</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td class="fw-bold"><?php echo htmlspecialchars($payment['receipt_number'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($payment['payment_date']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($payment['email']); ?></small>
                                </td>
                                <td><span class="badge bg-light text-dark"><?php echo ucfirst($payment['payment_type']); ?></span></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                <td class="fw-bold"><?php echo formatCurrency($payment['amount']); ?></td>
                                <td>
                                    <?php
                                    $status_colors = [
                                        'completed' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        'refunded' => 'info'
                                    ];
                                    $color = $status_colors[$payment['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $color; ?>">
                                        <?php echo ucfirst($payment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($payment['receipt_file']): ?>
                                        <a href="../uploads/<?php echo htmlspecialchars($payment['receipt_file']); ?>" 
                                           class="btn btn-sm btn-outline-primary" target="_blank" title="View Receipt">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-secondary fw-bold">
                            <tr>
                                <td colspan="5" class="text-end">TOTAL:</td>
                                <td><?php echo formatCurrency($summary['total_amount']); ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($revenue_trend, 'month')); ?>,
                datasets: [{
                    label: 'Monthly Revenue (KES)',
                    data: <?php echo json_encode(array_column($revenue_trend, 'revenue')); ?>,
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
                    legend: { display: true }
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

        // Export to CSV
        function exportToCSV() {
            const table = document.getElementById('paymentsTable');
            let csv = [];
            
            table.querySelectorAll('thead tr').forEach(row => {
                const cols = [];
                row.querySelectorAll('th').forEach(th => cols.push(th.textContent.trim()));
                csv.push(cols.join(','));
            });
            
            table.querySelectorAll('tbody tr').forEach(row => {
                const cols = [];
                row.querySelectorAll('td').forEach(td => {
                    cols.push('"' + td.textContent.trim().replace(/"/g, '""') + '"');
                });
                csv.push(cols.join(','));
            });
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('href', url);
            a.setAttribute('download', 'payments_report_' + new Date().toISOString().slice(0,10) + '.csv');
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>