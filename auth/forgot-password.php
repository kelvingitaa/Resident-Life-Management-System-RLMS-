<?php
include("../config/database.php");
include("../includes/functions.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitizeInput($_POST['email']);

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Here you would normally send an email with a reset link
        $message = "A password reset link has been sent to your email.";
    } else {
        $error = "No account found with that email.";
    }
}
?>
<?php include("../includes/header.php"); ?>
<?php include("../includes/navbar.php"); ?>
<div class="auth-container">
    <h2>Forgot Password</h2>
    <?php 
    if(isset($error)) echo "<p style='color:red;'>$error</p>"; 
    if(isset($message)) echo "<p style='color:green;'>$message</p>"; 
    ?>
    <form method="POST">
        <label>Enter your email</label>
        <input type="email" name="email" required>
        <button type="submit">Send Reset Link</button>
    </form>
</div>
<?php include("../includes/footer.php"); ?>
