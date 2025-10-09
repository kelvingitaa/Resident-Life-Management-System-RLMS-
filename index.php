<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/functions.php';

// If user is logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    redirect("/{$role}/dashboard.php");
}

$page_title = 'Welcome to HDMS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . ' - ' . SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-building"></i> HDMS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-light text-primary ms-2 px-3" href="auth/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2 px-3" href="auth/register.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero bg-gradient py-5">
        <div class="container text-center text-white py-5">
            <h1 class="display-3 fw-bold mb-4">Find Your Perfect Home</h1>
            <p class="lead mb-4">Streamlined housing management for students, residents, and staff</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="auth/register.php" class="btn btn-light btn-lg px-5">Apply Now</a>
                <a href="#features" class="btn btn-outline-light btn-lg px-5">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose HDMS?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-file-earmark-text fs-1"></i>
                            </div>
                            <h4>Easy Applications</h4>
                            <p class="text-muted">Submit housing applications online with real-time status tracking</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-credit-card fs-1"></i>
                            </div>
                            <h4>Online Payments</h4>
                            <p class="text-muted">Manage rent payments and view billing history securely</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-tools fs-1"></i>
                            </div>
                            <h4>Maintenance Requests</h4>
                            <p class="text-muted">Submit and track maintenance requests with ease</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-info text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-file-earmark-pdf fs-1"></i>
                            </div>
                            <h4>Digital Contracts</h4>
                            <p class="text-muted">Access and sign lease agreements digitally</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-bell fs-1"></i>
                            </div>
                            <h4>Instant Notifications</h4>
                            <p class="text-muted">Stay updated with real-time alerts and reminders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="bi bi-graph-up fs-1"></i>
                            </div>
                            <h4>Analytics Dashboard</h4>
                            <p class="text-muted">Comprehensive reporting for administrators</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="mb-4">About Our System</h2>
                    <p class="lead">HDMS is a comprehensive housing management solution designed to streamline the entire housing lifecycle.</p>
                    <p>From initial applications to contract management, payment tracking, and maintenance requests, our system provides an all-in-one platform for housing administrators and residents.</p>
                    <ul class="list-unstyled mt-4">
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Secure and reliable</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> User-friendly interface</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> 24/7 accessibility</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Mobile responsive</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=600" alt="Housing" class="img-fluid rounded shadow">
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-primary text-white py-5">
        <div class="container text-center">
            <h2 class="mb-4">Ready to Get Started?</h2>
            <p class="lead mb-4">Join thousands of satisfied residents using our platform</p>
            <a href="auth/register.php" class="btn btn-light btn-lg px-5">Create an Account</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="bi bi-building"></i> HDMS</h5>
                    <p class="text-muted">Housing & Dormitory Management System</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">&copy; <?php echo date('Y'); ?> HDMS. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>