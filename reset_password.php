<?php
require __DIR__ . '/database/db.php';
session_start();

$message = '';
$messageType = '';
$validToken = false;
$token = $_GET['token'] ?? '';

if ($token) {
    try {
        // Debug information
        error_log("Attempting to validate token: " . $token);
        
        // First check if token exists
        $stmt = $pdo->prepare("SELECT pr.*, u.email, u.fullname 
            FROM password_resets pr 
            JOIN users u ON pr.user_id = u.id 
            WHERE pr.token = ?");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch();
        
        if (!$tokenData) {
            error_log("Token not found in database");
            $message = "Invalid reset link. Please request a new password reset.";
            $messageType = "error";
        } else {
            error_log("Token found in database. Checking conditions:");
            error_log("Expiry: " . $tokenData['expiry']);
            error_log("Used: " . $tokenData['used']);
            
            // Check if token is expired
            if (strtotime($tokenData['expiry']) < time()) {
                error_log("Token has expired");
                $message = "Reset link has expired. Please request a new password reset.";
                $messageType = "error";
            }
            // Check if token has been used
            elseif ($tokenData['used'] == 1) {
                error_log("Token has already been used");
                $message = "This reset link has already been used. Please request a new password reset.";
                $messageType = "error";
            }
            // Token is valid
            else {
                error_log("Token is valid for user: " . $tokenData['email']);
                $validToken = true;
                $reset = $tokenData;
            }
        }
    } catch (Exception $e) {
        error_log("Error validating token: " . $e->getMessage());
        $message = "An error occurred while validating your reset link. Please try again later.";
        $messageType = "error";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    try {
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        if ($password !== $confirmPassword) {
            $message = "Passwords do not match.";
            $messageType = "error";
        } elseif (strlen($password) < 8) {
            $message = "Password must be at least 8 characters long.";
            $messageType = "error";
        } else {
            // Update password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $reset['user_id']]);

            // Mark token as used
            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);

            // Send confirmation email
            require_once __DIR__ . '/includes/email_helper.php';
            $emailData = [
                'name' => $reset['fullname']
            ];
            $emailBody = getEmailTemplate('password_reset_success', $emailData);
            sendEmail($reset['email'], 'Password Reset Successful - VitaVista Apartments', $emailBody);

            $message = "Your password has been reset successfully. You can now login with your new password.";
            $messageType = "success";
            $validToken = false; // Prevent form from showing again
        }
    } catch (Exception $e) {
        error_log("Error resetting password: " . $e->getMessage());
        $message = "An error occurred while resetting your password. Please try again later.";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - VitaVista</title>
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

        .reset-password-container {
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

        form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        input[type="password"] {
            padding: 12px;
            border: 1px solid #333;
            border-radius: 5px;
            background-color: #1e1e1e;
            color: white;
            font-size: 16px;
        }

        input[type="password"]:focus {
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

    <div class="reset-password-container">
        <h1>Reset Password</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($validToken): ?>
            <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>">
                <input type="password" name="password" placeholder="New Password" required minlength="8">
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="8">
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="back-to-login">
            <a href="login.php">Back to Login</a>
        </div>
    </div>
</body>
</html> 