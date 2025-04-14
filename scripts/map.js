function changeImage(newSrc) {
    document.getElementById('mainImage').src = newSrc;
}

const map = L.map('map').setView([14.982806366091523, 120.48461305712443], 50);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

// Add a marker with responsive images in the popup
const marker = L.marker([14.982806366091523, 120.48461305712443])
    .addTo(map)
    .bindPopup(`
        <div class="popup-content">
            <p>Vita <span>Vista</span> Apartment</p>
            <div id="slideshow-container">
                <img id="slideshow-main" src="Pictures/jay.jpg" alt="Vita Villa Apartment" style="width: 100%; border-radius: 10px;">
                <div class="slideshow-controls">
                    <button onclick="prevImage()">&#10094;</button>
                    <button onclick="nextImage()">&#10095;</button>
                </div>
            </div>
        </div>`,
        { maxWidth: 600, minWidth: 500, maxHeight: 400 }
    );

let currentImageIndex = 0;
const images = [
    "Pictures/jay.jpg",
    "Pictures/slep.jpg",
    "Pictures/jay.jpg",
    "Pictures/slep.jpg",
    "Pictures/pexels.jpg",
];

function updateSlideshow() {
    const mainImage = document.getElementById("slideshow-main");
    if (mainImage) {
        mainImage.src = images[currentImageIndex];
    }
}

function prevImage() {
    currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
    updateSlideshow();
}

function nextImage() {
    currentImageIndex = (currentImageIndex + 1) % images.length;
    updateSlideshow();
}

// Ensure the map resizes properly
setTimeout(() => {
    map.invalidateSize();
}, 100);