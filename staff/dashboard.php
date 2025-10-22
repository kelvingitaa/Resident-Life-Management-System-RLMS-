<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../middleware/auth.php';

requireRole('staff');

$db = getDB();

// Get statistics
$stats = [];

// Total apartments
$stmt = $db->query("SELECT COUNT(*) as total FROM apartments");
$stats['total_apartments'] = $stmt->fetch()['total'];

// Vacant apartments
$stmt = $db->query("SELECT COUNT(*) as total FROM apartments WHERE status = 'vacant'");
$stats['vacant'] = $stmt->fetch()['total'];

// Occupied apartments
$stmt = $db->query("SELECT COUNT(*) as total FROM apartments WHERE status = 'occupied'");
$stats['occupied'] = $stmt->fetch()['total'];

// Pending applications
$stmt = $db->query("SELECT COUNT(*) as total FROM applications WHERE status = 'pending'");
$stats['pending_applications'] = $stmt->fetch()['total'];

// Maintenance requests pending
$stmt = $db->query("SELECT COUNT(*) as total FROM maintenance_requests WHERE status IN ('pending', 'assigned')");
$stats['pending_maintenance'] = $stmt->fetch()['total'];

// Active contracts
$stmt = $db->query("SELECT COUNT(*) as total FROM contracts WHERE status = 'active'");
$stats['active_contracts'] = $stmt->fetch()['total'];

// Recent maintenance requests
$stmt = $db->prepare("
    SELECT mr.*, u.first_name, u.last_name, a.apartment_number, b.name as building_name
    FROM maintenance_requests mr
    JOIN users u ON mr.user_id = u.id
    JOIN apartments a ON mr.apartment_id = a.id
    JOIN buildings b ON a.building_id = b.id
    ORDER BY mr.created_at DESC
    LIMIT 5
");
$stmt->execute();
$recent_maintenance = $stmt->fetchAll();

// Recent applications
$stmt = $db->prepare("
    SELECT app.*, u.first_name, u.last_name, u.email, u.phone
    FROM applications app
    JOIN users u ON app.user_id = u.id
    WHERE app.status = 'pending'
    ORDER BY app.created_at DESC
    LIMIT 5
");
$stmt->execute();
$pending_apps = $stmt->fetchAll();

$page_title = 'Staff Dashboard';
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

    <div class="container-fluid py-4">
        <?php
        $flash = getFlash();
        if ($flash):
        ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-12">
                <h2>Staff Dashboard</h2>
                <p class="text-muted">Manage housing operations and maintenance</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-building text-primary fs-1 mb-2"></i>
                        <h3 class="mb-0"><?php echo $stats['total_apartments']; ?></h3>
                        <p class="text-muted mb-0 small">Total Units</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-door-open text-success fs-1 mb-2"></i>
                        <h3 class="mb-0"><?php echo $stats['vacant']; ?></h3>
                        <p class="text-muted mb-0 small">Vacant</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-door-closed text-info fs-1 mb-2"></i>
                        <h3 class="mb-0"><?php echo $stats['occupied']; ?></h3>
                        <p class="text-muted mb-0 small">Occupied</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-text text-warning fs-1 mb-2"></i>
                        <h3 class="mb-0"><?php echo $stats['pending_applications']; ?></h3>
                        <p class="text-muted mb-0 small">Applications</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-tools text-danger fs-1 mb-2"></i>
                        <h3 class="mb-0"><?php echo $stats['pending_maintenance']; ?></h3>
                        <p class="text-muted mb-0 small">Maintenance</p>
                    </div>
                </div>
            </div>

            <div class="col-md-4 col-lg-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <i class="bi bi-file-text text-dark fs-1 mb-2"></i>
                        <h3 class="mb-0"><?php echo $stats['active_contracts']; ?></h3>
                        <p class="text-muted mb-0 small">Contracts</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Quick Actions</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="apartments.php" class="btn btn-primary">
                                <i class="bi bi-building me-2"></i>Manage Apartments
                            </a>
                            <a href="contracts.php?action=create" class="btn btn-success">
                                <i class="bi bi-file-plus me-2"></i>Create Contract
                            </a>
                            <a href="maintenance.php" class="btn btn-warning">
                                <i class="bi bi-tools me-2"></i>View Maintenance
                            </a>
                            <a href="reports.php" class="btn btn-info">
                                <i class="bi bi-graph-up me-2"></i>Generate Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Pending Applications -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Pending Applications</h5>
                        <span class="badge bg-warning"><?php echo $stats['pending_applications']; ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_apps)): ?>
                            <p class="text-muted text-center py-3">No pending applications</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($pending_apps as $app): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                                                </h6>
                                                <p class="mb-1 small text-muted">
                                                    <i class="bi bi-envelope me-1"></i><?php echo htmlspecialchars($app['email']); ?>
                                                </p>
                                                <p class="mb-1 small">
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo ucwords(str_replace('_', ' ', $app['apartment_type_preference'])); ?>
                                                    </span>
                                                    <span class="text-muted ms-2">
                                                        Move-in: <?php echo formatDate($app['move_in_date']); ?>
                                                    </span>
                                                </p>
                                            </div>
                                            <div>
                                                <a href="applications.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    Review
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="applications.php" class="btn btn-sm btn-outline-primary">View All Applications</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Maintenance Requests -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Maintenance</h5>
                        <span class="badge bg-danger"><?php echo $stats['pending_maintenance']; ?></span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_maintenance)): ?>
                            <p class="text-muted text-center py-3">No maintenance requests</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_maintenance as $req): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($req['title']); ?></h6>
                                                <p class="mb-1 small text-muted">
                                                    <?php echo htmlspecialchars($req['building_name']); ?> - 
                                                    Apt <?php echo htmlspecialchars($req['apartment_number']); ?>
                                                </p>
                                                <p class="mb-1 small">
                                                    <?php
                                                    $priority_colors = [
                                                        'low' => 'success',
                                                        'medium' => 'warning',
                                                        'high' => 'danger',
                                                        'urgent' => 'dark'
                                                    ];
                                                    $priority_color = $priority_colors[$req['priority']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $priority_color; ?>">
                                                        <?php echo ucfirst($req['priority']); ?>
                                                    </span>
                                                    <span class="badge bg-light text-dark ms-1">
                                                        <?php echo ucfirst($req['category']); ?>
                                                    </span>
                                                    <span class="text-muted ms-2">
                                                        <?php echo formatDate($req['created_at']); ?>
                                                    </span>
                                                </p>
                                            </div>
                                            <div>
                                                <a href="maintenance.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    View
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="maintenance.php" class="btn btn-sm btn-outline-primary">View All Requests</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>