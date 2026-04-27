console.log('External JavaScript loaded successfully - VERSION 3.0 - DEBUG MODE');

let currentWeekStart = new Date();
let selectedSlot = null;
let serviceId = 0;
let baseUrl = '';
let currencySymbol = '';

// Get variables from hidden inputs
function loadBookingVariables() {
    serviceId = parseInt(document.getElementById('php-service-id')?.value || '0');
    baseUrl = document.getElementById('php-base-url')?.value || '';
    currencySymbol = document.getElementById('php-currency-symbol')?.value || '';
    
    console.log('Variables loaded:', { serviceId, baseUrl, currencySymbol });
}

document.addEventListener('DOMContentLoaded', function() {
    // Load variables from hidden inputs
    loadBookingVariables();
    
    const serviceSelect = document.getElementById('service_id');
    
    if (serviceId > 0) {
        showCalendarSection();
        loadWeekCalendar();
    }
    
    serviceSelect.addEventListener('change', function() {
        console.log('Service changed to:', this.value);
        if (this.value) {
            serviceId = parseInt(this.value);
            console.log('ServiceId updated to:', serviceId);
            showServiceDetails(this.value);
            showCalendarSection();
            loadWeekCalendar();
        } else {
            console.log('No service selected');
        }
    });
    
    // Show service details if service is pre-selected
    if (serviceId > 0) {
        showServiceDetails(serviceId);
    }
    
    // Add form submission handler
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            console.log('Form submission attempt - selectedSlot:', selectedSlot);
            
            if (!selectedSlot) {
                alert('Please select a time slot before adding to cart!');
                e.preventDefault();
                return false;
            }
            
            // Set hidden form fields
            const serviceIdInput = document.getElementById('form_service_id');
            const preferredDateInput = document.getElementById('preferred_date');
            const preferredTimeInput = document.getElementById('preferred_time');
            const timeSlotIdInput = document.getElementById('time_slot_id');
            
            const serviceSelect = document.getElementById('service_id');
            
            if (serviceIdInput) serviceIdInput.value = serviceSelect.value;
            if (preferredDateInput) preferredDateInput.value = selectedSlot.date;
            if (preferredTimeInput) preferredTimeInput.value = selectedSlot.time;
            if (timeSlotIdInput) timeSlotIdInput.value = selectedSlot.slotId;
            
            console.log('Form submitting with slot:', selectedSlot);
            return true;
        });
    }
});

function showServiceDetails(serviceIdParam) {
    const detailsContainer = document.getElementById('dynamic-service-details');
    const staticDetailsBox = document.getElementById('service-details-box');
    
    // Update global serviceId when service is selected
    if (serviceIdParam && serviceIdParam !== '0') {
        serviceId = parseInt(serviceIdParam);
        console.log('Service ID updated to:', serviceId);
    }
    
    if (!serviceId) {
        detailsContainer.style.display = 'none';
        if (staticDetailsBox) {
            staticDetailsBox.style.display = 'none';
        }
        hideCalendarSection();
        return;
    }
    
    // Hide static details box when using dynamic version
    if (staticDetailsBox) {
        staticDetailsBox.style.display = 'none';
    }
    
    const selectElement = document.getElementById('service_id');
    const selectedOption = selectElement.querySelector('option[value="' + serviceId + '"]');
    
    if (selectedOption) {
        const name = selectedOption.getAttribute('data-name');
        const description = selectedOption.getAttribute('data-description');
        const price = selectedOption.getAttribute('data-price');
        const duration = selectedOption.getAttribute('data-duration');
        const image = selectedOption.getAttribute('data-image');
        
        let html = '<div class="row align-items-center">';
        
        if (image) {
            html += '<div class="col-md-3">';
            html += '<img src="' + baseUrl + 'assets/uploads/services/' + image + '" ';
            html += 'alt="' + name + '" class="img-fluid rounded">';
            html += '</div>';
        }
        
        const contentClass = image ? 'col-md-9' : 'col-12';
        html += '<div class="' + contentClass + '">';
        html += '<h6 class="mb-2">' + name + '</h6>';
        
        if (description) {
            html += '<p class="mb-1 small">' + description.replace(/\n/g, '<br>') + '</p>';
        }
        
        html += '<div class="d-flex gap-3 mt-2">';
        html += '<span class="badge bg-primary">';
        html += '<i class="fas fa-clock me-1"></i>' + (duration || '0') + ' min';
        html += '</span>';
        html += '<span class="badge bg-success">';
        html += '<i class="fas fa-tag me-1"></i>' + currencySymbol + parseFloat(price).toFixed(2);
        html += '</span>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        
        detailsContainer.innerHTML = html;
        detailsContainer.style.display = 'block';
        
        // Show calendar section when service is selected
        showCalendarSection();
        selectedSlot = null;
        updateSelectedSlotDisplay();
        loadWeekCalendar(); // This should now work with the updated serviceId
        updateSummary();
    }
}

