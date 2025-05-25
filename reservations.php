<?php

// Registers a custom post type for handling reservations
add_action('init', function() {
    // Post type labels for the resrvation post type
    register_post_type('reservation', [
        'label' => 'Reservations',
        'labels' => [
            'name' => 'Reservations',
            'singular_name' => 'Reservation',
            'add_new' => 'Add New Reservation',
            'add_new_item' => 'Add New Reservation',
            'edit_item' => 'Edit Reservation',
            'view_item' => 'View Reservation',
        ],
        'public' => true,
        'supports' => ['title'],
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-calendar',
    ]);

    // Adds custom statuses for reservations
    register_post_status('confirmed', [
        'label' => 'Confirmed',
        'public' => true,
        'label_count' => _n_noop('Confirmed <span class="count">(%s)</span>', 'Confirmed <span class="count">(%s)</span>')
    ]);
    // Cancel status and sets up a meta box
    register_post_status('cancelled', [
        'label' => 'Cancelled',
        'public' => true,
        'label_count' => _n_noop('Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>')
    ]);
});

// Custom meta box for the reservation specifics
add_action('add_meta_boxes', function() {
    add_meta_box(
        'reservation_details',
        'Reservation Details',
        'rs_reservation_details_callback',
        'reservation',
        'normal',
        'high'
    );
});

// Meta box callback function
function rs_reservation_details_callback($post) {
    // Add nonce for security
    wp_nonce_field('reservation_details_nonce', 'reservation_details_nonce');
    
    // Saved values
    $date = get_post_meta($post->ID, 'res_date', true);
    $time = get_post_meta($post->ID, 'res_time', true);
    $table = get_post_meta($post->ID, 'res_table', true);
    $guests = get_post_meta($post->ID, 'res_guests', true);
    $customer = get_post_meta($post->ID, 'res_customer', true);
    if (!$customer) {
        $customer = get_the_author_meta('display_name', $post->post_author);
    }
    
    // Output fields
    ?>
    <style>
        .res-form-field { margin-bottom: 15px; }
        .res-form-field label { display: block; font-weight: bold; margin-bottom: 5px; }
        .res-form-field input, .res-form-field select { width: 100%; padding: 8px; }
    </style>
    
    <div class="res-form-field">
        <label for="res_customer">Customer Name:</label>
        <input type="text" id="res_customer" name="res_customer" value="<?php echo esc_attr($customer); ?>">
    </div>
    
    <div class="res-form-field">
        <label for="res_date">Reservation Date:</label>
        <input type="date" id="res_date" name="res_date" value="<?php echo esc_attr($date); ?>" required>
    </div>
    
    <div class="res-form-field">
        <label for="res_time">Reservation Time:</label>
        <input type="time" id="res_time" name="res_time" value="<?php echo esc_attr($time); ?>" required>
    </div>
    
    <div class="res-form-field">
        <label for="res_table">Table Number:</label>
        <input type="number" id="res_table" name="res_table" min="1" value="<?php echo esc_attr($table); ?>" required>
    </div>
    
    <div class="res-form-field">
        <label for="res_guests">Number of Guests:</label>
        <input type="number" id="res_guests" name="res_guests" min="1" value="<?php echo esc_attr($guests); ?>" required>
    </div>
    <?php
}

// Save meta box data
add_action('save_post_reservation', function($post_id) {
    // Check if nonce is valid
    if (!isset($_POST['reservation_details_nonce']) || !wp_verify_nonce($_POST['reservation_details_nonce'], 'reservation_details_nonce')) {
        return;
    }
    
    // Check if auto saving is on
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Checks permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Saves data
    $fields = ['res_customer', 'res_date', 'res_time', 'res_table', 'res_guests'];
    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
});

