<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../middleware/auth.php';

requireRole('applicant');

$user_id = getCurrentUserId();
$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $apartment_type = sanitize($_POST['apartment_type'] ?? '');
        $move_in_date = $_POST['move_in_date'] ?? '';
        $duration_months = intval($_POST['duration_months'] ?? 0);
        $employment_status = sanitize($_POST['employment_status'] ?? '');
        $monthly_income = floatval($_POST['monthly_income'] ?? 0);
        $emergency_contact_name = sanitize($_POST['emergency_contact_name'] ?? '');
        $emergency_contact_phone = sanitize($_POST['emergency_contact_phone'] ?? '');
        $emergency_contact_relationship = sanitize($_POST['emergency_contact_relationship'] ?? '');
        $additional_notes = sanitize($_POST['additional_notes'] ?? '');
        
        // Validation
        if (empty($apartment_type) || empty($move_in_date) || $duration_months < 1) {
            $error = 'Please fill in all required fields.';
        } elseif (strtotime($move_in_date) < strtotime('today')) {
            $error = 'Move-in date cannot be in the past.';
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO applications (
                        user_id, apartment_type_preference, move_in_date, duration_months,
                        employment_status, monthly_income, emergency_contact_name,
                        emergency_contact_phone, emergency_contact_relationship, additional_notes, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                
                $stmt->execute([
                    $user_id,
                    $apartment_type,
                    $move_in_date,
                    $duration_months,
                    $employment_status,
                    $monthly_income,
                    $emergency_contact_name,
                    $emergency_contact_phone,
                    $emergency_contact_relationship,
                    $additional_notes
                ]);
                
                $application_id = $db->lastInsertId();
                
                // Log audit
                logAudit('Application Submitted', 'applications', $application_id);
                
                // Create notification
                createNotification(
                    $user_id,
                    'Application Submitted',
                    'Your housing application has been submitted successfully. We will review it shortly.',
                    'application',
                    '/applicant/status.php?id=' . $application_id
                );
                
                setFlash('success', 'Application submitted successfully! Application ID: #' . str_pad($application_id, 5, '0', STR_PAD_LEFT));
                redirect('/applicant/status.php');
            } catch(PDOException $e) {
                error_log("Application Error: " . $e->getMessage());
                $error = 'An error occurred. Please try again later.';
            }
        }
    }
}

$csrf_token = generateCSRFToken();
$page_title = 'Apply for Housing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title . ' - ' . SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Apply for Housing</li>
                    </ol>
                </nav>
            </div>
        </div>

    <div class="dashboard-card mt-3">
        <form method="POST" action="" id="applicationForm">
            <input type="text" name="full_name" placeholder="Full Name" class="form-control mb-2" required>
            <input type="email" name="email" placeholder="Email" class="form-control mb-2" required>
            <input type="text" name="student_id" placeholder="Student ID" class="form-control mb-2" required>
            <select name="apartment_choice" class="form-control mb-2" required>
                <option value="">-- Select Apartment --</option>
                <option value="Apt 1">Apartment 1</option>
                <option value="Apt 2">Apartment 2</option>
            </select>
            <button type="submit" name="apply" class="btn btn-primary">Submit Application</button>
        </form>
    </div>
</div>

<?php
if (isset($_POST['apply'])) {
    $stmt = $pdo->prepare("INSERT INTO applicants (full_name, email, student_id, apartment_choice) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['student_id'], $_POST['apartment_choice']]);
    echo "<div class='alert alert-success mt-3'>Application submitted successfully!</div>";
}
include '../includes/footer.php';
?>
