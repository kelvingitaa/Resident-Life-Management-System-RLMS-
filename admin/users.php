<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';
require_once '../middleware/auth.php';

// Ensure requireRole function exists
if (!function_exists('requireRole')) {
    function requireRole($role) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== $role) {
            header('Location: /login.php');
            exit;
        }
    }
}

requireRole('admin');

$db = getDB();

// Handle user status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $user_id = intval($_POST['user_id']);
        $status = sanitize($_POST['status']);
        
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$status, $user_id]);
        
        logAudit('User Status Updated', 'users', $user_id);
        setFlash('success', 'User status updated successfully!');
        redirect('/admin/users.php');
    }
}

// Handle role change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $user_id = intval($_POST['user_id']);
        $new_role = sanitize($_POST['role']);
        
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $user_id]);
        
        logAudit('User Role Changed', 'users', $user_id);
        setFlash('success', 'User role updated successfully!');
        redirect('/admin/users.php');
    }
}

// Get filters
$filter_role = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$filter_status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$where_clauses = [];
$params = [];

if ($filter_role) {
    $where_clauses[] = "role = ?";
    $params[] = $filter_role;
}

if ($filter_status) {
    $where_clauses[] = "status = ?";
    $params[] = $filter_status;
}

if ($search) {
    $where_clauses[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get users with pagination
define('RECORDS_PER_PAGE', 10); // Set the number of records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * RECORDS_PER_PAGE;

$count_stmt = $db->prepare("SELECT COUNT(*) as total FROM users $where_sql");
$count_stmt->execute($params);
$total_records = $count_stmt->fetch()['total'];

$pagination = paginate($total_records, $page);

$stmt = $db->prepare("
    SELECT * FROM users 
    $where_sql
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$pagination['records_per_page'], $pagination['offset']]));
$users = $stmt->fetchAll();

// Get statistics
$stmt = $db->query("
    SELECT role, COUNT(*) as count 
    FROM users 
    WHERE status = 'active' 
    GROUP BY role
");
$role_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$csrf_token = generateCSRFToken();
$page_title = 'User Management';
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
                <h2><i class="bi bi-people me-2"></i>User Management</h2>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-person-plus text-warning fs-1"></i>
                        <h3 class="mt-2"><?php echo $role_stats['applicant'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Applicants</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-person-check text-success fs-1"></i>
                        <h3 class="mt-2"><?php echo $role_stats['resident'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Residents</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-person-gear text-info fs-1"></i>
                        <h3 class="mt-2"><?php echo $role_stats['staff'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Staff</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <i class="bi bi-shield-check text-danger fs-1"></i>
                        <h3 class="mt-2"><?php echo $role_stats['admin'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Administrators</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="Name or email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            <option value="applicant" <?php echo $filter_role === 'applicant' ? 'selected' : ''; ?>>Applicant</option>
                            <option value="resident" <?php echo $filter_role === 'resident' ? 'selected' : ''; ?>>Resident</option>
                            <option value="staff" <?php echo $filter_role === 'staff' ? 'selected' : ''; ?>>Staff</option>
                            <option value="admin" <?php echo $filter_role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $filter_status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="bi bi-search me-2"></i>Search
                        </button>
                        <a href="users.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($user['profile_image']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                                                 class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2" 
                                                 style="width: 32px; height: 32px;">
                                                <i class="bi bi-person text-primary"></i>
                                            </div>
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['role'] === 'admin' ? 'danger' : 
                                            ($user['role'] === 'staff' ? 'info' : 
                                            ($user['role'] === 'resident' ? 'success' : 'warning')); 
                                    ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $user['status'] === 'active' ? 'success' : 
                                            ($user['status'] === 'suspended' ? 'danger' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($user['created_at']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateModal"
                                                onclick="setUserData(<?php echo $user['id']; ?>, '<?php echo $user['role']; ?>', '<?php echo $user['status']; ?>', '<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>')">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                <nav aria-label="User pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo !$pagination['has_prev'] ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&role=<?php echo $filter_role; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&role=<?php echo $filter_role; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo !$pagination['has_next'] ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&role=<?php echo $filter_role; ?>&status=<?php echo $filter_status; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Update Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="user_id">
                    
                    <div class="mb-3">
                        <label class="form-label">User</label>
                        <input type="text" class="form-control" id="user_name" disabled>
                    </div>

                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#roleTab">Change Role</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#statusTab">Change Status</a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="roleTab">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="user_id" id="role_user_id">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" name="role" id="role" required>
                                        <option value="applicant">Applicant</option>
                                        <option value="resident">Resident</option>
                                        <option value="staff">Staff</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <button type="submit" name="change_role" class="btn btn-primary w-100">Update Role</button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="statusTab">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="user_id" id="status_user_id">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" name="status" id="status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="suspended">Suspended</option>
                                    </select>
                                </div>
                                <button type="submit" name="update_status" class="btn btn-primary w-100">Update Status</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setUserData(id, role, status, name) {
            document.getElementById('user_id').value = id;
            document.getElementById('user_name').value = name;
            document.getElementById('role_user_id').value = id;
            document.getElementById('status_user_id').value = id;
            document.getElementById('role').value = role;
            document.getElementById('status').value = status;
        }
    </script>
</body>
</html>