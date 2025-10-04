<?php
// includes/navbar.php
session_start();
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="/index.php">HDMS</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">

        <!-- Always visible -->
        <li class="nav-item">           
          <a class="nav-link" href="/index.php">Home</a>
        </li>

        <?php if (isset($_SESSION['user_role'])): ?>
            
            <!-- Applicant -->
            <?php if ($_SESSION['user_role'] === 'applicant'): ?>
              <li class="nav-item"><a class="nav-link" href="/applicant/dashboard.php">Dashboard</a></li>
              <li class="nav-item"><a class="nav-link" href="/applicant/application.php">My Application</a></li>
            
            <!-- Resident -->
            <?php elseif ($_SESSION['user_role'] === 'resident'): ?>
              <li class="nav-item"><a class="nav-link" href="/resident/dashboard.php">Dashboard</a></li>
              <li class="nav-item"><a class="nav-link" href="/resident/payments.php">Payments</a></li>
              <li class="nav-item"><a class="nav-link" href="/resident/maintenance.php">Maintenance</a></li>
            
            <!-- Admin -->
            <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
              <li class="nav-item"><a class="nav-link" href="/admin/dashboard.php">Admin Dashboard</a></li>
              <li class="nav-item"><a class="nav-link" href="/admin/apartments.php">Apartments</a></li>
              <li class="nav-item"><a class="nav-link" href="/admin/users.php">Users</a></li>
              <li class="nav-item"><a class="nav-link" href="/admin/payment.php">Payments</a></li>
              <li class="nav-item"><a class="nav-link" href="/admin/analytics.php">Analytics</a></li>
            <?php endif; ?>

        <?php endif; ?>
      </ul>

      <ul class="navbar-nav ms-auto">
        <?php if (isset($_SESSION['user_id'])): ?>
          <li class="nav-item">
            <a class="nav-link" href="/auth/logout.php">Logout</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="/auth/login.php">Login</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="/auth/register.php">Register</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
