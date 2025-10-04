<?php
// config/session.php
session_start();

function checkLogin($role = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "auth/login.php");
        exit();
    }
    if ($role && $_SESSION['user_role'] !== $role) {
        header("Location: " . BASE_URL . "index.php");
        exit();
    }
}
?>
