function changeImage(newSrc) {
    document.getElementById('mainImage').src = newSrc;
}

window.onload = function () {
    const map = L.map('map').setView([15.04672281044628, 120.53928483798512], 50);

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
            "Pictures/main_apt.jpeg",
            "Pictures/room_2.jpeg",
            "Pictures/room.jpeg"
        ]
    },
    dhvsu: { // Changed from 'trademark' to match the ID
        currentIndex: 0,
        images: [
            "https://centralluzon.politiko.com.ph/wp-content/uploads/2022/07/dhvsu.jpg"
        ]
    },
    Jaylord: { // Changed from 'trademark' to match the ID
        currentIndex: 0,
        images: [
            "https://scontent.fmnl4-7.fna.fbcdn.net/v/t1.15752-9/490993662_687521160496904_461312470324144195_n.png?_nc_cat=111&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeEPzCEtXAGW99PJ0ohHgB6bHFgWiWGSjqEcWBaJYZKOoYpDsp0qeTCs18bqrvwjzS_0gc-qjWPpIZyzhiDB7jJq&_nc_ohc=vyVvrMZf0JMQ7kNvwFqT3eh&_nc_oc=AdmHH0jNLp__-40HeUD59RKNhU7kbLBtiVet0eu8UiyqBEc4pCndicW0uWzKPT2ht_DptDOPPQ_g2oe-CiVJMHY8&_nc_zt=23&_nc_ht=scontent.fmnl4-7.fna&oh=03_Q7cD2AF3fRvnGEcNMBqhqUrIJ6YndpgbYiWlCaQAFYNUjjhltQ&oe=682DD236",
            "https://scontent.fmnl4-4.fna.fbcdn.net/v/t1.15752-9/486104511_1613528156060045_4836050190801713640_n.png?_nc_cat=100&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeHcaq6CRLh9zA8wA4k1PGXzFYqMF_I5FS8ViowX8jkVL6nqDw5mbNNy9hhKJ_5_0nejSbnpwOH-fh1BxRDpj1MY&_nc_ohc=ZEybUZk_SO8Q7kNvwE1at7F&_nc_oc=AdntMq_6uzEedgk0PPh-sozz9uGg0KpJQlOAifqmfaBJjwpdA9LQaFJTn50N5Ya9BQLZdJj85Gn9YmiDBQc1O_93&_nc_zt=23&_nc_ht=scontent.fmnl4-4.fna&oh=03_Q7cD2AFlJw2QB0Yad6vHKNyMOaBeozkqApCHwQEFhQRmYPi5yw&oe=682DDBC1"
        ]
    },
    mel: { // Changed from 'trademark' to match the ID
        currentIndex: 0,
        images: [
            "https://scontent.fmnl4-3.fna.fbcdn.net/v/t1.15752-9/490680293_742089461475317_8781521118642272879_n.png?_nc_cat=110&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeGBuW6AtWFf_Sdtuny4WCmzRzbjhw-E0YFHNuOHD4TRgWkT9QXQE0fqSI58wMEkehUGHn471BB8FG8cRvOqHogO&_nc_ohc=War8BKVFTwcQ7kNvwFxOaYW&_nc_oc=AdmcMp1hD0B3wBH-H89o0GFhmlW3l4TmUtPC3iCwOv4W22W6gvhQWA-yGItNlce9KZVjGwbLdIeKZZaBcnbuKe49&_nc_zt=23&_nc_ht=scontent.fmnl4-3.fna&oh=03_Q7cD2AFJeVrO7cLPyY8HEZoUtj8kNsrUT4jKh-uh4dygA12u4Q&oe=682DE1C7", // Replace with the actual path
            "https://scontent.fmnl4-4.fna.fbcdn.net/v/t1.15752-9/490976261_1202813944552381_8332798786151154861_n.png?_nc_cat=102&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeGOKXcmxuyJQu1y_AfVAOMEVmn2R3s-KAFWafZHez4oAdsDZocbJMEf-DAvLU-WmOcNL3TKiBpQwE-c4_QGhTzp&_nc_ohc=G0S2FOvfhzYQ7kNvwHjukFt&_nc_oc=AdlIfzsCwAHFYfkT71EckTTFUQxH3rdNa7BHtKQNGEgFeetoRhQwslcCrWa7aQrjg5utJswgFq2erZH3VmVL5Jip&_nc_zt=23&_nc_ht=scontent.fmnl4-4.fna&oh=03_Q7cD2AHHUETf_zP5tYQo5UmDbNArx70COPwPcYCKUTj5gq9CwQ&oe=682DB982"  // Replace with the actual path
        ]
    },
    jayvee: { // Changed from 'trademark' to match the ID
        currentIndex: 0,
        images: [
            "https://scontent.fmnl4-2.fna.fbcdn.net/v/t1.15752-9/490993090_1116132523649555_3828756529878466567_n.png?_nc_cat=107&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeFKXdwhvQsXSXmsM5_DMl4HAIQtixaZL2AAhC2LFpkvYEVMEnBuyqzv4je7yc0IvuXVf9YzMgIL4BX74qvJypQZ&_nc_ohc=77aT4IER8N4Q7kNvwG4hF2y&_nc_oc=Admv48W4tXGm9kLG7Fxt-cYxaLuRO0jrnPjJdUSVeMbTYMYQsZTKP7NTtJ81QV6JPuL2qceiOB1nDSQ4D5EGNz4z&_nc_zt=23&_nc_ht=scontent.fmnl4-2.fna&oh=03_Q7cD2AFAv1fMMTI2qsNYsxHEGn5J0ItIFxl_hl4pZWqte6Odug&oe=682DBFF4", // Replace with the actual path
            "https://scontent.fmnl4-7.fna.fbcdn.net/v/t1.15752-9/490997929_1706132003630340_1322352625026994742_n.png?_nc_cat=108&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeGQWjo_7DglUsnyzMzIBVckWN7v8gSzuJBY3u_yBLO4kK8euD79cNe5X_lg40D3vmwRCxWeREyIWLlx2ptq7jcj&_nc_ohc=MTXv5cJLHskQ7kNvwEjqN6S&_nc_oc=Adk8mdZd1_cUuZ5pgyOyEh3eSr7C8BydpUcjjMJVjL-FuMDeRMgKoQZO7192l5TLmrKtb14eTu7B6BgRxLxTMhNK&_nc_zt=23&_nc_ht=scontent.fmnl4-7.fna&oh=03_Q7cD2AFlk5E_X-ce3rkvT7sLW7dAkfcS5SQl09ZjUNkrvx97Mw&oe=682DC580"  // Replace with the actual path
        ]
    },
    roku: { // Changed from 'trademark' to match the ID
        currentIndex: 0,
        images: [
            "https://scontent.fmnl4-2.fna.fbcdn.net/v/t1.15752-9/483699766_1135450125289051_3354091417767486910_n.png?_nc_cat=107&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeHxBd8O77n_nOS0WpbbkLpekEVqWL1NSfWQRWpYvU1J9WTeIgmkrgdUsSCkcSRAP49T_xJ4OgYhJDsb9FyA1f8G&_nc_ohc=4AMfQz_3V4wQ7kNvwHUdR4w&_nc_oc=AdkXmKKF3Vv1I2gHez_KyKPLPcb5RkFwSKQTCA_m02uHrsgwzPY3UEVN1zDytqwZ8h5d_C05MHcxIoKfY-CU8Zoq&_nc_zt=23&_nc_ht=scontent.fmnl4-2.fna&oh=03_Q7cD2AF1ClHoeUy19R8XC2FdV0U-U-Kb21GutPzBn8dAVPn7Ow&oe=682DB340", // Replace with the actual path
            "https://scontent.fmnl4-2.fna.fbcdn.net/v/t1.15752-9/490986540_2315875798870712_8056130910510411213_n.png?_nc_cat=101&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeE6Wive-XwsQqq7Da-GimqVUApFBBXBotFQCkUEFcGi0UhlOs1Eo8OP83R90fc8fWlSkEXbdgHEiIZhsFUcyUUm&_nc_ohc=6NRTZxkxv8UQ7kNvwGBI4VI&_nc_oc=AdlGnsXEOueXfg9dz_sS-L1ioU7p--6k9yvdR1sz8Zdn1dci9hPoURlRT9FPbTcMdC5QlJYsJBsfZJWEKgUTLSVC&_nc_zt=23&_nc_ht=scontent.fmnl4-2.fna&oh=03_Q7cD2AFL5hl78JtSOw1bYT1ypMYQP8ywXYlZ3aCZcQ51EOvZBg&oe=682DDFA8"  // Replace with the actual path
        ]
    },
    roi: { // Changed from 'trademark' to match the ID
        currentIndex: 0,
        images: [
            "https://scontent.fmnl4-2.fna.fbcdn.net/v/t1.15752-9/491281967_2849771781874362_2865005064820500546_n.png?_nc_cat=105&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeEti6ceTwkTAmb09EOqSK2Aw-0qfe6Kh-TD7Sp97oqH5EtFfMnj3tPvykke5t5P0jvXIfbhy-Uk6xVzxVLiD6F5&_nc_ohc=R82PBdS765gQ7kNvwGUt8Fm&_nc_oc=AdksfzrK9ZavS3pBwics5OlwTlKG-IvVLEMsxAeFovSloQsXDOosWeVwrbY_oF1K9oXP4DboF-lC7ocuvHMW8XxG&_nc_zt=23&_nc_ht=scontent.fmnl4-2.fna&oh=03_Q7cD2AH8eku6-wzl6f49pvaRJ9QhohbgMhOleRHDw77HdsYTKA&oe=682DD308", // Replace with the actual path
            "https://scontent.fmnl4-2.fna.fbcdn.net/v/t1.15752-9/491281967_2849771781874362_2865005064820500546_n.png?_nc_cat=105&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeEti6ceTwkTAmb09EOqSK2Aw-0qfe6Kh-TD7Sp97oqH5EtFfMnj3tPvykke5t5P0jvXIfbhy-Uk6xVzxVLiD6F5&_nc_ohc=R82PBdS765gQ7kNvwGUt8Fm&_nc_oc=AdksfzrK9ZavS3pBwics5OlwTlKG-IvVLEMsxAeFovSloQsXDOosWeVwrbY_oF1K9oXP4DboF-lC7ocuvHMW8XxG&_nc_zt=23&_nc_ht=scontent.fmnl4-2.fna&oh=03_Q7cD2AH8eku6-wzl6f49pvaRJ9QhohbgMhOleRHDw77HdsYTKA&oe=682DD308"  // Replace with the actual path
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

L.marker([15.044500386520967, 120.52870465191211], { icon: tradeMarkIcon })
    .addTo(map)
    .bindPopup(`
        <div class="popup-content">
            <div class="popup-header">
                <p class="apartment-title" style="font-size: 1rem">Don Honorio Ventura State University</p>
                <p><i class="fa-solid fa-phone" style="color: #049f33;"></i> 09758257308</p>
                <p><i class="fa-solid fa-location-dot" style="color: red;"></i> XFJM+J8 Floridablanca, Pampanga</p>
            </div>
            <div id="slideshow-container-dhvsu">
                <img id="slideshow-main-dhvsu" src="${slideshowManager.dhvsu.images[0]}" 
                     alt="dhvsu Campus" style="width: 100%; border-radius: 10px;">
                <div class="slideshow-controls">
                    <button onclick="prevImage('dhvsu')">&#10094;</button>
                    <button onclick="nextImage('dhvsu')">&#10095;</button>
                </div>
            </div>
        </div>`,
        { maxWidth: 600, minWidth: 500, maxHeight: 400 }
    );

L.marker([15.026272, 120.562684], { icon: tradeMarkIcon })
    .addTo(map)
    .bindPopup(`
        <div class="popup-content">
            <div class="popup-header">
                <p class="apartment-title" style="font-size: 1rem">Jaylord's Mansion</p>
                <p><i class="fa-solid fa-phone" style="color: #049f33;"></i> 09758257308</p>
                <p><i class="fa-solid fa-location-dot" style="color: red;"></i> Pias, Porac, Pampanga</p>
            </div>
            <div id="slideshow-container-Jaylord">
                <img id="slideshow-main-Jaylord" src="${slideshowManager.Jaylord.images[0]}" 
                     alt="Jaylord Campus" style="width: 100%; border-radius: 10px;">
                <div class="slideshow-controls">
                    <button onclick="prevImage('Jaylord')">&#10094;</button>
                    <button onclick="nextImage('Jaylord')">&#10095;</button>
                </div>
            </div>
        </div>`,
        { maxWidth: 600, minWidth: 500, maxHeight: 400 }
    );

    L.marker([15.011404, 120.581731], { icon: tradeMarkIcon })
    .addTo(map)
    .bindPopup(`
        <div class="popup-content">
            <div class="popup-header">
                <p class="apartment-title" style="font-size: 1rem">Mel's Mansion</p>
                <p><i class="fa-solid fa-phone" style="color: #049f33;"></i> 09758257308</p>
                <p><i class="fa-solid fa-location-dot" style="color: red;"></i> Pias, Porac, Pampanga</p>
            </div>
            <div id="slideshow-container-mel">
                <img id="slideshow-main-mel" src="${slideshowManager.mel.images[0]}" 
                     alt="mel Campus" style="width: 100%; border-radius: 10px;">
                <div class="slideshow-controls">
                    <button onclick="prevImage('mel')">&#10094;</button>
                    <button onclick="nextImage('mel')">&#10095;</button>
                </div>
            </div>
        </div>`,
        { maxWidth: 600, minWidth: 500, maxHeight: 400 }
    );

L.marker([15.016280933980243, 120.56211301165017], { icon: tradeMarkIcon })
    .addTo(map)
    .bindPopup(`
        <div class="popup-content">
            <div class="popup-header">
                <p class="apartment-title" style="font-size: 1rem">Jayvee's Mansion</p>
                <p><i class="fa-solid fa-phone" style="color: #049f33;"></i> 09758257308</p>
                <p><i class="fa-solid fa-location-dot" style="color: red;"></i> Pias, Porac, Pampanga</p>
            </div>
            <div id="slideshow-container-jayvee">
                <img id="slideshow-main-jayvee" src="${slideshowManager.jayvee.images[0]}" 
                     alt="jayvee Campus" style="width: 100%; border-radius: 10px;">
                <div class="slideshow-controls">
                    <button onclick="prevImage('jayvee')">&#10094;</button>
                    <button onclick="nextImage('jayvee')">&#10095;</button>
                </div>
            </div>
        </div>`,
        { maxWidth: 600, minWidth: 500, maxHeight: 400 }
    );

L.marker([15.043631, 120.528121], { icon: tradeMarkIcon })
    .addTo(map)
    .bindPopup(`
        <div class="popup-content">
            <div class="popup-header">
                <p class="apartment-title" style="font-size: 1rem">roku's Mansion</p>
                <p><i class="fa-solid fa-phone" style="color: #049f33;"></i> 09758257308</p>
                <p><i class="fa-solid fa-location-dot" style="color: red;"></i> Pias, Porac, Pampanga</p>
            </div>
            <div id="slideshow-container-roku">
                <img id="slideshow-main-roku" src="${slideshowManager.roku.images[0]}" 
                     alt="roku Campus" style="width: 100%; border-radius: 10px;">
                <div class="slideshow-controls">
                    <button onclick="prevImage('roku')">&#10094;</button>
                    <button onclick="nextImage('roku')">&#10095;</button>
                </div>
            </div>
        </div>`,
        { maxWidth: 600, minWidth: 500, maxHeight: 400 }
    );

L.marker([14.978196728709467, 120.50317486776413], { icon: tradeMarkIcon })
    .addTo(map)
    .bindPopup(`
        <div class="popup-content">
            <div class="popup-header">
                <p class="apartment-title" style="font-size: 1rem">Roi's Mansion</p>
                <p><i class="fa-solid fa-phone" style="color: #049f33;"></i> 09758257308</p>
                <p><i class="fa-solid fa-location-dot" style="color: red;"></i> Pias, Porac, Pampanga</p>
            </div>
            <div id="slideshow-container-roi">
                <img id="slideshow-main-roi" src="${slideshowManager.roi.images[0]}" 
                     alt="roi Campus" style="width: 100%; border-radius: 10px;">
                <div class="slideshow-controls">
                    <button onclick="prevImage('roi')">&#10094;</button>
                    <button onclick="nextImage('roi')">&#10095;</button>
                </div>
            </div>
        </div>`,
        { maxWidth: 600, minWidth: 500, maxHeight: 400 }
    );

L.marker([15.04672281044628, 120.53928483798512], { icon: apartmentIcon })
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


setTimeout(() => {
    map.invalidateSize();
}, 100);
}

