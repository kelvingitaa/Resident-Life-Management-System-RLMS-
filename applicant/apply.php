<?php
// applicant/apply.php
include '../includes/header.php';
include '../includes/navbar.php';
include '../config/database.php';
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'applicant') {
    header("Location: ../auth/login.php");
    exit();
}
?>

<div class="container mt-5">
    <div class="dashboard-header">
        <h2>📝 Apply for Housing</h2>
    </div>

    <div class="dashboard-card mt-3">
        <form method="POST" action="" id="applicationForm">
            <input type="text" name="full_name" placeholder="Full Name" class="form-control mb-2" required>
            <input type="email" name="email" placeholder="Email" class="form-control mb-2" required>
            <input type="text" name="student_id" placeholder="Student ID" class="form-control mb-2" required>
            <select name="apartment_choice" class="form-control mb-2" required>
                <option value="">-- Select Apartment --</option>
                <option value="Apt 1">Apartment 1</option>
                <option value="Apt 2">Apartment 2</option>
            </select>
            <button type="submit" name="apply" class="btn btn-primary">Submit Application</button>
        </form>
    </div>
</div> 
<?php
if (isset($_POST['apply'])) {
    $stmt = $pdo->prepare("INSERT INTO applicants (full_name, email, student_id, apartment_choice) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['student_id'], $_POST['apartment_choice']]);
    echo "<div class='alert alert-success mt-3'>Application submitted successfully!</div>";
}
include '../includes/footer.php';
?>
