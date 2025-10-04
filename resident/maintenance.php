<?php
// resident/maintenance.php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'resident') {
    header("Location: ../auth/login.php");
    exit();
}
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>🔧 Maintenance Request</h2>
    </div>

    <div class="dashboard-card mt-3">
        <form method="POST" action="" id="maintenanceForm">
            <textarea name="description" placeholder="Describe the issue" class="form-control mb-2" required></textarea>
            <select name="priority" class="form-control mb-2" required>
                <option value="Low">Low</option>
                <option value="Medium">Medium</option>
                <option value="High">High</option>
            </select>
            <button type="submit" name="request" class="btn btn-primary">Submit Request</button>
        </form>
    </div>
</div>

<?php
if (isset($_POST['request'])) {
    $stmt = $pdo->prepare("INSERT INTO maintenance_requests (resident_id, description, priority) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_POST['description'], $_POST['priority']]);
    echo "<div class='alert alert-success mt-3'>Maintenance request submitted!</div>";
}
include '../includes/footer.php';
?>
