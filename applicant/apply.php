<?php
include("../config/database.php");
include("../includes/functions.php");
session_start();

if (!isLoggedIn() || $_SESSION['role'] != 'applicant') {
    redirect("../auth/login.php");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $residentID = $_SESSION['user_id'];
    $village = sanitizeInput($_POST['village']);
    $apartmentType = sanitizeInput($_POST['apartmentType']);
    $applicationDate = date("Y-m-d");

    $stmt = $conn->prepare("INSERT INTO applications (applicant_id, prefVillage, prefApartmentType, applicationDate, status) 
                             VALUES (?, ?, ?, ?, 'Pending')");
    $stmt->bind_param("isss", $residentID, $village, $apartmentType, $applicationDate);

    if ($stmt->execute()) {
        $message = "Application submitted successfully!";
    } else {
        $error = "Failed to submit application.";
    }
}
?>
<?php include("../includes/header.php"); ?>
<?php include("../includes/navbar.php"); ?>

<div class="app-container">
    <h2>Housing Application</h2>
    <?php if(isset($message)) echo "<p style='color:green;'>$message</p>"; ?>
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <form method="POST">
        <label>Preferred Village</label>
        <select name="village" required>
            <option value="Village A">Village A</option>
            <option value="Village B">Village B</option>
            <option value="Village C">Village C</option>
        </select>

        <label>Apartment Type</label>
        <select name="apartmentType" required>
            <option value="Single">Single</option>
            <option value="Double">Double</option>
            <option value="Family">Family</option>
        </select>

        <button type="submit">Submit Application</button>
    </form>
</div>

<?php include("../includes/footer.php"); ?>
