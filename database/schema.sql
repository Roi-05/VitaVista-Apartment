CREATE DATABASE IF NOT EXISTS vitavista;
USE vitavista;

-- Create the users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone_number VARCHAR(20),
    role ENUM('user', 'admin') DEFAULT 'user',
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create the apartments table
CREATE TABLE IF NOT EXISTS apartments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('studio', '1-bedroom', '2-bedroom', 'penthouse') NOT NULL,
    unit VARCHAR(50) NOT NULL,
    availability TINYINT(1) DEFAULT 1, -- 1 for available, 0 for unavailable
    price_per_night DECIMAL(10, 2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create the bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    apartment_id INT NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('wallet', 'cash', 'card', 'bank_transfer' , 'counter') NOT NULL DEFAULT 'wallet',
    payment_status ENUM('paid', 'pending', 'partial') NOT NULL DEFAULT 'pending',
    status ENUM('confirmed', 'cancellation_requested', 'cancelled', 'completed') NOT NULL DEFAULT 'confirmed',
    booking_type ENUM('online', 'onsite') NOT NULL DEFAULT 'online',
    created_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert sample data into the apartments table
INSERT INTO apartments (type, unit, availability, price_per_night, description) VALUES
('studio', 'Unit 1', 1, 3500.00, 'A modern studio with a balcony.'),
('studio', 'Unit 2', 1, 3500.00, 'A modern studio with a balcony.'),
('studio', 'Unit 3', 1, 3500.00, 'A modern studio with a balcony.'),
('1-bedroom', 'Unit 1', 1, 4500.00, 'A modern 1-bedroom with a balcony.'),
('1-bedroom', 'Unit 2', 1, 4500.00, 'A modern 1-bedroom with a balcony.'),
('1-bedroom', 'Unit 3', 1, 4500.00, 'A modern 1-bedroom with a balcony.'),
('2-bedroom', 'Unit 1', 1, 12000.00, 'A luxurious 2-bedroom with city views.'),
('2-bedroom', 'Unit 2', 1, 12000.00, 'A luxurious 2-bedroom with city views.'),
('2-bedroom', 'Unit 3', 1, 12000.00, 'A luxurious 2-bedroom with city views.'),
('penthouse', 'Unit 1', 1, 29000.00, 'A luxurious penthouse with skyline views.'),
('penthouse', 'Unit 2', 1, 29000.00, 'A luxurious penthouse with skyline views.'),
('penthouse', 'Unit 3', 1, 29000.00, 'A luxurious penthouse with skyline views.');

-- Create the amenity_subscriptions table
CREATE TABLE IF NOT EXISTS amenity_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amenity_type VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('active', 'cancellation_requested', 'cancelled', 'expired') NOT NULL DEFAULT 'active',
    cancelled_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    balance DECIMAL(12,2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wallet_id INT NOT NULL,
    type ENUM('deposit', 'withdrawal', 'payment', 'refund') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(50),
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cancellation_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('booking', 'subscription') NOT NULL,
    reference_id INT NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    admin_notes TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    expiry DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (token),
    INDEX (expiry)
); 