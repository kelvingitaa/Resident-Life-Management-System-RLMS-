<?php
/**
 * Notifications API Endpoint
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = getCurrentUserId();
$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            // Get all notifications
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 20);
            $offset = ($page - 1) * $limit;
            
            $stmt = $db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$user_id, $limit, $offset]);
            $notifications = $stmt->fetchAll();
            
            // Get total count
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $total = $stmt->fetch()['total'];
            
            echo json_encode([
                'success' => true,
                'data' => $notifications,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'get_unread':
            // Get unread notifications
            $stmt = $db->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? AND is_read = 0 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$user_id]);
            $notifications = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $notifications,
                'count' => count($notifications)
            ]);
            break;
            
        case 'mark_read':
            // Mark single notification as read
            $notification_id = intval($_POST['id'] ?? 0);
            
            if (!$notification_id) {
                throw new Exception('Notification ID is required');
            }
            
            $stmt = $db->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notification_id, $user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
            break;
            
        case 'mark_all_read':
            // Mark all notifications as read
            $stmt = $db->prepare("
                UPDATE notifications 
                SET is_read = 1 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
            break;
            
        case 'delete':
            // Delete notification
            $notification_id = intval($_POST['id'] ?? 0);
            
            if (!$notification_id) {
                throw new Exception('Notification ID is required');
            }
            
            $stmt = $db->prepare("
                DELETE FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notification_id, $user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Notification deleted'
            ]);
            break;
            
        case 'delete_all':
            // Delete all notifications
            $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'All notifications deleted'
            ]);
            break;
            
        case 'get_count':
            // Get unread count
            $count = getUnreadNotificationCount($user_id);
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}