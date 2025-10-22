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

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Housing Application Form</h4>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" action="" id="applicationForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <h5 class="mb-3">Housing Preferences</h5>
                            
                            <div class="mb-3">
                                <label for="apartment_type" class="form-label">Preferred Apartment Type <span class="text-danger">*</span></label>
                                <select class="form-select" id="apartment_type" name="apartment_type" required>
                                    <option value="">Select Apartment Type</option>
                                    <option value="studio">Studio</option>
                                    <option value="one_bedroom">One Bedroom</option>
                                    <option value="two_bedroom">Two Bedroom</option>
                                    <option value="shared">Shared Room</option>
                                    <option value="single">Single Room</option>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="move_in_date" class="form-label">Desired Move-in Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="move_in_date" name="move_in_date" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="duration_months" class="form-label">Duration (Months) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="duration_months" name="duration_months" 
                                           min="1" max="24" required>
                                    <small class="text-muted">Minimum 1 month, Maximum 24 months</small>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h5 class="mb-3">Employment & Financial Information</h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="employment_status" class="form-label">Employment Status</label>
                                    <select class="form-select" id="employment_status" name="employment_status">
                                        <option value="">Select Status</option>
                                        <option value="student">Student</option>
                                        <option value="employed">Employed</option>
                                        <option value="self_employed">Self-Employed</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="monthly_income" class="form-label">Monthly Income (KES)</label>
                                    <input type="number" class="form-control" id="monthly_income" name="monthly_income" 
                                           step="0.01" min="0">
                                </div>
                            </div>

                            <hr class="my-4">

                            <h5 class="mb-3">Emergency Contact</h5>

                            <div class="mb-3">
                                <label for="emergency_contact_name" class="form-label">Contact Name</label>
                                <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="emergency_contact_phone" class="form-label">Contact Phone</label>
                                    <input type="tel" class="form-control" id="emergency_contact_phone" name="emergency_contact_phone">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                                    <input type="text" class="form-control" id="emergency_contact_relationship" 
                                           name="emergency_contact_relationship" placeholder="e.g., Parent, Spouse, Friend">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="additional_notes" class="form-label">Additional Notes</label>
                                <textarea class="form-control" id="additional_notes" name="additional_notes" 
                                          rows="4" placeholder="Any special requirements or additional information..."></textarea>
                            </div>

                            <hr class="my-4">

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="confirm" required>
                                <label class="form-check-label" for="confirm">
                                    I confirm that all information provided is accurate and complete <span class="text-danger">*</span>
                                </label>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send me-2"></i>Submit Application
                                </button>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Calculate estimated end date based on duration
        document.getElementById('duration_months').addEventListener('change', function() {
            const moveInDate = document.getElementById('move_in_date').value;
            if (moveInDate && this.value) {
                const date = new Date(moveInDate);
                date.setMonth(date.getMonth() + parseInt(this.value));
                console.log('Estimated move-out date:', date.toLocaleDateString());
            }
        });
    </script>
</body>
</html>