<?php
session_start();

require_once __DIR__ . '/database/db.php';

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];


$stmt = $pdo->prepare("
SELECT b.*, a.type AS apartment_type, a.unit 
FROM bookings b 
JOIN apartments a ON b.apartment_id = a.id 
WHERE b.user_id = ?
ORDER BY b.check_in_date DESC
");
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Dashboard</title>
  <link rel="stylesheet" href="styles/profile.css?v=<?php echo time(); ?>">

</head>
<body>

  <div class="sidebar">
    <div class="sidebar-header">
      <h2>User Dashboard</h2>
    </div>
    <ul class="sidebar-nav">
      <li class="nav-item active" data-target="profile">Profile</li>
      <li class="nav-item" data-target="bookings">Bookings</li>
      <li class="nav-item" data-target="settings">Settings</li>
      <li class="nav-item" data-target="notifications">Notifications</li>
    </ul>
  </div>

  <div class="content-area">
    <div id="profile" class="content-section active">
      <div class="card">
        <div class="profile-header">
          <img src="<?php echo isset($user['profile_picture']) ? $user['profile_picture'] : 'Pictures/Default_pfp.jpg'; ?>" 
               alt="Profile Picture" class="profile-pic" id="profilePic">
          <h2><?php echo htmlspecialchars($user['fullname']); ?></h2>
          <p><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
        <div class="profile-section actions" style="text-align:center;">
          <button>Edit Profile</button>
          <button>Change Password</button>
        </div>
        <div id="lightbox" class="lightbox">
          <span class="close">&times;</span>
          <img class="lightbox-content" id="lightboxImg">
        </div>
      </div>
    </div>

    <div id="bookings" class="content-section">
      <div class="card">
        <h2>Your Bookings</h2>
        <?php
        if (empty($bookings)) {
            echo "<p>No bookings found.</p>";
        }  else {
          foreach ($bookings as $booking) {
            echo "<ul class='booking-list'>";
            echo "<li>Apartment: {$booking['apartment_type']} ({$booking['unit']})</li>";
            echo "<li>Check-in: " . date('F j, Y', strtotime($booking['check_in_date'])) . "</li>";
            echo "<li>Check-out: " . date('F j, Y', strtotime($booking['check_out_date'])) . "</li>";
            echo "<li>Total Price: ₱" . number_format($booking['total_price'], 2) . "</li>";
            echo "<li>Booked On: " . date('F j, Y g:i A', strtotime($booking['created_at'])) . "</li>";
            echo "<li><button>Cancel Booking</button></li>";
            echo "</ul>";
          }
        }
        ?>
      </div>
    </div>


    <div id="settings" class="content-section">
      <div class="card">
        <h2>Account Settings</h2>
        <form>
          <label>Change Password:</label>
          <input type="password">
          <button type="submit">Update</button>
        </form>
      </div>
    </div>

    <div id="notifications" class="content-section">
      <div class="card">
        <h2>Notifications</h2>
        <p>No new notifications</p>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const navItems = document.querySelectorAll('.nav-item');
      const contentSections = document.querySelectorAll('.content-section');

      navItems.forEach(item => {
        item.addEventListener('click', function() {
          navItems.forEach(nav => nav.classList.remove('active'));
          contentSections.forEach(content => content.classList.remove('active'));
          this.classList.add('active');
          const target = this.getAttribute('data-target');
          document.getElementById(target).classList.add('active');
        });
      });

      // Profile picture lightbox logic
      const profilePic = document.getElementById('profilePic');
      const lightbox = document.getElementById('lightbox');
      const lightboxImg = document.getElementById('lightboxImg');
      const closeBtn = document.querySelector('.close');

      profilePic.onclick = function() {
        lightbox.style.display = "flex";
        lightboxImg.src = this.src;
      };

      closeBtn.onclick = function() {
        lightbox.style.display = "none";
      };

      lightbox.onclick = function(event) {
        if (event.target === lightbox) {
          lightbox.style.display = "none";
        }
      };
    });
  </script>
</body>
</html>
