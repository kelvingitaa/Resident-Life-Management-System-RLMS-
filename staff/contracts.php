<?php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
include '../config/session.php';
checkLogin('staff');
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>📑 All Contracts</h2>
    </div>

    <div class="dashboard-card mt-3">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Resident</th>
                    <th>Apartment</th>
                    <th>Start</th>
                    <th>End</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT r.Name, a.VillageName, c.StartDate, c.EndDate 
                                    FROM contract c 
                                    JOIN resident r ON c.ResidentID=r.ResidentID 
                                    JOIN apartment a ON c.ApartmentID=a.ApartmentID");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>
                            <td>{$row['Name']}</td>
                            <td>{$row['VillageName']}</td>
                            <td>{$row['StartDate']}</td>
                            <td>{$row['EndDate']}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
