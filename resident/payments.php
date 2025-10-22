<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../middleware/auth.php';

requireRole('resident');

$user_id = getCurrentUserId();
$db = getDB();

// Get payments with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * RECORDS_PER_PAGE;

$stmt = $db->prepare("SELECT COUNT(*) as total FROM payments WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_records = $stmt->fetch()['total'];

$pagination = paginate($total_records, $page);

$stmt = $db->prepare("
    SELECT p.*, c.contract_number 
    FROM payments p
    LEFT JOIN contracts c ON p.contract_id = c.id
    WHERE p.user_id = ?
    ORDER BY p.payment_date DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$user_id, $pagination['records_per_page'], $pagination['offset']]);
$payments = $stmt->fetchAll();

// Get payment summary
$stmt = $db->prepare("
    SELECT 
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_paid,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
        COUNT(*) as total_payments
    FROM payments 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$summary = $stmt->fetch();

$page_title = 'My Payments';
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
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Payments</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php
        $flash = getFlash();
        if ($flash):
        ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Payment Summary -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Paid</h6>
                        <h3 class="text-success mb-0"><?php echo formatCurrency($summary['total_paid']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Pending Payments</h6>
                        <h3 class="text-warning mb-0"><?php echo formatCurrency($summary['pending_amount']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="text-muted mb-2">Total Transactions</h6>
                        <h3 class="text-primary mb-0"><?php echo $summary['total_payments']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="bi bi-credit-card me-2"></i>Payment History</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($payments)): ?>
                            <div class="empty-state py-5">
                                <i class="bi bi-inbox"></i>
                                <h5>No Payments Found</h5>
                                <p>You haven't made any payments yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Receipt No.</th>
                                            <th>Date</th>
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
                                                <span class="badge bg-light text-dark">
                                                    <?php echo ucfirst($payment['payment_type']); ?>
                                                </span>
                                            </td>
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
                                                       class="btn btn-sm btn-outline-primary" target="_blank" title="Download Receipt">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($pagination['total_pages'] > 1): ?>
                            <nav aria-label="Payment pagination">
                                <ul class="pagination justify-content-center mt-4">
                                    <li class="page-item <?php echo !$pagination['has_prev'] ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo !$pagination['has_next'] ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>