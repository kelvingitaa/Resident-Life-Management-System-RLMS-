<?php
// api/applicants.php
include '../config/database.php';
header('Content-Type: application/json');

$stmt = $pdo->query("SELECT id, name, email, status FROM applicants");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
