<?php
include("../config/database.php");
include("../includes/functions.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = "applicant"; // Default role

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $password, $role);

    if ($stmt->execute()) {
        $_SESSION['user_id'] = $stmt->insert_id;
        $_SESSION['role'] = $role;
        redirect("/applicant/dashboard.php");
    } else {
        $error = "Registration failed. Email may already exist.";
    }
}
?>
<?php include("../includes/header.php"); ?>
<?php include("../includes/navbar.php"); ?>
<div class="auth-container">
    <h2>Register</h2>
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
        <label>Full Name</label>
        <input type="text" name="name" required>
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Register</button>
    </form>
</div>
<?php include("../includes/footer.php"); ?>
