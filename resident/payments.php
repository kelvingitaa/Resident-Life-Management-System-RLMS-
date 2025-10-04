<?php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
include '../config/session.php';
checkLogin('resident');
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>💳 My Payments</h2>
    </div>

    <div class="dashboard-card mt-3">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Mode</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->prepare("SELECT * FROM payment WHERE ResidentID=? ORDER BY Date DESC");
                $stmt->execute([$_SESSION['user_id']]);
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>
                            <td>{$row['Date']}</td>
                            <td>{$row['Amount']}</td>
                            <td>{$row['Mode']}</td>
                            <td>{$row['Status']}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
