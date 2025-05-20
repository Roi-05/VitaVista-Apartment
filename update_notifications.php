<?php
session_start();
require_once __DIR__ . '/database/db.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['email_notifications']) || !isset($data['sms_notifications']) || !isset($data['promotional_emails'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing preferences']);
    exit;
}

$userId = $_SESSION['user']['id'];

try {
    $stmt = $pdo->prepare("
        UPDATE user_preferences 
        SET 
            email_notifications = ?,
            sms_notifications = ?,
            promotional_emails = ?
        WHERE user_id = ?
    ");

    $stmt->execute([
        $data['email_notifications'] ? 1 : 0,
        $data['sms_notifications'] ? 1 : 0,
        $data['promotional_emails'] ? 1 : 0,
        $userId
    ]);

    // If no row was updated, insert new preferences
    if ($stmt->rowCount() === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO user_preferences 
            (user_id, email_notifications, sms_notifications, promotional_emails)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $data['email_notifications'] ? 1 : 0,
            $data['sms_notifications'] ? 1 : 0,
            $data['promotional_emails'] ? 1 : 0
        ]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating preferences']);
} 