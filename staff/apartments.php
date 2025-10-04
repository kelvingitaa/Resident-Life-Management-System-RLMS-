<?php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
include '../config/session.php';
checkLogin('staff');
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>🏠 Manage Apartments</h2>
    </div>

    <div class="dashboard-card mt-3">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Village</th>
                    <th>Unit</th>
                    <th>Status</th>
                    <th>Rent</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM apartment");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>
                            <td>{$row['VillageName']}</td>
                            <td>{$row['UnitNo']}</td>
                            <td>{$row['Status']}</td>
                            <td>{$row['RentAmount']}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
