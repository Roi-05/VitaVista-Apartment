<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}
$admin = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../styles/admin.css?v=<?php echo time(); ?>">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
    body { display: flex; min-height: 100vh; }
    .sidebar { width: 250px; background-color: #1e272e; padding: 20px; color: white; }
    .sidebar-header { margin-bottom: 30px; text-align: center; }
    .sidebar-nav { list-style: none; }
    .nav-item { margin-bottom: 15px; padding: 10px; border-radius: 5px; cursor: pointer; transition: 0.3s; }
    .nav-item:hover, .nav-item.active { background-color: #2f3640; }
    .content-area { flex: 1; padding: 30px; background-color: #f1f2f6; }
    .content-section { display: none; }
    .content-section.active { display: block; }
    .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    h2 { margin-bottom: 20px; }
    @media (max-width: 768px) {
      body { flex-direction: column; }
      .sidebar { width: 100%; height: auto; }
    }
  </style>
</head>
<body>

<div class="sidebar">
  <div class="sidebar-header">
    <h2>Admin Panel</h2>
    <p><?php echo htmlspecialchars($admin['fullname']); ?></p>
  </div>
  <ul class="sidebar-nav">
    <li class="nav-item active" data-target="dashboard">Dashboard</li>
    <li class="nav-item" data-target="bookings">Bookings</li>
    <li class="nav-item" data-target="apartments">Apartments</li>
    <li class="nav-item" data-target="users">Users</li>
    <li class="nav-item" data-target="reports">Reports</li>
    <li class="nav-item" onclick="if (confirm('Are you sure you want to log out?')) { window.location.href='../logout.php'; }">Logout</li>
  </ul>
</div>

<div class="content-area">
  <div id="dashboard" class="content-section active">
    <div class="card">
      <h2>Welcome, Admin!</h2>
      <p>This is your control center.</p>
    </div>
  </div>

  <div id="bookings" class="content-section">
    <div class="card">
      <h2>Manage Bookings</h2>
      <p>List of all bookings will appear here.</p>
    </div>
  </div>

  <div id="apartments" class="content-section">
    <div class="card">
      <h2>Manage Apartments</h2>
      <p>Add, edit, or delete apartments.</p>
    </div>
  </div>

  <div id="users" class="content-section">
    <div class="card">
      <h2>Manage Users</h2>
      <p>View and manage users.</p>
    </div>
  </div>

  <div id="reports" class="content-section">
    <div class="card">
      <h2>Reports</h2>
      <p>View statistics and system reports here.</p>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.content-section');

    navItems.forEach(item => {
      item.addEventListener('click', function () {
        if (!item.dataset.target) return;
        navItems.forEach(i => i.classList.remove('active'));
        sections.forEach(sec => sec.classList.remove('active'));

        this.classList.add('active');
        document.getElementById(this.dataset.target).classList.add('active');
      });
    });
  });
</script>

</body>
</html>
