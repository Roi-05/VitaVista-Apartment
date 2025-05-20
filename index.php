<?php
require __DIR__ . '/database/db.php';
session_start();

if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
    header("Location: admin_dashboard.php");
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vita Vista</title>
    
    <link rel="stylesheet" href="styles/index.css?v=<?php echo time(); ?>">       
    <link rel="stylesheet" href="styles/header.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/first.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/room.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/map.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/contact.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/chat.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/about.css?v=<?php echo time(); ?>">
   
    <link rel="stylesheet" href="https://unpkg.com/aos@2.3.1/dist/aos.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <script src="scripts/chat.js" defer></script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js" defer></script>
    <script src="scripts/chat_database.js" defer></script>
   

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="logo-container" style="margin-left: 5.5rem;">
            <a href="#"><img src="Pictures/logo-apt.png" alt="VitaVista Logo" class="logo-image"></a>
            <a href="#" class="logo">Vita<span>Vista</span></a>
            
        </div>
        <div style="color: white; font-size: 2rem;">
         </div>
        <nav class="nav-links">
            <a href="#first">Home</a>
            <a href="#about">About</a>
            <a href="#rooms">Rooms</a>
            <a href="#map-section">Location</a>
            <a href="#contact">Contact</a>
            <?php if (isset($_SESSION['user'])): ?>
            <div class="profile-container">
                <img src="Pictures/Default_pfp.jpg" alt="Profile Picture" class="profile-picture">
                <div class="dropdown-menu">
                    <a href="profile_dashboard.php">Dashboard</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
            <?php else: ?>
                <a class="login-or-sign-up" href="login.php">Login or Sign Up</a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="chatbox-container">
        <div class="chat-window">
            <div class="chat-header">
                <h3>Chat The Owner</h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="chat-body"></div>
            <div class="chat-input">
                <input type="text" placeholder="Type your message...">
                <button class="send-btn">Send</button>
            </div>
        </div>
        <div class="chat-button">
            <i style="font-size: 2rem;" class="fas fa-comment-dots"></i>
        </div>
    </div>

    <section id="first" class="first">
        <video autoplay muted loop class="background-video">
            <source src="Pictures/cinematic.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <div class="first-content">
            <h1>Luxury Apartment Residences</h1>
            <p>Experience Sophisticated Urban Living</p>
            <a href="#rooms"><button class="bookbutton">Book Now</button></a>
        </div>
    </section>

    <section id="about" class="about">
        <div class="section-title">
            <h2>Welcome to Vita Vista Apartments</h2>
            <p>Situated in the peaceful town of Pias, Porac, Pampanga, Vita Vista offers a perfect blend of modern living and a warm, welcoming community atmosphere.
            Our apartments are strategically located close to essential establishments — schools, hospitals, shopping centers — and just a short drive from Clark Freeport Zone and Angeles City.</p>
        </div>
        <div class="about-content">
            <div class="about-text" data-aos="fade-up">

                <h4>🏢 Our Building</h4>
                <p>
                    Vita Vista is a thoughtfully designed 5-storey low-rise building offering a range of unit types — from cozy studio apartments to spacious penthouse suites.
                    Each unit is crafted to deliver quality living spaces that balance style, functionality, and comfort.
                </p>

                <h4>✨ Highlights</h4>
                <ul>
                    <li>Well-maintained, gated community</li>
                    <li>Beautiful landscaping and outdoor spaces</li>
                    <li>Ample parking areas</li>
                    <li>24/7 CCTV surveillance and security personnel</li>
                    <li>Strong fiber internet connection available</li>
                    <li>Pet-friendly environment (selected units)</li>
                    <li>Flood-free and secure location</li>
                </ul>

                <h4>🏡 Units Available</h4>
                <ul>
                    <li>Studio-Type Apartments</li>
                    <li>1-Bedroom Units</li>
                    <li>2-Bedroom Suites</li>
                    <li>Luxurious Penthouse Units</li>
                </ul>

                <p>
                    Each Vita Vista unit is thoughtfully designed with quality interiors, efficient layouts, and premium amenities to perfectly suit modern lifestyles.
                </p>

                <h4>💬 Why Choose Vita Vista?</h4>
                <p>
                    At Vita Vista, we don't just offer apartments — we offer a place you can proudly call home. 
                    Whether you're a young professional, a growing family, or someone seeking a peaceful retreat away from the city's busy life, Vita Vista provides accessibility, affordability, and a higher quality of living.
                </p>

                <h4>📍 Visit Us:</h4>
                <p>Vita Vista Apartments, Pias, Porac, Pampanga</p>
                <p>Experience a higher standard of living, only at Vita Vista.</p>
            </div>

            <div class="about-image" data-aos="fade-up">
                <img src="Pictures/vita_vista.jpg" alt="About Vita Vista">
            </div>
        </div>                                
    </section>
                

    <section id="rooms" class="rooms">
        <div class="section-title">
            <h2>Our Residences</h2>
            <p>Select from our exclusive collection</p>
        </div>
        <div class="rooms-grid">
            <div class="room-card" data-aos="fade-up">
                <div class="room-details">
                    <h3>Studio Type Apartment</h3>
                    <ul class="room-features">
                        <li>🛏️ 1 Bedroom</li>
                        <li>🛁 1 Bathroom</li>
                    </ul>
                    <a href="apartment_1.php"><button class="view-more">View More</button></a>
                </div>
            </div>
            <div class="room-card" data-aos="fade-up">
                <div class="room-details">
                    <h3>1 Bedroom Apartment</h3>
                    <ul class="room-features">
                        <li>🛏️ 1 Room</li>
                        <li>🛁 1 Bathroom</li>
                    </ul>                
                    <a href="apartment_2.php"><button class="view-more">View More</button></a>
                </div>
            </div>
            <div class="room-card" data-aos="fade-up">
                <div class="room-details">
                    <h3>2 Bedroom Apartment</h3>
                    <ul class="room-features">
                        <li>🛏️ 2 Rooms</li>
                        <li>🛁 1 Bathroom</li>
                    </ul>
                    <a href="apartment_3.php"><button class="view-more">View More</button></a>
                </div>
            </div>
            <div class="room-card" data-aos="fade-up">
                <div class="room-details">
                    <h3>Penthouse</h3>
                    <ul class="room-features">
                        <li>🛏️ 3 Rooms</li>
                        <li>🛁 3 Bathroom</li>
                    </ul>
                    <a href="apartment_4.php"><button class="view-more">View More</button></a>
                </div>
            </div>
    </section>

    <section id="map-section">
        <div class="section-title">
            <h2>Vicinity Map</h2>
        </div>
        <div id="map"></div>
    </section>

    <section id="contact" class="contact" data-aos="fade-up">
        <div class="info">
            <p class="connect-with-us">Connect with us!</p>

            <p style="color: white;">We'd love to hear from you.</p>
        </div>
        <div class="contact-container">
            <div>
                <div class="logo-container">
                    <i class="fa-solid fa-phone" style="color: #049f33; font-size: 2.5rem;"></i>
                </div>
                <div>
                    <p>+1 234 567 890</p>
                </div>
            </div>
            <div>
                <div class="logo-container">
                    <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="48" height="48" viewBox="0 0 48 48">
                        <path fill="#4caf50" d="M45,16.2l-5,2.75l-5,4.75L35,40h7c1.657,0,3-1.343,3-3V16.2z"></path><path fill="#1e88e5" d="M3,16.2l3.614,1.71L13,23.7V40H6c-1.657,0-3-1.343-3-3V16.2z"></path><polygon fill="#e53935" points="35,11.2 24,19.45 13,11.2 12,17 13,23.7 24,31.95 35,23.7 36,17"></polygon><path fill="#c62828" d="M3,12.298V16.2l10,7.5V11.2L9.876,8.859C9.132,8.301,8.228,8,7.298,8h0C4.924,8,3,9.924,3,12.298z"></path><path fill="#fbc02d" d="M45,12.298V16.2l-10,7.5V11.2l3.124-2.341C38.868,8.301,39.772,8,40.702,8h0 C43.076,8,45,9.924,45,12.298z"></path>
                    </svg>
                </div>
                <div>
                    <p>vitavista@gmail.com</p>
                </div>
            </div>
            <div>
                <div class="logo-container">
                    <i class="fa-brands fa-facebook fa-sm fa-bounce" style="color: #3253e6;"></i>
                </div>
                <div>
                    <p>vitavista</p>
                </div>
            </div>
            <div>
                <div class="logo-container">
                    <i class="fa-brands fa-instagram fa-bounce"></i>
                </div>
                <div>
                    <p>vitavilla</p>
                </div>
            </div>
        </div>

    </section>
    <footer>
        <p>© 2025 Vita Vista. All Rights Reserved.</p>
    </footer>
    <script src="scripts/header.js"></script>
    <script src="scripts/map.js?v=<?php echo time(); ?>"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
        AOS.init({
            duration: 1000, // Animation duration in milliseconds    // Whether animation should happen only once
        });
        });
    </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sections = document.querySelectorAll('section');
                const navLinks = document.querySelectorAll('.nav-links a');

                function activateLink() {
                    let index = sections.length;

                    while (--index >= 0) {
                        const sectionTop = sections[index].offsetTop - 120; // adjust if needed
                        if (scrollY >= sectionTop) {
                            navLinks.forEach(link => link.classList.remove('active'));
                            if (navLinks[index]) {
                                navLinks[index].classList.add('active');
                            }
                            break;
                        }
                    }
                }

                activateLink(); // Activate on load
                window.addEventListener('scroll', activateLink);
    });
    </script>

    <script>
        (function(){
            if(!window.chatbase||window.chatbase("getState")!=="initialized"){
                window.chatbase=(...arguments)=>{
                    if(!window.chatbase.q){
                        window.chatbase.q=[]
                    }
                    window.chatbase.q.push(arguments)
                };
                window.chatbase=new Proxy(window.chatbase,{
                    get(target,prop){
                        if(prop==="q"){
                            return target.q
                        }
                        return(...args)=>target(prop,...args)
                    }
                })
            }
            const onLoad=function(){
                const script=document.createElement("script");
                script.src="https://www.chatbase.co/embed.min.js";
                script.id="Hd_72aiMggC-PmJBKHMNU";
                script.domain="www.chatbase.co";
                document.body.appendChild(script);
                
                // Initialize chat with user info if available
                <?php if (isset($_SESSION['user'])): ?>
                // Clear any existing chat state
                window.chatbase('clear');
                
                // Initialize with user info and load history
                window.chatbase('init', {
                    userId: '<?php echo $_SESSION['user']['id']; ?>',
                    userEmail: '<?php echo $_SESSION['user']['email']; ?>',
                    userName: '<?php echo $_SESSION['user']['fullname']; ?>',
                    loadHistory: true,
                    sessionId: '<?php echo session_id(); ?>'
                });
                
                // Set up event listener for chat state changes
                window.chatbase('onStateChange', function(state) {
                    if (state === 'ready') {
                        // Chat is ready, load user's history
                        window.chatbase('loadHistory', {
                            userId: '<?php echo $_SESSION['user']['id']; ?>'
                        });
                    }
                });
                <?php else: ?>
                // For non-logged in users, initialize without user info
                window.chatbase('init', {
                    loadHistory: false
                });
                <?php endif; ?>
            };
            if(document.readyState==="complete"){
                onLoad()
            }else{
                window.addEventListener("load",onLoad)
            }
        })();
    </script>
</body>
</html>