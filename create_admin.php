<?php
require_once __DIR__ . '/database/db.php';

// Admin account details
$adminData = [
    'fullname' => 'Admin User',
    'email' => 'admin@vitavista.com',
    'password' => password_hash('Admin@123', PASSWORD_DEFAULT), // Default password: Admin@123
    'phone_number' => '09123456789',
    'role' => 'admin'
];

try {
    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$adminData['email']]);
    $existingAdmin = $stmt->fetch();

    if ($existingAdmin) {
        echo "Admin account already exists.\n";
        exit;
    }

    // Create admin account
    $stmt = $pdo->prepare("
        INSERT INTO users (fullname, email, password, phone_number, role)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $adminData['fullname'],
        $adminData['email'],
        $adminData['password'],
        $adminData['phone_number'],
        $adminData['role']
    ]);

    // Create wallet for admin
    $adminId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO wallets (user_id) VALUES (?)");
    $stmt->execute([$adminId]);

    echo "Admin account created successfully!\n";
    echo "Email: " . $adminData['email'] . "\n";
    echo "Password: Admin@123\n";
    echo "Please change the password after first login.\n";

} catch (Exception $e) {
    echo "Error creating admin account: " . $e->getMessage() . "\n";
} 