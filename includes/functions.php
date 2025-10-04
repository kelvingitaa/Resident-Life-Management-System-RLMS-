<?php
// functions.php
// Common helper functions

// Secure input
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Check login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect helper
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Role check
function checkRole($role) {
    return (isset($_SESSION['role']) && $_SESSION['role'] === $role);
}
?>
