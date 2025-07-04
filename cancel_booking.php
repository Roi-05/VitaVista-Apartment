<?php
session_start();
require_once __DIR__ . '/database/db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit;
}

$bookingId = $data['booking_id'];
$userId = $_SESSION['user']['id'];

try {
    // First, verify that the booking belongs to the user and is upcoming
    $stmt = $pdo->prepare("
        SELECT b.*, a.id as apartment_id 
        FROM bookings b
        JOIN apartments a ON b.apartment_id = a.id
        WHERE b.id = ? AND b.user_id = ? AND b.check_in_date > NOW()
    ");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid booking or cannot be cancelled']);
        exit;
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Delete the booking
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
        if (!$stmt->execute([$bookingId, $userId])) {
            throw new Exception('Failed to cancel booking');
        }

        // Increase apartment availability
        $stmt = $pdo->prepare("UPDATE apartments SET availability = availability + 1 WHERE id = ?");
        if (!$stmt->execute([$booking['apartment_id']])) {
            throw new Exception('Failed to update apartment availability');
        }

        // Commit transaction
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error cancelling booking: ' . $e->getMessage()]);
}