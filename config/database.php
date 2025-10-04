<?php
// config/database.php

$host = "127.0.0.1";   // try "localhost" if this fails
$dbname = "your_db_name"; 
$username = "root";     // XAMPP default
$password = "";         // XAMPP default (empty)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB Connection failed: " . $e->getMessage());
}
