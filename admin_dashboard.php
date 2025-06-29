<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require __DIR__ . '/database/db.php';
require_once __DIR__ . '/get_apartment_availability.php';

$admin = $_SESSION['user'];

// Get dashboard statistics
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'active_bookings' => $pdo->query("
        SELECT COUNT(*) FROM bookings 
        WHERE check_in_date <= CURDATE() 
        AND check_out_date >= CURDATE() 
        AND status NOT IN ('cancelled', 'completed')
    ")->fetchColumn(),
    'upcoming_bookings' => $pdo->query("
        SELECT COUNT(*) FROM bookings 
        WHERE check_in_date > CURDATE() 
        AND status NOT IN ('cancelled', 'completed')
    ")->fetchColumn(),
    'total_revenue' => $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE payment_status = 'paid'")->fetchColumn(),
    'active_apartments' => count(array_filter(getAvailableApartments($pdo), function($apt) { return $apt['is_available']; }))
];

// Get recent bookings
$recentBookings = $pdo->query("
    SELECT b.*, u.fullname, a.type, a.unit,
           CASE 
               WHEN b.status IN ('cancelled', 'completed') THEN b.status
               WHEN b.check_in_date > CURDATE() THEN 'upcoming'
               WHEN b.check_in_date <= CURDATE() AND b.check_out_date >= CURDATE() THEN 'active'
               ELSE 'completed'
           END as status
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN apartments a ON b.apartment_id = a.id
    ORDER BY b.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get all users
$users = $pdo->query("
    SELECT u.*, COUNT(b.id) as total_bookings
    FROM users u
    LEFT JOIN bookings b ON u.id = b.user_id
    WHERE u.role = 'user'
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get apartments with their availability status
$apartments = getAvailableApartments($pdo);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="styles/admin.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <h2>Admin Panel</h2>
        <p><?php echo htmlspecialchars($admin['fullname']); ?></p>
    </div>
    <ul class="sidebar-nav">
        <li class="nav-item active" data-target="dashboard">
            <i class="fas fa-home"></i> Dashboard
        </li>
        <li class="nav-item" data-target="bookings">
            <i class="fas fa-calendar-check"></i> Bookings
        </li>
        <li class="nav-item" data-target="apartments">
            <i class="fas fa-building"></i> Apartments
        </li>
        <li class="nav-item" data-target="users">
            <i class="fas fa-users"></i> Users
        </li>
        <li class="nav-item" data-target="reports">
            <i class="fas fa-chart-bar"></i> Reports
        </li>
        <li class="nav-item" data-target="amenities">
            <i class="fas fa-swimming-pool"></i> Amenities
        </li>
        <li class="nav-item" onclick="if (confirm('Are you sure you want to log out?')) { window.location.href='logout.php'; }">
            <i class="fas fa-sign-out-alt"></i> Logout
        </li>
    </ul>
</div>

<div class="content-area">
    <!-- Dashboard Section -->
    <div id="dashboard" class="content-section active">
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3>Total Users</h3>
                <p><?php echo number_format($stats['total_users']); ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <h3>Total Bookings</h3>
                <p><?php echo number_format($stats['total_bookings']); ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-check"></i>
                <h3>Active Bookings</h3>
                <p><?php echo number_format($stats['active_bookings']); ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-alt"></i>
                <h3>Upcoming Bookings</h3>
                <p><?php echo number_format($stats['upcoming_bookings']); ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-peso-sign"></i>
                <h3>Total Revenue</h3>
                <p>₱<?php echo number_format($stats['total_revenue'], 2); ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-building"></i>
                <h3>Available Apartments</h3>
                <p><?php echo number_format($stats['active_apartments']); ?></p>
            </div>
        </div>

        <!-- Add Pending Cancellation Requests to Dashboard -->
        <?php
        // Get pending cancellation requests
        $pendingCancellations = $pdo->query("
            SELECT COUNT(*) 
            FROM cancellation_requests 
            WHERE status = 'pending'
        ")->fetchColumn();
        ?>
        <div class="pending-cancellations">
            <h2>Pending Cancellation Requests</h2>
            <div class="alert <?php echo $pendingCancellations > 0 ? 'alert-warning' : 'alert-success'; ?>">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $pendingCancellations; ?> pending cancellation request(s)
                <?php if ($pendingCancellations > 0): ?>
                    <a href="#" onclick="showSection('cancellations')" class="view-link">View Requests</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="recent-activity">
            <h2>Recent Bookings</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Guest</th>
                            <th>Apartment</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $booking): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($booking['type'] . ' - ' . $booking['unit']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></td>
                            <td><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></td>
                            <td>₱<?php echo number_format($booking['total_price'], 2); ?></td>
                            <td><span class="status-badge <?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bookings Section -->
    <div id="bookings" class="content-section">
        <div class="section-header">
            <h2>Manage Bookings</h2>
            <div class="booking-tabs">
                <button class="tab-btn active" data-tab="onsite">On-site Bookings</button>
                <button class="tab-btn" data-tab="online">Online Bookings</button>
            </div>
            <div class="booking-filters">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="upcoming">Upcoming</button>
                <button class="filter-btn" data-filter="active">Active</button>
                <button class="filter-btn" data-filter="completed">Completed</button>
            </div>
            <button class="add-btn" id="addBookingBtn" onclick="showAddBookingModal()">
                <i class="fas fa-plus"></i> Add On-site Booking
            </button>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Guest</th>
                        <th>Apartment</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Amount</th>
                        <th>Payment Status</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="bookings-table">
                    <!-- Bookings will be loaded here via AJAX -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Apartments Section -->
    <div id="apartments" class="content-section">
        <div class="section-header">
            <h2><i class="fas fa-building"></i> Manage Apartments</h2>
            <div class="header-actions">
                <div class="search-box">
                    <input type="text" id="apartmentSearch" placeholder="Search apartments...">
                    <i class="fas fa-search"></i>
                </div>
                <button class="add-btn" onclick="showAddApartmentModal()">
                    <i class="fas fa-plus"></i> Add Apartment
                </button>
            </div>
        </div>

        <div class="apartments-grid">
            <?php foreach ($apartments as $apt): ?>
            <div class="apartment-card" data-id="<?php echo $apt['id']; ?>">
                <div class="apartment-image">
                    <?php
                    $imagePath = '';
                    switch(strtolower($apt['type'])) {
                        case 'studio':
                            $imagePath = 'Pictures/studiotype/1.avif';
                            break;
                        case '1-bedroom':
                            $imagePath = 'Pictures/1_bedroom/1.png';
                            break;
                        case '2-bedroom':
                            $imagePath = 'Pictures/2_bedroom/1.png';
                            break;
                        case 'penthouse':
                            $imagePath = 'Pictures/penthouse/1.avif';
                            break;
                    }
                    ?>
                    <img src="<?php echo $imagePath; ?>" 
                         alt="<?php echo htmlspecialchars($apt['type']); ?>">
                    <span class="status-badge-apt <?php echo $apt['is_available'] ? 'available' : 'occupied'; ?>">
                        <i class="fas <?php echo $apt['is_available'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        <?php echo $apt['is_available'] ? 'Available' : 'Occupied'; ?>
                    </span>
                </div>
                <div class="apartment-content">
                    <div class="apartment-header">
                        <h3><?php echo htmlspecialchars($apt['type']); ?></h3>
                        <p class="unit-number"><?php echo htmlspecialchars($apt['unit']); ?></p>
                    </div>
                    <div class="apartment-details">
                        <div class="detail-item">
                            <i class="fas fa-peso-sign"></i>
                            <div class="detail-info">
                                <span class="label">Price per Night</span>
                                <span class="value">₱<?php echo number_format($apt['price_per_night'], 2); ?></span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-calendar-check"></i>
                            <div class="detail-info">
                                <span class="label">Total Bookings</span>
                                <span class="value"><?php echo $apt['total_bookings']; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="apartment-actions">
                        <button class="edit-btn" onclick="editApartment(<?php echo $apt['id']; ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="delete-btn" onclick="deleteApartment(<?php echo $apt['id']; ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Users Section -->
    <div id="users" class="content-section">
        <div class="section-header">
            <h2>Manage Users</h2>
            <div class="search-box">
                <input type="text" id="userSearch" placeholder="Search users...">
                <i class="fas fa-search"></i>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact Number</th>
                        <th>Total Bookings</th>
                        <th>Joined Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone_number'] ?? 'N/A'); ?></td>
                        <td><?php echo $user['total_bookings']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $user['status'] ?? 'active'; ?>">
                                <?php echo ucfirst($user['status'] ?? 'active'); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reports Section -->
    <div id="reports" class="content-section">
        <div class="reports-grid">
            <div class="report-card">
                <h3>Revenue Overview</h3>
                <canvas id="revenueChart"></canvas>
            </div>
            <div class="report-card">
                <h3>Booking Statistics</h3>
                <canvas id="bookingChart"></canvas>
            </div>
            <div class="report-card">
                <h3>Popular Apartments</h3>
                <canvas id="apartmentChart"></canvas>
            </div>
            <div class="report-card">
                <h3>User Growth</h3>
                <canvas id="userChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Amenities Section -->
    <div id="amenities" class="content-section">
        <div class="section-header">
            <h2><i class="fas fa-swimming-pool"></i> Manage Amenity Subscriptions</h2>
            <div class="amenity-filters">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="active">Active</button>
                <button class="filter-btn" data-filter="cancelled">Cancelled</button>
                <button class="filter-btn" data-filter="expired">Expired</button>
                <button class="filter-btn" data-filter="cancellation_requested">Cancellation Requested</button>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Amenity Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Total Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="amenities-table">
                    <!-- Amenity subscriptions will be loaded here via AJAX -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cancellation Requests Section -->
    <div id="cancellations" class="content-section">
        <div class="section-header">
            <h2><i class="fas fa-ban"></i> Manage Cancellation Requests</h2>
            <div class="cancellation-filters">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="pending">Pending</button>
                <button class="filter-btn" data-filter="approved">Approved</button>
                <button class="filter-btn" data-filter="rejected">Rejected</button>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Reference ID</th>
                        <th>Reason</th>
                        <th>Requested Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="cancellations-table">
                    <!-- Cancellation requests will be loaded here via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Booking Modal -->
<div id="bookingModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add New Booking</h2>
        <form id="bookingForm">
            <div class="form-group">
                <label for="guestName">Guest Name:</label>
                <input type="text" id="guestName" name="guestName" required>
            </div>
            <div class="form-group">
                <label for="guestEmail">Guest Email:</label>
                <input type="email" id="guestEmail" name="guestEmail" required>
            </div>
            <div class="form-group">
                <label for="guestPhone">Guest Phone:</label>
                <input type="tel" id="guestPhone" name="guestPhone" required>
            </div>
            <div class="form-group">
                <label for="apartmentType">Apartment Type:</label>
                <select id="apartmentType" name="apartmentType" required>
                    <option value="">Select Type</option>
                    <option value="studio">Studio</option>
                    <option value="1-bedroom">1 Bedroom</option>
                    <option value="2-bedroom">2 Bedroom</option>
                    <option value="penthouse">Penthouse</option>
                </select>
            </div>
            <div class="form-group">
                <label for="apartmentUnit">Unit:</label>
                <select id="apartmentUnit" name="apartmentUnit" required>
                    <option value="">Select Unit</option>
                </select>
            </div>
            <div class="form-group">
                <label for="checkIn">Check-in Date:</label>
                <input type="date" id="checkIn" name="checkIn" required>
            </div>
            <div class="form-group">
                <label for="checkOut">Check-out Date:</label>
                <input type="date" id="checkOut" name="checkOut" required>
            </div>
            <div class="form-group">
                <label for="paymentMethod">Payment Method:</label>
                <select id="paymentMethod" name="paymentMethod" required>
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="bank_transfer">Bank Transfer</option>
                </select>
            </div>
            <div class="form-group">
                <label for="paymentStatus">Payment Status:</label>
                <select id="paymentStatus" name="paymentStatus" required>
                    <option value="paid">Paid</option>
                    <option value="pending">Pending</option>
                    <option value="partial">Partial Payment</option>
                </select>
            </div>
            <div class="form-group">
                <label for="totalAmount">Total Amount:</label>
                <input type="number" id="totalAmount" name="totalAmount" required>
            </div>
            <div class="form-actions">
                <button type="submit" class="submit-btn">Create Booking</button>
                <button type="button" class="cancel-btn">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Add/Edit Apartment Modal -->
<div id="apartmentModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Add New Apartment</h2>
        <form id="apartmentForm">
            <!-- Form fields will be added here -->
        </form>
    </div>
</div>

<style>
    .apartments-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        padding: 20px;
    }

    .apartment-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .apartment-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    }

    .apartment-image {
        position: relative;
        height: 200px;
        overflow: hidden;
    }

    .apartment-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .status-badge-apt { 
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .status-badge-apt.available {
        background-color: #28a745;
        color: white;
    }

    .status-badge-apt.occupied {
        background-color: #dc3545;
        color: white;
    }

    .apartment-content {
        padding: 20px;
    }

    .apartment-header {
        margin-bottom: 15px;
    }

    .apartment-header h3 {
        margin: 0;
        font-size: 1.2em;
        color: #333;
    }

    .unit-number {
        color: #666;
        font-size: 0.9em;
        margin: 5px 0 0;
    }

    .apartment-details {
        display: grid;
        gap: 15px;
        margin-bottom: 20px;
    }

    .detail-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .detail-item i {
        font-size: 1.2em;
        color: #666;
        width: 20px;
    }

    .detail-info {
        display: flex;
        flex-direction: column;
    }

    .detail-info .label {
        font-size: 0.8em;
        color: #666;
    }

    .detail-info .value {
        font-weight: 500;
        color: #333;
    }

    .apartment-actions {
        display: flex;
        gap: 10px;
    }

    .apartment-actions button {
        flex: 1;
        padding: 8px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        font-size: 0.9em;
        transition: background-color 0.3s ease;
    }

    .edit-btn {
        background-color: #007bff;
        color: white;
    }

    .edit-btn:hover {
        background-color: #0056b3;
    }

    .delete-btn {
        background-color: #dc3545;
        color: white;
    }

    .delete-btn:hover {
        background-color: #c82333;
    }

    .header-actions {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .search-box {
        position: relative;
        width: 300px;
    }

    .search-box input {
        width: 100%;
        padding: 8px 35px 8px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 0.9em;
    }

    .search-box i {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
    }

    .add-btn {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 5px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: background-color 0.3s ease;
    }

    .add-btn:hover {
        background-color: #218838;
    }

    .pending-cancellations {
        margin: 20px 0;
        padding: 20px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .pending-cancellations h2 {
        margin-bottom: 15px;
        color: #333;
    }

    .alert {
        padding: 15px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-warning {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .view-link {
        margin-left: auto;
        color: inherit;
        text-decoration: underline;
    }

    .cancellation-filters {
        margin-bottom: 20px;
    }

    .filter-btn {
        padding: 8px 15px;
        margin-right: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .filter-btn.active {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }

    .approve-btn, .reject-btn {
        padding: 5px 10px;
        border: none;
        border-radius: 3px;
        cursor: pointer;
        margin-right: 5px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .approve-btn {
        background: #28a745;
        color: white;
    }

    .reject-btn {
        background: #dc3545;
        color: white;
    }

    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.8em;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .status-badge.pending {
        background: #ffc107;
        color: #000;
    }

    .status-badge.approved {
        background: #28a745;
        color: white;
    }

    .status-badge.rejected {
        background: #dc3545;
        color: white;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .modal-content {
        background: white;
        padding: 30px;
        border-radius: 8px;
        width: 90%;
        max-width: 600px;
        margin: 50px auto;
        position: relative;
        max-height: 90vh;
        overflow-y: auto;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #333;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .form-group textarea {
        resize: vertical;
    }

    .form-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
    }

    .submit-btn,
    .cancel-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: 500;
    }

    .submit-btn {
        background: #28a745;
        color: white;
    }

    .submit-btn:hover {
        background: #218838;
    }

    .cancel-btn {
        background: #dc3545;
        color: white;
    }

    .cancel-btn:hover {
        background: #c82333;
    }

    .close {
        position: absolute;
        right: 20px;
        top: 15px;
        font-size: 24px;
        cursor: pointer;
        color: #666;
    }

    .close:hover {
        color: #333;
    }

    .booking-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .tab-btn {
        padding: 8px 20px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .tab-btn.active {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }

    .booking-type-badge {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.8em;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .booking-type-badge.onsite {
        background: #17a2b8;
        color: white;
    }

    .booking-type-badge.online {
        background: #6c757d;
        color: white;
    }

    .mark-paid-btn {
        background-color: #28a745;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .mark-paid-btn:hover {
        background-color: #218838;
    }

    .text-center {
        text-align: center;
    }

    .apartment-card {
        transition: opacity 0.3s ease;
    }

    .complete-btn, .cancel-btn {
        padding: 5px 10px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin: 0 2px;
        font-size: 0.9em;
    }

    .complete-btn {
        background-color: #28a745;
        color: white;
    }

    .complete-btn:hover {
        background-color: #218838;
    }

    .cancel-btn {
        background-color: #dc3545;
        color: white;
    }

    .cancel-btn:hover {
        background-color: #c82333;
    }

    .complete-btn:disabled, .cancel-btn:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
        opacity: 0.65;
    }

    .amenity-filters {
        margin-bottom: 20px;
    }

    .amenity-filters .filter-btn {
        padding: 8px 15px;
        margin-right: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .amenity-filters .filter-btn.active {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }

    .status-badge.expired {
        background: #6c757d;
        color: white;
    }

    .status-badge.cancellation_requested {
        background: #ffc107;
        color: #000;
    }
</style>

<script src="scripts/admin.js?v=<?php echo time(); ?>"></script>
<script>
// Add these functions at the beginning of your script section
function loadBookings(type = 'onsite', filter = 'all') {
    const tbody = document.getElementById('bookings-table');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center">Loading...</td></tr>';

    fetch(`get_bookings.php?type=${type}&filter=${filter}`)
        .then(response => response.json())
        .then(data => {
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" class="text-center">No bookings found</td></tr>';
                return;
            }

            data.forEach(booking => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${booking.id}</td>
                    <td>${booking.fullname}</td>
                    <td>${booking.type} - ${booking.unit}</td>
                    <td>${formatDate(booking.check_in_date)}</td>
                    <td>${formatDate(booking.check_out_date)}</td>
                    <td>₱${formatNumber(booking.total_price)}</td>
                    <td>
                        <span class="status-badge ${booking.payment_status}">
                            ${capitalizeFirst(booking.payment_status)}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge ${booking.status}">
                            ${capitalizeFirst(booking.status)}
                        </span>
                    </td>
                    <td>
                        ${booking.booking_type === 'onsite' ? `
                            <button onclick="completeBooking(${booking.id})" class="complete-btn" ${booking.status === 'completed' ? 'disabled' : ''}>
                                <i class="fas fa-check"></i> Complete
                            </button>
                            <button onclick="cancelBooking(${booking.id})" class="cancel-btn" ${booking.status === 'cancelled' ? 'disabled' : ''}>
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        ` : booking.payment_status === 'pending' ? `
                            <button onclick="markAsPaid(${booking.id})" class="mark-paid-btn">
                                <i class="fas fa-check"></i> Mark as Paid
                            </button>
                        ` : ''}
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error loading bookings:', error);
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">Error loading bookings</td></tr>';
        });
}

// Add event listeners for booking filters
document.addEventListener('DOMContentLoaded', function() {
    // Booking tabs
    const tabBtns = document.querySelectorAll('.tab-btn');
    const addBookingBtn = document.getElementById('addBookingBtn');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            tabBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const isOnsiteTab = this.dataset.tab === 'onsite';
            addBookingBtn.style.display = isOnsiteTab ? 'flex' : 'none';
            
            loadBookings(this.dataset.tab, document.querySelector('.filter-btn.active').dataset.filter);
        });
    });

    // Booking filters
    const filterBtns = document.querySelectorAll('.filter-btn');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
            loadBookings(activeTab, this.dataset.filter);
        });
    });

    // Apartment search
    const apartmentSearch = document.getElementById('apartmentSearch');
    if (apartmentSearch) {
        apartmentSearch.addEventListener('input', debounce(function() {
            const searchTerm = this.value.toLowerCase();
            const apartmentCards = document.querySelectorAll('.apartment-card');
            
            apartmentCards.forEach(card => {
                const type = card.querySelector('h3').textContent.toLowerCase();
                const unit = card.querySelector('.unit-number').textContent.toLowerCase();
                const price = card.querySelector('.detail-info .value').textContent.toLowerCase();
                
                const matches = type.includes(searchTerm) || 
                              unit.includes(searchTerm) || 
                              price.includes(searchTerm);
                
                card.style.display = matches ? '' : 'none';
            });
        }, 300));
    }

    // Initial load of onsite bookings
    loadBookings('onsite', 'all');
});

// Utility function for debouncing search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById(sectionId).classList.add('active');
    
    // Update navigation
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-target="${sectionId}"]`).classList.add('active');

    // If it's the cancellations section, load the requests
    if (sectionId === 'cancellations') {
        loadCancellationRequests();
    }
}

// Function to load cancellation requests
function loadCancellationRequests(filter = 'all') {
    fetch(`get_cancellation_requests.php?filter=${filter}`)
        .then(response => response.json())
        .then(data => {
            const table = document.getElementById('cancellations-table');
            table.innerHTML = '';
            
            data.forEach(request => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${request.id}</td>
                    <td>${request.fullname}</td>
                    <td>${request.type}</td>
                    <td>${request.reference_id}</td>
                    <td>${request.reason}</td>
                    <td>${new Date(request.created_at).toLocaleDateString()}</td>
                    <td><span class="status-badge ${request.status}">${request.status}</span></td>
                    <td>
                        ${request.status === 'pending' ? `
                            <button onclick="handleCancellation(${request.id}, 'approved')" class="approve-btn">
                                <i class="fas fa-check"></i> Approve
                            </button>
                            <button onclick="handleCancellation(${request.id}, 'rejected')" class="reject-btn">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        ` : ''}
                    </td>
                `;
                table.appendChild(row);
            });
        })
        .catch(error => console.error('Error loading cancellation requests:', error));
}

