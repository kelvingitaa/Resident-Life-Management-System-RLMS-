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

// Total applicants
$totalApplicants = $pdo->query("SELECT COUNT(*) FROM applicants")->fetchColumn();

// Total offers
$totalOffers = $pdo->query("SELECT COUNT(*) FROM offers")->fetchColumn();

// Total payments
$totalPayments = $pdo->query("SELECT SUM(amount) FROM payments")->fetchColumn();
?>
<h1>Analytics Dashboard</h1>
<ul>
  <li>Total Applicants: <?= $totalApplicants; ?></li>
  <li>Total Offers: <?= $totalOffers; ?></li>
  <li>Total Payments: $<?= number_format($totalPayments, 2); ?></li>
</ul>
<?php include '../includes/footer.php'; ?>
