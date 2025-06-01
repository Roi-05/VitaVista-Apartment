<?php
session_start();
require __DIR__ . '/database/db.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Update booking payment status
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET payment_status = 'paid',
            updated_at = NOW()
        WHERE id = ? AND payment_status = 'pending'
    ");

    if (!$stmt->execute([$data['booking_id']])) {
        throw new Exception('Failed to update booking status');
    }

    if ($stmt->rowCount() === 0) {
        throw new Exception('Booking not found or already paid');
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Booking marked as paid successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 