// Function to handle cancellation approval/rejection
function handleCancellation(requestId, action) {
    if (!confirm(`Are you sure you want to ${action} this cancellation request?`)) {
        return;
    }

    fetch('handle_cancellation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            request_id: requestId,
            action: action
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            loadCancellationRequests();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => console.error('Error handling cancellation:', error));
}

// Handle filter buttons
document.querySelectorAll('.cancellation-filters .filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.cancellation-filters .filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        loadCancellationRequests(btn.dataset.filter);
    });
});

function showAddBookingModal() {
    const modal = document.getElementById('bookingModal');
    modal.style.display = 'block';
    initializeDatePickers();
}

// Handle apartment type change
document.getElementById('apartmentType').addEventListener('change', async function() {
    const type = this.value;
    const unitSelect = document.getElementById('apartmentUnit');
    
    if (!type) {
        unitSelect.innerHTML = '<option value="">Select Unit</option>';
        return;
    }
    
    try {
        const response = await fetch(`get_available_units.php?type=${type}`);
        const data = await response.json();
        
        unitSelect.innerHTML = '<option value="">Select Unit</option>';
        if (Array.isArray(data)) {
            data.forEach(unit => {
                const option = document.createElement('option');
                option.value = unit.id;
                option.textContent = `${unit.unit} (₱${parseFloat(unit.price_per_night).toLocaleString()}/night)`;
                unitSelect.appendChild(option);
            });
        } else if (data.error) {
            console.error('Error:', data.error);
        }
    } catch (error) {
        console.error('Error fetching units:', error);
    }
});

