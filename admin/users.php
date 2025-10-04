<?php
// admin/users.php
include '../includes/header.php';
include '../config/database.php';

$users = $pdo->query("SELECT * FROM users")->fetchAll();
?>
<h2>Manage Users</h2>
<ul>
  <?php foreach($users as $user): ?>
    <li><?= $user['username']; ?> - <?= $user['role']; ?></li>
  <?php endforeach; ?>
</ul>
<?php include '../includes/footer.php'; ?>
