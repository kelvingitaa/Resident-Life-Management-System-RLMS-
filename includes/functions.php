<?php
/**
 * Helper Functions
 */

// Sanitize input
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone number (basic)
function isValidPhone($phone) {
    return preg_match('/^[0-9+\-\(\)\s]{10,20}$/', $phone);
}

// Hash password
function hashPassword($password) {
    return password_hash($password, HASH_ALGO, ['cost' => HASH_COST]);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Redirect helper
function redirect($url) {
    header("Location: " . SITE_URL . $url);
    exit();
}

// Flash message helper
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Format date
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    return date($format, strtotime($date));
}

// Format currency
function formatCurrency($amount) {
    return 'KES ' . number_format($amount, 2);
}

// Generate unique filename
function generateUniqueFilename($original_filename) {
    $ext = pathinfo($original_filename, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $ext;
}

// Upload file with validation
function uploadFile($file, $subfolder = '', $allowed_types = ALLOWED_DOC_TYPES) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception('Invalid file parameters');
    }
    
    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new Exception('File size exceeds limit');
        case UPLOAD_ERR_NO_FILE:
            throw new Exception('No file uploaded');
        default:
            throw new Exception('Upload error occurred');
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File size exceeds ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB');
    }
    
    // Check file type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    
    if (!in_array($mime, $allowed_types)) {
        throw new Exception('Invalid file type');
    }
    
    // Create upload directory if not exists
    $upload_dir = UPLOAD_PATH . $subfolder;
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $filename = generateUniqueFilename($file['name']);
    $filepath = $upload_dir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to move uploaded file');
    }
    
    return [
        'filename' => $filename,
        'filepath' => $subfolder . '/' . $filename,
        'size' => $file['size'],
        'mime' => $mime
    ];
}

// Delete file
function deleteFile($filepath) {
    $full_path = UPLOAD_PATH . $filepath;
    if (file_exists($full_path)) {
        return unlink($full_path);
    }
    return false;
}

// Pagination helper
function paginate($total_records, $current_page = 1, $records_per_page = RECORDS_PER_PAGE) {
    $total_pages = ceil($total_records / $records_per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $records_per_page;
    
    return [
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'records_per_page' => $records_per_page,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}

// Create notification
function createNotification($user_id, $title, $message, $type, $link = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $title, $message, $type, $link]);
    } catch(PDOException $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

// Get unread notification count
function getUnreadNotificationCount($user_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'];
    } catch(PDOException $e) {
        error_log("Error getting notification count: " . $e->getMessage());
        return 0;
    }
}

// Log audit trail
function logAudit($action, $table_name = null, $record_id = null, $old_values = null, $new_values = null) {
    try {
        $db = getDB();
        $user_id = getCurrentUserId();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt = $db->prepare("
            INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user_id,
            $action,
            $table_name,
            $record_id,
            $old_values ? json_encode($old_values) : null,
            $new_values ? json_encode($new_values) : null,
            $ip,
            $user_agent
        ]);
    } catch(PDOException $e) {
        error_log("Error logging audit: " . $e->getMessage());
    }
}

// Check if apartment is available
function isApartmentAvailable($apartment_id, $start_date, $end_date) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM contracts 
            WHERE apartment_id = ? 
            AND status = 'active'
            AND (
                (start_date <= ? AND end_date >= ?) OR
                (start_date <= ? AND end_date >= ?) OR
                (start_date >= ? AND end_date <= ?)
            )
        ");
        $stmt->execute([$apartment_id, $start_date, $start_date, $end_date, $end_date, $start_date, $end_date]);
        $result = $stmt->fetch();
        return $result['count'] == 0;
    } catch(PDOException $e) {
        error_log("Error checking apartment availability: " . $e->getMessage());
        return false;
    }
}

// Get user full name
function getUserFullName($user_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        return $user ? $user['first_name'] . ' ' . $user['last_name'] : 'Unknown';
    } catch(PDOException $e) {
        return 'Unknown';
    }
}

// Calculate contract duration in months
function calculateMonthsDifference($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    return ($interval->y * 12) + $interval->m;
}