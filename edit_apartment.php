<?php
session_start();
require_once __DIR__ . '/database/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get form data
    $id = $_POST['id'] ?? '';
    $price = $_POST['price'] ?? '';

    // Validate required fields
    if (empty($id) || empty($price)) {
        throw new Exception('All required fields must be filled out');
    }

    // Validate price
    $price = floatval($price);
    if ($price <= 0) {
        throw new Exception('Price must be greater than 0');
    }

    // Check if apartment exists
    $stmt = $pdo->prepare("SELECT id FROM apartments WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception('Apartment not found');
    }

    // Update apartment price
    $stmt = $pdo->prepare("UPDATE apartments SET price_per_night = ? WHERE id = ?");
    if (!$stmt->execute([$price, $id])) {
        throw new Exception('Failed to update apartment price');
    }

    // Get the updated apartment
    $stmt = $pdo->prepare("SELECT * FROM apartments WHERE id = ?");
    $stmt->execute([$id]);
    $apartment = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Apartment price updated successfully',
        'apartment' => $apartment
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 