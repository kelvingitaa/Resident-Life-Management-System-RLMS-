<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../middleware/auth.php';

requireRole('staff');

$db = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $apartment_id = intval($_POST['apartment_id']);
        $status = sanitize($_POST['status']);
        
        $stmt = $db->prepare("UPDATE apartments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $apartment_id]);
        
        logAudit('Apartment Status Updated', 'apartments', $apartment_id);
        setFlash('success', 'Apartment status updated successfully!');
        redirect('/staff/apartments.php');
    }
}

// Get filter
$filter_status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$filter_type = isset($_GET['type']) ? sanitize($_GET['type']) : '';

// Build query
$where_clauses = [];
$params = [];

if ($filter_status) {
    $where_clauses[] = "a.status = ?";
    $params[] = $filter_status;
}

if ($filter_type) {
    $where_clauses[] = "a.type = ?";
    $params[] = $filter_type;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get apartments
$stmt = $db->prepare("
    SELECT a.*, b.name as building_name, b.address,
           COUNT(DISTINCT c.id) as active_contracts
    FROM apartments a
    JOIN buildings b ON a.building_id = b.id
    LEFT JOIN contracts c ON a.id = c.apartment_id AND c.status = 'active'
    $where_sql
    GROUP BY a.id
    ORDER BY b.name, a.apartment_number
");
$stmt->execute($params);
$apartments = $stmt->fetchAll();

// Get statistics
$stmt = $db->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM apartments
    GROUP BY status
");
$stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$csrf_token = generateCSRFToken();
$page_title = 'Manage Apartments';
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
                <h2><i class="bi bi-building me-2"></i>Apartment Management</h2>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-door-open text-success fs-1"></i>
                        <h3 class="mt-2"><?php echo $stats['vacant'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Vacant</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-door-closed text-info fs-1"></i>
                        <h3 class="mt-2"><?php echo $stats['occupied'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Occupied</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-tools text-warning fs-1"></i>
                        <h3 class="mt-2"><?php echo $stats['maintenance'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Maintenance</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-check text-primary fs-1"></i>
                        <h3 class="mt-2"><?php echo $stats['reserved'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Reserved</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="vacant" <?php echo $filter_status === 'vacant' ? 'selected' : ''; ?>>Vacant</option>
                            <option value="occupied" <?php echo $filter_status === 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                            <option value="maintenance" <?php echo $filter_status === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                            <option value="reserved" <?php echo $filter_status === 'reserved' ? 'selected' : ''; ?>>Reserved</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="studio" <?php echo $filter_type === 'studio' ? 'selected' : ''; ?>>Studio</option>
                            <option value="one_bedroom" <?php echo $filter_type === 'one_bedroom' ? 'selected' : ''; ?>>One Bedroom</option>
                            <option value="two_bedroom" <?php echo $filter_type === 'two_bedroom' ? 'selected' : ''; ?>>Two Bedroom</option>
                            <option value="shared" <?php echo $filter_type === 'shared' ? 'selected' : ''; ?>>Shared</option>
                            <option value="single" <?php echo $filter_type === 'single' ? 'selected' : ''; ?>>Single</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-funnel me-2"></i>Filter
                        </button>
                        <a href="apartments.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Apartments Table -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Building</th>
                                <th>Apartment</th>
                                <th>Type</th>
                                <th>Capacity</th>
                                <th>Occupants</th>
                                <th>Rent</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apartments as $apt): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($apt['building_name']); ?></td>
                                <td class="fw-bold"><?php echo htmlspecialchars($apt['apartment_number']); ?></td>
                                <td><?php echo ucwords(str_replace('_', ' ', $apt['type'])); ?></td>
                                <td><?php echo $apt['capacity']; ?></td>
                                                                <td><?php echo $apt['active_contracts']; ?> / <?php echo $apt['capacity']; ?></td>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
                                </body>
                                </html>