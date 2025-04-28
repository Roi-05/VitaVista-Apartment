<?php
session_start();
require __DIR__ . '/database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    // Validate input
    $required = ['apartmentId', 'checkIn', 'checkOut', 'totalPrice'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Insert booking
    $stmt = $pdo->prepare("
        INSERT INTO bookings 
        (user_id, apartment_id, check_in_date, check_out_date, total_price)
        VALUES (?, ?, ?, ?, ?)
    ");

    $success = $stmt->execute([
        $_SESSION['user']['id'], // User ID from session
        $data['apartmentId'],     // Apartment ID from input
        $data['checkIn'],         // Check-in date from input
        $data['checkOut'],        // Check-out date from input
        $data['totalPrice']       // Total price from input
    ]);

    if ($success) {
        // Decrease apartment availability by 1
        $update = $pdo->prepare("UPDATE apartments SET availability = availability - 1 WHERE id = ?");
        $update->execute([$data['apartmentId']]);

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
