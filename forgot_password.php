<?php
require __DIR__ . '/database/db.php';
session_start();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } else {
        try {
            // Check if email exists in database
            $stmt = $pdo->prepare("SELECT id, fullname FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Delete any existing unused tokens for this user
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ? AND used = 0");
                $stmt->execute([$user['id']]);

                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Debug information
                error_log("Generating password reset token for user: " . $email);
                error_log("Token: " . $token);
                error_log("Expiry: " . $expiry);
                
                // Store token in database
                $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expiry) VALUES (?, ?, ?)");
                $result = $stmt->execute([$user['id'], $token, $expiry]);
                
                if (!$result) {
                    error_log("Failed to store password reset token: " . print_r($stmt->errorInfo(), true));
                    throw new Exception("Failed to store password reset token");
                }
                
                // Send reset email
                require_once __DIR__ . '/includes/email_helper.php';
                
                // Use absolute URL for reset link
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $resetLink = $protocol . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;
                
                $emailData = [
                    'name' => $user['fullname'],
                    'reset_link' => $resetLink
                ];
                
                $emailBody = getEmailTemplate('password_reset', $emailData);
                sendEmail($email, 'Password Reset Request - VitaVista Apartments', $emailBody);
                
                $message = "Password reset instructions have been sent to your email.";
                $messageType = "success";
            } else {
                $message = "If an account exists with this email, you will receive password reset instructions.";
                $messageType = "info";
            }
        } catch (Exception $e) {
            error_log("Error in password reset process: " . $e->getMessage());
            $message = "An error occurred while processing your request. Please try again later.";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - VitaVista</title>
    <link rel="stylesheet" href="styles/header.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(90deg, rgb(0, 0, 77) 0%, rgb(0, 0, 110) 50%, rgb(0, 44, 147) 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .forgot-password-container {
            background: linear-gradient(145deg, #151515, #101010);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 0 35px rgba(212, 175, 55, 0.3);
            width: 100%;
            max-width: 400px;
            margin-top: 50px;
            border: 1px solid #d4af37;
        }

        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
        }

        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .message.success {
            background-color: #4CAF50;
            color: white;
        }

        .message.error {
            background-color: #f44336;
            color: white;
        }

        .message.info {
            background-color: #2196F3;
            color: white;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        input[type="email"] {
            padding: 12px;
            border: 1px solid #333;
            border-radius: 5px;
            background-color: #1e1e1e;
            color: white;
            font-size: 16px;
        }

        input[type="email"]:focus {
            border-color: #d4af37;
            outline: none;
            box-shadow: 0 0 5px rgba(212, 175, 55, 0.5);
        }

        button {
            background-color: #d4af37;
            color: black;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #b89430;
        }

        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-login a {
            color: #d4af37;
            text-decoration: none;
        }

        .back-to-login a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="logo-container">
            <a href="index.php"><img src="Pictures/logo-apt.png" alt="VitaVista Logo" class="logo-image"></a>
            <a href="index.php" class="logo">Vita<span>Vista</span></a>
        </div>
    </header>

    <div class="forgot-password-container">
        <h1>Forgot Password</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="forgot_password.php">
            <input type="email" name="email" placeholder="Enter your email address" required>
            <button type="submit">Send Reset Instructions</button>
        </form>

        <div class="back-to-login">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html> 