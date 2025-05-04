<?php
require __DIR__ . '/database/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        }
        else if ($user && password_verify($password, $user['password']) && $user['role'] == 'user') {
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
        }
        else {
            $error = "Invalid email or password.";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
      }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>VitaVista Login</title>
  <link rel="stylesheet" href="styles/login.css?v=<?php echo time(); ?>" />
  
</head>
<body>


  <div id="success-popup">Login Successful! Redirecting...</div>

  <div class="login-container">
    <h1>VitaVista</h1>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="POST" action="login.php">
      <input class="email" type="email" name="email" placeholder="Email" required>
      <input class="password" type="password" name="password" placeholder="Password" required />
      <input class="submit" type="submit" value="Login" />
    </form>

    <div class="divider">OR</div>

    <button class="google-btn">
      <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google logo" />
      Continue with Google
    </button>

    <div class="footer-text">
      Forgot your password? <br />
      <p>Don't have an account? <a href="register.php">Register</a></p>

      <div id="error-message" class="error-alert" style="display: none"></div>
    </div>
  </div>
</body>
</html>
