<?php
include("../config/database.php");
include("../includes/functions.php");
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hashed_password, $role);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hashed_password)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['role'] = $role;
        redirect("/index.php");
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<?php include("../includes/header.php"); ?>
<?php include("../includes/navbar.php"); ?>
<div class="auth-container">
    <h2>Login</h2>
    <?php if(isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
    <p><a href="forgot-password.php">Forgot Password?</a></p>
</div>
<?php include("../includes/footer.php"); ?>
