<?php
require __DIR__ . '/database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    if ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$fullname, $email, $hashedPassword]);
            $success = "Registration successful! You can now log in.";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>VitaVista Register</title>
  <link rel="stylesheet" href="styles/register.css?v=<?php echo time(); ?>"/>
</head>
<body>
  <div class="login-container">
    <h1>VitaVista</h1>
    <?php if (isset($error)): ?>
        <p style="color: red; text-align: center;"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <p style="color: green; text-align: center;"><?php echo $success; ?></p>
    <?php endif; ?>
    <form method="POST" action="register.php">
      <input class="text" type="text" name="fullname" placeholder="Full Name" required>
      <input class="email" type="email" name="email" placeholder="Email" required>
      <input class="password" type="password" name="password" placeholder="Password" required>
      <input class="password" type="password" name="confirm_password" placeholder="Confirm Password" required>
      <button type="submit" class="submit-btn">Register</button>
      <button type="button" class="back-btn" onclick="window.location.href='login.php';">Back to Login</button>
    </form>

    <div class="divider">OR</div>
    
    <button class="google-btn">
      <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google logo" />
      Register with Google
    </button>

    <div class="footer-text">
      Already have an account? <a href="login.php">Login</a>
    </div>
  </div>
</body>
</html>
