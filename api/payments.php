<?php
// api/payments.php
header("Content-Type: application/json");
include '../config/database.php';
session_start();

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Fetch user payments
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(["success" => true, "payments" => $payments]);

} elseif ($method === 'POST') {
    // Add a payment
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['amount'], $data['status'])) {
        echo json_encode(["success" => false, "message" => "Invalid data"]);
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, status, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $data['amount'], $data['status']]);

    echo json_encode(["success" => true, "message" => "Payment recorded"]);
} else {
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
}
