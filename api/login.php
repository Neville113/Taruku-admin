<?php
// ============================================================
//  TARUKU HEALTH — Login API
//  File: api/login.php
//  Handles login for both admin website and Android app
// ============================================================

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "POST method required"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['email']) || empty($data['password']) || empty($data['role'])) {
    echo json_encode(["success" => false, "message" => "Email, password and role are required"]);
    exit;
}

$email    = $data['email'];
$password = $data['password'];
$role     = $data['role']; // 'patient' or 'admin'

if ($role === 'patient') {
    // ── Patient login (Android app) ──
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        unset($user['password_hash']); // never send password back
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "role"    => "patient",
            "user"    => $user
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    }

} elseif ($role === 'admin') {
    // ── Admin login (website) ──
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        unset($user['password_hash']);
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "role"    => $user['role'],
            "user"    => $user
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Invalid role"]);
}
?>
