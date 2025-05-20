document.addEventListener('DOMContentLoaded', function() {
    // Navigation
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.content-section');

    navItems.forEach(item => {
        item.addEventListener('click', function() {
            if (!item.dataset.target) return;
            navItems.forEach(nav => nav.classList.remove('active'));
            sections.forEach(content => content.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(this.dataset.target).classList.add('active');
        });
    });

    // Profile Picture Upload
    const profilePic = document.getElementById('profilePic');
    const modal = document.getElementById('profilePicModal');
    const closeBtn = modal.querySelector('.close');
    const uploadArea = modal.querySelector('.upload-area');
    const fileInput = document.getElementById('profilePicInput');

    profilePic.addEventListener('click', () => {
        modal.style.display = 'block';
    });

    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    uploadArea.addEventListener('click', () => {
        fileInput.click();
    });

    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            handleProfilePicture(file);
        }
    });

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (file) {
            handleProfilePicture(file);
        }
    });

    // Booking Filters
    const filterBtns = document.querySelectorAll('.filter-btn');
    const bookingCards = document.querySelectorAll('.booking-card');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.dataset.filter;
            
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            bookingCards.forEach(card => {
                if (filter === 'all' || card.dataset.status === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Form Validation
    const passwordForm = document.querySelector('form[name="change_password"]');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPass = document.getElementById('new_password').value;
            const confirmPass = document.getElementById('confirm_password').value;

            if (newPass !== confirmPass) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });
    }
});

// Handle Profile Picture Upload
function handleProfilePicture(file) {
    const formData = new FormData();
    formData.append('profile_picture', file);

    fetch('upload_profile_picture.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('profilePic').src = data.url;
            document.getElementById('profilePicModal').style.display = 'none';
        } else {
            alert('Error uploading profile picture: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error uploading profile picture');
    });
}

// Cancel Booking
function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?')) {
        fetch('cancel_booking.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ booking_id: bookingId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the booking card from the UI
                const card = document.querySelector(`[data-booking-id="${bookingId}"]`);
                if (card) {
                    card.remove();
                }
                alert('Booking cancelled successfully');
            } else {
                alert('Error cancelling booking: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error cancelling booking');
        });
    }
}

// Update notification preferences
function updateNotificationPreferences(preferences) {
    fetch('update_notifications.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(preferences)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Notification preferences updated successfully');
        } else {
            alert('Error updating preferences: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating notification preferences');
    });
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
}

// Format date
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}