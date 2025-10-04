<?php
// navbar.php
?>
<nav class="navbar">
    <div class="logo">
        <a href="/index.php">🏠 HDMS</a>
    </div>
    <ul class="nav-links">
        <li><a href="/index.php">Home</a></li>
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'applicant'): ?>
            <li><a href="/applicant/dashboard.php">Applicant Dashboard</a></li>
        <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] == 'resident'): ?>
            <li><a href="/resident/dashboard.php">Resident Dashboard</a></li>
        <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] == 'staff'): ?>
            <li><a href="/staff/dashboard.php">Staff Dashboard</a></li>
        <?php elseif(isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <li><a href="/admin/dashboard.php">Admin Panel</a></li>
        <?php endif; ?>
        <?php if(isset($_SESSION['user_id'])): ?>
            <li><a href="/auth/logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="/auth/login.php">Login</a></li>
            <li><a href="/auth/register.php">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>
