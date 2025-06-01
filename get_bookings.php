<?php
session_start();
require __DIR__ . '/database/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get filter parameters
$type = $_GET['type'] ?? 'all';
$filter = $_GET['filter'] ?? 'all';

try {
    // Base query
    $query = "
        SELECT b.id, b.check_in_date, b.check_out_date, b.total_price, b.payment_status, 
               b.booking_type, b.created_at, u.fullname, a.type, a.unit,
               CASE 
                   WHEN b.status IN ('cancelled', 'completed') THEN b.status
                   WHEN b.check_in_date > CURDATE() THEN 'upcoming'
                   WHEN b.check_in_date <= CURDATE() AND b.check_out_date >= CURDATE() THEN 'active'
                   ELSE 'completed'
               END as status
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN apartments a ON b.apartment_id = a.id
        WHERE 1=1
    ";
    $params = [];

    // Add type filter
    if ($type !== 'all') {
        $query .= " AND b.booking_type = ?";
        $params[] = $type;
    }

    // Add status filter
    switch ($filter) {
        case 'upcoming':
            $query .= " AND b.check_in_date > CURDATE() AND b.status NOT IN ('cancelled', 'completed')";
            break;
        case 'active':
            $query .= " AND b.check_in_date <= CURDATE() AND b.check_out_date >= CURDATE() AND b.status NOT IN ('cancelled', 'completed')";
            break;
        case 'completed':
            $query .= " AND (b.status IN ('completed', 'cancelled') OR b.check_out_date < CURDATE())";
            break;
    }

    // Order by check-in date
    $query .= " ORDER BY b.check_in_date DESC";

    // Prepare and execute query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($bookings);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch bookings: ' . $e->getMessage()]);
} 