<?php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
include '../config/session.php';
checkLogin('staff');
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>📊 Reports</h2>
    </div>

    <div class="dashboard-card mt-3">
        <p><a href="../api/reports.php?type=occupancy" class="btn btn-info">Occupancy Report</a></p>
        <p><a href="../api/reports.php?type=financial" class="btn btn-info">Financial Report</a></p>
        <p><a href="../api/reports.php?type=maintenance" class="btn btn-info">Maintenance Report</a></p>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