function showCalendarSection() {
    const calendarSection = document.getElementById('calendar-section');
    if (calendarSection) {
        calendarSection.style.display = 'block';
    }
}

function hideCalendarSection() {
    const calendarSection = document.getElementById('calendar-section');
    if (calendarSection) {
        calendarSection.style.display = 'none';
    }
}

// Placeholder functions for calendar functionality
function loadWeekCalendar() {
    console.log('loadWeekCalendar called with serviceId:', serviceId);
    const container = document.getElementById('calendar-container');
    if (!container) {
        console.error('Calendar container not found');
        return;
    }
    
    container.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Loading available time slots...</p></div>';
    
    if (!serviceId || serviceId === 0) {
        console.log('No serviceId, showing warning message');
        container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-info-circle me-2"></i>Please select a service first</div>';
        return;
    }
    
    console.log('Fetching time slots for serviceId:', serviceId);
    const weekStartStr = formatDate(currentWeekStart);
    const apiUrl = baseUrl + 'api/get-slots-clean.php?service_id=' + serviceId + '&week_start=' + weekStartStr;
    console.log('API URL:', apiUrl);
    
    fetch(apiUrl)
        .then(response => {
            console.log('API response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('API response data:', data);
            if (data.error || data.success === false) {
                container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-info-circle me-2"></i>' + (data.error || data.message || 'Unable to load time slots') + '</div>';
                return;
            }
            renderCalendar(data);
        })
        .catch(error => {
            console.error('Calendar load error:', error);
            container.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading time slots. ' + error.message + '</div>';
        });
}

function renderCalendar(data) {
    const container = document.getElementById('calendar-container');
    if (!container) return;
    
    let html = '<div class="week-calendar">';
    html += '<div class="d-flex justify-content-between align-items-center mb-3">';
    html += '<button type="button" onclick="previousWeek()" class="btn btn-sm btn-outline-secondary"><i class="fas fa-chevron-left me-1"></i> Previous</button>';
    html += '<h6 class="mb-0">Week of ' + formatDateDisplay(data.week_start) + '</h6>';
    html += '<button type="button" onclick="nextWeek()" class="btn btn-sm btn-outline-secondary">Next <i class="fas fa-chevron-right ms-1"></i></button>';
    html += '</div>';
    
    html += '<div class="calendar-grid">';
    
    if (data.days && data.days.length > 0) {
        data.days.forEach(day => {
            html += '<div class="day-column">';
            html += '<div class="day-header ' + (day.is_today ? 'today' : '') + '">' + day.day_short + '<br><strong>' + day.day_number + '</strong></div>';
            html += '<div class="time-slots">';
            
            if (day.slots_count === 0) {
                html += '<div class="no-slots"><small>No slots</small></div>';
            } else if (day.slots && day.slots.length > 0) {
                day.slots.forEach(slot => {
                    const isSelected = selectedSlot && selectedSlot.date === day.date && selectedSlot.time === slot.start_time;
                    
                    // Use slot_id from API response and make all slots clickable
                    const slotId = slot.slot_id || slot.id || '';
                    
                    html += '<div class="time-slot ' + slot.status + ' ' + (isSelected ? 'selected' : '') + '" ';
                    html += 'onclick="handleSlotClick(\'' + day.date + '\', \'' + slot.start_time + '\', \'' + slotId + '\', \'' + day.day_name + '\', \'' + slot.status + '\', event)"';
                    html += '>';
                    html += formatTime(slot.start_time);
                    
                    // Add status labels
                    if (slot.status === 'available') {
                        html += '<br><small><i class="fas fa-check"></i> Available</small>';
                    } else if (slot.status === 'booked') {
                        html += '<br><small><i class="fas fa-times"></i> Booked</small>';
                    } else if (slot.status === 'past') {
                        html += '<br><small><i class="fas fa-history"></i> Past</small>';
                    }
                    
                    html += '</div>';
                });
            } else {
                html += '<div class="no-slots"><small>No slots available</small></div>';
            }
            
            html += '</div></div>';
        });
    } else {
        html += '<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No time slots available for this week</div>';
    }
    
    html += '</div></div>';
    
    container.innerHTML = html;
}

function handleSlotClick(date, time, slotId, dayName, status, event) {
    console.log('Slot clicked:', { date, time, slotId, dayName, status });
    
    // Check if slot is booked or past
    if (status === 'booked') {
        alert('Sorry, this time slot is not available, please choose another one');
        return;
    }
    
    if (status === 'past') {
        return;
    }
    
    // Allow selection for available slots
    selectSlot(date, time, slotId, dayName, event);
}

function selectSlot(date, time, slotId, dayName, event) {
    console.log('selectSlot called with:', { date, time, slotId, dayName });
        
    // Remove any existing selection
    document.querySelectorAll('.time-slot.selected').forEach(slot => {
        slot.classList.remove('selected');
    });
        
    // Set new selection
    selectedSlot = {
        date: date,
        time: time,
        slotId: slotId,
        dayName: dayName
    };
        
    // Add selected class to clicked slot
    if (event && event.target) {
        event.target.classList.add('selected');
    }
        
    console.log('Slot selected:', selectedSlot);
        
    // Update displays
    updateSelectedSlotDisplay();
    updateSummary();
        
    // Enable submit button
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.disabled = false;
        console.log('Submit button enabled');
    }
}

