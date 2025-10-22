<?php
// Site Configuration
define("SITE_NAME", "HDMS - Housing Department Management System");
define("BASE_URL", "http://localhost/Resident-Life-Management-System-RLMS-/");
define("SITE_URL", "http://localhost/Resident-Life-Management-System-RLMS-");

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '5665');
define('DB_NAME', 'hdms');
define('DB_CHARSET', 'utf8mb4');

// Session Configuration
define('SESSION_LIFETIME', 3600); // 1 hour

// Security Configuration
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 12);

// File Upload Configuration
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_DOC_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png'
]);

// Display Configuration
define('RECORDS_PER_PAGE', 10);
define('DISPLAY_DATE_FORMAT', 'M d, Y');
?>