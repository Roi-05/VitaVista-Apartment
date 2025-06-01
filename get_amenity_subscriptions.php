<?php
session_start();
require __DIR__ . '/database/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get filter from query parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Base query
$query = "
    SELECT 
        a.id,
        u.fullname,
        a.amenity_type,
        a.start_date,
        a.end_date,
        a.total_price,
        a.status,
        a.created_at
    FROM amenity_subscriptions a
    JOIN users u ON a.user_id = u.id
";

// Add filter conditions
switch ($filter) {
    case 'active':
        $query .= " WHERE a.status = 'active'";
        break;
    case 'cancelled':
        $query .= " WHERE a.status = 'cancelled'";
        break;
    case 'expired':
        $query .= " WHERE a.status = 'expired'";
        break;
    case 'cancellation_requested':
        $query .= " WHERE a.status = 'cancellation_requested'";
        break;
}

// Add ordering
$query .= " ORDER BY a.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($subscriptions);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 