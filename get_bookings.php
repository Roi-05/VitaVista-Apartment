<?php
session_start();
require_once __DIR__ . '/database/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    // Fetch all bookings with user and apartment details
    $stmt = $pdo->prepare("
        SELECT 
            b.id,
            b.check_in_date,
            b.check_out_date,
            b.total_price,
            b.created_at,
            u.fullname as guest_name,
            a.type as apartment_type,
            a.unit as apartment_unit,
            CASE 
                WHEN b.check_in_date > CURDATE() THEN 'upcoming'
                WHEN b.check_in_date <= CURDATE() AND b.check_out_date >= CURDATE() THEN 'active'
                ELSE 'completed'
            END as status
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN apartments a ON b.apartment_id = a.id
        ORDER BY b.created_at DESC
    ");
    
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data for display
    $formattedBookings = array_map(function($booking) {
        return [
            'id' => $booking['id'],
            'guest' => htmlspecialchars($booking['guest_name']),
            'apartment' => htmlspecialchars($booking['apartment_type'] . ' - Unit ' . $booking['apartment_unit']),
            'check_in' => date('M d, Y', strtotime($booking['check_in_date'])),
            'check_out' => date('M d, Y', strtotime($booking['check_out_date'])),
            'amount' => '₱' . number_format($booking['total_price'], 2),
            'status' => $booking['status']
        ];
    }, $bookings);
    
    echo json_encode(['success' => true, 'bookings' => $formattedBookings]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch bookings: ' . $e->getMessage()]);
} 