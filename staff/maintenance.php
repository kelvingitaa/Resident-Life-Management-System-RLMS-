<?php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
include '../config/session.php';
checkLogin('staff');
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>🔧 Maintenance Requests</h2>
    </div>

    <div class="dashboard-card mt-3">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Resident</th>
                    <th>Description</th>
                    <th>Priority</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT r.Name, m.Description, m.Priority, m.Status 
                                    FROM maintenance_request m 
                                    JOIN resident r ON m.ResidentID=r.ResidentID");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>
                            <td>{$row['Name']}</td>
                            <td>{$row['Description']}</td>
                            <td>{$row['Priority']}</td>
                            <td>{$row['Status']}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
