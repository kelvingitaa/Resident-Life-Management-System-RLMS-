<?php
// resident/dashboard.php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
include '../config/session.php';

// Check login
checkLogin('resident');

// Fetch resident info
$stmt = $pdo->prepare("SELECT * FROM resident WHERE ResidentID=?");
$stmt->execute([$_SESSION['user_id']]);
$resident = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch quick stats
$paymentStmt = $pdo->prepare("SELECT SUM(Amount) as totalPaid FROM payment WHERE ResidentID=? AND Status='Paid'");
$paymentStmt->execute([$_SESSION['user_id']]);
$payments = $paymentStmt->fetch(PDO::FETCH_ASSOC);

$maintenanceStmt = $pdo->prepare("SELECT COUNT(*) as totalRequests FROM maintenance_request WHERE ResidentID=? AND Status='Pending'");
$maintenanceStmt->execute([$_SESSION['user_id']]);
$maintenance = $maintenanceStmt->fetch(PDO::FETCH_ASSOC);

$contractStmt = $pdo->prepare("SELECT * FROM contract WHERE ResidentID=? ORDER BY EndDate DESC LIMIT 1");
$contractStmt->execute([$_SESSION['user_id']]);
$contract = $contractStmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>👨‍🎓 Resident Dashboard</h2>
        <p>Welcome back, <?= htmlspecialchars($resident['Name']); ?>!</p>
    </div>

    <div class="row mt-4">
        <!-- Contracts -->
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>📑 Lease</h3>
                <?php if ($contract): ?>
                    <p>Active until <strong><?= htmlspecialchars($contract['EndDate']); ?></strong></p>
                    <a href="contracts.php" class="btn btn-sm btn-primary">View Contracts</a>
                <?php else: ?>
                    <p>No active lease found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payments -->
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>💳 Payments</h3>
                <p>Total Paid: <strong>$<?= number_format($payments['totalPaid'] ?? 0, 2); ?></strong></p>
                <a href="payments.php" class="btn btn-sm btn-success">Manage Payments</a>
            </div>
        </div>

        <!-- Maintenance -->
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>🔧 Maintenance</h3>
                <p>Pending Requests: <strong><?= $maintenance['totalRequests']; ?></strong></p>
                <a href="maintenance.php" class="btn btn-sm btn-warning">Request Maintenance</a>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Profile -->
        <div class="col-md-12">
            <div class="dashboard-card">
                <h3>👤 Profile Summary</h3>
                <p><strong>Name:</strong> <?= htmlspecialchars($resident['Name']); ?></p>
                <p><strong>Degree Level:</strong> <?= htmlspecialchars($resident['DegreeLevel']); ?></p>
                <p><strong>Department:</strong> <?= htmlspecialchars($resident['Dept']); ?></p>
                <a href="profile.php" class="btn btn-sm btn-info">View Full Profile</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
