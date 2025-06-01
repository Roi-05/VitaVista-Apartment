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

if (!isset($data['booking_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Validate status value
$validStatuses = ['cancelled', 'completed'];
if (!in_array($data['status'], $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get booking details first
    $stmt = $pdo->prepare("
        SELECT b.*, a.id as apartment_id 
        FROM bookings b 
        JOIN apartments a ON b.apartment_id = a.id 
        WHERE b.id = ? AND b.booking_type = 'onsite'
    ");
    $stmt->execute([$data['booking_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        throw new Exception('Booking not found or not an on-site booking');
    }

    // Check if booking is already in the target status
    if ($booking['status'] === $data['status']) {
        throw new Exception('Booking is already ' . $data['status']);
    }

    // Update booking status
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = ?,
            updated_at = NOW()
        WHERE id = ? AND booking_type = 'onsite'
    ");

    if (!$stmt->execute([$data['status'], $data['booking_id']])) {
        throw new Exception('Failed to update booking status');
    }

    // If the booking is being cancelled or completed, update apartment availability
    if ($data['status'] === 'cancelled' || $data['status'] === 'completed') {
        $stmt = $pdo->prepare("
            UPDATE apartments 
            SET availability = availability + 1 
            WHERE id = ?
        ");
        
        if (!$stmt->execute([$booking['apartment_id']])) {
            throw new Exception('Failed to update apartment availability');
        }
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Booking status updated successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 