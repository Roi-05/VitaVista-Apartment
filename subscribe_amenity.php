<?php
session_start();
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/email_helper.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['amenity_type']) || !isset($data['start_date']) || !isset($data['duration'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$userId = $_SESSION['user']['id'];
$amenityType = $data['amenity_type'];
$startDate = $data['start_date'];
$duration = (int)$data['duration'];
$totalPrice = $data['total_price'];

// Calculate end date
$endDate = date('Y-m-d', strtotime($startDate . ' + ' . $duration . ' months'));

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check wallet balance
    $stmt = $pdo->prepare("SELECT w.id, w.balance FROM wallets w WHERE w.user_id = ?");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        throw new Exception('Wallet not found. Please deposit funds first.');
    }

    if ($wallet['balance'] < $totalPrice) {
        throw new Exception('Insufficient wallet balance. Please deposit more funds.');
    }

    // Check if user already has an active subscription
    $stmt = $pdo->prepare("
        SELECT * FROM amenity_subscriptions 
        WHERE user_id = ? AND amenity_type = ? AND status = 'active'
        AND (
            (start_date <= ? AND end_date >= ?) OR
            (start_date <= ? AND end_date >= ?) OR
            (start_date >= ? AND end_date <= ?)
        )
    ");
    $stmt->execute([
        $userId, 
        $amenityType, 
        $startDate, 
        $startDate,
        $endDate, 
        $endDate,
        $startDate, 
        $endDate
    ]);
    $existingSubscription = $stmt->fetch();

    if ($existingSubscription) {
        throw new Exception('You already have an active subscription for this period');
    }

    // Insert new subscription
    $stmt = $pdo->prepare("
        INSERT INTO amenity_subscriptions 
        (user_id, amenity_type, start_date, end_date, total_price, status)
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    
    if (!$stmt->execute([$userId, $amenityType, $startDate, $endDate, $totalPrice])) {
        throw new Exception('Failed to create subscription');
    }

    // Deduct from wallet
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE id = ?");
    if (!$stmt->execute([$totalPrice, $wallet['id']])) {
        throw new Exception('Failed to process payment');
    }

    // Record transaction
    $stmt = $pdo->prepare("
        INSERT INTO transactions 
        (wallet_id, type, amount, description, payment_method) 
        VALUES (?, 'payment', ?, ?, 'wallet')
    ");
    $description = "Payment for {$amenityType} subscription ({$duration} months)";
    if (!$stmt->execute([$wallet['id'], $totalPrice, $description])) {
        throw new Exception('Failed to record transaction');
    }

    // Get new balance
    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE id = ?");
    $stmt->execute([$wallet['id']]);
    $newBalance = $stmt->fetchColumn();

    // Send email notification
    try {
        $emailData = [
            'name' => $_SESSION['user']['fullname'],
            'amenity_type' => ucfirst($amenityType),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_price' => number_format($totalPrice, 2)
        ];

        $emailBody = getEmailTemplate('subscription', $emailData);
        sendEmail($_SESSION['user']['email'], 'Subscription Confirmation - VitaVista Apartments', $emailBody);
    } catch (Exception $e) {
        // Log email error but don't fail the transaction
        error_log("Failed to send subscription confirmation email: " . $e->getMessage());
    }

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Successfully subscribed to ' . ucfirst($amenityType),
        'newBalance' => $newBalance
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error processing subscription: ' . $e->getMessage()
    ]);
} 