// Reservation form shortcode
function rs_reservation_form() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to reserve a table.</p>';
    }
    
    ob_start(); 
    
    // Checks if the form was submitted
    if (isset($_POST['submit_res']) && isset($_POST['reservation_nonce']) && wp_verify_nonce($_POST['reservation_nonce'], 'submit_reservation')) {
        // Validate inputs
        $errors = [];
        $required_fields = ['res_date', 'res_time', 'res_table', 'res_guests'];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = 'All fields are required.';
                break;
            }
        }
        
        // Validates the date is in the future and not an already paassed 
        if (!empty($_POST['res_date'])) {
            $reservation_date = strtotime($_POST['res_date']);
            if ($reservation_date < strtotime('today')) {
                $errors[] = 'Reservation date must be in the future.';
            }
        }
        
        // Checks for table availability 
        if (empty($errors) && !empty($_POST['res_date']) && !empty($_POST['res_time']) && !empty($_POST['res_table'])) {
            $existing = get_posts([
                'post_type' => 'reservation',
                'post_status' => 'publish',
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => 'res_date',
                        'value' => sanitize_text_field($_POST['res_date']),
                    ],
                    [
                        'key' => 'res_table',
                        'value' => sanitize_text_field($_POST['res_table']),
                    ]
                ]
            ]);
            
            if (!empty($existing)) {
                $errors[] = 'Sorry, this table is already reserved for the selected date.';
            }
        }
        
        if (empty($errors)) {
            // Creates the reservation
            $id = wp_insert_post([
                'post_type' => 'reservation',
                'post_title' => 'Reservation - ' . current_time('mysql'),
                'post_status' => 'publish',
                'post_author' => get_current_user_id()
            ]);
            
            if ($id) {
                foreach ($required_fields as $field) {
                    update_post_meta($id, $field, sanitize_text_field($_POST[$field]));
                }
                
                // Stores customer name
                $current_user = wp_get_current_user();
                update_post_meta($id, 'res_customer', $current_user->display_name);
                
                // Shows success message
                echo '<div class="reservation-success">';
                echo '<h3>Reservation Confirmed!</h3>';
                echo '<p>Your table has been reserved for ' . esc_html($_POST['res_date']) . ' at ' . esc_html($_POST['res_time']) . '.</p>';
                echo '<p><a href="' . esc_url(get_permalink()) . '">Book another table</a> or <a href="#my-reservations">view your reservations</a>.</p>';
                echo '</div>';
                
                // Sends confirmation email
                $to = $current_user->user_email;
                $subject = 'Your Reservation Confirmation';
                $message = 'Hello ' . $current_user->display_name . ",\n\n";
                $message .= "Your reservation has been confirmed for " . $_POST['res_date'] . " at " . $_POST['res_time'] . ".\n";
                $message .= "Table: " . $_POST['res_table'] . "\n";
                $message .= "Guests: " . $_POST['res_guests'] . "\n\n";
                $message .= "We look forward to seeing you!\n";
                $message .= get_bloginfo('name');
                
                wp_mail($to, $subject, $message);
            }
        } else {
            // Shows errors
            echo '<div class="reservation-errors">';
            foreach ($errors as $error) {
                echo '<p>' . esc_html($error) . '</p>';
            }
            echo '</div>';
        }
    }
    
    // Only shows the form if we haven't just successfully submitted
    if (!isset($id) || !$id) {
        ?>
        <div class="reservation-form-container">
            <h3>Reserve a Table</h3>
            <form method="post" class="reservation-form">
                <?php wp_nonce_field('submit_reservation', 'reservation_nonce'); ?>
                
                <div class="form-row">
                    <label for="res_date">Date</label>
                    <input name="res_date" id="res_date" type="date" required 
                           min="<?php echo date('Y-m-d'); ?>" 
                           value="<?php echo isset($_POST['res_date']) ? esc_attr($_POST['res_date']) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <label for="res_time">Time</label>
                    <input name="res_time" id="res_time" type="time" required
                           value="<?php echo isset($_POST['res_time']) ? esc_attr($_POST['res_time']) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <label for="res_table">Table Number</label>
                    <input name="res_table" id="res_table" type="number" min="1" placeholder="Table #" required
                           value="<?php echo isset($_POST['res_table']) ? esc_attr($_POST['res_table']) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <label for="res_guests">Number of Guests</label>
                    <input name="res_guests" id="res_guests" type="number" min="1" placeholder="Number of guests" required
                           value="<?php echo isset($_POST['res_guests']) ? esc_attr($_POST['res_guests']) : ''; ?>">
                </div>
                
                <div class="form-row">
                    <input type="submit" name="submit_res" value="Book Table" class="button">
                </div>
            </form>
        </div>
        <?php
    }
    
    return ob_get_clean();
}
add_shortcode('reservation_form', 'rs_reservation_form');

