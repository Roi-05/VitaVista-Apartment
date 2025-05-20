<?php
session_start();
require_once 'database/db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type'] ?? '';
$id = $data['id'] ?? '';
$reason = $data['reason'] ?? '';

if (empty($type) || empty($id) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($type === 'booking') {
        // Get booking details
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user']['id']]);
        $booking = $stmt->fetch();

        if (!$booking) {
            throw new Exception('Booking not found');
        }

        // Check if booking is already cancelled or has a pending cancellation
        if ($booking['status'] === 'cancelled' || $booking['status'] === 'cancellation_requested') {
            throw new Exception('This booking is already cancelled or has a pending cancellation request');
        }

        // Insert cancellation request
        $stmt = $pdo->prepare("INSERT INTO cancellation_requests (user_id, type, reference_id, reason, status, created_at) 
                              VALUES (?, 'booking', ?, ?, 'pending', NOW())");
        $stmt->execute([$_SESSION['user']['id'], $id, $reason]);

        // Update booking status
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancellation_requested' WHERE id = ?");
        $stmt->execute([$id]);

        $message = 'Cancellation request submitted successfully. You will be notified once it is processed.';

    } elseif ($type === 'subscription') {
        // Get subscription details
        $stmt = $pdo->prepare("SELECT * FROM amenity_subscriptions WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $_SESSION['user']['id']]);
        $subscription = $stmt->fetch();

        if (!$subscription) {
            throw new Exception('Subscription not found');
        }

        // Check if subscription is already cancelled or has a pending cancellation
        if ($subscription['status'] === 'cancelled' || $subscription['status'] === 'cancellation_requested') {
            throw new Exception('This subscription is already cancelled or has a pending cancellation request');
        }

        // Insert cancellation request
        $stmt = $pdo->prepare("INSERT INTO cancellation_requests (user_id, type, reference_id, reason, status, created_at) 
                              VALUES (?, 'subscription', ?, ?, 'pending', NOW())");
        $stmt->execute([$_SESSION['user']['id'], $id, $reason]);

        // Update subscription status
        $stmt = $pdo->prepare("UPDATE amenity_subscriptions SET status = 'cancellation_requested' WHERE id = ?");
        $stmt->execute([$id]);

        $message = 'Cancellation request submitted successfully. You will be notified once it is processed.';
    } else {
        throw new Exception('Invalid cancellation type');
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 