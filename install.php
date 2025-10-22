<?php
require_once 'config/config.php';
require_once 'config/database.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Create database if not exists
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Add this before creating the database
        $pdo->exec("DROP DATABASE IF EXISTS " . DB_NAME);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        
        // Execute the schema.sql file
        $sql = file_get_contents('database/schema.sql');
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        $pdo->exec($sql);
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        
        $success = true;
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install HDMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h1 class="text-center mb-4">HDMS Installation</h1>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <h4>Installation Successful!</h4>
                                <p>The Housing Department Management System has been successfully installed.</p>
                                <p>Default admin credentials:</p>
                                <ul>
                                    <li><strong>Email:</strong> admin@example.com</li>
                                    <li><strong>Password:</strong> admin123</li>
                                </ul>
                                <div class="mt-4">
                                    <a href="index.php" class="btn btn-primary">Go to Homepage</a>
                                </div>
                            </div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger">
                                <h4>Installation Failed</h4>
                                <p><?php echo $error; ?></p>
                                <form method="post" class="mt-3">
                                    <button type="submit" class="btn btn-primary">Try Again</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h4>Welcome to HDMS Installation</h4>
                                <p>This wizard will help you install the Housing Department Management System.</p>
                                <p>Make sure you have:</p>
                                <ul>
                                    <li>MySQL/MariaDB server running</li>
                                    <li>Correct database credentials in config files</li>
                                </ul>
                            </div>
                            
                            <form method="post">
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">Install Now</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>