<?php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
include '../config/session.php';
checkLogin('resident');

$stmt = $pdo->prepare("SELECT * FROM resident WHERE ResidentID=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>👤 My Profile</h2>
    </div>

    <div class="dashboard-card mt-3">
        <p><strong>Name:</strong> <?= htmlspecialchars($user['Name']) ?></p>
        <p><strong>Gender:</strong> <?= htmlspecialchars($user['Gender']) ?></p>
        <p><strong>Marital Status:</strong> <?= htmlspecialchars($user['MaritalStatus']) ?></p>
        <p><strong>Degree Level:</strong> <?= htmlspecialchars($user['DegreeLevel']) ?></p>
        <p><strong>Department:</strong> <?= htmlspecialchars($user['Dept']) ?></p>
        <p><strong>Contact:</strong> <?= htmlspecialchars($user['Contact']) ?></p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
