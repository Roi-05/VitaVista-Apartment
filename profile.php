<?php
session_start();

// Redirect to login if the user is not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Get user data from the session
$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="styles/profile.css">
</head>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const profilePic = document.getElementById('profilePic');
        const lightbox = document.getElementById('lightbox');
        const lightboxImg = document.getElementById('lightboxImg');
        const closeBtn = document.querySelector('.close');

        profilePic.onclick = function() {
            lightbox.style.display = "block";
            lightboxImg.src = this.src;
        };

        closeBtn.onclick = function() {
            lightbox.style.display = "none";
        };

        lightbox.onclick = function(event) {
            if (event.target == lightbox) {
                lightbox.style.display = "none";
            }
        };
    });
</script>

<body>
    <div class="container">
        <div class="profile-header">
            <!-- Display the user's profile picture -->
            <img src="<?php echo isset($user['profile_picture']) ? $user['profile_picture'] : 'Pictures/Default_pfp.jpg'; ?>" 
                 alt="Profile Picture" 
                 class="profile-pic" 
                 id="profilePic" 
                 style="cursor: pointer;">
            
            <!-- Display the user's full name and email -->
            <h1><?php echo htmlspecialchars($user['fullname']); ?></h1>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <!-- Lightbox for profile picture -->
        <div id="lightbox" class="lightbox" style="display: none;">
            <span class="close">&times;</span>
            <img class="lightbox-content" id="lightboxImg">
        </div>

        <div class="profile-section actions">
            <button>Edit Profile</button>
            <button>Change Password</button>
        </div>
    </div>
</body>
</html>