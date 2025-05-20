<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

require __DIR__ . '/database/db.php';

$admin = $_SESSION['user'];

// Get statistics
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'total_bookings' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
    'total_revenue' => $pdo->query("SELECT SUM(total_price) FROM bookings")->fetchColumn(),
    'active_apartments' => $pdo->query("SELECT COUNT(*) FROM apartments WHERE availability > 0")->fetchColumn()
];

// Get recent bookings
$recentBookings = $pdo->query("
    SELECT b.*, u.fullname, a.type, a.unit
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

// Get all apartments
$apartments = $pdo->query("
    SELECT a.*, COUNT(b.id) as total_bookings
    FROM apartments a
    LEFT JOIN bookings b ON a.id = b.apartment_id
    GROUP BY a.id
    ORDER BY a.type
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles/admin.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <i class="fas fa-peso-sign"></i>
                <h3>Total Revenue</h3>
                <p>₱<?php echo number_format($stats['total_revenue'], 2); ?></p>
            </div>
            <div class="stat-card">
                <i class="fas fa-building"></i>
                <h3>Active Apartments</h3>
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
                            <td><span class="status-badge active">Active</span></td>
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
            <div class="booking-filters">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="upcoming">Upcoming</button>
                <button class="filter-btn" data-filter="active">Active</button>
                <button class="filter-btn" data-filter="completed">Completed</button>
            </div>
            <button class="add-btn" onclick="showAddBookingModal()">
                <i class="fas fa-plus"></i> Add Booking
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
            <div class="apartment-card">
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
                    <span class="status-badge-apt <?php echo $apt['availability'] > 0 ? 'available' : 'occupied'; ?>">
                        <i class="fas <?php echo $apt['availability'] > 0 ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                        <?php echo $apt['availability'] > 0 ? 'Available' : 'Occupied'; ?>
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
                        <th>Total Bookings</th>
                        <th>Joined Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['total_bookings']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <span class="status-badge <?php echo $user['status'] ?? 'active'; ?>">
                                <?php echo ucfirst($user['status'] ?? 'active'); ?>
                            </span>
                        </td>
                        <td>
                            <button onclick="editUser(<?php echo $user['id']; ?>)">Edit</button>
                            <button onclick="deleteUser(<?php echo $user['id']; ?>)">Delete</button>
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
            <!-- Form fields will be added here -->
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
</style>

<script src="scripts/admin.js"></script>
<script>
// Add this to your existing JavaScript
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
</script>
</body>
</html>
