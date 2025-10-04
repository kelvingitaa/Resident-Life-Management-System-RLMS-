<?php
// admin/payment.php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$payments = $pdo->query("SELECT * FROM payments ORDER BY created_at DESC")->fetchAll();
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>💳 Payments Management</h2>
    </div>

    <table class="table table-hover mt-3">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($payments as $p): ?>
                <tr>
                    <td><?= $p['id']; ?></td>
                    <td><?= htmlspecialchars($p['user_id']); ?></td>
                    <td>$<?= number_format($p['amount'], 2); ?></td>
                    <td><?= htmlspecialchars($p['status']); ?></td>
                    <td><?= $p['created_at']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