// Shows user reservations
function rs_my_reservations() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to view your reservations.</p>';
    }
    
    $posts = get_posts([
        'post_type' => 'reservation',
        'author' => get_current_user_id(),
        'posts_per_page' => -1,
        'orderby' => 'meta_value',
        'meta_key' => 'res_date',
        'order' => 'DESC'
    ]);
    
    if (empty($posts)) {
        return '<div class="my-reservations" id="my-reservations"><p>You have no reservations yet.</p></div>';
    }
    
    ob_start(); 
    
    echo '<div class="my-reservations" id="my-reservations">';
    echo '<h3>My Reservations</h3>';
    echo '<table class="reservations-table">';
    echo '<thead><tr><th>Date</th><th>Time</th><th>Table</th><th>Guests</th><th></th></tr></thead>';
    echo '<tbody>';
    
    foreach ($posts as $post) {
        $date = get_post_meta($post->ID, 'res_date', true);
        $time = get_post_meta($post->ID, 'res_time', true);
        $table = get_post_meta($post->ID, 'res_table', true);
        $guests = get_post_meta($post->ID, 'res_guests', true);
        
        // Checks if the reservation is in the past
        $is_past = strtotime($date) < strtotime('today');
        $row_class = $is_past ? 'past-reservation' : '';
        $cancel_link = !$is_past ? '<a href="#" class="cancel-reservation" data-id="' . esc_attr($post->ID) . '">Cancel</a>' : '';
        
        echo '<tr class="' . $row_class . '">';
        echo '<td>' . esc_html($date) . '</td>';
        echo '<td>' . esc_html($time) . '</td>';
        echo '<td>' . esc_html($table) . '</td>';
        echo '<td>' . esc_html($guests) . '</td>';
        echo '<td>' . $cancel_link . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table></div>';
    
    return ob_get_clean();
}
add_shortcode('my_reservations', 'rs_my_reservations');

// Adds AJAX handler for reservation cancellation
add_action('wp_ajax_cancel_reservation', 'rs_cancel_reservation');
function rs_cancel_reservation() {
    // Checks nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cancel_reservation_nonce')) {
        wp_send_json_error('Invalid security token');
        exit;
    }
    
    // Checks for reservation ID
    if (!isset($_POST['reservation_id'])) {
        wp_send_json_error('Missing reservation ID');
        exit;
    }
    
    $reservation_id = intval($_POST['reservation_id']);
    $post = get_post($reservation_id);
    
    // Checks if post exists and user owns it
    if (!$post || $post->post_type !== 'reservation' || $post->post_author != get_current_user_id()) {
        wp_send_json_error('You cannot cancel this reservation');
        exit;
    }
    
    // Cancels the reservation
    $result = wp_update_post([
        'ID' => $reservation_id,
        'post_status' => 'cancelled'
    ]);
    
    if ($result) {
        wp_send_json_success('Reservation cancelled successfully');
    } else {
        wp_send_json_error('Failed to cancel reservation');
    }
    exit;
}

// Enqueues the necessary scripts
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script(
        'reservation-scripts',
        get_stylesheet_directory_uri() . '/js/reservation.js',
        ['jquery'],
        '1.0',
        true
    );
    
    wp_localize_script('reservation-scripts', 'ReservationData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cancel_reservation_nonce')
    ]);
});
