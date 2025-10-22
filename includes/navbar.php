<?php
$user_id = getCurrentUserId();
$role = getCurrentUserRole();
$unread_count = getUnreadNotificationCount($user_id);

// Get user info
$db = getDB();
$stmt = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get recent notifications
$stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Define navigation based on role
$nav_items = [
    'applicant' => [
        ['url' => 'dashboard.php', 'icon' => 'house-door', 'label' => 'Dashboard'],
        ['url' => 'apply.php', 'icon' => 'file-earmark-plus', 'label' => 'Apply'],
        ['url' => 'status.php', 'icon' => 'clock-history', 'label' => 'Status']
    ],
    'resident' => [
        ['url' => 'dashboard.php', 'icon' => 'house-door', 'label' => 'Dashboard'],
        ['url' => 'contracts.php', 'icon' => 'file-text', 'label' => 'Contracts'],
        ['url' => 'payments.php', 'icon' => 'credit-card', 'label' => 'Payments'],
        ['url' => 'maintenance.php', 'icon' => 'tools', 'label' => 'Maintenance'],
        ['url' => 'profile.php', 'icon' => 'person', 'label' => 'Profile']
    ],
    'staff' => [
        ['url' => 'dashboard.php', 'icon' => 'house-door', 'label' => 'Dashboard'],
        ['url' => 'apartments.php', 'icon' => 'building', 'label' => 'Apartments'],
        ['url' => 'contracts.php', 'icon' => 'file-text', 'label' => 'Contracts'],
        ['url' => 'maintenance.php', 'icon' => 'tools', 'label' => 'Maintenance'],
        ['url' => 'reports.php', 'icon' => 'graph-up', 'label' => 'Reports']
    ],
    'admin' => [
        ['url' => 'dashboard.php', 'icon' => 'house-door', 'label' => 'Dashboard'],
        ['url' => 'users.php', 'icon' => 'people', 'label' => 'Users'],
        ['url' => 'apartments.php', 'icon' => 'building', 'label' => 'Apartments'],
        ['url' => 'payments.php', 'icon' => 'cash-stack', 'label' => 'Payments'],
        ['url' => 'analytics.php', 'icon' => 'graph-up-arrow', 'label' => 'Analytics']
    ]
];

$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard.php">
            <i class="bi bi-building"></i> HDMS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php foreach ($nav_items[$role] as $item): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === $item['url'] ? 'active' : ''; ?>" 
                           href="<?php echo $item['url']; ?>">
                            <i class="bi bi-<?php echo $item['icon']; ?> me-1"></i>
                            <?php echo $item['label']; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <ul class="navbar-nav">
                <!-- Notifications Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" id="notificationsDropdown">
                        <i class="bi bi-bell fs-5"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unread_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: 320px; max-height: 400px; overflow-y: auto;">
                        <li class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Notifications</span>
                            <?php if ($unread_count > 0): ?>
                                <a href="#" class="text-primary text-decoration-none small" onclick="markAllAsRead()">Mark all read</a>
                            <?php endif; ?>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if (empty($notifications)): ?>
                            <li><span class="dropdown-item text-muted text-center py-3">No notifications</span></li>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <li>
                                    <a class="dropdown-item <?php echo !$notification['is_read'] ? 'bg-light' : ''; ?>" 
                                       href="<?php echo $notification['link'] ?? '#'; ?>">
                                        <div class="d-flex">
                                            <div class="flex-grow-1">
                                                <div class="fw-bold small"><?php echo htmlspecialchars($notification['title']); ?></div>
                                                <div class="text-muted small"><?php echo htmlspecialchars($notification['message']); ?></div>
                                                <div class="text-muted small mt-1">
                                                    <i class="bi bi-clock"></i> 
                                                    <?php echo formatDate($notification['created_at'], 'd M, H:i'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </li>

                <!-- User Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <?php echo htmlspecialchars($user['first_name']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text">
                                <div class="fw-bold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                                <div class="text-muted small"><?php echo ucfirst($role); ?></div>
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <?php if ($role === 'resident' || $role === 'applicant'): ?>
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
function markAllAsRead() {
    // AJAX call to mark all notifications as read
    fetch('../api/notifications.php?action=mark_all_read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>