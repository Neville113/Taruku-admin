<?php
// ============================================================
//  TARUKU HEALTH — Notifications API
//  File: api/notifications.php
//  Admin sends → chatbot delivers to patient on Android app
// ============================================================

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // ── GET notifications for a patient (Android app fetches these) ──
    case 'GET':
        if (empty($_GET['patient_id'])) {
            echo json_encode(["success" => false, "message" => "Patient ID required"]);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT * FROM notifications
            WHERE patient_id = ? OR target = 'all'
            ORDER BY created_at DESC
        ");
        $stmt->execute([$_GET['patient_id']]);
        echo json_encode($stmt->fetchAll());
        break;

    // ── POST send a notification (admin website sends this) ──
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['title']) || empty($data['message'])) {
            echo json_encode(["success" => false, "message" => "Title and message are required"]);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO notifications (title, message, target, patient_id, sent_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['message'],
            $data['target']     ?? 'all',
            $data['patient_id'] ?? null,
            $data['sent_by']    ?? null
        ]);

        echo json_encode([
            "success" => true,
            "message" => "Notification sent successfully",
            "id"      => $pdo->lastInsertId()
        ]);
        break;

    // ── PUT mark notification as read (Android app marks it read) ──
    case 'PUT':
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['id'])) {
            echo json_encode(["success" => false, "message" => "Notification ID required"]);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$data['id']]);

        echo json_encode(["success" => true, "message" => "Marked as read"]);
        break;

    default:
        echo json_encode(["success" => false, "message" => "Method not allowed"]);
}
?>
