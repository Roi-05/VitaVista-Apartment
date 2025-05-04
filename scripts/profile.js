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