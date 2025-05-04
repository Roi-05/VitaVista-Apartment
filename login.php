<?php
require __DIR__ . '/database/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['register'])) {
        // Registration logic
        $fullname = $_POST['fullname'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];

        if ($password !== $confirmPassword) {
            $error = "Passwords do not match.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            try {
                $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->execute([$fullname, $email, $hashedPassword]);
                $success = "Registration successful! You can now log in.";
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['login'])) {
        // Login logic
        $email = $_POST['email'];
        $password = $_POST['password'];

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password']) && $user['role'] == 'admin') {
                $_SESSION['user'] = $user;
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const successPopup = document.getElementById('success-popup');
                        successPopup.style.display = 'block';
                        setTimeout(() => {
                            window.location.href = 'admin_dashboard.php';
                        }, 2000);
                    });
                </script>";
            } elseif ($user && password_verify($password, $user['password']) && $user['role'] == 'user') {
                $_SESSION['user'] = $user;
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const successPopup = document.getElementById('success-popup');
                        successPopup.style.display = 'block';
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 2000);
                    });
                </script>";
            } else {
                $error = "Invalid email or password.";
            }
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
  <title>VitaVista</title>
  <link rel="stylesheet" href="styles/login-slider.css?v=<?php echo time(); ?>">
  <script src="scripts/login-slider.js" defer></script>
</head>
<body>
  <div id="success-popup" style="display: none;">Login Successful! Redirecting...</div>

  <div class="container" id="container">
    <div class="form-container sign-up-container">
      <form method="POST" action="login.php">
        <h1>Create Account</h1>
        <?php if (isset($error) && isset($_POST['register'])): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php elseif (isset($success)): ?>
            <p style="color: green;"><?php echo $success; ?></p>
        <?php endif; ?>
        <input type="text" name="fullname" placeholder="Full Name" required />
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Password" required />
        <input type="password" name="confirm_password" placeholder="Confirm Password" required />
        <button type="submit" name="register">Register</button>
      </form>
    </div>
    <div class="form-container sign-in-container">
      <form method="POST" action="login.php">
        <h1>Sign in</h1>
        <?php if (isset($error) && isset($_POST['login'])): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Password" required />
        <a href="#">Forgot your password?</a>
        <button type="submit" name="login">Login</button>
      </form>
    </div>
    <div class="overlay-container">
      <div class="overlay">
        <div class="overlay-panel overlay-left">
          <h1>Welcome Back!</h1>
          <p>To keep connected with us, please login with your personal info</p>
          <button class="ghost" id="signIn">Login</button>
        </div>
        <div class="overlay-panel overlay-right">
          <h1>Vita Vista</h1>
          <p>Enter your personal details and start your journey with us!</p>
          <button class="ghost" id="signUp">Register</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>