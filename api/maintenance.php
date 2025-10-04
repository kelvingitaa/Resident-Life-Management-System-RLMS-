<?php
// api/maintenance.php
header("Content-Type: application/json");
include '../config/database.php';
session_start();

// Ensure resident logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch maintenance requests for this resident
    $stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE resident_id = ? ORDER BY request_date DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "requests" => $requests]);

} elseif ($method === 'POST') {
    // Submit new maintenance request
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['apartment_id'], $data['description'])) {
        echo json_encode(["success" => false, "message" => "Invalid data"]);
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO maintenance_requests (apartment_id, resident_id, description, request_date, status) VALUES (?, ?, ?, NOW(), 'pending')");
    $stmt->execute([$data['apartment_id'], $_SESSION['user_id'], $data['description']]);

    echo json_encode(["success" => true, "message" => "Maintenance request submitted"]);
} else {
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
}
