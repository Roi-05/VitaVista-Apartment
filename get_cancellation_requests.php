<?php
session_start();
require_once __DIR__ . '/database/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$filter = $_GET['filter'] ?? 'all';

try {
    $query = "
        SELECT cr.*, u.fullname
        FROM cancellation_requests cr
        JOIN users u ON cr.user_id = u.id
        WHERE 1=1
    ";

    if ($filter !== 'all') {
        $query .= " AND cr.status = ?";
    }

    $query .= " ORDER BY cr.created_at DESC";

    $stmt = $pdo->prepare($query);
    
    if ($filter !== 'all') {
        $stmt->execute([$filter]);
    } else {
        $stmt->execute();
    }

    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($requests);

} catch (Exception $e) {
    error_log("Error fetching cancellation requests: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error fetching cancellation requests']);
} 