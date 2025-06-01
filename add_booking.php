<?php
session_start();
require __DIR__ . '/database/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$requiredFields = ['guestName', 'guestEmail', 'guestPhone', 'apartmentType', 'apartmentUnit', 
                  'checkIn', 'checkOut', 'paymentMethod', 'paymentStatus', 'totalAmount'];

foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check if the unit is available for the selected dates
    $checkAvailability = $pdo->prepare("
        SELECT COUNT(*) 
        FROM bookings 
        WHERE apartment_id = ? 
        AND (
            (check_in_date <= ? AND check_out_date >= ?) OR
            (check_in_date <= ? AND check_out_date >= ?) OR
            (check_in_date >= ? AND check_out_date <= ?)
        )
    ");

    $checkAvailability->execute([
        $data['apartmentUnit'],
        $data['checkIn'],
        $data['checkIn'],
        $data['checkOut'],
        $data['checkOut'],
        $data['checkIn'],
        $data['checkOut']
    ]);

    if ($checkAvailability->fetchColumn() > 0) {
        throw new Exception('The selected unit is not available for the chosen dates.');
    }

    // Create a temporary user account for the guest if they don't exist
    $checkUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $checkUser->execute([$data['guestEmail']]);
    $userId = $checkUser->fetchColumn();

    if (!$userId) {
        // Generate a random password
        $tempPassword = bin2hex(random_bytes(8));
        $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

        // Insert new user
        $createUser = $pdo->prepare("
            INSERT INTO users (fullname, email, phone_number, password, role)
            VALUES (?, ?, ?, ?, 'user')
        ");
        $createUser->execute([
            $data['guestName'],
            $data['guestEmail'],
            $data['guestPhone'],
            $hashedPassword
        ]);
        $userId = $pdo->lastInsertId();
    }

    // Create the booking
    $createBooking = $pdo->prepare("
        INSERT INTO bookings (
            user_id, apartment_id, check_in_date, check_out_date,
            total_price, payment_method, payment_status, notes,
            created_by, booking_type
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'onsite')
    ");

    $createBooking->execute([
        $userId,
        $data['apartmentUnit'],
        $data['checkIn'],
        $data['checkOut'],
        $data['totalAmount'],
        $data['paymentMethod'],
        $data['paymentStatus'],
        $data['notes'] ?? null,
        $_SESSION['user']['id']
    ]);

    // Update apartment availability
    $updateApartment = $pdo->prepare("
        UPDATE apartments 
        SET availability = availability - 1 
        WHERE id = ?
    ");
    $updateApartment->execute([$data['apartmentUnit']]);

    // Commit transaction
    $pdo->commit();

    // Send confirmation email to guest
    $to = $data['guestEmail'];
    $subject = "Booking Confirmation - VitaVista Apartments";
    $message = "Dear " . $data['guestName'] . ",\n\n";
    $message .= "Your booking has been confirmed.\n\n";
    $message .= "Check-in: " . $data['checkIn'] . "\n";
    $message .= "Check-out: " . $data['checkOut'] . "\n";
    $message .= "Total Amount: ₱" . number_format($data['totalAmount'], 2) . "\n\n";
    $message .= "Thank you for choosing VitaVista Apartments!\n";
    
    $headers = "From: bookings@vitavista.com\r\n";
    $headers .= "Reply-To: bookings@vitavista.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    mail($to, $subject, $message, $headers);

    echo json_encode([
        'success' => true,
        'message' => 'Booking created successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}