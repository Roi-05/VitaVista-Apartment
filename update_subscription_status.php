<?php
session_start();
require __DIR__ . '/database/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['subscription_id']) || !isset($data['status'])) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$subscription_id = $data['subscription_id'];
$status = $data['status'];

// Validate status
$valid_statuses = ['active', 'cancelled', 'expired'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['error' => 'Invalid status']);
    exit;
}

try {
    // Update subscription status
    $stmt = $pdo->prepare("
        UPDATE amenity_subscriptions 
        SET status = ?, 
            updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    
    $stmt->execute([$status, $subscription_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Subscription not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 