<?php
session_start();

require_once __DIR__ . '/database/db.php';

if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}


$user = $_SESSION['user'];

// Get user's bookings
$stmt = $pdo->prepare("
    SELECT b.*, a.type AS apartment_type, a.unit, a.price_per_night
    FROM bookings b 
    JOIN apartments a ON b.apartment_id = a.id 
    WHERE b.user_id = ?
    ORDER BY b.check_in_date DESC
");
$stmt->execute([$user['id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total spent
$totalSpent = array_sum(array_column($bookings, 'total_price'));

// Get upcoming bookings
$upcomingBookings = array_filter($bookings, function($booking) {
    return strtotime($booking['check_in_date']) > time();
});

// Get past bookings
$pastBookings = array_filter($bookings, function($booking) {
    return strtotime($booking['check_out_date']) < time();
});

// Get active bookings
$activeBookings = array_filter($bookings, function($booking) {
    $now = time();
    return strtotime($booking['check_in_date']) <= $now && strtotime($booking['check_out_date']) >= $now;
});

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ?, phone_number = ? WHERE id = ?");
    $stmt->execute([$fullname, $email, $phone, $user['id']]);
    
    $_SESSION['user']['fullname'] = $fullname;
    $_SESSION['user']['email'] = $email;
    $_SESSION['user']['phone_number'] = $phone;
    
    $updateSuccess = true;
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (password_verify($currentPassword, $user['password'])) {
        if ($newPassword === $confirmPassword) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);
            $passwordSuccess = true;
        } else {
            $passwordError = "New passwords do not match.";
        }
    } else {
        $passwordError = "Current password is incorrect.";
    }
}

// Fetch wallet balance
$stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
$stmt->execute([$user['id']]);
$walletBalance = $stmt->fetchColumn() ?: 0;

// Fetch recent transactions
$stmt = $pdo->prepare("
    SELECT * FROM transactions 
    WHERE wallet_id = (SELECT id FROM wallets WHERE user_id = ?) 
    ORDER BY created_at DESC LIMIT 10
");
$stmt->execute([$user['id']]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user's active subscriptions
$stmt = $pdo->prepare("
    SELECT id, amenity_type, start_date, end_date, total_price, status 
    FROM amenity_subscriptions 
    WHERE user_id = ? AND status IN ('active', 'cancellation_requested')
");
$stmt->execute([$user['id']]);
$activeSubscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$activeSubscriptionTypes = array_column($activeSubscriptions, 'amenity_type');
$activeSubscriptionIds = array_column($activeSubscriptions, 'id');
$activeSubscriptionMap = array_combine($activeSubscriptionTypes, $activeSubscriptionIds);
$subscriptionStatusMap = array_combine($activeSubscriptionTypes, array_column($activeSubscriptions, 'status'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Dashboard</title>
  <link rel="stylesheet" href="styles/profile.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="styles/profile-dashboard.css?">
  <link rel="stylesheet" href="styles/amenities.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .back-button {
      margin-bottom: 20px;
    }

    .back-button button {
      background-color: var(--primary-color);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 14px;
      transition: background-color 0.3s ease;
    }

    .back-button button:hover {
      background-color: var(--primary-color-dark);
    }

    .back-button button i {
      font-size: 16px;
    }

    .loading-spinner {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 50px;
      height: 50px;
      border: 5px solid var(--secondary-color);
      border-top: 5px solid var(--primary-color);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      z-index: 9999;
    }

    @keyframes spin {
      0% { transform: translate(-50%, -50%) rotate(0deg); }
      100% { transform: translate(-50%, -50%) rotate(360deg); }
    }

    /* New notification styles */
    .notification {
      display: none;
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background-color: white;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      z-index: 9999;
      text-align: center;
      min-width: 300px;
      animation: fadeIn 0.3s ease;
    }

    .notification.success {
      border-left: 4px solid #28a745;
    }

    .notification.error {
      border-left: 4px solid #dc3545;
    }

    .notification i {
      font-size: 24px;
      margin-bottom: 10px;
    }

    .notification.success i {
      color: #28a745;
    }

    .notification.error i {
      color: #dc3545;
    }

    .notification p {
      margin: 10px 0;
      font-size: 16px;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translate(-50%, -60%); }
      to { opacity: 1; transform: translate(-50%, -50%); }
    }

    @keyframes fadeOut {
      from { opacity: 1; transform: translate(-50%, -50%); }
      to { opacity: 0; transform: translate(-50%, -60%); }
    }

    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
    }

    .modal.active {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background-color: white;
      padding: 20px;
      border-radius: 8px;
      width: 90%;
      max-width: 500px;
      position: relative;
    }

    .modal .close {
      position: absolute;
      right: 20px;
      top: 15px;
      font-size: 24px;
      cursor: pointer;
    }

    #cancellation-reason {
      width: 100%;
      min-height: 100px;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-top: 5px;
      resize: vertical;
    }

    .submit-btn {
      background-color: #dc3545;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 4px;
      cursor: pointer;
      width: 100%;
      margin-top: 15px;
    }

    .submit-btn:hover {
      background-color: #c82333;
    }

    .status-badge.pending-cancellation {
      background-color: #ffc107;
      color: #000;
    }
    
    .status-badge.pending-cancellation i {
      color: #000;
    }
  </style>
</head>
<body>
  <div class="loading-spinner" id="loading-spinner"></div>
  <div class="notification" id="notification">
    <i class="fas"></i>
    <p></p>
  </div>
  <div class="theme-toggle">
    <button id="theme-toggle-btn">
      <i class="fas fa-moon"></i>
    </button>
  </div>
  <div class="sidebar">
    <div class="sidebar-header">
      <div class="profile-pic-container">
        <img src="<?php echo isset($user['profile_picture']) ? $user['profile_picture'] : 'Pictures/Default_pfp.jpg'; ?>" 
             alt="Profile Picture" class="profile-pic" id="profilePic">
        <div class="profile-pic-overlay">
          <i class="fas fa-camera"></i>
          <span>Change Photo</span>
        </div>
      </div>
      <h2><?php echo htmlspecialchars($user['fullname']); ?></h2>
      <p><?php echo htmlspecialchars($user['email']); ?></p>
    </div>
    <ul class="sidebar-nav">
      <li class="nav-item active" data-target="dashboard">
        <i class="fas fa-home"></i> Dashboard
      </li>
      <li class="nav-item" data-target="bookings">
        <i class="fas fa-calendar-check"></i> My Bookings
      </li>
      <li class="nav-item" data-target="profile">
        <i class="fas fa-user"></i> Profile Settings
      </li>
      <li class="nav-item" data-target="security">
        <i class="fas fa-lock"></i> Security
      </li>
      <li class="nav-item" data-target="amenities">
        <i class="fas fa-dumbbell"></i> Amenities & Services
      </li>
      <li class="nav-item" data-target="wallet">
        <i class="fas fa-wallet"></i> Wallet
      </li>
      <li class="nav-item" onclick="if(confirm('Are you sure you want to logout?')) window.location.href='logout.php'">
        <i class="fas fa-sign-out-alt"></i> Logout
      </li>
    </ul>
  </div>

  <div class="content-area">
    <div class="back-button">
      <button onclick="window.location.href='index.php'">
        <i class="fas fa-arrow-left"></i> Back to Home
      </button>
    </div>
    <!-- Dashboard Section -->
    <div id="dashboard" class="content-section active">
      <div class="welcome-banner">
        <h1>Welcome back, <?php echo htmlspecialchars($user['fullname']); ?>!</h1>
        <p><i class="fas fa-chart-line"></i> Here's your booking summary</p>
      </div>
      
      <div class="stats-grid">
        <div class="stat-card">
          <i class="fas fa-calendar-check"></i>
          <h3>Total Bookings</h3>
          <p><?php echo count($bookings); ?></p>
        </div>
        <div class="stat-card">
          <i class="fas fa-clock"></i>
          <h3>Upcoming Bookings</h3>
          <p><?php echo count($upcomingBookings); ?></p>
        </div>
        <div class="stat-card">
          <i class="fas fa-check-circle"></i>
          <h3>Active Bookings</h3>
          <p><?php echo count($activeBookings); ?></p>
        </div>
        <div class="stat-card">
          <i class="fas fa-peso-sign"></i>
          <h3>Total Spent</h3>
          <p>₱<?php echo number_format($totalSpent, 2); ?></p>
        </div>
      </div>

      <?php if (!empty($activeBookings)): ?>
      <div class="section-card">
        <h2><i class="fas fa-star"></i> Active Bookings</h2>
        <div class="active-bookings">
          <?php foreach ($activeBookings as $booking): ?>
          <div class="booking-card active">
            <div class="booking-header">
              <h3><?php echo htmlspecialchars($booking['apartment_type']); ?> - Unit <?php echo htmlspecialchars($booking['unit']); ?></h3>
              <span class="status active"><i class="fas fa-check-circle"></i> Active</span>
            </div>
            <div class="booking-details">
              <p><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?> - <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></p>
              <p><i class="fas fa-peso-sign"></i> ₱<?php echo number_format($booking['total_price'], 2); ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Bookings Section -->
    <div id="bookings" class="content-section">
      <div class="section-card">
        <div class="section-header">
          <h2><i class="fas fa-calendar-alt"></i> My Bookings</h2>
          <div class="booking-filters">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="upcoming">Upcoming</button>
            <button class="filter-btn" data-filter="active">Active</button>
            <button class="filter-btn" data-filter="past">Past</button>
          </div>
        </div>

        <div class="bookings-grid">
          <?php foreach ($bookings as $booking): ?>
          <?php
            $status = 'past';
            $now = time();
            $checkIn = strtotime($booking['check_in_date']);
            $checkOut = strtotime($booking['check_out_date']);
            
            if ($checkIn > $now) {
              $status = 'upcoming';
            } elseif ($checkIn <= $now && $checkOut >= $now) {
              $status = 'active';
            }

            $statusIcon = [
              'upcoming' => '<i class="fas fa-clock"></i>',
              'active' => '<i class="fas fa-check-circle"></i>',
              'past' => '<i class="fas fa-history"></i>'
            ][$status];
          ?>
          <div class="booking-card <?php echo $status; ?>" data-status="<?php echo $status; ?>">
            <div class="booking-header">
              <h3><?php echo htmlspecialchars($booking['apartment_type']); ?> - Unit <?php echo htmlspecialchars($booking['unit']); ?></h3>
              <span class="status <?php echo $status; ?>"><?php echo $statusIcon; ?> <?php echo ucfirst($status); ?></span>
            </div>
            <div class="booking-details">
              <p><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?> - <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></p>
              <p><i class="fas fa-peso-sign"></i> ₱<?php echo number_format($booking['total_price'], 2); ?></p>
              <?php if ($status === 'upcoming'): ?>
              <button class="cancel-btn" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                <i class="fas fa-times"></i> Cancel Booking
              </button>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Profile Settings Section -->
    <div id="profile" class="content-section">
      <div class="section-card">
        <h2>Profile Settings</h2>
        <?php if (isset($updateSuccess)): ?>
        <div class="alert success">Profile updated successfully!</div>
        <?php endif; ?>
        <form method="POST" class="settings-form" id="profile-settings-form">
          <div class="form-group">
            <label for="fullname">Full Name</label>
            <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
          </div>
          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" required>
          </div>
          <button type="submit" name="update_profile">Save Changes</button>
        </form>
      </div>
    </div>

    <!-- Security Section -->
    <div id="security" class="content-section">
      <div class="section-card">
        <h2>Security Settings</h2>
        <?php if (isset($passwordSuccess)): ?>
        <div class="alert success">Password changed successfully!</div>
        <?php endif; ?>
        <?php if (isset($passwordError)): ?>
        <div class="alert error"><?php echo $passwordError; ?></div>
        <?php endif; ?>
        <form method="POST" class="settings-form">
          <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required>
          </div>
          <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
          </div>
          <button type="submit" name="change_password">Change Password</button>
        </form>
      </div>
    </div>

    <!-- Amenities Section -->
    <div id="amenities" class="content-section">
      <div class="section-card">
        <h2><i class="fas fa-dumbbell"></i> Amenities & Services</h2>
        <div class="amenities-grid">
          <div class="amenity-card">
            <div class="amenity-image">
              <img src="Pictures/gym.jpg" alt="Gym">
            </div>
            <div class="amenity-content">
              <i class="fas fa-dumbbell"></i>
              <h3>Gym Membership</h3>
              <p>Access to our fully equipped fitness center with state-of-the-art equipment and personal training options.</p>
              <div class="amenity-price">
                <span class="price">₱2,500</span> / month
              </div>
              <div class="amenity-status">
                <?php if (in_array('gym', $activeSubscriptionTypes)): ?>
                  <?php if ($subscriptionStatusMap['gym'] === 'cancellation_requested'): ?>
                    <span class="status-badge pending-cancellation"><i class="fas fa-clock"></i> Cancellation Pending</span>
                  <?php else: ?>
                    <span class="status-badge subscribed"><i class="fas fa-check-circle"></i> Subscribed</span>
                    <button class="cancel-subscription-btn" onclick="cancelSubscription(<?php echo $activeSubscriptionMap['gym']; ?>, 'Gym Membership')">
                      Cancel Subscription
                    </button>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="status-badge"><i class="fas fa-clock"></i> Available</span>
                  <button class="subscribe-btn" onclick="showSubscriptionModal('gym', 2500, 'Gym Membership')">Subscribe</button>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="amenity-card">
            <div class="amenity-image">
              <img src="Pictures/pool.webp" alt="Swimming Pool">
            </div>
            <div class="amenity-content">
              <i class="fas fa-swimming-pool"></i>
              <h3>Swimming Pool</h3>
              <p>Enjoy our rooftop infinity pool with stunning city views, perfect for relaxation and exercise.</p>
              <div class="amenity-price">
                <span class="price">₱1,800</span> / month
              </div>
              <div class="amenity-status">
                <?php if (in_array('pool', $activeSubscriptionTypes)): ?>
                  <?php if ($subscriptionStatusMap['pool'] === 'cancellation_requested'): ?>
                    <span class="status-badge pending-cancellation"><i class="fas fa-clock"></i> Cancellation Pending</span>
                  <?php else: ?>
                    <span class="status-badge subscribed"><i class="fas fa-check-circle"></i> Subscribed</span>
                    <button class="cancel-subscription-btn" onclick="cancelSubscription(<?php echo $activeSubscriptionMap['pool']; ?>, 'Swimming Pool')">
                      Cancel Subscription
                    </button>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="status-badge"><i class="fas fa-clock"></i> Available</span>
                  <button class="subscribe-btn" onclick="showSubscriptionModal('pool', 1800, 'Swimming Pool')">Subscribe</button>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="amenity-card">
            <div class="amenity-image">
              <img src="Pictures/meal.avif" alt="Meal Service">
            </div>
            <div class="amenity-content">
              <i class="fas fa-utensils"></i>
              <h3>Meal Service</h3>
              <p>Daily gourmet meal delivery service with customizable menu options and dietary preferences.</p>
              <div class="amenity-price">
                <span class="price">₱3,500</span> / month
              </div>
              <div class="amenity-status">
                <?php if (in_array('meal', $activeSubscriptionTypes)): ?>
                  <?php if ($subscriptionStatusMap['meal'] === 'cancellation_requested'): ?>
                    <span class="status-badge pending-cancellation"><i class="fas fa-clock"></i> Cancellation Pending</span>
                  <?php else: ?>
                    <span class="status-badge subscribed"><i class="fas fa-check-circle"></i> Subscribed</span>
                    <button class="cancel-subscription-btn" onclick="cancelSubscription(<?php echo $activeSubscriptionMap['meal']; ?>, 'Meal Service')">
                      Cancel Subscription
                    </button>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="status-badge"><i class="fas fa-clock"></i> Available</span>
                  <button class="subscribe-btn" onclick="showSubscriptionModal('meal', 3500, 'Meal Service')">Subscribe</button>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="amenity-card">
            <div class="amenity-image">
              <img src="Pictures/spa.avif" alt="Spa & Wellness">
            </div>
            <div class="amenity-content">
              <i class="fas fa-spa"></i>
              <h3>Spa & Wellness</h3>
              <p>Access to our luxury spa facilities including sauna, massage services, and wellness programs.</p>
              <div class="amenity-price">
                <span class="price">₱4,000</span> / month
              </div>
              <div class="amenity-status">
                <?php if (in_array('spa', $activeSubscriptionTypes)): ?>
                  <?php if ($subscriptionStatusMap['spa'] === 'cancellation_requested'): ?>
                    <span class="status-badge pending-cancellation"><i class="fas fa-clock"></i> Cancellation Pending</span>
                  <?php else: ?>
                    <span class="status-badge subscribed"><i class="fas fa-check-circle"></i> Subscribed</span>
                    <button class="cancel-subscription-btn" onclick="cancelSubscription(<?php echo $activeSubscriptionMap['spa']; ?>, 'Spa & Wellness')">
                      Cancel Subscription
                    </button>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="status-badge"><i class="fas fa-clock"></i> Available</span>
                  <button class="subscribe-btn" onclick="showSubscriptionModal('spa', 4000, 'Spa & Wellness')">Subscribe</button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Wallet Section -->
    <div id="wallet" class="content-section">
      <div class="section-card">
        <h2><i class="fas fa-wallet"></i> My Wallet</h2>
        <div class="wallet-balance">
          <h3>Current Balance</h3>
          <p>₱<?php echo number_format($walletBalance, 2); ?></p>
        </div>
        <div class="wallet-actions">
          <button onclick="showDepositModal()">Deposit</button>
          <button onclick="showWithdrawModal()">Withdraw</button>
        </div>
        <div class="transaction-history">
          <h3>Recent Transactions</h3>
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Payment Method</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($transactions as $transaction): ?>
              <tr>
                <td><?php echo date('M d, Y', strtotime($transaction['created_at'])); ?></td>
                <td><?php echo ucfirst($transaction['type']); ?></td>
                <td>₱<?php echo number_format($transaction['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                <td><?php echo isset($transaction['payment_method']) ? ucfirst($transaction['payment_method']) : '-'; ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Deposit Modal -->
    <div id="deposit-modal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Deposit Funds</h3>
        <form id="deposit-form">
          <div class="form-group">
            <label for="deposit-amount">Amount (₱)</label>
            <input type="number" id="deposit-amount" name="amount" min="1" step="0.01" required>
          </div>
          <div class="form-group">
            <label for="deposit-description">Description</label>
            <input type="text" id="deposit-description" name="description" placeholder="e.g., Initial deposit">
          </div>
          <div class="form-group">
            <label for="payment-method">Payment Method</label>
            <select id="payment-method" name="payment_method" required>
              <option value="gcash">Gcash</option>
              <option value="paymaya">Paymaya</option>
              <option value="bank-transfer">Bank Transfer</option>
            </select>
          </div>
          <button type="submit" class="deposit-btn">Confirm Deposit</button>
        </form>
      </div>
    </div>

    <!-- Withdraw Modal -->
    <div id="withdraw-modal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Withdraw Funds</h3>
        <form id="withdraw-form">
          <div class="form-group">
            <label for="withdraw-amount">Amount (₱)</label>
            <input type="number" id="withdraw-amount" name="amount" min="1" step="0.01" required>
          </div>
          <div class="form-group">
            <label for="withdraw-description">Description</label>
            <input type="text" id="withdraw-description" name="description" placeholder="e.g., Withdrawal for expenses">
          </div>
          <button type="submit" class="withdraw-btn">Confirm Withdrawal</button>
        </form>
      </div>
    </div>

    <!-- Subscription Modal -->
    <div id="subscription-modal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h3>Subscribe to <span id="amenity-name"></span></h3>
        <div class="subscription-details">
          <div class="subscription-price">
            <h4>Monthly Fee</h4>
            <p>₱<span id="amenity-price"></span></p>
          </div>
          <form id="subscription-form">
            <input type="hidden" id="amenity-type" name="amenity_type">
            <div class="form-group">
              <label for="subscription-start">Start Date</label>
              <input type="date" id="subscription-start" name="start_date" required>
            </div>
            <div class="form-group">
              <label for="subscription-duration">Duration</label>
              <select id="subscription-duration" name="duration" required>
                <option value="1">1 Month</option>
                <option value="3">3 Months</option>
                <option value="6">6 Months</option>
                <option value="12">12 Months</option>
              </select>
            </div>
            <div class="total-price">
              <h4>Total Price</h4>
              <p>₱<span id="total-subscription-price">0</span></p>
            </div>
            <button type="submit" class="subscribe-btn">Confirm Subscription</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Cancellation Modal -->
    <div id="cancellation-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Request Cancellation</h3>
            <form id="cancellation-form">
                <input type="hidden" id="cancellation-type" name="type">
                <input type="hidden" id="cancellation-id" name="id">
                <div class="form-group">
                    <label for="cancellation-reason">Reason for Cancellation</label>
                    <textarea id="cancellation-reason" name="reason" required 
                              placeholder="Please provide a reason for your cancellation request..."></textarea>
                </div>
                <button type="submit" class="submit-btn">Submit Request</button>
            </form>
      </div>
    </div>
  </div>

  <!-- Profile Picture Modal -->
  <div id="profilePicModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Change Profile Picture</h2>
      <form id="profilePicForm" enctype="multipart/form-data">
        <div class="upload-area" id="uploadArea">
          <i class="fas fa-cloud-upload-alt"></i>
          <p>Drag and drop an image here or click to select</p>
          <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" hidden>
        </div>
        <img id="profilePicPreview" class="profile-pic-preview" src="" alt="Preview">
        <button type="submit">Upload Picture</button>
      </form>
    </div>
  </div>

  <script>
    // Profile Picture Modal
    const modal = document.getElementById('profilePicModal');
    const closeBtn = modal.querySelector('.close');
    const uploadArea = document.getElementById('uploadArea');
    const profilePicInput = document.getElementById('profilePicInput');
    const profilePicForm = document.getElementById('profilePicForm');
    const profilePicPreview = document.getElementById('profilePicPreview');
    const profilePic = document.getElementById('profilePic');

    // Sidebar Navigation
    const navItems = document.querySelectorAll('.nav-item');
    const contentSections = document.querySelectorAll('.content-section');

    navItems.forEach(item => {
      item.addEventListener('click', () => {
        // Skip if it's the logout button
        if (item.getAttribute('onclick')) return;

        // Remove active class from all items and sections
        navItems.forEach(nav => nav.classList.remove('active'));
        contentSections.forEach(section => section.classList.remove('active'));

        // Add active class to clicked item
        item.classList.add('active');

        // Show corresponding section
        const target = item.getAttribute('data-target');
        document.getElementById(target).classList.add('active');
      });
    });

    // Cancel Booking Function
    async function cancelBooking(bookingId) {
      showCancellationModal('booking', bookingId);
    }

    // Open modal when clicking on profile picture
    document.querySelector('.profile-pic-container').addEventListener('click', () => {
      modal.classList.add('active');
    });

    // Close modal
    closeBtn.addEventListener('click', () => {
      modal.classList.remove('active');
    });

    // Close modal when clicking outside
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.classList.remove('active');
      }
    });

    // Handle file selection
    uploadArea.addEventListener('click', () => {
      profilePicInput.click();
    });

    // Handle drag and drop
    uploadArea.addEventListener('dragover', (e) => {
      e.preventDefault();
      uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
      uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadArea.classList.remove('dragover');
      const file = e.dataTransfer.files[0];
      if (file && file.type.startsWith('image/')) {
        handleFile(file);
      }
    });

    // Handle file input change
    profilePicInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        handleFile(file);
      }
    });

    // Preview selected image
    function handleFile(file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        profilePicPreview.src = e.target.result;
        profilePicPreview.classList.add('active');
      };
      reader.readAsDataURL(file);
    }

    // Handle form submission
    profilePicForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(profilePicForm);
      
      try {
        const response = await fetch('upload_profile_picture.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.success) {
          profilePic.src = data.url;
          modal.classList.remove('active');
          showNotification('Profile picture updated successfully!', 'success');
        } else {
          throw new Error(data.message);
        }
      } catch (error) {
        showNotification(error.message || 'Error uploading profile picture', 'error');
      }
    });

    // Subscription Modal Functions
    function showSubscriptionModal(amenityType, price, name) {
        const modal = document.getElementById('subscription-modal');
        const amenityNameSpan = document.getElementById('amenity-name');
        const amenityPriceSpan = document.getElementById('amenity-price');
        const amenityTypeInput = document.getElementById('amenity-type');
        const startDateInput = document.getElementById('subscription-start');
        const durationSelect = document.getElementById('subscription-duration');
        const totalPriceSpan = document.getElementById('total-subscription-price');
        const loadingSpinner = document.getElementById('loading-spinner');

        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        startDateInput.min = today;
        startDateInput.value = today;

        amenityNameSpan.textContent = name;
        amenityPriceSpan.textContent = price.toLocaleString();
        amenityTypeInput.value = amenityType;
        
        // Calculate total price
        function updateTotalPrice() {
            const duration = parseInt(durationSelect.value);
            const total = price * duration;
            totalPriceSpan.textContent = total.toLocaleString();
        }

        durationSelect.addEventListener('change', updateTotalPrice);
        updateTotalPrice();

        // Show modal
        modal.classList.add('active');

        // Close modal when clicking the close button or outside the modal
        const closeBtn = modal.querySelector('.close');
        closeBtn.onclick = () => modal.classList.remove('active');
        window.onclick = (event) => {
            if (event.target === modal) {
                modal.classList.remove('active');
            }
        };

        // Handle form submission
        const form = document.getElementById('subscription-form');
        form.onsubmit = async (e) => {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            loadingSpinner.style.display = 'block';
            
            const formData = {
                amenity_type: amenityType,
                start_date: startDateInput.value,
                duration: parseInt(durationSelect.value),
                total_price: price * parseInt(durationSelect.value)
            };

            try {
                const response = await fetch('subscribe_amenity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (data.success) {
                    // Update UI
                    const card = document.querySelector(`[onclick="showSubscriptionModal('${amenityType}', ${price}, '${name}')"]`).closest('.amenity-card');
                    const statusDiv = card.querySelector('.amenity-status');
                    
                    // Update status badge
                    const statusBadge = statusDiv.querySelector('.status-badge');
                    statusBadge.className = 'status-badge subscribed';
                    statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Subscribed';
                    
                    // Create cancel button with correct styling
                    const cancelBtn = document.createElement('button');
                    cancelBtn.className = 'cancel-subscription-btn';
                    cancelBtn.innerHTML = 'Cancel Subscription';
                    cancelBtn.onclick = () => cancelSubscription(data.subscription_id, name);
                    
                    // Remove subscribe button and add cancel button
                    const subscribeBtn = statusDiv.querySelector('.subscribe-btn');
                    if (subscribeBtn) {
                        subscribeBtn.remove();
                    }
                    statusDiv.appendChild(cancelBtn);

                    // Update wallet balance if available
                    const walletBalanceElement = document.querySelector('.wallet-balance p');
                    if (walletBalanceElement && data.newBalance !== undefined) {
                        walletBalanceElement.textContent = `₱${parseFloat(data.newBalance).toLocaleString()}`;
                    }

                    // Show success message and close modal
                    modal.classList.remove('active');
                    showNotification(data.message, 'success');
                } else {
                    if (data.message.includes('Insufficient wallet balance')) {
                        showNotification(data.message, 'error');
                        setTimeout(() => {
                            document.querySelector('[data-target="wallet"]').click();
                        }, 2000);
                    } else {
                        throw new Error(data.message);
                    }
                }
            } catch (error) {
                showNotification(error.message || 'Error processing subscription', 'error');
            } finally {
                submitBtn.disabled = false;
                loadingSpinner.style.display = 'none';
            }
        };
    }

    // Booking Filters
    document.addEventListener('DOMContentLoaded', function() {
        const filterButtons = document.querySelectorAll('.filter-btn');
        const bookingCards = document.querySelectorAll('.booking-card');

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                button.classList.add('active');

                const filter = button.getAttribute('data-filter');

                bookingCards.forEach(card => {
                    if (filter === 'all') {
                        card.style.display = 'block';
                        // Add fade-in animation
                        card.style.opacity = '0';
                        setTimeout(() => {
                            card.style.opacity = '1';
                        }, 50);
                    } else {
                        if (card.getAttribute('data-status') === filter) {
                            card.style.display = 'block';
                            // Add fade-in animation
                            card.style.opacity = '0';
                            setTimeout(() => {
                                card.style.opacity = '1';
                            }, 50);
                        } else {
                            card.style.display = 'none';
                        }
                    }
                });
            });
        });
    });

    // Deposit Modal
    function showDepositModal() {
      const phone = "<?php echo isset($user['phone_number']) ? $user['phone_number'] : ''; ?>";
      if (!phone) {
        showNotification('Please update your profile with a phone number before depositing.', 'error');
        return;
      }
      const modal = document.getElementById('deposit-modal');
      modal.classList.add('active');
    }

    // Withdraw Modal
    function showWithdrawModal() {
      const phone = "<?php echo isset($user['phone_number']) ? $user['phone_number'] : ''; ?>";
      if (!phone) {
        showNotification('Please update your profile with a phone number before depositing.', 'error');
        return;
      }
      const modal = document.getElementById('withdraw-modal');
      modal.classList.add('active');
    }

    // Close modals
    document.querySelectorAll('.modal .close').forEach(closeBtn => {
      closeBtn.addEventListener('click', () => {
        closeBtn.closest('.modal').classList.remove('active');
      });
    });

    // Handle deposit form submission
    document.getElementById('deposit-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const amount = document.getElementById('deposit-amount').value;
      const description = document.getElementById('deposit-description').value;
      const paymentMethod = document.getElementById('payment-method').value;

      try {
        const response = await fetch('process_deposit.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ amount, description, payment_method: paymentMethod }),
        });

        const data = await response.json();

        if (data.success) {
          document.querySelector('.wallet-balance p').textContent = `₱${data.newBalance}`;
          showNotification('Deposit successful!', 'success');
          location.reload();
        } else {
          throw new Error(data.message);
        }
      } catch (error) {
        showNotification(error.message || 'Error processing deposit', 'error');
      }
    });

    // Handle withdraw form submission
    document.getElementById('withdraw-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const amount = document.getElementById('withdraw-amount').value;
      const description = document.getElementById('withdraw-description').value;

      try {
        const response = await fetch('process_withdraw.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ amount, description }),
        });

        const data = await response.json();

        if (data.success) {
          document.querySelector('.wallet-balance p').textContent = `₱${data.newBalance}`;
          showNotification('Withdrawal successful!', 'success');
          location.reload();
        } else {
          throw new Error(data.message);
        }
      } catch (error) {
        showNotification(error.message || 'Error processing withdrawal', 'error');
      }
    });

    // Handle profile settings form submission
    document.getElementById('profile-settings-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(e.target);
      formData.append('update_profile', '1');

      try {
        const response = await fetch('profile_dashboard.php', {
          method: 'POST',
          body: formData
        });

        const text = await response.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'text/html');
        const successAlert = doc.querySelector('.alert.success');

        if (successAlert) {
          showNotification('Profile updated successfully!', 'success');
        }
      } catch (error) {
        showNotification('Error updating profile', 'error');
      }
    });

    // Add cancel subscription function
    async function cancelSubscription(subscriptionId, amenityName) {
        showCancellationModal('subscription', subscriptionId);
        }

    // Show loading spinner during AJAX requests
        const loadingSpinner = document.getElementById('loading-spinner');
    const originalFetch = window.fetch;
    window.fetch = function() {
        loadingSpinner.style.display = 'block';
      return originalFetch.apply(this, arguments)
        .then(response => {
          loadingSpinner.style.display = 'none';
          return response;
        })
        .catch(error => {
          loadingSpinner.style.display = 'none';
          throw error;
        });
    };

    // Add to existing script
    function showCancellationModal(type, id) {
        const modal = document.getElementById('cancellation-modal');
        document.getElementById('cancellation-type').value = type;
        document.getElementById('cancellation-id').value = id;
        modal.classList.add('active');
    }

    // Handle cancellation form submission
    document.getElementById('cancellation-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const type = document.getElementById('cancellation-type').value;
        const id = document.getElementById('cancellation-id').value;
        const reason = document.getElementById('cancellation-reason').value;

        try {
            const response = await fetch('request_cancellation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ type, id, reason })
            });

            const data = await response.json();

            if (data.success) {
                // Close modal
                document.getElementById('cancellation-modal').classList.remove('active');

                // Show success message
                showNotification(data.message, 'success');

                // Reload page to update UI
                setTimeout(() => location.reload(), 2000);
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            showNotification(error.message || 'Error submitting cancellation request', 'error');
        }
    });

    // Close modal when clicking outside or on close button
    document.querySelectorAll('.modal .close').forEach(closeBtn => {
        closeBtn.addEventListener('click', () => {
            closeBtn.closest('.modal').classList.remove('active');
        });
    });

    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('active');
        }
    });

    // Add notification function
    function showNotification(message, type = 'success') {
      const notification = document.getElementById('notification');
      const icon = notification.querySelector('i');
      const text = notification.querySelector('p');
      
      // Set icon based on type
      icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-times-circle';
      
      // Set message
      text.textContent = message;
      
      // Set type
      notification.className = `notification ${type}`;
      
      // Show notification
      notification.style.display = 'block';
      
      // Hide after 3 seconds
      setTimeout(() => {
        notification.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => {
          notification.style.display = 'none';
          notification.style.animation = 'fadeIn 0.3s ease';
        }, 300);
      }, 3000);
    }
  </script>
</body>
</html>
