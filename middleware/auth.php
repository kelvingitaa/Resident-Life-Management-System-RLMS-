<?php
/**
 * Authentication Middleware
 */

// Include required files if not already included
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../config/session.php';
}

/**
 * Require user to be logged in
 * Redirects to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Please login to access this page');
        redirect('/auth/login.php');
    }
    
    // Check session timeout
    if (!checkSessionTimeout()) {
        setFlash('error', 'Your session has expired. Please login again');
        redirect('/auth/login.php');
    }
}

/**
 * Require user to have specific role
 * Redirects to appropriate page if not authorized
 * 
 * @param string $role Required role
 */
function requireRole($role) {
    requireLogin();
    
    if (!hasRole($role)) {
        setFlash('error', 'You do not have permission to access this page');
        redirect('/index.php');
    }
}

/**
 * Require user to have any of the specified roles
 * Redirects to appropriate page if not authorized
 * 
 * @param array $roles Array of allowed roles
 */
function requireAnyRole($roles) {
    requireLogin();
    
    if (!hasAnyRole($roles)) {
        setFlash('error', 'You do not have permission to access this page');
        redirect('/index.php');
    }
}

/**
 * Check if current user is the owner of a resource
 * 
 * @param int $resource_user_id User ID of the resource owner
 * @return bool True if current user is the owner
 */
function isResourceOwner($resource_user_id) {
    return getCurrentUserId() == $resource_user_id;
}

/**
 * Require user to be the owner of a resource
 * Redirects to appropriate page if not the owner
 * 
 * @param int $resource_user_id User ID of the resource owner
 */
function requireResourceOwner($resource_user_id) {
    requireLogin();
    
    if (!isResourceOwner($resource_user_id) && !hasRole('admin')) {
        setFlash('error', 'You do not have permission to access this resource');
        redirect('/index.php');
    }
}
?>