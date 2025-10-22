<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../middleware/auth.php';

requireRole('applicant');

$user_id = getCurrentUserId();
$db = getDB();

// Get specific application if ID provided
$application_id = intval($_GET['id'] ?? 0);

if ($application_id) {
    $stmt = $db->prepare("SELECT * FROM applications WHERE id = ? AND user_id = ?");
    $stmt->execute([$application_id, $user_id]);
    $application = $stmt->fetch();
    
    if (!$application) {
        setFlash('danger', 'Application not found.');
        redirect('/applicant/status.php');
    }
}

// Get all applications
$stmt = $db->prepare("SELECT * FROM applications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll();

$page_title = 'Application Status';
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
                        <li class="breadcrumb-item active">Application Status</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php
        $flash = getFlash();
        if ($flash):
        ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($application_id && $application): ?>
            <!-- Single Application Details -->
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                Application #<?php echo str_pad($application['id'], 5, '0', STR_PAD_LEFT); ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Status Badge -->
                            <div class="mb-4 text-center">
                                <?php
                                $status_config = [
                                    'pending' => ['color' => 'warning', 'icon' => 'clock-history', 'text' => 'Pending Review'],
                                    'under_review' => ['color' => 'info', 'icon' => 'eye', 'text' => 'Under Review'],
                                    'approved' => ['color' => 'success', 'icon' => 'check-circle', 'text' => 'Approved'],
                                    'rejected' => ['color' => 'danger', 'icon' => 'x-circle', 'text' => 'Rejected'],
                                    'cancelled' => ['color' => 'secondary', 'icon' => 'slash-circle', 'text' => 'Cancelled']
                                ];
                                $config = $status_config[$application['status']] ?? $status_config['pending'];
                                ?>
                                <span class="badge bg-<?php echo $config['color']; ?> fs-5 px-4 py-2">
                                    <i class="bi bi-<?php echo $config['icon']; ?> me-2"></i>
                                    <?php echo $config['text']; ?>
                                </span>
                            </div>

                            <!-- Application Details -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-2">Preferred Apartment Type</h6>
                                    <p class="fw-bold"><?php echo ucwords(str_replace('_', ' ', $application['apartment_type_preference'])); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-2">Move-in Date</h6>
                                    <p class="fw-bold"><?php echo formatDate($application['move_in_date']); ?></p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-2">Duration</h6>
                                    <p class="fw-bold"><?php echo $application['duration_months']; ?> Months</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-2">Employment Status</h6>
                                    <p class="fw-bold"><?php echo ucwords(str_replace('_', ' ', $application['employment_status'] ?? 'N/A')); ?></p>
                                </div>
                            </div>

                            <?php if ($application['monthly_income']): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-2">Monthly Income</h6>
                                    <p class="fw-bold"><?php echo formatCurrency($application['monthly_income']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <hr>

                            <!-- Emergency Contact -->
                            <?php if ($application['emergency_contact_name']): ?>
                            <h6 class="mb-3">Emergency Contact</h6>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <small class="text-muted">Name</small>
                                    <p><?php echo htmlspecialchars($application['emergency_contact_name']); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Phone</small>
                                    <p><?php echo htmlspecialchars($application['emergency_contact_phone']); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted">Relationship</small>
                                    <p><?php echo htmlspecialchars($application['emergency_contact_relationship']); ?></p>
                                </div>
                            </div>
                            <hr>
                            <?php endif; ?>

                            <!-- Additional Notes -->
                            <?php if ($application['additional_notes']): ?>
                            <h6 class="mb-2">Additional Notes</h6>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($application['additional_notes'])); ?></p>
                            <hr>
                            <?php endif; ?>

                            <!-- Review Information -->
                            <?php if ($application['reviewed_by']): ?>
                            <div class="alert alert-<?php echo $application['status'] === 'approved' ? 'success' : 'danger'; ?>">
                                <h6 class="alert-heading">Review Details</h6>
                                <p class="mb-1">
                                    <strong>Reviewed by:</strong> <?php echo getUserFullName($application['reviewed_by']); ?>
                                </p>
                                <p class="mb-1">
                                    <strong>Review Date:</strong> <?php echo formatDate($application['reviewed_at']); ?>
                                </p>
                                <?php if ($application['review_notes']): ?>
                                <p class="mb-0">
                                    <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($application['review_notes'])); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <!-- Timestamps -->
                            <div class="row text-muted small">
                                <div class="col-md-6">
                                    <i class="bi bi-calendar-plus me-1"></i>
                                    Submitted: <?php echo formatDate($application['created_at']); ?>
                                </div>
                                <div class="col-md-6">
                                    <i class="bi bi-calendar-check me-1"></i>
                                    Last Updated: <?php echo formatDate($application['updated_at']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-light">
                            <a href="status.php" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-left me-2"></i>Back to All Applications
                            </a>
                            <?php if ($application['status'] === 'pending'): ?>
                            <button class="btn btn-outline-danger" onclick="cancelApplication(<?php echo $application['id']; ?>)">
                                <i class="bi bi-x-circle me-2"></i>Cancel Application
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- All Applications List -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">My Applications</h5>
                            <a href="apply.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle me-2"></i>New Application
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($applications)): ?>
                                <div class="empty-state py-5">
                                    <i class="bi bi-inbox"></i>
                                    <h5>No Applications Yet</h5>
                                    <p>You haven't submitted any housing applications.</p>
                                    <a href="apply.php" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-2"></i>Submit Your First Application
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Apartment Type</th>
                                                <th>Move-in Date</th>
                                                <th>Duration</th>
                                                <th>Status</th>
                                                <th>Submitted</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($applications as $app): ?>
                                            <tr>
                                                <td class="fw-bold">#<?php echo str_pad($app['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo ucwords(str_replace('_', ' ', $app['apartment_type_preference'])); ?></td>
                                                <td><?php echo formatDate($app['move_in_date']); ?></td>
                                                <td><?php echo $app['duration_months']; ?> months</td>
                                                <td>
                                                    <?php
                                                    $status_colors = [
                                                        'pending' => 'warning',
                                                        'under_review' => 'info',
                                                        'approved' => 'success',
                                                        'rejected' => 'danger',
                                                        'cancelled' => 'secondary'
                                                    ];
                                                    $color = $status_colors[$app['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $color; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatDate($app['created_at']); ?></td>
                                                <td>
                                                    <a href="status.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelApplication(id) {
            if (confirm('Are you sure you want to cancel this application? This action cannot be undone.')) {
                // TODO: Implement cancellation via AJAX
                window.location.href = `../api/applications.php?action=cancel&id=${id}`;
            }
        }
    </script>
</body>
</html>