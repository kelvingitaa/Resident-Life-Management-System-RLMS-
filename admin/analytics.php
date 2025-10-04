<?php
// admin/analytics.php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$totalApplicants = $pdo->query("SELECT COUNT(*) FROM applicants")->fetchColumn();
$totalOffers = $pdo->query("SELECT COUNT(*) FROM offers")->fetchColumn();
$totalPayments = $pdo->query("SELECT SUM(amount) FROM payments")->fetchColumn();
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>📊 Analytics Dashboard</h2>
    </div>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>Total Applicants</h3>
                <p><?= $totalApplicants; ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>Total Offers</h3>
                <p><?= $totalOffers; ?></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>Total Payments</h3>
                <p>$<?= number_format($totalPayments, 2); ?></p>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
