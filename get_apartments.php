<?php
session_start();
require_once __DIR__ . '/database/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get all apartments with their booking count
    $stmt = $pdo->query("
        SELECT a.*, COUNT(b.id) as total_bookings
        FROM apartments a
        LEFT JOIN bookings b ON a.id = b.apartment_id
        GROUP BY a.id
        ORDER BY a.type, a.unit
    ");
    
    $apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'apartments' => $apartments
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch apartments: ' . $e->getMessage()
    ]);
} 