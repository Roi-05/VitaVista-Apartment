<?php
require_once __DIR__ . '/database/db.php';

function isApartmentAvailable($pdo, $apartmentId, $checkInDate = null, $checkOutDate = null) {
    // If no dates provided, check current availability
    if ($checkInDate === null) {
        $checkInDate = date('Y-m-d');
    }
    if ($checkOutDate === null) {
        $checkOutDate = date('Y-m-d');
    }

    // Check for any active bookings that overlap with the given dates
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM bookings b
        WHERE b.apartment_id = ?
        AND b.status NOT IN ('cancelled', 'completed')
        AND (
            (b.check_in_date <= ? AND b.check_out_date >= ?) OR  -- Booking overlaps start date
            (b.check_in_date <= ? AND b.check_out_date >= ?) OR  -- Booking overlaps end date
            (b.check_in_date >= ? AND b.check_out_date <= ?)     -- Booking is within the period
        )
    ");
    
    $stmt->execute([
        $apartmentId,
        $checkOutDate, $checkInDate,  // For start date overlap
        $checkOutDate, $checkInDate,  // For end date overlap
        $checkInDate, $checkOutDate   // For period containment
    ]);

    return $stmt->fetchColumn() === 0;
}

function getAvailableApartments($pdo, $type = null, $checkInDate = null, $checkOutDate = null) {
    // If no dates provided, check current availability
    if ($checkInDate === null) {
        $checkInDate = date('Y-m-d');
    }
    if ($checkOutDate === null) {
        $checkOutDate = date('Y-m-d');
    }

    $params = [];
    $typeCondition = "";
    if ($type !== null) {
        $typeCondition = "AND a.type = ?";
        $params[] = $type;
    }

    // Get all apartments of the specified type with their availability status and total bookings
    $stmt = $pdo->prepare("
        SELECT a.*, 
               CASE WHEN EXISTS (
                   SELECT 1 
                   FROM bookings b
                   WHERE b.apartment_id = a.id
                   AND b.status NOT IN ('cancelled', 'completed')
                   AND (
                       (b.check_in_date <= ? AND b.check_out_date >= ?) OR
                       (b.check_in_date <= ? AND b.check_out_date >= ?) OR
                       (b.check_in_date >= ? AND b.check_out_date <= ?)
                   )
               ) THEN 0 ELSE 1 END as is_available,
               (SELECT COUNT(*) FROM bookings WHERE apartment_id = a.id) as total_bookings
        FROM apartments a
        WHERE 1=1 $typeCondition
        ORDER BY a.type, a.unit
    ");

    // Add date parameters
    $params = array_merge($params, [
        $checkOutDate, $checkInDate,  // For start date overlap
        $checkOutDate, $checkInDate,  // For end date overlap
        $checkInDate, $checkOutDate   // For period containment
    ]);

    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
} 