function updateSelectedSlotDisplay() {
    console.log('updateSelectedSlotDisplay called');
    const display = document.getElementById('selected-slot-display');
    const text = document.getElementById('selected-slot-text');
    
    if (!display || !text) return;
    
    if (selectedSlot) {
        text.innerHTML = '<strong>' + selectedSlot.dayName + ', ' + formatDateDisplay(selectedSlot.date) + '</strong> at <strong>' + formatTime(selectedSlot.time) + '</strong>';
        display.classList.remove('d-none');
    } else {
        display.classList.add('d-none');
    }
}

function previousWeek() {
    currentWeekStart.setDate(currentWeekStart.getDate() - 7);
    selectedSlot = null;
    updateSelectedSlotDisplay();
    loadWeekCalendar();
}

function nextWeek() {
    currentWeekStart.setDate(currentWeekStart.getDate() + 7);
    selectedSlot = null;
    updateSelectedSlotDisplay();
    loadWeekCalendar();
}

function updateSummary() {
    console.log('updateSummary called - serviceId:', serviceId);
    const summaryContent = document.getElementById('summary-content');
    const serviceSelect = document.getElementById('service_id');
    
    if (!summaryContent || !serviceSelect) {
        console.log('Summary content or service select not found');
        return;
    }
    
    const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
    const currentServiceId = parseInt(serviceSelect.value);
    
    console.log('Current serviceId:', serviceId, 'Selected serviceId:', currentServiceId);
    
    // Fix: Check if a service is actually selected (not just default option)
    if (!currentServiceId || currentServiceId === 0) {
        console.log('No service selected - showing message');
        summaryContent.innerHTML = '<p class="text-muted text-center py-4"><i class="fas fa-info-circle"></i><br>Please select a service to start booking</p>';
        return;
    } else {
        console.log('Service selected - updating summary');
    }
    
    let html = '<div class="mb-3">';
    html += '<h6 class="border-bottom pb-2 mb-2">Selected Service</h6>';
    html += '<p class="mb-1"><strong>' + selectedOption.text.split(' - ')[0] + '</strong></p>';
    html += '<p class="text-muted mb-0">' + (selectedOption.text.split(' - ')[1] || '') + '</p>';
    html += '</div>';
    
    if (selectedSlot) {
        html += '<div class="mb-3">';
        html += '<h6 class="border-bottom pb-2 mb-2">Appointment Time</h6>';
        html += '<p class="mb-0"><i class="far fa-calendar me-2"></i>' + formatDateDisplay(selectedSlot.date) + '</p>';
        html += '<p class="mb-0"><i class="far fa-clock me-2"></i>' + formatTime(selectedSlot.time) + '</p>';
        html += '</div>';
    }
    
    summaryContent.innerHTML = html;
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return year + '-' + month + '-' + day;
}

function formatDateDisplay(dateStr) {
    const date = new Date(dateStr + 'T00:00:00');
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

function formatTime(timeStr) {
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return displayHour + ':' + minutes + ' ' + ampm;
}
