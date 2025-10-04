<?php
// api/status.php
include '../config/database.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(["error" => "Missing applicant ID"]);
    exit;
}

$id = (int) $_GET['id'];
$stmt = $pdo->prepare("SELECT status FROM applicants WHERE id=?");
$stmt->execute([$id]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
