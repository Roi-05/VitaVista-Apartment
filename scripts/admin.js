document.addEventListener('DOMContentLoaded', function() {
    // Navigation
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.content-section');

    navItems.forEach(item => {
        item.addEventListener('click', function() {
            if (!item.dataset.target) return;
            navItems.forEach(i => i.classList.remove('active'));
            sections.forEach(sec => sec.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(this.dataset.target).classList.add('active');
        });
    });

    // Charts
    setupCharts();

    // Search functionality
    const userSearch = document.getElementById('userSearch');
    if (userSearch) {
        userSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#users tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // Modal functionality
    const modals = document.querySelectorAll('.modal');
    const closeBtns = document.querySelectorAll('.close');

    closeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            modal.style.display = 'none';
        });
    });

    window.addEventListener('click', function(e) {
        modals.forEach(modal => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    });

    // Load bookings when the bookings section is shown
    const bookingsSection = document.getElementById('bookings');
    if (bookingsSection) {
        loadBookings();
    }
});

// Chart setup
async function setupCharts() {
    try {
        const response = await fetch('get_chart_data.php');
        const data = await response.json();

        if (!data.success) {
            console.error('Failed to fetch chart data:', data.message);
            return;
        }

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart')?.getContext('2d');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                    labels: data.revenue.labels,
                datasets: [{
                    label: 'Revenue',
                        data: data.revenue.data,
                    borderColor: '#001166',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                }
            }
        });
    }

    // Booking Chart
    const bookingCtx = document.getElementById('bookingChart')?.getContext('2d');
    if (bookingCtx) {
        new Chart(bookingCtx, {
            type: 'bar',
            data: {
                    labels: data.bookingStats.labels,
                datasets: [{
                    label: 'Bookings',
                        data: data.bookingStats.data,
                    backgroundColor: 'gold'
                }]
            },
            options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
            }
        });
    }

    // Apartment Chart
    const apartmentCtx = document.getElementById('apartmentChart')?.getContext('2d');
    if (apartmentCtx) {
        new Chart(apartmentCtx, {
            type: 'doughnut',
            data: {
                    labels: data.popularApartments.labels,
                datasets: [{
                        data: data.popularApartments.data,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
                }]
            },
            options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
            }
        });
    }

    // User Growth Chart
    const userCtx = document.getElementById('userChart')?.getContext('2d');
    if (userCtx) {
        new Chart(userCtx, {
            type: 'line',
            data: {
                    labels: data.userGrowth.labels,
                datasets: [{
                    label: 'New Users',
                        data: data.userGrowth.data,
                    borderColor: '#4BC0C0',
                        tension: 0.4,
                        fill: true,
                        backgroundColor: 'rgba(75, 192, 192, 0.1)'
                }]
            },
            options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
            }
        });
        }
    } catch (error) {
        console.error('Error setting up charts:', error);
    }
}

// Form Validation Functions
function validateApartmentForm(formData) {
    const errors = [];
    
    if (!formData.get('type')) {
        errors.push('Apartment type is required');
    }
    
    const unit = formData.get('unit');
    if (!unit) {
        errors.push('Unit number is required');
    } else if (!/^Unit\s+\d+$/.test(unit)) {
        errors.push('Unit must be in the format "Unit" followed by a number (e.g., "Unit 1")');
    }
    
    const price = parseFloat(formData.get('price'));
    if (isNaN(price) || price <= 0) {
        errors.push('Price must be greater than 0');
    }
    return errors;
}

function validateBookingForm(formData) {
    const errors = [];
    
    if (!formData.get('user')) {
        errors.push('User selection is required');
    }
    
    if (!formData.get('apartment')) {
        errors.push('Apartment selection is required');
    }
    
    const checkin = new Date(formData.get('checkin'));
    const checkout = new Date(formData.get('checkout'));
    const today = new Date();
    
    if (isNaN(checkin.getTime())) {
        errors.push('Invalid check-in date');
    } else if (checkin < today) {
        errors.push('Check-in date cannot be in the past');
    }
    
    if (isNaN(checkout.getTime())) {
        errors.push('Invalid check-out date');
    } else if (checkout <= checkin) {
        errors.push('Check-out date must be after check-in date');
    }
    
    return errors;
}

