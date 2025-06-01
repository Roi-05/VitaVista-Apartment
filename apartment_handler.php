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
    $type = $_POST['type'] ?? '';
    $unit = $_POST['unit'] ?? '';
    $price = $_POST['price'] ?? '';
    $description = $_POST['description'] ?? '';

    // Validate required fields
    if (empty($type) || empty($unit) || empty($price)) {
        throw new Exception('All required fields must be filled out');
    }

    // Validate apartment type
    $validTypes = ['studio', '1-bedroom', '2-bedroom', 'penthouse'];
    if (!in_array($type, $validTypes)) {
        throw new Exception('Invalid apartment type');
    }

    // Validate unit format - must be 'Unit' followed by a number
    if (!preg_match('/^Unit\s+\d+$/', $unit)) {
        throw new Exception('Unit must be in the format "Unit" followed by a number (e.g., "Unit 1")');
    }

    // Validate price
    $price = floatval($price);
    if ($price <= 0) {
        throw new Exception('Price must be greater than 0');
    }

    // Check if unit already exists for this type
    $stmt = $pdo->prepare("SELECT id FROM apartments WHERE type = ? AND unit = ?");
    $stmt->execute([$type, $unit]);
    if ($stmt->fetch()) {
        throw new Exception('This unit already exists for the selected apartment type');
    }

    // Insert new apartment with availability set to 1
    $stmt = $pdo->prepare("
        INSERT INTO apartments (type, unit, price_per_night, availability, description)
        VALUES (?, ?, ?, 1, ?)
    ");

    if (!$stmt->execute([$type, $unit, $price, $description])) {
        throw new Exception('Failed to create apartment');
    }

    // Get the newly created apartment
    $apartmentId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM apartments WHERE id = ?");
    $stmt->execute([$apartmentId]);
    $apartment = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Apartment created successfully',
        'apartment' => $apartment
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 