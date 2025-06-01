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
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const errorPopup = document.getElementById('error-popup');
                    const popupOverlay = document.getElementById('popup-overlay');
                    const errorMessage = document.getElementById('error-message');
                    errorMessage.textContent = 'Passwords do not match.';
                    errorPopup.style.display = 'block';
                    popupOverlay.style.display = 'block';
                    setTimeout(() => {
                        errorPopup.style.display = 'none';
                        popupOverlay.style.display = 'none';
                    }, 3000);
                });
            </script>";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            try {
                $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, 'user')");
                $stmt->execute([$fullname, $email, $hashedPassword]);
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const successPopup = document.getElementById('success-popup');
                        const popupOverlay = document.getElementById('popup-overlay');
                        successPopup.querySelector('span').textContent = 'Registration Successful!';
                        successPopup.style.display = 'block';
                        popupOverlay.style.display = 'block';
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 1000);
                    });
                </script>";
            } catch (PDOException $e) {
                // Check for duplicate email error
                if ($e->getCode() == 23000) {
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const errorPopup = document.getElementById('error-popup');
                            const popupOverlay = document.getElementById('popup-overlay');
                            const errorMessage = document.getElementById('error-message');
                            errorMessage.textContent = 'This email is already registered. Please use a different email address.';
                            errorPopup.style.display = 'block';
                            popupOverlay.style.display = 'block';
                            setTimeout(() => {
                                errorPopup.style.display = 'none';
                                popupOverlay.style.display = 'none';
                            }, 3000);
                        });
                    </script>";
                } else {
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const errorPopup = document.getElementById('error-popup');
                            const popupOverlay = document.getElementById('popup-overlay');
                            const errorMessage = document.getElementById('error-message');
                            errorMessage.textContent = 'Registration failed. Please try again later.';
                            errorPopup.style.display = 'block';
                            popupOverlay.style.display = 'block';
                            setTimeout(() => {
                                errorPopup.style.display = 'none';
                                popupOverlay.style.display = 'none';
                            }, 3000);
                        });
                    </script>";
                }
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
                        const popupOverlay = document.getElementById('popup-overlay');
                        successPopup.style.display = 'block';
                        popupOverlay.style.display = 'block';
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
                        const popupOverlay = document.getElementById('popup-overlay');
                        successPopup.style.display = 'block';
                        popupOverlay.style.display = 'block';
                        setTimeout(() => {
                            window.location.href = 'index.php';
                        }, 2000);
                    });
                </script>";
            } else {
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const errorPopup = document.getElementById('error-popup');
                        const popupOverlay = document.getElementById('popup-overlay');
                        const errorMessage = document.getElementById('error-message');
                        errorMessage.textContent = 'Invalid email or password.';
                        errorPopup.style.display = 'block';
                        popupOverlay.style.display = 'block';
                        setTimeout(() => {
                            errorPopup.style.display = 'none';
                            popupOverlay.style.display = 'none';
                        }, 3000);
                    });
                </script>";
            }
        } catch (PDOException $e) {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const errorPopup = document.getElementById('error-popup');
                    const popupOverlay = document.getElementById('popup-overlay');
                    const errorMessage = document.getElementById('error-message');
                    errorMessage.textContent = 'Login failed. Please try again later.';
                    errorPopup.style.display = 'block';
                    popupOverlay.style.display = 'block';
                    setTimeout(() => {
                        errorPopup.style.display = 'none';
                        popupOverlay.style.display = 'none';
                    }, 3000);
                });
            </script>";
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
  <style>
    #error-popup {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background-color: #ff4444;
      color: white;
      padding: 20px;
      border-radius: 5px;
      z-index: 1000;
      text-align: center;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }
    #error-popup i {
      font-size: 24px;
      margin-bottom: 10px;
      display: block;
    }
    .popup-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 999;
      display: none;
    }
  </style>
  <script src="scripts/login-slider.js" defer></script>
</head>
<body>
  <div class="popup-overlay" id="popup-overlay"></div>
  <div id="success-popup" style="display: none;">
    <i class="fas fa-check-circle"></i>
    <span>Login Successful! Redirecting...</span>
  </div>
  <div id="error-popup" style="display: none;">
    <i class="fas fa-times-circle"></i>
    <span id="error-message"></span>
  </div>

  <div class="container" id="container">
    <div class="form-container sign-up-container">
      <form method="POST" action="login.php">
        <h1>Create Account</h1>
        <?php if (isset($error) && isset($_POST['register'])): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php elseif (isset($success)): ?>
            <p class="success-message"><?php echo $success; ?></p>
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
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Password" required />
        <a href="forgot_password.php">Forgot your password?</a>
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

  <script>
    // Update the login success script
    document.addEventListener('DOMContentLoaded', function() {
        const successPopup = document.getElementById('success-popup');
        const popupOverlay = document.getElementById('popup-overlay');
        
        if (successPopup.style.display === 'block') {
            popupOverlay.style.display = 'block';
            setTimeout(() => {
                successPopup.style.display = 'none';
                popupOverlay.style.display = 'none';
                <?php if (isset($_SESSION['user'])): ?>
                    window.location.href = '<?php echo $_SESSION['user']['role'] === 'admin' ? 'admin_dashboard.php' : 'index.php'; ?>';
                <?php endif; ?>
            }, 2000);
        }
    });
  </script>
</body>
</html>