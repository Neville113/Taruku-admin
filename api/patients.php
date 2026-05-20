<?php
// ============================================================
//  TARUKU HEALTH — Patients API
//  File: api/patients.php
//  Used by: Admin website + Android app
// ============================================================

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ── GET all patients or one patient ──
    case 'GET':
        if (isset($_GET['id'])) {
            // Get single patient
            $stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            $patient = $stmt->fetch();
            echo json_encode($patient ?: ["success" => false, "message" => "Patient not found"]);
        } else {
            // Get all patients
            $stmt = $pdo->query("SELECT id, full_name, email, phone, date_of_birth, gender, address, created_at FROM patients ORDER BY created_at DESC");
            echo json_encode($stmt->fetchAll());
        }
        break;

    // ── POST register a new patient (from Android app) ──
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate required fields
        if (empty($data['full_name']) || empty($data['email']) || empty($data['password'])) {
            echo json_encode(["success" => false, "message" => "Name, email and password are required"]);
            exit;
        }

        // Check if email already exists
        $check = $pdo->prepare("SELECT id FROM patients WHERE email = ?");
        $check->execute([$data['email']]);
        if ($check->fetch()) {
            echo json_encode(["success" => false, "message" => "Email already registered"]);
            exit;
        }

        // Insert patient
        $stmt = $pdo->prepare("
            INSERT INTO patients (full_name, email, phone, password_hash, date_of_birth, gender, address)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['full_name'],
            $data['email'],
            $data['phone']         ?? null,
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['date_of_birth'] ?? null,
            $data['gender']        ?? null,
            $data['address']       ?? null
        ]);

        echo json_encode([
            "success" => true,
            "message" => "Patient registered successfully",
            "id"      => $pdo->lastInsertId()
        ]);
        break;

    // ── PUT update patient details ──
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            echo json_encode(["success" => false, "message" => "Patient ID required"]);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE patients SET full_name=?, phone=?, date_of_birth=?, gender=?, address=?
            WHERE id=?
        ");
        $stmt->execute([
            $data['full_name']     ?? null,
            $data['phone']         ?? null,
            $data['date_of_birth'] ?? null,
            $data['gender']        ?? null,
            $data['address']       ?? null,
            $data['id']
        ]);

        echo json_encode(["success" => true, "message" => "Patient updated"]);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Method not allowed"]);
}
?>
