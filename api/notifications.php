<?php
// api/notifications.php
header('Content-Type: application/json');

// Mock notification data
$notifications = [
    ["message" => "New applicant registered", "time" => "2 mins ago"],
    ["message" => "Offer accepted by John Doe", "time" => "1 hour ago"]
];
echo json_encode($notifications);
