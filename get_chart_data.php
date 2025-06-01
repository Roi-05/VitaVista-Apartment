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
    // Get revenue data for the last 6 months
    $revenueQuery = "
        SELECT 
            DATE_FORMAT(check_in_date, '%Y-%m') as month,
            SUM(total_price) as revenue
        FROM bookings 
        WHERE payment_status = 'paid'
        AND check_in_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(check_in_date, '%Y-%m')
        ORDER BY month ASC
    ";
    $revenueData = $pdo->query($revenueQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Get booking statistics by apartment type
    $bookingStatsQuery = "
        SELECT 
            a.type,
            COUNT(b.id) as booking_count
        FROM apartments a
        LEFT JOIN bookings b ON a.id = b.apartment_id
        WHERE b.status NOT IN ('cancelled')
        GROUP BY a.type
        ORDER BY booking_count DESC
    ";
    $bookingStats = $pdo->query($bookingStatsQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Get popular apartments data
    $popularApartmentsQuery = "
        SELECT 
            a.type,
            COUNT(b.id) as booking_count
        FROM apartments a
        LEFT JOIN bookings b ON a.id = b.apartment_id
        WHERE b.status NOT IN ('cancelled')
        GROUP BY a.type
        ORDER BY booking_count DESC
    ";
    $popularApartments = $pdo->query($popularApartmentsQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Get user growth data for the last 6 months
    $userGrowthQuery = "
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as new_users
        FROM users 
        WHERE role = 'user'
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ";
    $userGrowth = $pdo->query($userGrowthQuery)->fetchAll(PDO::FETCH_ASSOC);

    // Format the data for the charts
    $response = [
        'success' => true,
        'revenue' => [
            'labels' => array_column($revenueData, 'month'),
            'data' => array_column($revenueData, 'revenue')
        ],
        'bookingStats' => [
            'labels' => array_column($bookingStats, 'type'),
            'data' => array_column($bookingStats, 'booking_count')
        ],
        'popularApartments' => [
            'labels' => array_column($popularApartments, 'type'),
            'data' => array_column($popularApartments, 'booking_count')
        ],
        'userGrowth' => [
            'labels' => array_column($userGrowth, 'month'),
            'data' => array_column($userGrowth, 'new_users')
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch chart data: ' . $e->getMessage()
    ]);
} 