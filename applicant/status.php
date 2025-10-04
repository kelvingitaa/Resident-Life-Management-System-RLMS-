<?php
include("../config/database.php");
include("../includes/functions.php");
session_start();

if (!isLoggedIn() || $_SESSION['role'] != 'applicant') {
    redirect("../auth/login.php");
}

$applicantID = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT applicationDate, prefVillage, prefApartmentType, status FROM applications WHERE applicant_id = ? ORDER BY applicationDate DESC LIMIT 1");
$stmt->bind_param("i", $applicantID);
$stmt->execute();
$result = $stmt->get_result();
$app = $result->fetch_assoc();
?>
<?php include("../includes/header.php"); ?>
<?php include("../includes/navbar.php"); ?>

<div class="status">
    <h2>Application Status</h2>
    <?php if($app): ?>
        <p><strong>Applied on:</strong> <?php echo $app['applicationDate']; ?></p>
        <p><strong>Preferred Village:</strong> <?php echo $app['prefVillage']; ?></p>
        <p><strong>Apartment Type:</strong> <?php echo $app['prefApartmentType']; ?></p>
        <p><strong>Status:</strong> <?php echo $app['status']; ?></p>
    <?php else: ?>
        <p>No applications found.</p>
    <?php endif; ?>
</div>

<?php include("../includes/footer.php"); ?>
