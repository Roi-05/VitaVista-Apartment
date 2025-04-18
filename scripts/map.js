function changeImage(newSrc) {
    document.getElementById('mainImage').src = newSrc;
}

window.onload = function () {
    const map = L.map('map').setView([14.982806366091523, 120.48461305712443], 50);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
    const apartmentIcon = L.icon({
        iconUrl: '/Pictures/location-dot-gold.svg',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
    });
    
    const tradeMarkIcon = L.icon({
        iconUrl: '/Pictures/location-dot.svg',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
    });
    // Slideshow Manager with separate states
const slideshowManager = {
    apartment: {
        currentIndex: 0,
        images: [
            "Pictures/room1.jpg",
            "Pictures/room2.jpg",
            "Pictures/room3.jpg"
        ]
    },
    philsca: { // Changed from 'trademark' to match the ID
        currentIndex: 0,
        images: [
            "https://www.philsca.edu.ph/wp-content/uploads/2023/01/bg2-1536x861.jpg",
            "https://www.philsca.edu.ph/wp-content/uploads/2023/01/basa.jpg",
            "https://www.philsca.edu.ph/wp-content/uploads/2023/01/fab-1536x864.png"
        ]
    }
};

window.prevImage = function(slideshowId) {
    const slideshow = slideshowManager[slideshowId];
    slideshow.currentIndex = (slideshow.currentIndex - 1 + slideshow.images.length) % slideshow.images.length;
    updateSlideshow(slideshowId);
};

window.nextImage = function(slideshowId) {
    const slideshow = slideshowManager[slideshowId];
    slideshow.currentIndex = (slideshow.currentIndex + 1) % slideshow.images.length;
    updateSlideshow(slideshowId);
};

function updateSlideshow(slideshowId) {
    const slideshow = slideshowManager[slideshowId];
    const mainImage = document.getElementById(`slideshow-main-${slideshowId}`);
    if (mainImage) {
        mainImage.src = slideshow.images[slideshow.currentIndex];
    }
}

L.marker([14.982806366091523, 120.48461305712443], { icon: apartmentIcon })
    .addTo(map)
    .bindPopup(`
        <div class="popup-content">
            <div class="popup-header">
                <p class="apartment-title">Vita <span>Vista</span> Apartment</p>
                <p><i class="fa-solid fa-phone" style="color: #049f33;"></i> (123) 456-7890</p>
                <p><i class="fa-solid fa-location-dot" style="color: gold;"></i> 1234 Example St, City, Country</p>
            </div>
            <div id="slideshow-container-apartment">
                <img id="slideshow-main-apartment" src="${slideshowManager.apartment.images[0]}" 
                     alt="Vita Villa Apartment" style="width: 100%; border-radius: 10px;">
                <div class="slideshow-controls">
                    <button onclick="prevImage('apartment')">&#10094;</button>
                    <button onclick="nextImage('apartment')">&#10095;</button>
                </div>
            </div>
        </div>`,
        { maxWidth: 600, minWidth: 500, maxHeight: 400 }
    );

L.marker([14.98174147669493, 120.48330289716195], { icon: tradeMarkIcon })
    .addTo(map)
    .bindPopup(`
        <div class="popup-content">
            <div class="popup-header">
                <p class="apartment-title" style="font-size: 1rem">Philippine State College of Aeronautics Palmayo Campus</p>
                <p><i class="fa-solid fa-phone" style="color: #049f33;"></i> 09758257308</p>
                <p><i class="fa-solid fa-location-dot" style="color: gold;"></i> XFJM+J8 Floridablanca, Pampanga</p>
            </div>
            <div id="slideshow-container-philsca">
                <img id="slideshow-main-philsca" src="${slideshowManager.philsca.images[0]}" 
                     alt="PhilSCA Campus" style="width: 100%; border-radius: 10px;">
                <div class="slideshow-controls">
                    <button onclick="prevImage('philsca')">&#10094;</button>
                    <button onclick="nextImage('philsca')">&#10095;</button>
                </div>
            </div>
        </div>`,
        { maxWidth: 600, minWidth: 500, maxHeight: 400 }
    );

setTimeout(() => {
    map.invalidateSize();
}, 100);
}


