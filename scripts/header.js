document.addEventListener('DOMContentLoaded', () => {
    const profileContainer = document.querySelector('.profile-container');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    if (profileContainer && dropdownMenu) {
        profileContainer.addEventListener('click', (e) => {
            e.stopPropagation();
            profileContainer.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!profileContainer.contains(e.target)) {
                profileContainer.classList.remove('active');
            }
        });
    }
});
