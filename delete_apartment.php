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
    // Get apartment ID from request
    $data = json_decode(file_get_contents('php://input'), true);
    $apartmentId = $data['id'] ?? null;

    if (!$apartmentId) {
        throw new Exception('Apartment ID is required');
    }

    // Check if apartment exists
    $stmt = $pdo->prepare("SELECT id FROM apartments WHERE id = ?");
    $stmt->execute([$apartmentId]);
    if (!$stmt->fetch()) {
        throw new Exception('Apartment not found');
    }

    // Check if apartment has any bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE apartment_id = ?");
    $stmt->execute([$apartmentId]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Cannot delete apartment with existing bookings');
    }

    // Delete the apartment
    $stmt = $pdo->prepare("DELETE FROM apartments WHERE id = ?");
    if (!$stmt->execute([$apartmentId])) {
        throw new Exception('Failed to delete apartment');
    }

    echo json_encode([
        'success' => true,
        'message' => 'Apartment deleted successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 