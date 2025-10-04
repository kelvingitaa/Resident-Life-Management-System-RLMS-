<?php
include("../config/database.php");
include("../includes/functions.php");
session_start();

if (!isLoggedIn() || $_SESSION['role'] != 'applicant') {
    redirect("../auth/login.php");
}

$applicantID = $_SESSION['user_id'];

// Handle accept/reject
if (isset($_POST['action'])) {
    $offerID = $_POST['offer_id'];
    $action = $_POST['action'];

    $stmt = $conn->prepare("UPDATE offers SET status = ? WHERE id = ? AND applicant_id = ?");
    $stmt->bind_param("sii", $action, $offerID, $applicantID);
    $stmt->execute();
}

$stmt = $conn->prepare("SELECT id, apartment_id, status FROM offers WHERE applicant_id = ?");
$stmt->bind_param("i", $applicantID);
$stmt->execute();
$result = $stmt->get_result();
?>
<?php include("../includes/header.php"); ?>
<?php include("../includes/navbar.php"); ?>

<div class="offers">
    <h2>Housing Offers</h2>
    <?php if($result->num_rows > 0): ?>
        <table border="1">
            <tr>
                <th>Apartment ID</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['apartment_id']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td>
                        <?php if($row['status'] == 'Pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="offer_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="action" value="Accepted">Accept</button>
                                <button type="submit" name="action" value="Rejected">Reject</button>
                            </form>
                        <?php else: ?>
                            <?php echo $row['status']; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No offers available.</p>
    <?php endif; ?>
</div>

<?php include("../includes/footer.php"); ?>
