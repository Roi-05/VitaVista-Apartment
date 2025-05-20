<?php
session_start();
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/email_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['amount']) || !isset($data['description']) || !isset($data['payment_method'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$amount = floatval($data['amount']);
$description = trim($data['description']);
$paymentMethod = trim($data['payment_method']);
$userId = $_SESSION['user']['id'];

// Validate amount
if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Amount must be greater than zero']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get wallet ID
    $stmt = $pdo->prepare("SELECT id FROM wallets WHERE user_id = ?");
    $stmt->execute([$userId]);
    $walletId = $stmt->fetchColumn();

    if (!$walletId) {
        // Create wallet if it doesn't exist
        $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0)");
        $stmt->execute([$userId]);
        $walletId = $pdo->lastInsertId();
    }

    // Update wallet balance
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$amount, $walletId]);

    // Record transaction
    $stmt = $pdo->prepare("INSERT INTO transactions (wallet_id, type, amount, description, payment_method) VALUES (?, 'deposit', ?, ?, ?)");
    $stmt->execute([$walletId, $amount, $description, $paymentMethod]);

    // Get new balance
    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE id = ?");
    $stmt->execute([$walletId]);
    $newBalance = $stmt->fetchColumn();

    // Send email notification
    $emailData = [
        'name' => $_SESSION['user']['fullname'],
        'amount' => number_format($amount, 2),
        'payment_method' => ucfirst($paymentMethod),
        'new_balance' => number_format($newBalance, 2)
    ];

    $emailBody = getEmailTemplate('deposit', $emailData);
    sendEmail($_SESSION['user']['email'], 'Deposit Confirmation - VitaVista Apartments', $emailBody);

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'newBalance' => $newBalance]);
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error processing deposit: ' . $e->getMessage()]);
} 