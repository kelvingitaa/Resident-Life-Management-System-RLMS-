<?php
// admin/dashboard.php
include '../includes/header.php';
include '../includes/navbar.php';
session_start();

// Restrict access to admins only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}
?>
<h1>Admin Dashboard</h1>
<p>Welcome, <?php echo $_SESSION['username']; ?>!</p>
<?php include '../includes/footer.php'; ?>
