<?php
// ============================================================
//  TARUKU HEALTH — Database Connection
//  File: api/config.php
//  Include this file in every PHP file that needs the database
// ============================================================

$host     = "localhost";
$db_name  = "taruku_health";
$username = "root";        // default XAMPP username
$password = "";            // default XAMPP password (empty)

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db_name;charset=utf8",
        $username,
        $password
    );
    // Show errors during development
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
    exit;
}

// Allow requests from Android app and admin website
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");
?>
