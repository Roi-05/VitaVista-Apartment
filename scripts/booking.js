document.addEventListener('DOMContentLoaded', () => {
  console.log('Script loaded');

  const bookingSection = document.querySelector('.booking-section');
  if (!bookingSection) {
    console.error('Booking section not found');
    return;
  }

  const pricePerNight = parseFloat(bookingSection.dataset.pricePerNight);
  const isUserSignedIn = document.body.dataset.userSignedIn === 'true';
  console.log('Is user signed in:', isUserSignedIn);

  const checkInInput = document.getElementById('check-in');
  const checkOutInput = document.getElementById('check-out');
  const bookroomsSelect = document.getElementById('bookrooms');
  const bookButton = document.querySelector('.book-button');
  const modal = document.getElementById('popup-modal');
  const modalMessage = document.getElementById('modal-message');
  const closeModalButton = document.getElementById('close-modal');

  if (!checkInInput || !checkOutInput || !bookroomsSelect || !bookButton || !modal || !modalMessage || !closeModalButton) {
    console.error('One or more required elements are missing');
    return;
  }

  // Initialize Flatpickr
  const checkInPicker = flatpickr(checkInInput, { dateFormat: 'Y-m-d' });
  const checkOutPicker = flatpickr(checkOutInput, { dateFormat: 'Y-m-d' });

  function showModal(message) {
    console.log('Showing modal:', message);
    modalMessage.textContent = message;
    modal.style.display = 'block';
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

    if (checkInDate && checkOutDate) {
      const checkIn = new Date(checkInDate);
      const checkOut = new Date(checkOutDate);
      const nights = (checkOut - checkIn) / (1000 * 60 * 60 * 24);

      if (nights <= 0) {
        showModal('Check-out must be after Check-in.');
        return;
      }

      const totalPrice = nights * pricePerNight;
      document.getElementById('total-price').textContent = totalPrice.toLocaleString();
      document.getElementById('total-nights').textContent = nights;
    } else {
      document.getElementById('total-price').textContent = '0';
      document.getElementById('total-nights').textContent = '0';
    }
  }

  checkInInput.addEventListener('change', () => calculatePrice(pricePerNight));
  checkOutInput.addEventListener('change', () => calculatePrice(pricePerNight));

  bookButton.addEventListener('click', () => {
    if (!isUserSignedIn) {
      showModal('You must be signed in to book.');
      return;
    }

    const checkIn = checkInInput.value;
    const checkOut = checkOutInput.value;
    const totalPrice = document.getElementById('total-price').textContent;

    if (!checkIn || !checkOut || totalPrice === '0') {
      showModal('Please select valid dates before booking.');
      return;
    }

    document.getElementById('payment-modal').style.display = 'block';
  });

  const closePaymentModalButton = document.getElementById('close-payment-modal');
  closePaymentModalButton.addEventListener('click', () => {
    document.getElementById('payment-modal').style.display = 'none';
  });

  closeModalButton.addEventListener('click', hideModal);

});
