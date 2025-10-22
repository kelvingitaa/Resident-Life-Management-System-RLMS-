<?php
/**
 * Applications API Endpoint
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
$user_role = getCurrentUserRole();
$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_all':
            // Get all applications (admin/staff only)
            if (!hasAnyRole(['admin', 'staff'])) {
                throw new Exception('Permission denied');
            }
            
            $status = $_GET['status'] ?? '';
            $where = $status ? "WHERE status = ?" : "";
            $params = $status ? [$status] : [];
            
            $stmt = $db->prepare("
                SELECT a.*, u.first_name, u.last_name, u.email, u.phone
                FROM applications a
                JOIN users u ON a.user_id = u.id
                $where
                ORDER BY a.created_at DESC
            ");
            $stmt->execute($params);
            $applications = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $applications,
                'count' => count($applications)
            ]);
            break;
            
        case 'get_user_applications':
            // Get applications for current user
            $stmt = $db->prepare("
                SELECT * FROM applications 
                WHERE user_id = ? 
                ORDER BY created_at DESC
            ");
            $stmt->execute([$user_id]);
            $applications = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'data' => $applications
            ]);
            break;
            
        case 'get_by_id':
            // Get single application
            $id = intval($_GET['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('Application ID is required');
            }
            
            $stmt = $db->prepare("
                SELECT a.*, u.first_name, u.last_name, u.email, u.phone
                FROM applications a
                JOIN users u ON a.user_id = u.id
                WHERE a.id = ?
            ");
            $stmt->execute([$id]);
            $application = $stmt->fetch();
            
            if (!$application) {
                throw new Exception('Application not found');
            }
            
            // Check permission
            if ($application['user_id'] != $user_id && !hasAnyRole(['admin', 'staff'])) {
                throw new Exception('Permission denied');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $application
            ]);
            break;
            
        case 'submit':
            // Submit new application
            if ($method !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!isset($data['apartment_type_preference'], $data['move_in_date'], $data['duration_months'])) {
                throw new Exception('Missing required fields');
            }
            
            $stmt = $db->prepare("
                INSERT INTO applications (
                    user_id, apartment_type_preference, move_in_date, duration_months,
                    employment_status, monthly_income, emergency_contact_name,
                    emergency_contact_phone, emergency_contact_relationship, 
                    additional_notes, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $user_id,
                $data['apartment_type_preference'],
                $data['move_in_date'],
                $data['duration_months'],
                $data['employment_status'] ?? null,
                $data['monthly_income'] ?? null,
                $data['emergency_contact_name'] ?? null,
                $data['emergency_contact_phone'] ?? null,
                $data['emergency_contact_relationship'] ?? null,
                $data['additional_notes'] ?? null
            ]);
            
            $application_id = $db->lastInsertId();
            
            // Log audit
            logAudit('Application Submitted', 'applications', $application_id);
            
            // Create notification
            createNotification(
                $user_id,
                'Application Submitted',
                'Your housing application has been submitted successfully.',
                'application',
                '/applicant/status.php?id=' . $application_id
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Application submitted successfully',
                'application_id' => $application_id
            ]);
            break;
            
        case 'update_status':
            // Update application status (staff/admin only)
            if (!hasAnyRole(['admin', 'staff'])) {
                throw new Exception('Permission denied');
            }
            
            if ($method !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!isset($data['id'], $data['status'])) {
                throw new Exception('Missing required fields');
            }
            
            $stmt = $db->prepare("
                UPDATE applications 
                SET status = ?, reviewed_by = ?, reviewed_at = NOW(), review_notes = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['status'],
                $user_id,
                $data['review_notes'] ?? null,
                $data['id']
            ]);
            
            // Get application details for notification
            $stmt = $db->prepare("SELECT user_id FROM applications WHERE id = ?");
            $stmt->execute([$data['id']]);
            $app = $stmt->fetch();
            
            if ($app) {
                createNotification(
                    $app['user_id'],
                    'Application Status Updated',
                    'Your application status has been updated to: ' . $data['status'],
                    'application',
                    '/applicant/status.php?id=' . $data['id']
                );
            }
            
            logAudit('Application Status Updated', 'applications', $data['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Application status updated successfully'
            ]);
            break;
            
        case 'cancel':
            // Cancel application
            $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
            
            if (!$id) {
                throw new Exception('Application ID is required');
            }
            
            // Verify ownership
            $stmt = $db->prepare("SELECT user_id, status FROM applications WHERE id = ?");
            $stmt->execute([$id]);
            $app = $stmt->fetch();
            
            if (!$app) {
                throw new Exception('Application not found');
            }
            
            if ($app['user_id'] != $user_id && !hasAnyRole(['admin', 'staff'])) {
                throw new Exception('Permission denied');
            }
            
            if ($app['status'] !== 'pending') {
                throw new Exception('Only pending applications can be cancelled');
            }
            
            $stmt = $db->prepare("UPDATE applications SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$id]);
            
            logAudit('Application Cancelled', 'applications', $id);
            
            echo json_encode([
                'success' => true,
                'message' => 'Application cancelled successfully'
            ]);
            break;
            
        case 'statistics':
            // Get application statistics (admin/staff only)
            if (!hasAnyRole(['admin', 'staff'])) {
                throw new Exception('Permission denied');
            }
            
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                FROM applications
            ");
            
            $stats = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'data' => $stats
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