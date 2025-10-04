<?php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
include '../config/session.php';
checkLogin('staff');
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>👨‍💼 Staff Dashboard</h2>
    </div>

    <div class="row mt-4">
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>Apartments</h3>
                <a href="apartments.php" class="btn btn-primary">Manage</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>Contracts</h3>
                <a href="contracts.php" class="btn btn-primary">View</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card">
                <h3>Reports</h3>
                <a href="reports.php" class="btn btn-primary">Generate</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
