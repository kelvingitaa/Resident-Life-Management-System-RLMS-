<?php
// resident/dashboard.php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'resident') {
    header("Location: ../auth/login.php");
    exit();
}
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>👨‍🎓 Resident Dashboard</h2>
        <p>Welcome, <?= htmlspecialchars($_SESSION['username']); ?>! Manage your housing below.</p>
    </div>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>📑 Lease</h3>
                <p>Your lease is active until <strong>Dec 2025</strong>.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>💳 Payments</h3>
                <p>Last payment: <strong>$500</strong> on 1 Oct 2025.</p>
                <a href="payments.php" class="btn btn-success">Pay Now</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>🔧 Maintenance</h3>
                <p>You have <strong>1 active request</strong>.</p>
                <a href="maintenance.php" class="btn btn-warning">Request Maintenance</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
