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

// Fetch payments
$payments = $pdo->query("SELECT * FROM payments ORDER BY created_at DESC")->fetchAll();
?>
<h2>Payments Management</h2>
<table border="1" cellpadding="5">
  <tr>
    <th>ID</th>
    <th>User</th>
    <th>Amount</th>
    <th>Status</th>
    <th>Date</th>
  </tr>
  <?php foreach($payments as $p): ?>
    <tr>
      <td><?= $p['id']; ?></td>
      <td><?= htmlspecialchars($p['user_id']); ?></td>
      <td>$<?= number_format($p['amount'], 2); ?></td>
      <td><?= htmlspecialchars($p['status']); ?></td>
      <td><?= $p['created_at']; ?></td>
    </tr>
  <?php endforeach; ?>
</table>
<?php include '../includes/footer.php'; ?>
