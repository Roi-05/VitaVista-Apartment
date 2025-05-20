<?php
session_start();
require __DIR__ . '/database/db.php';
require __DIR__ . '/includes/email_helper.php';

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

    // Start transaction
    $pdo->beginTransaction();

    // Get apartment details
    $stmt = $pdo->prepare("SELECT type, unit FROM apartments WHERE id = ?");
    $stmt->execute([$data['apartmentId']]);
    $apartment = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check wallet balance
    $stmt = $pdo->prepare("SELECT w.id, w.balance FROM wallets w WHERE w.user_id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        throw new Exception('Wallet not found. Please deposit funds first.');
    }

    if ($wallet['balance'] < $data['totalPrice']) {
        throw new Exception('Insufficient wallet balance. Please deposit more funds.');
    }

    // Insert booking
    $stmt = $pdo->prepare("
        INSERT INTO bookings 
        (user_id, apartment_id, check_in_date, check_out_date, total_price, payment_method)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt->execute([
        $_SESSION['user']['id'],
        $data['apartmentId'],
        $data['checkIn'],
        $data['checkOut'],
        $data['totalPrice'],
        $data['paymentMethod']
    ])) {
        throw new Exception('Failed to create booking');
    }

    $bookingId = $pdo->lastInsertId();

    // Decrease apartment availability by 1
    $stmt = $pdo->prepare("UPDATE apartments SET availability = availability - 1 WHERE id = ?");
    if (!$stmt->execute([$data['apartmentId']])) {
        throw new Exception('Failed to update apartment availability');
    }

    // Deduct from wallet
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE id = ?");
    if (!$stmt->execute([$data['totalPrice'], $wallet['id']])) {
        throw new Exception('Failed to process payment');
    }

    // Record transaction
    $stmt = $pdo->prepare("
        INSERT INTO transactions 
        (wallet_id, type, amount, description) 
        VALUES (?, 'payment', ?, ?)
    ");
    $description = "Payment for booking #{$bookingId}";
    if (!$stmt->execute([$wallet['id'], $data['totalPrice'], $description])) {
        throw new Exception('Failed to record transaction');
    }

    // Get new balance
    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE id = ?");
    $stmt->execute([$wallet['id']]);
    $newBalance = $stmt->fetchColumn();

    // Send email notification
    $emailData = [
        'name' => $_SESSION['user']['fullname'],
        'apartment_type' => $apartment['type'],
        'unit' => $apartment['unit'],
        'check_in' => $data['checkIn'],
        'check_out' => $data['checkOut'],
        'total_price' => number_format($data['totalPrice'], 2)
    ];

    $emailBody = getEmailTemplate('booking', $emailData);
    sendEmail($_SESSION['user']['email'], 'Booking Confirmation - VitaVista Apartments', $emailBody);

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Booking confirmed successfully',
        'newBalance' => $newBalance
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
