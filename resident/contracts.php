<?php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
include '../config/session.php';
checkLogin('resident');
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>📑 My Contracts</h2>
    </div>

    <div class="dashboard-card mt-3">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Apartment</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Deposit</th>
                    <th>Termination Notice</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->prepare("SELECT a.VillageName, c.StartDate, c.EndDate, c.Deposit, c.TerminationNotice 
                                        FROM contract c 
                                        JOIN apartment a ON c.ApartmentID=a.ApartmentID 
                                        WHERE c.ResidentID=?");
                $stmt->execute([$_SESSION['user_id']]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>
                            <td>{$row['VillageName']}</td>
                            <td>{$row['StartDate']}</td>
                            <td>{$row['EndDate']}</td>
                            <td>{$row['Deposit']}</td>
                            <td>{$row['TerminationNotice']} days</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