// Initialize date pickers with disabled dates
function initializeDatePickers() {
    const checkInPicker = flatpickr("#checkIn", {
        dateFormat: "Y-m-d",
        minDate: "today",
        onChange: function(selectedDates, dateStr) {
            checkOutPicker.set("minDate", selectedDates[0]);
            updateTotalPrice();
        }
    });

    const checkOutPicker = flatpickr("#checkOut", {
        dateFormat: "Y-m-d",
        minDate: "today",
        onChange: function() {
            updateTotalPrice();
        }
    });

    // Function to update total price
    function updateTotalPrice() {
        const checkIn = checkInPicker.selectedDates[0];
        const checkOut = checkOutPicker.selectedDates[0];
        const unitSelect = document.getElementById('apartmentUnit');
        const selectedOption = unitSelect.options[unitSelect.selectedIndex];
        
        if (checkIn && checkOut && selectedOption) {
            const priceText = selectedOption.textContent.match(/₱([\d,]+)/);
            if (priceText) {
                const pricePerNight = parseFloat(priceText[1].replace(/,/g, ''));
                const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
                const totalPrice = pricePerNight * nights;
                document.getElementById('totalAmount').value = totalPrice.toFixed(2);
            }
        }
    }

    // Add event listener for unit selection
    document.getElementById('apartmentUnit').addEventListener('change', function() {
        const selectedUnit = this.value;
        if (selectedUnit) {
            // Fetch booked dates for the selected unit
            fetch(`get_booked_dates.php?unit_id=${selectedUnit}`)
                .then(response => response.json())
                .then(bookedDates => {
                    // Disable booked dates in both pickers
                    const disabledDates = bookedDates.map(booking => ({
                        from: booking.check_in_date,
                        to: booking.check_out_date
                    }));
                    
                    checkInPicker.set('disable', disabledDates);
                    checkOutPicker.set('disable', disabledDates);
                })
                .catch(error => console.error('Error fetching booked dates:', error));
        }
    });
}

