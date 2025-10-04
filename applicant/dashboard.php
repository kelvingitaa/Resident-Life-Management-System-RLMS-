<?php
include("../config/database.php");
include("../includes/functions.php");
session_start();

if (!isLoggedIn() || $_SESSION['role'] != 'applicant') {
    redirect("../auth/login.php");
}
?>
<?php include("../includes/header.php"); ?>
<?php include("../includes/navbar.php"); ?>

<div class="dashboard">
    <h2>Applicant Dashboard</h2>
    <ul>
        <li><a href="apply.php">Apply for Housing</a></li>
        <li><a href="status.php">Check Application Status</a></li>
        <li><a href="offers.php">View Housing Offers</a></li>
    </ul>
</div>

<?php include("../includes/footer.php"); ?>