// Enhanced Modal Functions
function showAddApartmentModal() {
    const modal = document.getElementById('apartmentModal');
    const form = document.getElementById('apartmentForm');
    
    // Clear form
    form.innerHTML = `
        <div class="form-group">
            <label for="type">Apartment Type</label>
            <select id="type" name="type" required>
                <option value="">Select Type</option>
                <option value="studio">Studio</option>
                <option value="1-bedroom">1 Bedroom</option>
                <option value="2-bedroom">2 Bedroom</option>
                <option value="penthouse">Penthouse</option>
            </select>
        </div>
        <div class="form-group">
            <label for="unit">Unit Number</label>
            <input type="text" id="unit" name="unit" required 
                pattern="Unit\\s+\\d+" 
                title="Format: 'Unit' followed by a number (e.g., 'Unit 1', 'Unit 2', 'Unit 3')"
                placeholder="e.g., Unit 1">
        </div>
        <div class="form-group">
            <label for="price">Price per Night (₱)</label>
            <input type="number" id="price" name="price" required min="0" step="0.01">
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4"></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Add Apartment</button>
            <button type="button" class="btn-secondary" onclick="closeModal('apartmentModal')">Cancel</button>
        </div>
    `;
    
    // Add form submission handler
    form.onsubmit = async function(e) {
        e.preventDefault();
        console.log('Form submission started');
        
        const formData = new FormData(form);
        console.log('Form data:', Object.fromEntries(formData));
        
        const errors = validateApartmentForm(formData);
        console.log('Validation errors:', errors);
        
        if (errors.length > 0) {
            showErrors(errors);
            return;
        }
        
        try {
            console.log('Sending request to apartment_handler.php');
            const response = await fetch('apartment_handler.php', {
                method: 'POST',
                body: formData
            });
            
            console.log('Response status:', response.status);
            const data = await response.json();
            console.log('Response data:', data);
            
            if (!response.ok) {
                throw new Error(data.message || 'Failed to add apartment');
            }
            
            showSuccess('Apartment added successfully');
            closeModal('apartmentModal');
            loadApartments(); // Refresh the apartments list
        } catch (error) {
            console.error('Error:', error);
            showError('Failed to add apartment: ' + error.message);
        }
    };
    
    modal.style.display = 'block';
}

function editApartment(id) {
    const modal = document.getElementById('apartmentModal');
    const form = document.getElementById('apartmentForm');
    
    // Clear form
    form.innerHTML = `
        <div class="form-group">
            <label for="price">Price per Night (₱)</label>
            <input type="number" id="price" name="price" required min="0" step="0.01">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Update Price</button>
            <button type="button" class="btn-secondary" onclick="closeModal('apartmentModal')">Cancel</button>
        </div>
    `;
    
    // Add form submission handler
    form.onsubmit = async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        formData.append('id', id);
        
        try {
            const response = await fetch('edit_apartment.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Failed to update apartment');
            }
            
            showSuccess('Apartment price updated successfully');
            closeModal('apartmentModal');
            loadApartments(); // Refresh the apartments list
        } catch (error) {
            showError('Failed to update apartment: ' + error.message);
        }
    };
    
    modal.style.display = 'block';
}

function deleteApartment(id) {
    if (confirm('Are you sure you want to delete this apartment?')) {
        fetch('delete_apartment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess(data.message);
                // Remove the apartment card from the UI
                const apartmentCard = document.querySelector(`.apartment-card[data-id="${id}"]`);
                if (apartmentCard) {
                    apartmentCard.remove();
                }
            } else {
                showError(data.message);
            }
        })
        .catch(error => {
            showError('Failed to delete apartment: ' + error.message);
        });
    }
}

