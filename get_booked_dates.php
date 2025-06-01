<?php
require __DIR__ . '/database/db.php';

header('Content-Type: application/json');

if (!isset($_GET['unit_id'])) {
    echo json_encode(['error' => 'Unit ID is required']);
    exit;
}

$unitId = $_GET['unit_id'];

try {
    $query = $pdo->prepare("
        SELECT check_in_date, check_out_date 
        FROM bookings 
        WHERE apartment_id = ? 
        AND status NOT IN ('cancelled', 'completed')
        AND check_out_date >= CURDATE()
    ");
    
    $query->execute([$unitId]);
    $bookedDates = $query->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($bookedDates);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 