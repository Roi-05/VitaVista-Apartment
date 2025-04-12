function changeImage(newSrc) {
    document.getElementById('mainImage').src = newSrc;
}

const map = L.map('map').setView([14.982806366091523, 120.48461305712443], 15);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
L.marker([14.982806366091523, 120.48461305712443])
    .addTo(map)
    .bindPopup('Vita Villa Apartment');

// Ensure the map resizes properly
setTimeout(() => {
    map.invalidateSize();
}, 100);