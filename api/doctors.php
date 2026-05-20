<?php
// ============================================================
//  TARUKU HEALTH — Doctors API
//  File: api/doctors.php
//  Used by: Admin website + Android app (chatbot checks availability)
// ============================================================

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ── GET all doctors or only available ones ──
    case 'GET':
        if (isset($_GET['available'])) {
            $stmt = $pdo->prepare("SELECT * FROM doctors WHERE available = 1 ORDER BY full_name");
            $stmt->execute();
        } else {
            $stmt = $pdo->query("SELECT * FROM doctors ORDER BY full_name");
        }
        echo json_encode($stmt->fetchAll());
        break;

    // ── POST add a new doctor (admin website) ──
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['full_name'])) {
            echo json_encode(["success" => false, "message" => "Doctor name is required"]);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO doctors (full_name, specialisation, phone, email, available)
            VALUES (?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $data['full_name'],
            $data['specialisation'] ?? null,
            $data['phone']          ?? null,
            $data['email']          ?? null
        ]);

        echo json_encode([
            "success" => true,
            "message" => "Doctor added successfully",
            "id"      => $pdo->lastInsertId()
        ]);
        break;

    // ── PUT toggle doctor availability (admin website) ──
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            echo json_encode(["success" => false, "message" => "Doctor ID required"]);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE doctors SET available = ? WHERE id = ?");
        $stmt->execute([$data['available'] ? 1 : 0, $data['id']]);

        echo json_encode(["success" => true, "message" => "Doctor availability updated"]);
        break;

    // ── DELETE remove a doctor ──
    case 'DELETE':
        if (empty($_GET['id'])) {
            echo json_encode(["success" => false, "message" => "Doctor ID required"]);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
        $stmt->execute([$_GET['id']]);

        echo json_encode(["success" => true, "message" => "Doctor removed"]);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Method not allowed"]);
}
?>
