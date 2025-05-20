<?php
session_start();
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/email_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['request_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get cancellation request details
    $stmt = $pdo->prepare("
        SELECT cr.*, u.email, u.fullname, s.amenity_type, s.start_date, s.end_date, s.total_price, w.id as wallet_id
        FROM cancellation_requests cr
        JOIN users u ON cr.user_id = u.id
        JOIN amenity_subscriptions s ON cr.reference_id = s.id
        JOIN wallets w ON w.user_id = u.id
        WHERE cr.id = ? AND cr.status = 'pending'
    ");
    $stmt->execute([$data['request_id']]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        throw new Exception('Cancellation request not found or already processed');
    }

    if ($data['action'] === 'approved') {
        // Calculate refund amount (prorated)
        $startDate = new DateTime($request['start_date']);
        $endDate = new DateTime($request['end_date']);
        $today = new DateTime();
        
        $totalDays = $startDate->diff($endDate)->days;
        $remainingDays = $today->diff($endDate)->days;
        
        $refundAmount = ($request['total_price'] / $totalDays) * $remainingDays;
        $refundAmount = round($refundAmount, 2);

        // Update subscription status
        $stmt = $pdo->prepare("
            UPDATE amenity_subscriptions 
            SET status = 'cancelled', 
                cancelled_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$request['reference_id']]);

        // Add refund to wallet
        $stmt = $pdo->prepare("
            UPDATE wallets 
            SET balance = balance + ? 
            WHERE id = ?
        ");
        $stmt->execute([$refundAmount, $request['wallet_id']]);

        // Record refund transaction
        $stmt = $pdo->prepare("
            INSERT INTO transactions (wallet_id, type, amount, description, payment_method) 
            VALUES (?, 'refund', ?, ?, 'refund')
        ");
        $stmt->execute([
            $request['wallet_id'],
            $refundAmount,
            "Refund for cancelled {$request['amenity_type']} subscription"
        ]);

        // Get new wallet balance
        $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE id = ?");
        $stmt->execute([$request['wallet_id']]);
        $newBalance = $stmt->fetchColumn();

        // Send approval email to user
        $emailData = [
            'name' => $request['fullname'],
            'amenity_type' => ucfirst($request['amenity_type']),
            'refund_amount' => number_format($refundAmount, 2),
            'new_balance' => number_format($newBalance, 2)
        ];
        $emailBody = getEmailTemplate('subscription_cancellation_approved', $emailData);
        sendEmail($request['email'], 'Subscription Cancellation Approved - VitaVista Apartments', $emailBody);
    }

    // Update cancellation request status
    $stmt = $pdo->prepare("
        UPDATE cancellation_requests 
        SET status = ?, 
            admin_notes = ?,
            updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([
        $data['action'],
        $data['action'] === 'approved' ? 'Cancellation approved and refund processed' : 'Cancellation request rejected',
        $data['request_id']
    ]);

    // Send rejection email if rejected
    if ($data['action'] === 'rejected') {
        $emailData = [
            'name' => $request['fullname'],
            'amenity_type' => ucfirst($request['amenity_type'])
        ];
        $emailBody = getEmailTemplate('subscription_cancellation_rejected', $emailData);
        sendEmail($request['email'], 'Subscription Cancellation Request Rejected - VitaVista Apartments', $emailBody);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Cancellation request ' . $data['action'] . ' successfully'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error handling cancellation request: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error processing cancellation request: ' . $e->getMessage()
    ]);
} 