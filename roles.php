<?php

// Registers a custom role 
add_action('after_switch_theme', 'restaurant_register_roles');
function restaurant_register_roles() {
    // Customer role 
    add_role(
        'restaurant_customer',
        'Restaurant Customer',
        [
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'make_reservations' => true,
            'view_own_reservations' => true,
        ]
    );
    
    // Waiter role
    add_role(
        'restaurant_waiter',
        'Restaurant Waiter',
        [
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'view_reservations' => true,
            'create_orders' => true,
            'edit_orders' => true,
            'view_menu' => true,
            'view_own_schedule' => true,
            'request_time_off' => true,
        ]
    );
    
    // Kitchen staff role
    add_role(
        'restaurant_kitchen',
        'Kitchen Staff',
        [
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'view_orders' => true,
            'update_orders' => true,
            'view_menu' => true,
            'view_own_schedule' => true,
            'request_time_off' => true,
        ]
    );
    
    // Inventory manager role
    add_role(
        'restaurant_inventory',
        'Inventory Manager',
        [
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'manage_inventory' => true,
            'view_suppliers' => true,
            'create_purchase_orders' => true,
            'view_own_schedule' => true,
            'request_time_off' => true,
        ]
    );
    
    // Restaurant manager role 
    add_role(
        'restaurant_manager',
        'Restaurant Manager',
        [
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'manage_reservations' => true,
            'manage_orders' => true,
            'manage_menu' => true,
            'manage_inventory' => true,
            'manage_staff' => true,
            'manage_schedules' => true,
            'view_reports' => true,
            'export_data' => true,
        ]
    );
    
    // Add restaurant capabilities to administrator
    $admin = get_role('administrator');
    $admin->add_cap('manage_reservations');
    $admin->add_cap('manage_orders');
    $admin->add_cap('manage_menu');
    $admin->add_cap('manage_inventory');
    $admin->add_cap('manage_staff');
    $admin->add_cap('manage_schedules');
    $admin->add_cap('view_reports');
    $admin->add_cap('export_data');
}

// Remove roles 
add_action('switch_theme', 'restaurant_remove_roles');
function restaurant_remove_roles() {
    
    if (get_stylesheet() !== 'astra-child') {
        remove_role('restaurant_customer');
        remove_role('restaurant_waiter');
        remove_role('restaurant_kitchen');
        remove_role('restaurant_inventory');
        remove_role('restaurant_manager');
        
        // Remove capabilities from administrator
        $admin = get_role('administrator');
        $admin->remove_cap('manage_reservations');
        $admin->remove_cap('manage_orders');
        $admin->remove_cap('manage_menu');
        $admin->remove_cap('manage_inventory');
        $admin->remove_cap('manage_staff');
        $admin->remove_cap('manage_schedules');
        $admin->remove_cap('view_reports');
        $admin->remove_cap('export_data');
    }
}

// Check if user has restaurant capability
function restaurant_user_can($capability) {
    return current_user_can($capability);
}

// Restrict access to admin pages based on capabilities
add_action('admin_init', 'restaurant_restrict_admin_access');
function restaurant_restrict_admin_access() {
    // Get current screen
    $screen = get_current_screen();
    
    if (!$screen) {
        return;
    }
    
    // Define which screens are available to which roles
    $restricted_screens = [
        'edit.php?post_type=reservation' => 'manage_reservations',
        'edit.php?post_type=restaurant_order' => 'manage_orders',
        'edit.php?post_type=menu_item' => 'manage_menu',
        'edit.php?post_type=inventory_item' => 'manage_inventory',
        'edit.php?post_type=staff_schedule' => 'manage_schedules',
        'restaurant_page_restaurant-reports' => 'view_reports',
    ];
    
    // Check if current screen is restricted and user doesn't have capability
    foreach ($restricted_screens as $page => $capability) {
        if (strpos($screen->id, $page) !== false && !current_user_can($capability)) {
            wp_die(
                '<h1>' . __('You do not have sufficient permissions to access this page.') . '</h1>',
                403
            );
        }
    }
}

// Add custom login redirect based on user role
add_filter('login_redirect', 'restaurant_login_redirect', 10, 3);
function restaurant_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        // Redirect different roles to different pages
        if (in_array('restaurant_waiter', $user->roles)) {
            return home_url('/waiter-dashboard/');
        } elseif (in_array('restaurant_kitchen', $user->roles)) {
            return home_url('/kitchen-display/');
        } elseif (in_array('restaurant_inventory', $user->roles)) {
            return home_url('/inventory-management/');
        } elseif (in_array('restaurant_manager', $user->roles)) {
            return admin_url('index.php?page=restaurant-dashboard');
        } elseif (in_array('restaurant_customer', $user->roles)) {
            return home_url('/my-account/');
        }
    }
    
    return $redirect_to;
}

// Register user on reservation if they don't have an account
add_action('wp_ajax_nopriv_register_reservation_user', 'restaurant_register_reservation_user');
function restaurant_register_reservation_user() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'reservation_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    // Gets form data
    $email = sanitize_email($_POST['email']);
    $name = sanitize_text_field($_POST['name']);
    $phone = sanitize_text_field($_POST['phone']);
    
    // Checks if the user exists
    $user = get_user_by('email', $email);
    
    if (!$user) {
        // Generate username from email
        $username = sanitize_user(current(explode('@', $email)), true);
        $i = 1;
        while (username_exists($username)) {
            $username = sanitize_user(current(explode('@', $email)) . $i, true);
            $i++;
        }
        
        // Generates a password
        $password = wp_generate_password(12, false);
        
        // Creates a user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }
        
        // Sets a role
        $user = new WP_User($user_id);
        $user->set_role('restaurant_customer');
        
        // Update user meta
        update_user_meta($user_id, 'first_name', $name);
        update_user_meta($user_id, 'phone', $phone);
        
        // Sends a password to user
        wp_mail(
            $email,
            'Your account has been created',
            "Hello $name,\n\nYour account has been created. You can login with the following details:\n\nUsername: $username\nPassword: $password\n\nPlease change your password after logging in.",
            ['Content-Type: text/plain; charset=UTF-8']
        );
        
        wp_send_json_success([
            'message' => 'User registered successfully. Login details have been sent to your email.',
            'user_id' => $user_id
        ]);
    } else {
        wp_send_json_success([
            'message' => 'User already exists.',
            'user_id' => $user->ID
        ]);
    }
}