// User Management Functions
function editUser(id) {
    // Implement edit functionality
    console.log('Editing user:', id);
}

function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user?')) {
        // Implement delete functionality
        console.log('Deleting user:', id);
    }
}

// Booking Management Functions
function showAddBookingModal() {
    const modal = document.getElementById('bookingModal');
    const form = document.getElementById('bookingForm');
    
    // Clear form
    form.innerHTML = `
        <div class="form-group">
            <label for="user">Select User</label>
            <select id="user" name="user" required>
                <option value="">Select User</option>
                <!-- Users will be loaded dynamically -->
            </select>
        </div>
        <div class="form-group">
            <label for="apartment">Select Apartment</label>
            <select id="apartment" name="apartment" required>
                <option value="">Select Apartment</option>
                <!-- Apartments will be loaded dynamically -->
            </select>
        </div>
        <div class="form-group">
            <label for="checkin">Check-in Date</label>
            <input type="date" id="checkin" name="checkin" required 
                   min="${new Date().toISOString().split('T')[0]}">
        </div>
        <div class="form-group">
            <label for="checkout">Check-out Date</label>
            <input type="date" id="checkout" name="checkout" required>
        </div>
        <div class="form-group">
            <label for="guests">Number of Guests</label>
            <input type="number" id="guests" name="guests" required min="1">
        </div>
        <div class="form-group">
            <label for="special_requests">Special Requests</label>
            <textarea id="special_requests" name="special_requests" rows="3"></textarea>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn-primary">Create Booking</button>
            <button type="button" class="btn-secondary" onclick="closeModal('bookingModal')">Cancel</button>
        </div>
    `;
    
    // Load users and apartments
    loadUsersForBooking();
    loadApartmentsForBooking();
    
    // Add form submission handler
    form.onsubmit = async function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const errors = validateBookingForm(formData);
        
        if (errors.length > 0) {
            showErrors(errors);
            return;
        }
        
        try {
            const response = await fetch('/api/bookings', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Failed to create booking');
            }
            
            showSuccess('Booking created successfully');
            closeModal('bookingModal');
            loadBookings(); // Refresh the bookings list
        } catch (error) {
            showError('Failed to create booking: ' + error.message);
        }
    };
    
    modal.style.display = 'block';
}

