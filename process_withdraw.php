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

if (!isset($data['amount']) || !isset($data['description'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$amount = floatval($data['amount']);
$description = trim($data['description']);
$userId = $_SESSION['user']['id'];

// Validate amount
if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Amount must be greater than zero']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get wallet ID and balance
    $stmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ?");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        echo json_encode(['success' => false, 'message' => 'Wallet not found']);
        exit;
    }

    // Check if sufficient balance
    if ($wallet['balance'] < $amount) {
        echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
        exit;
    }

    // Update wallet balance
    $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE id = ?");
    $stmt->execute([$amount, $wallet['id']]);

    // Record transaction
    $stmt = $pdo->prepare("INSERT INTO transactions (wallet_id, type, amount, description) VALUES (?, 'withdrawal', ?, ?)");
    $stmt->execute([$wallet['id'], $amount, $description]);

    // Get new balance
    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE id = ?");
    $stmt->execute([$wallet['id']]);
    $newBalance = $stmt->fetchColumn();

    // Send email notification
    $emailData = [
        'name' => $_SESSION['user']['fullname'],
        'amount' => number_format($amount, 2),
        'new_balance' => number_format($newBalance, 2)
    ];

    $emailBody = getEmailTemplate('withdrawal', $emailData);
    sendEmail($_SESSION['user']['email'], 'Withdrawal Confirmation - VitaVista Apartments', $emailBody);

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'newBalance' => $newBalance]);
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error processing withdrawal: ' . $e->getMessage()]);
} 