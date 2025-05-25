
jQuery(document).ready(function($) {
    // Handle reservation cancellation
    $('.cancel-reservation').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to cancel this reservation?')) {
            return;
        }
        
        var reservationId = $(this).data('id');
        var row = $(this).closest('tr');
        
        $.ajax({
            url: ReservationData.ajax_url,
            type: 'POST',
            data: {
                action: 'cancel_reservation',
                reservation_id: reservationId,
                nonce: ReservationData.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Fade out the row
                    row.fadeOut(300, function() {
                        // Check if this was the last reservation
                        if ($('.reservations-table tbody tr:visible').length === 0) {
                            $('.my-reservations').html('<p>You have no reservations.</p>');
                        }
                    });
                    
                    // Optional: Show success message
                    $('<div class="reservation-success"><p>Reservation cancelled successfully.</p></div>')
                        .insertBefore('.my-reservations')
                        .delay(3000)
                        .fadeOut(500);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('There was an error processing your request. Please try again.');
            }
        });
    });
    
    // Date input validation - ensure it's not in the past
    $('#res_date').on('change', function() {
        var selectedDate = new Date($(this).val());
        var today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            alert('Please select a date in the future.');
            $(this).val('');
        }
    });
    
    // Enhance form validation
    $('.reservation-form').on('submit', function(e) {
        var valid = true;
        var date = $('#res_date').val();
        var time = $('#res_time').val();
        var table = $('#res_table').val();
        var guests = $('#res_guests').val();
        
        // Basic required field validation
        if (!date || !time || !table || !guests) {
            alert('Please fill out all required fields.');
            valid = false;
        }
        
        // Validates the guest count
        if (guests && (guests < 1 || guests > 20)) {
            alert('Please enter a valid number of guests (1-20).');
            valid = false;
        }
        
        // Validates the table number
        if (table && (table < 1 || table > 50)) {
            alert('Please enter a valid table number (1-50).');
            valid = false;
        }
        
        if (!valid) {
            e.preventDefault();
        }
    });
});
