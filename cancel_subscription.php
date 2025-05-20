<?php
session_start();
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/email_helper.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['subscription_id'])) {
    echo json_encode(['success' => false, 'message' => 'Subscription ID is required']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    error_log("Starting subscription cancellation request process for ID: " . $data['subscription_id']);

    // Get subscription details
    $stmt = $pdo->prepare("
        SELECT s.*, w.id as wallet_id, w.balance 
        FROM amenity_subscriptions s
        JOIN wallets w ON w.user_id = s.user_id
        WHERE s.id = ? AND s.user_id = ? AND s.status IN ('active', 'cancellation_requested')
    ");
    $stmt->execute([$data['subscription_id'], $_SESSION['user']['id']]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$subscription) {
        throw new Exception('Subscription not found or not in a cancellable state');
    }

    // Check if subscription already has a pending cancellation request
    if ($subscription['status'] === 'cancellation_requested') {
        throw new Exception('A cancellation request is already pending for this subscription');
    }

    // Update subscription status to cancellation_requested
    $stmt = $pdo->prepare("
        UPDATE amenity_subscriptions 
        SET status = 'cancellation_requested'
        WHERE id = ?
    ");
    $stmt->execute([$data['subscription_id']]);
    
    error_log("Updated subscription status to cancellation_requested");

    // Create cancellation request
    $stmt = $pdo->prepare("
        INSERT INTO cancellation_requests 
        (user_id, type, reference_id, reason, status, created_at) 
        VALUES (?, 'subscription', ?, ?, 'pending', NOW())
    ");
    $stmt->execute([
        $_SESSION['user']['id'],
        $data['subscription_id'],
        $data['reason'] ?? 'No reason provided'
    ]);
    
    error_log("Created cancellation request");

    // Send email notification to admin
    $emailData = [
        'name' => $_SESSION['user']['fullname'],
        'amenity_type' => ucfirst($subscription['amenity_type']),
        'subscription_id' => $data['subscription_id'],
        'reason' => $data['reason'] ?? 'No reason provided'
    ];

    $emailBody = getEmailTemplate('subscription_cancellation_request', $emailData);
    sendEmail('admin@vitavista.com', 'New Subscription Cancellation Request - VitaVista Apartments', $emailBody);
    
    error_log("Sent notification email to admin");

    // Send confirmation email to user
    $userEmailData = [
        'name' => $_SESSION['user']['fullname'],
        'amenity_type' => ucfirst($subscription['amenity_type']),
        'subscription_id' => $data['subscription_id']
    ];

    $userEmailBody = getEmailTemplate('subscription_cancellation_requested', $userEmailData);
    sendEmail($_SESSION['user']['email'], 'Subscription Cancellation Request Received - VitaVista Apartments', $userEmailBody);
    
    error_log("Sent confirmation email to user");

    // Commit transaction
    $pdo->commit();
    error_log("Transaction committed successfully");

    echo json_encode([
        'success' => true,
        'message' => 'Cancellation request submitted successfully. You will be notified once it is processed.'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    error_log("Error in subscription cancellation request: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error submitting cancellation request: ' . $e->getMessage()
    ]);
} 