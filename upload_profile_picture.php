<?php
session_start();
require_once __DIR__ . '/database/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

function handleFileUpload($file, $target_dir = "uploads/profile_pictures/") {
    // Check if file is empty or not set
    if (!isset($file) || empty($file['tmp_name'])) {
        return ["success" => false, "message" => "No file was uploaded"];
    }
    
    // Create absolute path for upload directory
    $target_dir = __DIR__ . '/' . $target_dir;
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            return ["success" => false, "message" => "Failed to create upload directory"];
        }
    }
    
    // Check if directory is writable
    if (!is_writable($target_dir)) {
        return ["success" => false, "message" => "Upload directory is not writable"];
    }
    
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is a actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return ["success" => false, "message" => "File is not an image."];
    }
    
    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ["success" => false, "message" => "File is too large."];
    }
    
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        return ["success" => false, "message" => "Only JPG, JPEG & PNG files are allowed."];
    }
    
    // Generate unique filename
    $new_filename = uniqid() . '.' . $imageFileType;
    $target_file = $target_dir . $new_filename;
    
    // Try to move the uploaded file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        // Return relative path for database storage
        $relative_path = "uploads/profile_pictures/" . $new_filename;
        return ["success" => true, "filename" => $new_filename, "path" => $relative_path];
    } else {
        $error = error_get_last();
        return ["success" => false, "message" => "Error uploading file: " . ($error ? $error['message'] : 'Unknown error')];
    }
}

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($_FILES['profile_pic'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$upload_result = handleFileUpload($_FILES['profile_pic']);

if (!$upload_result['success']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $upload_result['message']]);
    exit;
}

try {
    // Update database with new profile picture path
    $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    if ($stmt->execute([$upload_result['path'], $_SESSION['user']['id']])) {
        $_SESSION['user']['profile_picture'] = $upload_result['path'];
        echo json_encode(['success' => true, 'url' => $upload_result['path']]);
    } else {
        // If database update fails, delete the uploaded file
        unlink(__DIR__ . '/' . $upload_result['path']);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} catch (PDOException $e) {
    // If database update fails, delete the uploaded file
    unlink(__DIR__ . '/' . $upload_result['path']);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 