// AJAX Functions
function loadBookings() {
    fetch('get_bookings.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const bookingsTable = document.getElementById('bookings-table');
                bookingsTable.innerHTML = '';
                
                data.bookings.forEach(booking => {
                    const row = document.createElement('tr');
                    row.setAttribute('data-status', booking.status);
                    row.innerHTML = `
                        <td>${booking.id}</td>
                        <td>${booking.guest}</td>
                        <td>${booking.apartment}</td>
                        <td>${booking.check_in}</td>
                        <td>${booking.check_out}</td>
                        <td>${booking.amount}</td>
                        <td><span class="status-badge ${booking.status}">${booking.status}</span></td>
                        <td>
                            <button class="action-btn edit" onclick="editBooking(${booking.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn delete" onclick="deleteBooking(${booking.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    bookingsTable.appendChild(row);
                });

                // Initialize booking filters
                initializeBookingFilters();
            } else {
                console.error('Failed to load bookings:', data.error);
            }
        })
        .catch(error => {
            console.error('Error loading bookings:', error);
        });
}

function loadUsers() {
    // Implement AJAX call to load users
}

function loadApartments() {
    fetch('get_apartments.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const apartmentsGrid = document.querySelector('.apartments-grid');
                apartmentsGrid.innerHTML = '';
                
                data.apartments.forEach(apt => {
                    const card = document.createElement('div');
                    card.className = 'apartment-card';
                    card.setAttribute('data-id', apt.id);
                    
                    // Get image path based on type
                    let imagePath = '';
                    switch(apt.type.toLowerCase()) {
                        case 'studio':
                            imagePath = 'Pictures/studiotype/1.avif';
                            break;
                        case '1-bedroom':
                            imagePath = 'Pictures/1_bedroom/1.png';
                            break;
                        case '2-bedroom':
                            imagePath = 'Pictures/2_bedroom/1.png';
                            break;
                        case 'penthouse':
                            imagePath = 'Pictures/penthouse/1.avif';
                            break;
                    }
                    
                    card.innerHTML = `
                        <div class="apartment-image">
                            <img src="${imagePath}" alt="${apt.type}">
                            <span class="status-badge-apt ${apt.availability > 0 ? 'available' : 'occupied'}">
                                <i class="fas ${apt.availability > 0 ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                                ${apt.availability > 0 ? 'Available' : 'Occupied'}
                            </span>
                        </div>
                        <div class="apartment-content">
                            <div class="apartment-header">
                                <h3>${apt.type}</h3>
                                <p class="unit-number">${apt.unit}</p>
                            </div>
                            <div class="apartment-details">
                                <div class="detail-item">
                                    <i class="fas fa-peso-sign"></i>
                                    <div class="detail-info">
                                        <span class="label">Price per Night</span>
                                        <span class="value">₱${parseFloat(apt.price_per_night).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <div class="detail-info">
                                        <span class="label">Total Bookings</span>
                                        <span class="value">${apt.total_bookings || 0}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="apartment-actions">
                                <button class="edit-btn" onclick="editApartment(${apt.id})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="delete-btn" onclick="deleteApartment(${apt.id})">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    `;
                    
                    apartmentsGrid.appendChild(card);
                });
            } else {
                showError('Failed to load apartments');
            }
        })
        .catch(error => {
            console.error('Error loading apartments:', error);
            showError('Failed to load apartments');
        });
}

// Utility Functions
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'none';
}

function showErrors(errors) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.innerHTML = errors.map(error => `<p>${error}</p>`).join('');
    
    const existingError = document.querySelector('.error-message');
    if (existingError) {
        existingError.remove();
    }
    
    document.querySelector('.modal-content').insertBefore(
        errorDiv,
        document.querySelector('.modal-content').firstChild
    );
}

function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.textContent = message;
    
    document.body.appendChild(successDiv);
    setTimeout(() => successDiv.remove(), 3000);
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    document.body.appendChild(errorDiv);
    setTimeout(() => errorDiv.remove(), 3000);
}

// Data Loading Functions
async function loadUsersForBooking() {
    try {
        const response = await fetch('/api/users');
        const users = await response.json();
        
        const select = document.getElementById('user');
        users.forEach(user => {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = `${user.fullname} (${user.email})`;
            select.appendChild(option);
        });
    } catch (error) {
        showError('Failed to load users');
    }
}

async function loadApartmentsForBooking() {
    try {
        const response = await fetch('/api/apartments');
        const apartments = await response.json();
        
        const select = document.getElementById('apartment');
        apartments.forEach(apt => {
            const option = document.createElement('option');
            option.value = apt.id;
            option.textContent = `${apt.type} - ${apt.unit} (₱${apt.price_per_night}/night)`;
            select.appendChild(option);
        });
    } catch (error) {
        showError('Failed to load apartments');
    }
}

// Initialize booking filters
function initializeBookingFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const bookingRows = document.querySelectorAll('#bookings-table tr');

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            button.classList.add('active');

            const filter = button.getAttribute('data-filter');

            bookingRows.forEach(row => {
                if (filter === 'all') {
                    row.style.display = '';
                    // Add fade-in animation
                    row.style.opacity = '0';
                    setTimeout(() => {
                        row.style.opacity = '1';
                    }, 50);
                } else {
                    if (row.getAttribute('data-status') === filter) {
                        row.style.display = '';
                        // Add fade-in animation
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.style.opacity = '1';
                        }, 50);
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        });
    });
} 