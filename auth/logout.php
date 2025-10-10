<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Log audit before destroying session
if (isLoggedIn()) {
    logAudit('User Logout', 'users', getCurrentUserId());
}

// Destroy session
destroySession();

// Redirect to login
setFlash('success', 'You have been logged out successfully.');
redirect('/auth/login.php');