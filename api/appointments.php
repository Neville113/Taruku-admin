<?php
// ============================================================
//  TARUKU HEALTH — Appointments API
//  File: api/appointments.php
//  Used by: Admin website + Android app (via chatbot)
// ============================================================

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ── GET all appointments or filter by patient/status ──
    case 'GET':
        $where  = [];
        $params = [];

        if (isset($_GET['patient_id'])) {
            $where[]  = "a.patient_id = ?";
            $params[] = $_GET['patient_id'];
        }

        if (isset($_GET['status'])) {
            $where[]  = "a.status = ?";
            $params[] = $_GET['status'];
        }

        $whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

        $stmt = $pdo->prepare("
            SELECT a.*, p.full_name AS patient_name, p.phone AS patient_phone,
                   d.full_name AS doctor_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            LEFT JOIN doctors d ON a.doctor_id = d.id
            $whereSQL
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $stmt->execute($params);
        echo json_encode($stmt->fetchAll());
        break;

    // ── POST book a new appointment (chatbot books on behalf of patient) ──
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['patient_id']) || empty($data['appointment_date']) || empty($data['appointment_time'])) {
            echo json_encode(["success" => false, "message" => "Patient ID, date and time are required"]);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO appointments (patient_id, doctor_id, consultation_type, appointment_date, appointment_time, reason)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['patient_id'],
            $data['doctor_id']         ?? null,
            $data['consultation_type'] ?? 'General',
            $data['appointment_date'],
            $data['appointment_time'],
            $data['reason']            ?? null
        ]);

        echo json_encode([
            "success" => true,
            "message" => "Appointment booked successfully",
            "id"      => $pdo->lastInsertId()
        ]);
        break;

    // ── PUT update appointment status (admin confirms or cancels) ──
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id']) || empty($data['status'])) {
            echo json_encode(["success" => false, "message" => "Appointment ID and status required"]);
            exit;
        }

        $allowed = ['pending', 'confirmed', 'cancelled'];
        if (!in_array($data['status'], $allowed)) {
            echo json_encode(["success" => false, "message" => "Invalid status"]);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE appointments SET status=? WHERE id=?");
        $stmt->execute([$data['status'], $data['id']]);

        echo json_encode(["success" => true, "message" => "Appointment " . $data['status']]);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Method not allowed"]);
}
?>
