<?php
require __DIR__ . '/database/db.php';

header('Content-Type: application/json');

if (!isset($_GET['type'])) {
    echo json_encode(['error' => 'Apartment type is required']);
    exit;
}

$type = $_GET['type'];

try {
    $query = $pdo->prepare("
        SELECT id, unit, price_per_night, availability 
        FROM apartments 
        WHERE type = ? 
        ORDER BY unit ASC
    ");
    
    $query->execute([$type]);
    $units = $query->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($units);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 