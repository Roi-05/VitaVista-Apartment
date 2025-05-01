<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/database/db.php';
session_start();



$apartmentType = "2-bedroom";
$query = $pdo->prepare("SELECT * FROM apartments WHERE type = ?");
$query->execute([$apartmentType]);
$apartment = $query->fetch(PDO::FETCH_ASSOC);

$availableUnitsQuery = $pdo->prepare("SELECT unit, availability FROM apartments WHERE type = ?");
$availableUnitsQuery->execute([$apartmentType]);
$allUnits = $availableUnitsQuery->fetchAll(PDO::FETCH_ASSOC);

if (!$apartment) {
    die("Apartment not found.");
}

$userId = $_SESSION['user']['id'];
$pricePerNight = $apartment['price_per_night'];

$bookingsQuery = $pdo->prepare("
    SELECT apartments.unit, bookings.check_in_date, bookings.check_out_date 
    FROM bookings 
    INNER JOIN apartments ON bookings.apartment_id = apartments.id 
    WHERE apartments.type = ?
");
$bookingsQuery->execute([$apartmentType]);
$existingBookings = $bookingsQuery->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Studio Type Apartment</title>
  <link rel="stylesheet" href="styles/header.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="styles/room-details.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" href="styles/room_map.css?v=<?php echo time(); ?>">
    <!-- Flatpickr CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

  <!-- Flatpickr JS -->
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>


  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css">
  <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

  <script>
  window.existingBookings = <?php echo json_encode($existingBookings); ?>;
</script>
</head>
<body data-user-signed-in="<?php echo isset($_SESSION['user']) ? 'true' : 'false'; ?>" 
      data-user-id="<?php echo htmlspecialchars($userId); ?>" 
      data-apartment-type="<?php echo htmlspecialchars($apartmentType); ?>">
    <header class="header">
        <div class="logo-container">
            <a href="index.php"><img src="Pictures/logo-apt.png" alt="VitaVista Logo" class="logo-image"></a>
            <a href="index.php" class="logo">Vita<span>Vista</span></a>
        </div>
        <nav class="nav-links">
            <?php if (isset($_SESSION['user'])): ?>
            <div class="profile-container">
                <img src="Pictures/Default_pfp.jpg" alt="Profile Picture" class="profile-picture">
                <div class="dropdown-menu">
                    <a href="profile.php">Profile</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
            <?php else: ?>
                <a class="login-or-sign-up" href="login.php">Login or Sign Up</a>
            <?php endif; ?>
        </nav>
    </header>

  <!-- Mosaic Grid -->
  <div class="image-mosaic custom-grid">
    <div class="mosaic-large" onclick="openLightbox(0)">
        <img src="/Pictures/2_bedroom/9.png" alt="Living Room">
        <div class="view-more-overlay">Full View</div>
    </div>
    <div class="mosaic-small mosaic-small-top" onclick="openLightbox(1)">
        <img src="/Pictures/2_bedroom/2.png" alt="Kitchen">
        <div class="view-more-overlay">Full View</div>
    </div>
    <div class="mosaic-small mosaic-small-bottom" onclick="openLightbox(2)">
        <img src="/Pictures/2_bedroom/3.png" alt="Kitchen Sink">
        <div class="view-more-overlay">Full View</div>
    </div>
</div>

  <!-- Lightbox -->
  <div id="lightbox" class="lightbox">
    <div class="lightbox-controls">
        <button class="lightbox-btn left" onclick="changeSlide(-1)">&#10094;</button>
        <button class="lightbox-btn right" onclick="changeSlide(1)">&#10095;</button>
    </div>
    <div class="lightbox-content">
      <img id="lightbox-img" class="lightbox-img" src="" alt="Slideshow Image">
      <button class="close-btn" onclick="closeLightbox()">×</button>
    </div>
  </div>

  <!-- Sticky Nav -->
  <div class="sticky-header">
    <a href="#overview">Overview</a>
    <a href="#pricing">Pricing</a>
    <a href="#details">Details</a>
  </div>

  <div class="room-details-container">
  <!-- Overview Section -->
  <section id="overview">
    <h2>Overview</h2>
    <ul>
      <li>🛏️ 2 Bedrooms Apartment</li>
      <li>🛁 3 Bathrooms</li>
      <li>📐 Floor Area: 190 sqm</li>
    </ul>
  </section>

  <!-- Pricing Section -->
  <section id="pricing">
    <h2>Pricing</h2>
    <p><strong>Rental Price:</strong> Php 92,000/month</p>
    <p><strong>Payment Terms:</strong> 1 Month Advance + 2 Months Security Deposit</p>
    <p><strong>Contract Term:</strong> Preferred 1-Year Contract (Shorter Terms Possible Upon Request)</p>
  </section>

  <!-- Details Section -->
  <section id="details">
  <h2>Details</h2>
  <ul>
    <li>🛏️ 2 Spacious Bedrooms</li>
    <li>🛁 3 Bathrooms (Master Bath with Shower + Separate Bathtub)</li>
    <li>🚗 2 Car Parking (Tandem Parking)</li>
    <li>🏙️ Large Balcony with City and Skyline Views</li>
    <li>🧺 En-suite Laundry and Drying Area</li>
    <li>👩‍⚕️ Maid's Room with Own Toilet and Closet</li>
  </ul>

    <h3>Amenities</h3>
    <ul>
      <li>🚗 1 Free Parking Slot</li>
      <li>🎥 120" Projector Screen (Entertainment Ready)</li>
      <li>🔒 CCTV Security</li>
    </ul>

    <h3>Furnitures & Appliances Included</h3>
      <ul>
        <li>❄️ Air Conditioning Units (All Bedrooms, Living, Dining, Kitchen)</li>
        <li>🛏️ No bed included</li>
        <li>🔥 Built-in Oven, Stove, Microwave</li>
        <li>🌬️ Range Hood & Exhaust System</li>
        <li>🚿 Built-in Hot Water Heater (Showers & Sinks)</li>
        <li>🪞 Vanity Mirrors in Bathrooms</li>
        <li>🚪 Large Built-in Closets and Cabinet Drawers</li>
      </ul>
  </section>
</div>

<div class="booking-section" data-price-per-night="<?php echo $pricePerNight; ?>">
  <p class="price">₱<span id="total-price">0</span> <span>for <span id="total-nights">0</span> night(s)</span></p>
  <div class="booking-dates">
    <div class="date">
      <label for="check-in">CHECK-IN</label>
      <input type="date" id="check-in">
    </div>
    <div class="date">
      <label for="check-out">CHECKOUT</label>
      <input type="date" id="check-out">
    </div>
  </div>
  <div class="bookrooms">
    <label for="bookrooms">Available Units</label>
    <select id="bookrooms">
      <?php foreach ($allUnits as $unit): ?>
        <option value="<?php echo htmlspecialchars($unit['unit']); ?>">
          <?php echo htmlspecialchars($unit['unit']); ?>
        </option>
      <?php endforeach; ?>
</select>
  </div>
  <button class="book-button">Book Now</button>
  <p class="note">You won't be charged yet</p>
</div>

<div id="popup-modal" class="modal">
  <div class="modal-content">
    <span id="close-modal" class="close-button">&times;</span>
    <p id="modal-message"></p>
  </div>
</div>

<div id="payment-modal" class="modal">
  <div class="modal-content">
    <span id="close-payment-modal" class="close-button">&times;</span>
    <h3>Booking Confirmation</h3>
    <div class="booking-summary">
      <div class="user-details">
        <h4>User Information</h4>
        <p>Name: <?php echo $_SESSION['user']['fullname'] ?? 'N/A'; ?></p>
        <p>Email: <?php echo $_SESSION['user']['email'] ?? 'N/A'; ?></p>
      </div>
      
      <div class="apartment-details">
        <h4>Apartment Details</h4>
        <p>Type: <?php echo $apartment['type']; ?></p>
        <p class="unit-number"></p>
        <p>Price/Night: ₱<?php echo number_format($apartment['price_per_night'], 2); ?></p>
      </div>

      <div class="booking-info">
        <h4>Booking Dates</h4>
        <p>Check-in: <span id="modal-checkin"></span></p>
        <p>Check-out: <span id="modal-checkout"></span></p>
        <p>Total Price: ₱<span id="modal-total-price"></span></p>
      </div>
    </div>

    <form id="payment-form">
      <label for="payment-method">Choose payment method:</label>
      <select id="payment-method" required>
        <option value="credit-card">Credit Card</option>
        <option value="gcash">GCash</option>
        <option value="bank-transfer">Bank Transfer</option>
      </select>
      <button type="submit" class="payment-button">Confirm Booking</button>
    </form>
  </div>
</div>

  <!-- JavaScript -->
  <script>
    const images = [
      "/Pictures/2_bedroom/9.png",
      "/Pictures/2_bedroom/1.png",
      "/Pictures/2_bedroom/2.png",
      "/Pictures/2_bedroom/3.png",
      "/Pictures/2_bedroom/4.png",
      "/Pictures/2_bedroom/10.png",
      "/Pictures/2_bedroom/11.png",
      "/Pictures/2_bedroom/12.png"
    ];

    let currentIndex = 0;
    const stickyHeader = document.querySelector('.sticky-header');
    function openLightbox(index = 0) {
      const lightbox = document.getElementById('lightbox');
      const img = document.getElementById('lightbox-img');
      img.src = images[index];
      lightbox.classList.add('active');
      document.body.style.overflow = 'hidden';
      currentIndex = index;
      stickyHeader.style.display = 'none'; // Hide sticky header
    }

    function closeLightbox() {
      document.getElementById('lightbox').classList.remove('active');
      document.body.style.overflow = 'auto';
      stickyHeader.style.display = 'flex';
    }

    function changeSlide(offset) {
      currentIndex = (currentIndex + offset + images.length) % images.length;
      document.getElementById('lightbox-img').src = images[currentIndex];
    }

    // Trigger from mosaic or view more
    document.querySelector('.mosaic-small-top').addEventListener('click', () => openLightbox(0));

    // Optional: click small-bottom to also view
    document.querySelector('.mosaic-small-bottom').addEventListener('click', () => openLightbox(2));

    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
      if (document.getElementById('lightbox').classList.contains('active')) {
        if (e.key === 'ArrowLeft') changeSlide(-1);
        if (e.key === 'ArrowRight') changeSlide(1);
        if (e.key === 'Escape') closeLightbox();
      }
    });

    // Touch swipe support
    let touchStartX = 0;
    document.addEventListener('touchstart', e => {
      touchStartX = e.touches[0].clientX;
    });

    document.addEventListener('touchend', e => {
      const diff = touchStartX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) changeSlide(diff > 0 ? 1 : -1);
    });

    // Leaflet map
    const map = L.map('map').setView([14.9828, 120.4846], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    L.marker([14.9828, 120.4846])
      .addTo(map)
      .bindPopup(`
        <div class="popup-content">
          <div class="popup-header">
            <p class="apartment-title" style="font-size: 1rem">Mel's Mansion</p>
            <p><i class="fa-solid fa-phone" style="color: #049f33;"></i> 09758257308</p>
            <p><i class="fa-solid fa-location-dot" style="color: red;"></i> Pias, Porac, Pampanga</p>
          </div>
        </div>`);
    setTimeout(() => { map.invalidateSize(); }, 300);
  </script>
    <script>
        (function(){if(!window.chatbase||window.chatbase("getState")!=="initialized"){window.chatbase=(...arguments)=>{if(!window.chatbase.q){window.chatbase.q=[]}window.chatbase.q.push(arguments)};window.chatbase=new Proxy(window.chatbase,{get(target,prop){if(prop==="q"){return target.q}return(...args)=>target(prop,...args)}})}const onLoad=function(){const script=document.createElement("script");script.src="https://www.chatbase.co/embed.min.js";script.id="Hd_72aiMggC-PmJBKHMNU";script.domain="www.chatbase.co";document.body.appendChild(script)};if(document.readyState==="complete"){onLoad()}else{window.addEventListener("load",onLoad)}})();
    </script>
    <script>
  const pricePerNight = 15000; // Price specific to this apartment
</script>
<script src="scripts/booking.js"></script>
</body>
</html>
