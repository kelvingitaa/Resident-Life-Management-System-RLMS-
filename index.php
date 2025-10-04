<?php
// index.php - Landing Page
session_start();
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container mt-5">
    <div class="text-center">
        <h1>🏠 Housing Department Management System</h1>
        <p class="lead">Welcome to the University Housing Portal. Manage applications, apartments, residents, maintenance, and payments all in one place.</p>
        <hr>
    </div>

    <div class="row text-center mt-4">
        <!-- Applicant -->
        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h3>📄 Applicants</h3>
                <p>Apply for housing, check your status, and accept offers online.</p>
                <a href="auth/register.php" class="btn btn-primary">Apply Now</a>
                <a href="auth/login.php" class="btn btn-outline-secondary mt-2">Login</a>
            </div>
        </div>

        <!-- Resident -->
        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h3>👨‍🎓 Residents</h3>
                <p>Access your dashboard to view contracts, pay rent, and submit requests.</p>
                <a href="auth/login.php" class="btn btn-success">Resident Login</a>
            </div>
        </div>

        <!-- Admin -->
        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h3>⚙️ Staff / Admin</h3>
                <p>Manage apartments, allocations, financial reports, and maintenance tasks.</p>
                <a href="auth/login.php" class="btn btn-danger">Admin Login</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
