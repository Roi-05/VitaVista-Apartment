<?php
session_start();
require_once __DIR__ . '/database/db.php';

try {
    // Get all apartments with their IDs, types and units
    $stmt = $pdo->query("
        SELECT id, type, unit
        FROM apartments
        ORDER BY type, unit
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
        'message' => 'Failed to fetch apartment mapping: ' . $e->getMessage()
    ]);
} 