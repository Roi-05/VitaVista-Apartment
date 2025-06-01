document.addEventListener('DOMContentLoaded', () => {
  console.log('Script loaded');

  const bookingSection = document.querySelector('.booking-section');
  if (!bookingSection) {
    console.error('Booking section not found');
    return;
  }

  const pricePerNight = parseFloat(bookingSection.dataset.pricePerNight);
  const isUserSignedIn = document.body.dataset.userSignedIn === 'true';
  const isAdmin = document.body.dataset.userRole === 'admin';
  console.log('Is user signed in:', isUserSignedIn);
  console.log('Is admin:', isAdmin);

  const checkInInput = document.getElementById('check-in');
  const checkOutInput = document.getElementById('check-out');
  const bookroomsSelect = document.getElementById('bookrooms');
  const bookButton = document.querySelector('.book-button');
  const modal = document.getElementById('popup-modal');
  const modalMessage = document.getElementById('modal-message');
  const modalIcon = modal.querySelector('.modal-icon');
  const closeModalButton = document.getElementById('close-modal');
  const loadingSpinner = document.getElementById('loading-spinner');

  if (!checkInInput || !checkOutInput || !bookroomsSelect || !bookButton || !modal || !modalMessage || !closeModalButton || !loadingSpinner) {
    console.error('One or more required elements are missing');
    return;
  }

  // Initialize Flatpickr
  const checkInPicker = flatpickr(checkInInput, { dateFormat: 'Y-m-d' });
  const checkOutPicker = flatpickr(checkOutInput, { dateFormat: 'Y-m-d' });

  function showLoading() {
    loadingSpinner.style.display = 'flex';
  }

  function hideLoading() {
    loadingSpinner.style.display = 'none';
  }

  function showModal(message, isSuccess = true) {
    console.log('Showing modal:', message);
    modalMessage.textContent = message;
    modalIcon.className = 'modal-icon ' + (isSuccess ? 'success' : 'error');
    modal.style.display = 'flex';
  }

  function hideModal() {
    modal.style.display = 'none';
  }

  function getDisabledDatesForUnit(unit) {
    const disabledDates = [];

    for (const booking of window.existingBookings || []) {
      if (booking.unit === unit) {
        let currentDate = new Date(booking.check_in_date);
        const endDate = new Date(booking.check_out_date);

        while (currentDate <= endDate) {
          disabledDates.push(currentDate.toISOString().split('T')[0]);
          currentDate.setDate(currentDate.getDate() + 1);
        }
      }
    }

    console.log('Disabled dates for unit:', unit, disabledDates);
    return disabledDates;
  }

  function updateFlatpickr(unit) {
    const disabledDates = getDisabledDatesForUnit(unit);

    checkInPicker.set('disable', disabledDates);
    checkOutPicker.set('disable', disabledDates);
  }

  bookroomsSelect.addEventListener('change', () => {
    const selectedUnit = bookroomsSelect.value;
    updateFlatpickr(selectedUnit);
  });

  // Initialize flatpickr for default selected unit
  updateFlatpickr(bookroomsSelect.value);

  function calculatePrice(pricePerNight) {
    const checkInDate = checkInInput.value;
    const checkOutDate = checkOutInput.value;
    const numberOfRooms = parseInt(bookroomsSelect.value);

    if (checkInDate && checkOutDate) {
      const checkIn = new Date(checkInDate);
      const checkOut = new Date(checkOutDate);
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      // Validate dates
      if (checkIn < today || checkOut < today) {
        showModal('Booking dates cannot be in the past.', false);
        document.getElementById('total-price').textContent = '0';
        document.getElementById('total-nights').textContent = '0';
        return;
      }

      if (checkOut <= checkIn) {
        showModal('Check-out date must be after the check-in date.', false);
        document.getElementById('total-price').textContent = '0';
        document.getElementById('total-nights').textContent = '0';
        return;
      }

      // Calculate the number of nights
      const nights = (checkOut - checkIn) / (1000 * 60 * 60 * 24);

      // Calculate the total price
      const totalPrice = nights * pricePerNight;

      // Update the UI
      document.getElementById('total-price').textContent = totalPrice.toLocaleString();
      document.getElementById('total-nights').textContent = nights;
    } else {
      // Reset the price and nights if dates are invalid
      document.getElementById('total-price').textContent = '0';
      document.getElementById('total-nights').textContent = '0';
    }
  }

  checkInInput.addEventListener('change', () => calculatePrice(pricePerNight));
  checkOutInput.addEventListener('change', () => calculatePrice(pricePerNight));
  bookroomsSelect.addEventListener('change', () => calculatePrice(pricePerNight));

  let apartmentId = null;

  bookButton.addEventListener('click', async () => {
    if (!isUserSignedIn) {
      showModal('You must be signed in to book.', false);
      return;
    }

    const checkIn = checkInInput.value;
    const checkOut = checkOutInput.value;
    const totalPrice = document.getElementById('total-price').textContent;
    const unitSelect = document.getElementById('bookrooms');
    const apartmentType = document.body.dataset.apartmentType;
    const selectedOption = unitSelect.options[unitSelect.selectedIndex];
    const unitNo = document.querySelector('.unit-number');

    const unitData = {
      unit: unitSelect.value,
      apartmentType: apartmentType,
      available: !selectedOption.disabled
    };
    unitNo.textContent = unitData.unit;
    console.log('Unit data:', unitData);
    console.log('Apartment type:', apartmentType);
    console.log('Selected option:', selectedOption);

    try {
      showLoading();
      // Fetch apartment mapping from the server
      const response = await fetch('get_apartment_mapping.php');
      const data = await response.json();
      
      if (!data.success) {
        throw new Error(data.message || 'Failed to fetch apartment mapping');
      }

      // Find the apartmentId based on the selected type and unit
      const apartment = data.apartments.find(
        (item) => item.type === apartmentType && item.unit === unitData.unit
      );

      if (!apartment) {
        throw new Error('Apartment not found');
      }

      apartmentId = apartment.id;

      if (!unitData.available) {
        showModal('Please select an available unit', false);
        return;
      }

      if (!checkIn || !checkOut || totalPrice === '0') {
        showModal('Please select valid dates and rooms before booking.', false);
        return;
      }

      // Update payment modal with details
      document.getElementById('modal-checkin').textContent = checkIn;
      document.getElementById('modal-checkout').textContent = checkOut;
      document.getElementById('modal-total-price').textContent = totalPrice;

      // Show payment modal
      const paymentModal = document.getElementById('payment-modal');
      paymentModal.style.display = 'block';
    } catch (error) {
      console.error('Error:', error);
      showModal(error.message || 'An error occurred while processing your booking.', false);
    } finally {
      hideLoading();
    }
  });

  // Handle form submission
  const paymentForm = document.getElementById('payment-form');
  paymentForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const paymentMethod = document.getElementById('payment-method').value;
    if (!paymentMethod) {
      showModal('Please select a payment method', false);
      return;
    }

    const userId = document.body.dataset.userId;
    const bookingData = {
      userId: userId,
      apartmentId: apartmentId,
      checkIn: document.getElementById('check-in').value,
      checkOut: document.getElementById('check-out').value,
      totalPrice: document.getElementById('total-price').textContent.replace(/,/g, ''),
      paymentMethod: paymentMethod,
      numberOfRooms: document.getElementById('bookrooms').value,
      bookingType: isAdmin ? 'onsite' : 'online',
      paymentStatus: isAdmin ? 'pending' : 'paid'
    };

    try {
      showLoading();
      const response = await fetch('booking_handler.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(bookingData)
      });

      const result = await response.json();

      if (result.success) {
        // Update wallet balance if available
        const walletBalanceElement = document.querySelector('.wallet-balance p');
        if (walletBalanceElement && result.newBalance !== undefined) {
          walletBalanceElement.textContent = `₱${parseFloat(result.newBalance).toLocaleString()}`;
        }

        showModal('Booking confirmed! Check your email for details.', true);
        document.getElementById('payment-modal').style.display = 'none';
        
        // Redirect to profile dashboard after 2 seconds
        setTimeout(() => {
          window.location.href = isAdmin ? 'admin_dashboard.php' : 'profile_dashboard.php';
        }, 2000);
      } else {
        if (result.message.includes('Insufficient wallet balance')) {
          showModal('Insufficient wallet balance. Please deposit more funds before booking.', false);
          // Redirect to wallet section after 2 seconds
          setTimeout(() => {
            window.location.href = 'profile_dashboard.php#wallet';
          }, 2000);
        } else {
          showModal(result.message, false);
        }
      }
    } catch (error) {
      console.error('Error:', error);
      showModal('An error occurred during booking. Please try again.', false);
    } finally {
      hideLoading();
    }
  });

  // Close the payment modal
  const closePaymentModalButton = document.getElementById('close-payment-modal');
  closePaymentModalButton.addEventListener('click', () => {
    const paymentModal = document.getElementById('payment-modal');
    paymentModal.style.display = 'none';
  });

  closeModalButton.addEventListener('click', hideModal);
});