// Handle form submission
document.getElementById('bookingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const bookingData = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('add_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(bookingData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Booking created successfully!');
            document.getElementById('bookingModal').style.display = 'none';
            // Refresh the bookings table
            loadBookings();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error creating booking:', error);
        alert('An error occurred while creating the booking.');
    }
});

// Close modal when clicking the close button or outside the modal
document.querySelector('.close').addEventListener('click', function() {
    document.getElementById('bookingModal').style.display = 'none';
});

document.querySelector('.cancel-btn').addEventListener('click', function() {
    document.getElementById('bookingModal').style.display = 'none';
});

window.addEventListener('click', function(e) {
    const modal = document.getElementById('bookingModal');
    if (e.target === modal) {
        modal.style.display = 'none';
    }
});

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
}

function formatNumber(number) {
    return parseFloat(number).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function updateApartmentCard(apartment) {
    const card = document.querySelector(`.apartment-card[data-id="${apartment.id}"]`);
    if (card) {
        // Update status badge
        const statusBadge = card.querySelector('.status-badge-apt');
        statusBadge.className = `status-badge-apt ${apartment.is_available ? 'available' : 'occupied'}`;
        statusBadge.innerHTML = `
            <i class="fas ${apartment.is_available ? 'fa-check-circle' : 'fa-times-circle'}"></i>
            ${apartment.is_available ? 'Available' : 'Occupied'}
        `;

        // Update other apartment details
        card.querySelector('.unit-number').textContent = apartment.unit;
        card.querySelector('.detail-info .value').textContent = `₱${parseFloat(apartment.price_per_night).toFixed(2)}`;
    }
}

// Add this function to handle marking bookings as paid
function markAsPaid(bookingId) {
    if (!confirm('Are you sure you want to mark this booking as paid?')) {
        return;
    }

    fetch('mark_booking_paid.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ booking_id: bookingId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Booking marked as paid successfully');
            loadBookings(); // Refresh the bookings table
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while marking the booking as paid');
    });
}

