function changeImage(newSrc) {
    document.getElementById('mainImage').src = newSrc;
}

window.onload = function () {
    const map = L.map('map').setView([15.068899, 120.542461], 50);

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

const slideshowManager = {
    apartment: {
        currentIndex: 0,
        images: [
            "/Pictures/vita_vista.jpg",
            "/Pictures/studiotype/1.avif",
            "/Pictures/1_bedroom/1.png",
            "/Pictures/2_bedroom/9.png",
            "/Pictures/penthouse/1.avif"
        ]
    },
    baboSacan: { // Changed from 'trademark' to match the ID
        currentIndex: 0,
        images: [
            "https://streetviewpixels-pa.googleapis.com/v1/thumbnail?panoid=LuZPKVt1HEJCP1q2BK-S_g&cb_client=search.gws-prod.gps&w=408&h=240&yaw=315.85455&pitch=0&thumbfov=100"
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

L.marker([15.068899, 120.542461], { icon: apartmentIcon })
    .addTo(map)
    .bindPopup(`
        <div class="popup-content">
            <div class="popup-header">
                <p class="apartment-title">Vita <span>Vista</span> Apartment</p>
                <p><i class="fa-solid fa-phone" style="color: #049f33;"></i> (123) 456-7890</p>
                <p><i class="fa-solid fa-location-dot" style="color: gold;"></i> Porac Pampanga</p>
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

L.marker([15.068475953777517, 120.54288704773174], { icon: tradeMarkIcon })
    .addTo(map)
    .bindPopup(`
        <div class="popup-content">
            <div class="popup-header">
                <p class="apartment-title">Babo Sacan Elementary School</p>
                <p><i class="fa-solid fa-phone" style="color: #049f33;"></i> (123) 456-7890</p>
                <p><i class="fa-solid fa-location-dot" style="color: red;"></i> Babo Sacan, Porac,Pampanga</p>
            </div>
            <div id="slideshow-container-apartment">
                <img id="slideshow-main-apartment" src="${slideshowManager.baboSacan.images[0]}" 
                     alt="Vita Villa Apartment" style="width: 100%; border-radius: 10px;">
                <div class="slideshow-controls">
                    <button onclick="prevImage('baboSacan')">&#10094;</button>
                    <button onclick="nextImage('baboSacan')">&#10095;</button>
                </div>
            </div>
        </div>`,
        { maxWidth: 600, minWidth: 500, maxHeight: 400 }
    );


setTimeout(() => {
    map.invalidateSize();
}, 100);
}

