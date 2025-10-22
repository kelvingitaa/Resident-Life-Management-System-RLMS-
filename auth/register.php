<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = 'applicant'; // Default role for new registrations
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields.';
        } elseif (!isValidEmail($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (!empty($phone) && !isValidPhone($phone)) {
            $error = 'Please enter a valid phone number.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $db = getDB();
                
                // Check if email already exists
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $result = $stmt->fetch();
                
                if ($result['count'] > 0) {
                    $error = 'Email address is already registered.';
                } else {
                    // Hash password
                    $hashed_password = hashPassword($password);
                    
                    // Insert new user
                    $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                    $stmt->execute([$first_name, $last_name, $email, $phone, $hashed_password, $role]);
                    
                    $user_id = $db->lastInsertId();
                    
                    // Log audit
                    logAudit('User Registration', 'users', $user_id);
                    
                    // Create notification for admin
                    $admin_stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
                    $admin_stmt->execute();
                    $admin = $admin_stmt->fetch();
                    
                    if ($admin) {
                        createNotification(
                            $admin['id'],
                            'New User Registration',
                            "A new user {$first_name} {$last_name} has registered as an applicant.",
                            'registration',
                            '/admin/users.php'
                        );
                    }
                    
                    $success = 'Registration successful! You can now login.';
                }
            } catch(PDOException $e) {
                error_log("Registration Error: " . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$page_title = 'Register';
?>
<?php include("../includes/header.php"); ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4">Create an Account</h2>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-primary">Proceed to Login</a>
                        </div>
                    <?php else: ?>
                        <?php if($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>
                        
                        <div class="mt-3 text-center">
                            <p>Already have an account? <a href="login.php">Login</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>