// Add these new functions for handling booking actions
function completeBooking(bookingId) {
    if (!confirm('Are you sure you want to mark this booking as completed?')) {
        return;
    }

    fetch('update_booking_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            booking_id: bookingId,
            status: 'completed'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Booking marked as completed successfully');
            // Refresh the bookings table with current filter
            const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
            const activeFilter = document.querySelector('.filter-btn.active').dataset.filter;
            loadBookings(activeTab, activeFilter);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the booking status');
    });
}

function cancelBooking(bookingId) {
    if (!confirm('Are you sure you want to cancel this booking?')) {
        return;
    }

    fetch('update_booking_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            booking_id: bookingId,
            status: 'cancelled'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Booking cancelled successfully');
            // Refresh the bookings table with current filter
            const activeTab = document.querySelector('.tab-btn.active').dataset.tab;
            const activeFilter = document.querySelector('.filter-btn.active').dataset.filter;
            loadBookings(activeTab, activeFilter);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while cancelling the booking');
    });
}

// Add this new function to load amenity subscriptions
function loadAmenitySubscriptions(filter = 'all') {
    const tbody = document.getElementById('amenities-table');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center">Loading...</td></tr>';

    fetch(`get_amenity_subscriptions.php?filter=${filter}`)
        .then(response => response.json())
        .then(data => {
            tbody.innerHTML = '';
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">No amenity subscriptions found</td></tr>';
                return;
            }

            data.forEach(subscription => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${subscription.id}</td>
                    <td>${subscription.fullname}</td>
                    <td>${subscription.amenity_type}</td>
                    <td>${formatDate(subscription.start_date)}</td>
                    <td>${formatDate(subscription.end_date)}</td>
                    <td>₱${formatNumber(subscription.total_price)}</td>
                    <td>
                        <span class="status-badge ${subscription.status}">
                            ${capitalizeFirst(subscription.status)}
                        </span>
                    </td>
                `;
                tbody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error loading amenity subscriptions:', error);
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">Error loading amenity subscriptions</td></tr>';
        });
}

// Add event listeners for amenity filters
document.addEventListener('DOMContentLoaded', function() {
    // ... existing code ...

    // Amenity filters
    const amenityFilterBtns = document.querySelectorAll('.amenity-filters .filter-btn');
    amenityFilterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            amenityFilterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            loadAmenitySubscriptions(this.dataset.filter);
        });
    });

    // Initial load of amenity subscriptions
    loadAmenitySubscriptions('all');
});

// Function to handle amenity subscription cancellation
function cancelAmenitySubscription(subscriptionId) {
    if (!confirm('Are you sure you want to cancel this amenity subscription?')) {
        return;
    }

    fetch('cancel_amenity_subscription.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            subscription_id: subscriptionId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Amenity subscription cancelled successfully');
            loadAmenitySubscriptions(document.querySelector('.amenity-filters .filter-btn.active').dataset.filter);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while cancelling the amenity subscription');
    });
}
</script>
</body>
</html>
