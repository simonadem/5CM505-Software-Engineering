<?php

// Registers a post type for inventory
add_action('init', function() {
    register_post_type('inventory_item', [
        'label' => 'Inventory',
        'labels' => [
            'name' => 'Inventory',
            'singular_name' => 'Inventory Item',
            'add_new' => 'Add New Item',
            'add_new_item' => 'Add New Inventory Item',
            'edit_item' => 'Edit Inventory Item',
            'view_item' => 'View Inventory Item',
        ],
        'public' => true,
        'supports' => ['title', 'author'],
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-archive',
        'menu_position' => 27,
        'has_archive' => false,
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);
    
    // Registers post type for supplier
    register_post_type('supplier', [
        'label' => 'Suppliers',
        'labels' => [
            'name' => 'Suppliers',
            'singular_name' => 'Supplier',
            'add_new' => 'Add New Supplier',
            'add_new_item' => 'Add New Supplier',
            'edit_item' => 'Edit Supplier',
            'view_item' => 'View Supplier',
        ],
        'public' => true,
        'supports' => ['title', 'editor'],
        'show_in_menu' => 'edit.php?post_type=inventory_item',
        'has_archive' => false,
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);
    
    // Registers post type for orders
    register_post_type('purchase_order', [
        'label' => 'Purchase Orders',
        'labels' => [
            'name' => 'Purchase Orders',
            'singular_name' => 'Purchase Order',
            'add_new' => 'Add New Purchase Order',
            'add_new_item' => 'Add New Purchase Order',
            'edit_item' => 'Edit Purchase Order',
            'view_item' => 'View Purchase Order',
        ],
        'public' => true,
        'supports' => ['title', 'author'],
        'show_in_menu' => 'edit.php?post_type=inventory_item',
        'has_archive' => false,
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);
    
    // Adds categories
    register_taxonomy(
        'inventory_category',
        'inventory_item',
        [
            'label' => 'Categories',
            'hierarchical' => true,
            'show_admin_column' => true,
        ]
    );
});

// Adds meta boxes for inventory items
add_action('add_meta_boxes', function() {
    add_meta_box(
        'inventory_item_details',
        'Inventory Item Details',
        'render_inventory_item_meta_box',
        'inventory_item',
        'normal',
        'high'
    );
    
    add_meta_box(
        'supplier_details',
        'Supplier Details',
        'render_supplier_meta_box',
        'supplier',
        'normal',
        'high'
    );
    
    add_meta_box(
        'purchase_order_details',
        'Purchase Order Details',
        'render_purchase_order_meta_box',
        'purchase_order',
        'normal',
        'high'
    );
});

// Renders meta box for inventory items
function render_inventory_item_meta_box($post) {
    wp_nonce_field('save_inventory_item', 'inventory_item_nonce');
    
    // Gets current item data
    $quantity = get_post_meta($post->ID, 'inventory_quantity', true);
    $unit = get_post_meta($post->ID, 'inventory_unit', true);
    $reorder_level = get_post_meta($post->ID, 'inventory_reorder_level', true);
    $cost_per_unit = get_post_meta($post->ID, 'inventory_cost', true);
    $supplier_id = get_post_meta($post->ID, 'inventory_supplier', true);
    $sku = get_post_meta($post->ID, 'inventory_sku', true);
    $storage_location = get_post_meta($post->ID, 'inventory_location', true);
    
    // Gets suppliers
    $suppliers = get_posts([
        'post_type' => 'supplier',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    ?>
    <style>
        .inventory-field { margin-bottom: 15px; }
        .inventory-field label { display: block; font-weight: bold; margin-bottom: 5px; }
        .inventory-field input, .inventory-field select, .inventory-field textarea { width: 100%; }
        .inventory-field .inline-field { display: flex; align-items: center; }
        .inventory-field .inline-field input { width: auto; margin-right: 10px; }
    </style>
    
    <div class="inventory-field">
        <label for="inventory_sku">SKU/Item Code:</label>
        <input type="text" id="inventory_sku" name="inventory_sku" value="<?php echo esc_attr($sku); ?>" required>
    </div>
    
    <div class="inventory-field">
        <label for="inventory_quantity">Quantity on Hand:</label>
        <div class="inline-field">
            <input type="number" id="inventory_quantity" name="inventory_quantity" min="0" step="0.01" value="<?php echo esc_attr($quantity); ?>" required>
            <input type="text" id="inventory_unit" name="inventory_unit" placeholder="Unit (e.g., kg, liter)" value="<?php echo esc_attr($unit); ?>" required style="width:150px;">
        </div>
    </div>
    
    <div class="inventory-field">
        <label for="inventory_reorder_level">Reorder Level:</label>
        <input type="number" id="inventory_reorder_level" name="inventory_reorder_level" min="0" step="0.01" value="<?php echo esc_attr($reorder_level); ?>" required>
    </div>
    
    <div class="inventory-field">
        <label for="inventory_cost">Cost per Unit ($):</label>
        <input type="number" id="inventory_cost" name="inventory_cost" min="0" step="0.01" value="<?php echo esc_attr($cost_per_unit); ?>" required>
    </div>
    
    <div class="inventory-field">
        <label for="inventory_supplier">Primary Supplier:</label>
        <select id="inventory_supplier" name="inventory_supplier">
            <option value="">Select Supplier</option>
            <?php foreach ($suppliers as $supplier) : ?>
                <option value="<?php echo esc_attr($supplier->ID); ?>" <?php selected($supplier_id, $supplier->ID); ?>>
                    <?php echo esc_html($supplier->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="inventory-field">
        <label for="inventory_location">Storage Location:</label>
        <input type="text" id="inventory_location" name="inventory_location" value="<?php echo esc_attr($storage_location); ?>">
    </div>
    <?php
}

// Renders meta box for supplier
function render_supplier_meta_box($post) {
    wp_nonce_field('save_supplier', 'supplier_nonce');
    
    // Gets current supplier info
    $contact_name = get_post_meta($post->ID, 'supplier_contact_name', true);
    $email = get_post_meta($post->ID, 'supplier_email', true);
    $phone = get_post_meta($post->ID, 'supplier_phone', true);
    $address = get_post_meta($post->ID, 'supplier_address', true);
    $notes = get_post_meta($post->ID, 'supplier_notes', true);
    
    ?>
    <style>
        .supplier-field { margin-bottom: 15px; }
        .supplier-field label { display: block; font-weight: bold; margin-bottom: 5px; }
        .supplier-field input, .supplier-field textarea { width: 100%; }
    </style>
    
    <div class="supplier-field">
        <label for="supplier_contact_name">Contact Name:</label>
        <input type="text" id="supplier_contact_name" name="supplier_contact_name" value="<?php echo esc_attr($contact_name); ?>">
    </div>
    
    <div class="supplier-field">
        <label for="supplier_email">Email:</label>
        <input type="email" id="supplier_email" name="supplier_email" value="<?php echo esc_attr($email); ?>">
    </div>
    
    <div class="supplier-field">
        <label for="supplier_phone">Phone:</label>
        <input type="text" id="supplier_phone" name="supplier_phone" value="<?php echo esc_attr($phone); ?>">
    </div>
    
    <div class="supplier-field">
        <label for="supplier_address">Address:</label>
        <textarea id="supplier_address" name="supplier_address" rows="3"><?php echo esc_textarea($address); ?></textarea>
    </div>
    
    <div class="supplier-field">
        <label for="supplier_notes">Notes:</label>
        <textarea id="supplier_notes" name="supplier_notes" rows="5"><?php echo esc_textarea($notes); ?></textarea>
    </div>
    <?php
}

// Renders meta box orders purchase
function render_purchase_order_meta_box($post) {
    wp_nonce_field('save_purchase_order', 'purchase_order_nonce');
    
    // Gets current PO data
    $supplier_id = get_post_meta($post->ID, 'po_supplier', true);
    $status = get_post_meta($post->ID, 'po_status', true) ?: 'pending';
    $delivery_date = get_post_meta($post->ID, 'po_delivery_date', true);
    $items = get_post_meta($post->ID, 'po_items', true);
    $total = get_post_meta($post->ID, 'po_total', true) ?: 0;
    
    // Decodes items
    $items = !empty($items) ? json_decode($items, true) : [];
    
    // Gets suppliers
    $suppliers = get_posts([
        'post_type' => 'supplier',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    // Get inventory items
    $inventory_items = get_posts([
        'post_type' => 'inventory_item',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    ?>
    <style>
        .po-field { margin-bottom: 15px; }
        .po-field label { display: block; font-weight: bold; margin-bottom: 5px; }
        .po-field input, .po-field select, .po-field textarea { width: 100%; }
        table.po-items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.po-items th, table.po-items td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        table.po-items th { background-color: #f5f5f5; }
        .add-po-item { margin-top: 10px; }
        .remove-po-item { color: red; cursor: pointer; }
    </style>
    
    <div class="po-field">
        <label for="po_supplier">Supplier:</label>
        <select id="po_supplier" name="po_supplier" required>
            <option value="">Select Supplier</option>
            <?php foreach ($suppliers as $supplier) : ?>
                <option value="<?php echo esc_attr($supplier->ID); ?>" <?php selected($supplier_id, $supplier->ID); ?>>
                    <?php echo esc_html($supplier->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    
    <div class="po-field">
        <label for="po_status">Status:</label>
        <select id="po_status" name="po_status">
            <option value="pending" <?php selected($status, 'pending'); ?>>Pending</option>
            <option value="ordered" <?php selected($status, 'ordered'); ?>>Ordered</option>
            <option value="partial" <?php selected($status, 'partial'); ?>>Partially Received</option>
            <option value="received" <?php selected($status, 'received'); ?>>Received</option>
            <option value="cancelled" <?php selected($status, 'cancelled'); ?>>Cancelled</option>
        </select>
    </div>
    
    <div class="po-field">
        <label for="po_delivery_date">Expected Delivery Date:</label>
        <input type="date" id="po_delivery_date" name="po_delivery_date" value="<?php echo esc_attr($delivery_date); ?>">
    </div>
    
    <div class="po-field">
        <label>Order Items:</label>
        <table class="po-items" id="po-items">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Cost</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)) : ?>
                    <?php foreach ($items as $index => $item) : ?>
                        <tr class="po-item">
                            <td>
                                <select name="po_items[<?php echo $index; ?>][id]" class="po-item-select" required>
                                    <option value="">Select Item</option>
                                    <?php foreach ($inventory_items as $inv_item) : 
                                        $cost = get_post_meta($inv_item->ID, 'inventory_cost', true);
                                    ?>
                                        <option value="<?php echo esc_attr($inv_item->ID); ?>" 
                                                data-cost="<?php echo esc_attr($cost); ?>"
                                                <?php selected($item['id'], $inv_item->ID); ?>>
                                            <?php echo esc_html($inv_item->post_title); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="po_items[<?php echo $index; ?>][quantity]" 
                                       class="po-item-quantity" min="0.01" step="0.01" 
                                       value="<?php echo esc_attr($item['quantity']); ?>" required>
                            </td>
                            <td>
                                <input type="number" name="po_items[<?php echo $index; ?>][cost]" 
                                       class="po-item-cost" min="0.01" step="0.01" 
                                       value="<?php echo esc_attr($item['cost']); ?>" required>
                            </td>
                            <td>
                                <span class="po-item-total">$<?php echo number_format($item['quantity'] * $item['cost'], 2); ?></span>
                            </td>
                            <td>
                                <span class="remove-po-item dashicons dashicons-trash"></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr class="po-item">
                        <td>
                            <select name="po_items[0][id]" class="po-item-select" required>
                                <option value="">Select Item</option>
                                <?php foreach ($inventory_items as $inv_item) : 
                                    $cost = get_post_meta($inv_item->ID, 'inventory_cost', true);
                                ?>
                                    <option value="<?php echo esc_attr($inv_item->ID); ?>" 
                                            data-cost="<?php echo esc_attr($cost); ?>">
                                        <?php echo esc_html($inv_item->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <input type="number" name="po_items[0][quantity]" class="po-item-quantity" min="0.01" step="0.01" value="1" required>
                        </td>
                        <td>
                            <input type="number" name="po_items[0][cost]" class="po-item-cost" min="0.01" step="0.01" value="0.00" required>
                        </td>
                        <td>
                            <span class="po-item-total">$0.00</span>
                        </td>
                        <td>
                            <span class="remove-po-item dashicons dashicons-trash"></span>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                    <td><strong id="po-total">$<?php echo number_format($total, 2); ?></strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        <button type="button" class="button add-po-item">Add Item</button>
        <input type="hidden" name="po_total" id="po_total_input" value="<?php echo esc_attr($total); ?>">
    </div>
    <?php
}

// Save inventory item details
add_action('save_post_inventory_item', function($post_id) {
    // Check if nonce is set
    if (!isset($_POST['inventory_item_nonce']) || !wp_verify_nonce($_POST['inventory_item_nonce'], 'save_inventory_item')) {
        return;
    }
    
    // Check if autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save meta data
    update_post_meta($post_id, 'inventory_sku', sanitize_text_field($_POST['inventory_sku']));
    update_post_meta($post_id, 'inventory_quantity', floatval($_POST['inventory_quantity']));
    update_post_meta($post_id, 'inventory_unit', sanitize_text_field($_POST['inventory_unit']));
    update_post_meta($post_id, 'inventory_reorder_level', floatval($_POST['inventory_reorder_level']));
    update_post_meta($post_id, 'inventory_cost', floatval($_POST['inventory_cost']));
    update_post_meta($post_id, 'inventory_supplier', intval($_POST['inventory_supplier']));
    update_post_meta($post_id, 'inventory_location', sanitize_text_field($_POST['inventory_location']));
    
    // Checks if quantity is below reorder level
    if (floatval($_POST['inventory_quantity']) <= floatval($_POST['inventory_reorder_level'])) {
        // Send alert to inventory manager
        $item_title = get_the_title($post_id);
        $email_subject = "Low Inventory Alert: {$item_title}";
        $email_message = "Inventory for {$item_title} is low.\n\n";
        $email_message .= "Current quantity: {$_POST['inventory_quantity']} {$_POST['inventory_unit']}\n";
        $email_message .= "Reorder level: {$_POST['inventory_reorder_level']} {$_POST['inventory_unit']}\n\n";
        $email_message .= "Please reorder this item soon.\n\n";
        $email_message .= "View item: " . get_edit_post_link($post_id);
        
        // Get inventory managers
        $inventory_managers = get_users(['role__in' => ['restaurant_inventory', 'restaurant_manager', 'administrator']]);
        
        foreach ($inventory_managers as $manager) {
            wp_mail($manager->user_email, $email_subject, $email_message);
        }
    }
});

// Saves supplier details
add_action('save_post_supplier', function($post_id) {
    // Check if nonce is set
    if (!isset($_POST['supplier_nonce']) || !wp_verify_nonce($_POST['supplier_nonce'], 'save_supplier')) {
        return;
    }
    
    // Check if autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save meta data
    update_post_meta($post_id, 'supplier_contact_name', sanitize_text_field($_POST['supplier_contact_name']));
    update_post_meta($post_id, 'supplier_email', sanitize_email($_POST['supplier_email']));
    update_post_meta($post_id, 'supplier_phone', sanitize_text_field($_POST['supplier_phone']));
    update_post_meta($post_id, 'supplier_address', sanitize_textarea_field($_POST['supplier_address']));
    update_post_meta($post_id, 'supplier_notes', sanitize_textarea_field($_POST['supplier_notes']));
});

// Save purchase order details
add_action('save_post_purchase_order', function($post_id) {
    // Check if nonce is set
    if (!isset($_POST['purchase_order_nonce']) || !wp_verify_nonce($_POST['purchase_order_nonce'], 'save_purchase_order')) {
        return;
    }
    
    // Check if autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save meta data
    update_post_meta($post_id, 'po_supplier', intval($_POST['po_supplier']));
    update_post_meta($post_id, 'po_status', sanitize_text_field($_POST['po_status']));
    update_post_meta($post_id, 'po_delivery_date', sanitize_text_field($_POST['po_delivery_date']));
    update_post_meta($post_id, 'po_total', floatval($_POST['po_total']));
    
    // Format and save items
    $items = isset($_POST['po_items']) ? $_POST['po_items'] : [];
    $formatted_items = [];
    
    foreach ($items as $item) {
        if (!empty($item['id'])) {
            $formatted_items[] = [
                'id' => intval($item['id']),
                'name' => get_the_title($item['id']),
                'quantity' => floatval($item['quantity']),
                'cost' => floatval($item['cost'])
            ];
        }
    }
    
    update_post_meta($post_id, 'po_items', wp_json_encode($formatted_items));
    
    // Update inventory if PO status is "received"
    if ($_POST['po_status'] === 'received') {
        foreach ($formatted_items as $item) {
            $current_quantity = floatval(get_post_meta($item['id'], 'inventory_quantity', true));
            $new_quantity = $current_quantity + $item['quantity'];
            update_post_meta($item['id'], 'inventory_quantity', $new_quantity);
        }
    }
});

// AJAX handler for inventory search
add_action('wp_ajax_inventory_search', 'ajax_inventory_search');
function ajax_inventory_search() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'inventory_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    $search = sanitize_text_field($_POST['search']);
    
    $args = [
        'post_type' => 'inventory_item',
        'posts_per_page' => 20,
        'orderby' => 'title',
        'order' => 'ASC',
        's' => $search
    ];
    
    $items = get_posts($args);
    
    $results = [];
    foreach ($items as $item) {
        $quantity = get_post_meta($item->ID, 'inventory_quantity', true);
        $unit = get_post_meta($item->ID, 'inventory_unit', true);
        $cost = get_post_meta($item->ID, 'inventory_cost', true);
        
        $results[] = [
            'id' => $item->ID,
            'title' => $item->post_title,
            'quantity' => $quantity,
            'unit' => $unit,
            'cost' => $cost
        ];
    }
    
    wp_send_json_success($results);
}

// AJAX handler for inventory updates
add_action('wp_ajax_update_inventory', 'ajax_update_inventory');
function ajax_update_inventory() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'inventory_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    // Check permissions
    if (!current_user_can('manage_inventory')) {
        wp_send_json_error('Permission denied');
    }
    
    $item_id = intval($_POST['item_id']);
    $quantity = floatval($_POST['quantity']);
    $reason = sanitize_text_field($_POST['reason']);
    
    // Get current quantity
    $current_quantity = floatval(get_post_meta($item_id, 'inventory_quantity', true));
    
    // Update quantity
    update_post_meta($item_id, 'inventory_quantity', $quantity);
    
    // Log the change
    $user = wp_get_current_user();
    $log_entry = [
        'date' => current_time('mysql'),
        'user' => $user->display_name,
        'previous' => $current_quantity,
        'new' => $quantity,
        'reason' => $reason
    ];
    
    $logs = get_post_meta($item_id, 'inventory_logs', true);
    $logs = !empty($logs) ? json_decode($logs, true) : [];
    $logs[] = $log_entry;
    
    update_post_meta($item_id, 'inventory_logs', wp_json_encode($logs));
    
    wp_send_json_success([
        'message' => 'Inventory updated successfully',
        'new_quantity' => $quantity
    ]);
}

// AJAX handler for creating a quick purchase order
add_action('wp_ajax_create_quick_po', 'ajax_create_quick_po');
function ajax_create_quick_po() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'inventory_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    // Check permissions
    if (!current_user_can('create_purchase_orders')) {
        wp_send_json_error('Permission denied');
    }
    
    $item_id = intval($_POST['item_id']);
    $quantity = floatval($_POST['quantity']);
    $supplier_id = intval($_POST['supplier_id']);
    
    // Get item details
    $item = get_post($item_id);
    $cost = floatval(get_post_meta($item_id, 'inventory_cost', true));
    
    // Create purchase order
    $po_data = [
        'post_title' => 'PO - ' . $item->post_title . ' - ' . date('Y-m-d'),
        'post_status' => 'publish',
        'post_type' => 'purchase_order',
        'post_author' => get_current_user_id()
    ];
    
    $po_id = wp_insert_post($po_data);
    
    if ($po_id) {
        // Set PO meta
        update_post_meta($po_id, 'po_supplier', $supplier_id);
        update_post_meta($po_id, 'po_status', 'pending');
        update_post_meta($po_id, 'po_delivery_date', date('Y-m-d', strtotime('+7 days')));
        update_post_meta($po_id, 'po_total', $quantity * $cost);
        
        // Set PO items
        $po_items = [
            [
                'id' => $item_id,
                'name' => $item->post_title,
                'quantity' => $quantity,
                'cost' => $cost
            ]
        ];
        
        update_post_meta($po_id, 'po_items', wp_json_encode($po_items));
        
        wp_send_json_success([
            'message' => 'Purchase order created successfully',
            'po_id' => $po_id,
            'po_url' => get_edit_post_link($po_id, 'url')
        ]);
    } else {
        wp_send_json_error('Failed to create purchase order');
    }
}

// Filter to add low stock items to the admin bar
add_action('admin_bar_menu', 'add_low_stock_to_admin_bar', 999);
function add_low_stock_to_admin_bar($wp_admin_bar) {
    if (!current_user_can('manage_inventory')) {
        return;
    }
    
    // Get low stock items
    $args = [
        'post_type' => 'inventory_item',
        'posts_per_page' => -1,
        'meta_query' => [
            'relation'
'relation' => 'AND',
            [
                'key' => 'inventory_quantity',
                'value' => 'inventory_reorder_level',
                'compare' => '<=',
                'type' => 'NUMERIC'
            ],
            [
                'key' => 'inventory_reorder_level',
                'compare' => 'EXISTS'
            ]
        ],
        'orderby' => 'title',
        'order' => 'ASC'
    ];
    
    $items = get_posts($args);
    
    // Add to admin bar if low stock items exist
    if (!empty($items)) {
        $wp_admin_bar->add_node([
            'id' => 'low_inventory',
            'title' => '<span class="ab-icon dashicons dashicons-warning" style="margin-top:2px;"></span> Low Stock Items (' . count($items) . ')',
            'href' => admin_url('edit.php?post_type=inventory_item&filter=low_stock')
        ]);
        
        // Add sub-items
        foreach ($items as $item) {
            $quantity = get_post_meta($item->ID, 'inventory_quantity', true);
            $reorder_level = get_post_meta($item->ID, 'inventory_reorder_level', true);
            $unit = get_post_meta($item->ID, 'inventory_unit', true);
            
            $wp_admin_bar->add_node([
                'id' => 'low_inv_' . $item->ID,
                'parent' => 'low_inventory',
                'title' => esc_html($item->post_title) . " ({$quantity}/{$reorder_level} {$unit})",
                'href' => get_edit_post_link($item->ID)
            ]);
        }
    }
}

// Pre-filter inventory list to highlight low stock
add_filter('parse_query', 'filter_inventory_by_stock');
function filter_inventory_by_stock($query) {
    global $pagenow;
    
    if (is_admin() && $pagenow == 'edit.php' && 
        isset($_GET['post_type']) && $_GET['post_type'] == 'inventory_item' && 
        isset($_GET['filter']) && $_GET['filter'] == 'low_stock') {
        
        $query->query_vars['meta_query'] = [
            'relation' => 'AND',
            [
                'key' => 'inventory_quantity',
                'value' => 'inventory_reorder_level',
                'compare' => '<=',
                'type' => 'NUMERIC'
            ],
            [
                'key' => 'inventory_reorder_level',
                'compare' => 'EXISTS'
            ]
        ];
    }
}

// Add inventory dashboard widget
add_action('wp_dashboard_setup', 'add_inventory_dashboard_widget');
function add_inventory_dashboard_widget() {
    if (current_user_can('manage_inventory')) {
        wp_add_dashboard_widget(
            'inventory_dashboard_widget',
            'Inventory Status',
            'render_inventory_dashboard_widget'
        );
    }
}

function render_inventory_dashboard_widget() {
    // Get low stock items
    $low_stock_args = [
        'post_type' => 'inventory_item',
        'posts_per_page' => 5,
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'inventory_quantity',
                'value' => 'inventory_reorder_level',
                'compare' => '<=',
                'type' => 'NUMERIC'
            ],
            [
                'key' => 'inventory_reorder_level',
                'compare' => 'EXISTS'
            ]
        ],
        'orderby' => 'title',
        'order' => 'ASC'
    ];
    
    $low_stock_items = get_posts($low_stock_args);
    
    // Count total inventory items
    $total_items = wp_count_posts('inventory_item');
    
    // Count pending purchase orders
    $pending_pos = wp_count_posts('purchase_order');
    
    ?>
    <div class="inventory-dashboard">
        <div class="inventory-summary">
            <div class="inventory-stat">
                <span class="stat-value"><?php echo intval($total_items->publish); ?></span>
                <span class="stat-label">Total Items</span>
            </div>
            <div class="inventory-stat">
                <span class="stat-value"><?php echo count($low_stock_items); ?></span>
                <span class="stat-label">Low Stock Items</span>
            </div>
            <div class="inventory-stat">
                <span class="stat-value"><?php echo intval($pending_pos->publish); ?></span>
                <span class="stat-label">Purchase Orders</span>
            </div>
        </div>
        
        <?php if (!empty($low_stock_items)) : ?>
            <h4>Low Stock Items</h4>
            <ul class="low-stock-list">
                <?php foreach ($low_stock_items as $item) : 
                    $quantity = get_post_meta($item->ID, 'inventory_quantity', true);
                    $reorder_level = get_post_meta($item->ID, 'inventory_reorder_level', true);
                    $unit = get_post_meta($item->ID, 'inventory_unit', true);
                ?>
                    <li>
                        <a href="<?php echo get_edit_post_link($item->ID); ?>">
                            <?php echo esc_html($item->post_title); ?>
                        </a>
                        <span class="stock-level"><?php echo "{$quantity}/{$reorder_level} {$unit}"; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p>
                <a href="<?php echo admin_url('edit.php?post_type=inventory_item&filter=low_stock'); ?>" class="button">
                    View All Low Stock Items
                </a>
            </p>
        <?php else : ?>
            <p>No low stock items found.</p>
        <?php endif; ?>
    </div>
    <style>
        .inventory-summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .inventory-stat {
            text-align: center;
            flex: 1;
            padding: 10px;
            background: #f8f8f8;
            border-radius: 4px;
            margin: 0 5px;
        }
        .inventory-stat:first-child {
            margin-left: 0;
        }
        .inventory-stat:last-child {
            margin-right: 0;
        }
        .stat-value {
            display: block;
            font-size: 24px;
            font-weight: bold;
        }
        .stat-label {
            display: block;
            font-size: 12px;
        }
        .low-stock-list {
            margin: 0;
        }
        .low-stock-list li {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }
        .stock-level {
            color: #d63638;
            font-weight: bold;
        }
    </style>
    <?php
}

// Add custom columns to inventory list
add_filter('manage_inventory_item_posts_columns', 'set_inventory_columns');
function set_inventory_columns($columns) {
    $columns = [
        'cb' => $columns['cb'],
        'title' => __('Item Name'),
        'sku' => __('SKU'),
        'category' => __('Category'),
        'quantity' => __('Quantity'),
        'reorder_level' => __('Reorder Level'),
        'supplier' => __('Supplier'),
        'last_updated' => __('Last Updated')
    ];
    return $columns;
}

// Fill custom columns
add_action('manage_inventory_item_posts_custom_column', 'inventory_custom_column', 10, 2);
function inventory_custom_column($column, $post_id) {
    switch ($column) {
        case 'sku':
            echo get_post_meta($post_id, 'inventory_sku', true);
            break;
        case 'category':
            $terms = get_the_terms($post_id, 'inventory_category');
            if (!empty($terms)) {
                $term_names = [];
                foreach ($terms as $term) {
                    $term_names[] = $term->name;
                }
                echo implode(', ', $term_names);
            }
            break;
        case 'quantity':
            $quantity = get_post_meta($post_id, 'inventory_quantity', true);
            $unit = get_post_meta($post_id, 'inventory_unit', true);
            $reorder = get_post_meta($post_id, 'inventory_reorder_level', true);
            
            if ($quantity <= $reorder) {
                echo '<span style="color: red; font-weight: bold;">' . esc_html($quantity) . ' ' . esc_html($unit) . '</span>';
            } else {
                echo esc_html($quantity) . ' ' . esc_html($unit);
            }
            break;
        case 'reorder_level':
            $reorder = get_post_meta($post_id, 'inventory_reorder_level', true);
            $unit = get_post_meta($post_id, 'inventory_unit', true);
            echo esc_html($reorder) . ' ' . esc_html($unit);
            break;
        case 'supplier':
            $supplier_id = get_post_meta($post_id, 'inventory_supplier', true);
            if ($supplier_id) {
                $supplier = get_post($supplier_id);
                if ($supplier) {
                    echo '<a href="' . get_edit_post_link($supplier_id) . '">' . esc_html($supplier->post_title) . '</a>';
                }
            }
            break;
        case 'last_updated':
            echo get_the_modified_date('Y-m-d H:i', $post_id);
            break;
    }
}

// Make columns sortable
add_filter('manage_edit-inventory_item_sortable_columns', 'inventory_sortable_columns');
function inventory_sortable_columns($columns) {
    $columns['sku'] = 'sku';
    $columns['quantity'] = 'quantity';
    $columns['last_updated'] = 'modified';
    return $columns;
}

// Register custom sort
add_action('pre_get_posts', 'inventory_custom_orderby');
function inventory_custom_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if ($query->get('post_type') == 'inventory_item') {
        if ($query->get('orderby') == 'sku') {
            $query->set('meta_key', 'inventory_sku');
            $query->set('orderby', 'meta_value');
        } elseif ($query->get('orderby') == 'quantity') {
            $query->set('meta_key', 'inventory_quantity');
            $query->set('orderby', 'meta_value_num');
        }
    }
}

// Add inventory history log view
add_action('add_meta_boxes', function() {
    add_meta_box(
        'inventory_log_history',
        'Inventory Log History',
        'render_inventory_log_history',
        'inventory_item',
        'normal',
        'low'
    );
});

function render_inventory_log_history($post) {
    $logs = get_post_meta($post->ID, 'inventory_logs', true);
    $logs = !empty($logs) ? json_decode($logs, true) : [];
    
    if (empty($logs)) {
        echo '<p>No inventory history available.</p>';
        return;
    }
    
    // Sort logs by date descending
    usort($logs, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    $unit = get_post_meta($post->ID, 'inventory_unit', true);
?>
    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Previous Quantity</th>
                <th>New Quantity</th>
                <th>Change</th>
                <th>Reason</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log) : 
                $change = $log['new'] - $log['previous'];
                $change_class = $change > 0 ? 'positive' : ($change < 0 ? 'negative' : '');
                $change_prefix = $change > 0 ? '+' : '';
            ?>
                <tr>
                    <td><?php echo date('Y-m-d H:i', strtotime($log['date'])); ?></td>
                    <td><?php echo esc_html($log['user']); ?></td>
                    <td><?php echo esc_html($log['previous']) . ' ' . esc_html($unit); ?></td>
                    <td><?php echo esc_html($log['new']) . ' ' . esc_html($unit); ?></td>
                    <td class="<?php echo $change_class; ?>">
                        <?php echo $change_prefix . esc_html($change) . ' ' . esc_html($unit); ?>
                    </td>
                    <td><?php echo esc_html($log['reason']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <style>
        .positive { color: green; }
        .negative { color: red; }
    </style>
<?php
}

// Add inventory reporting page
add_action('admin_menu', 'add_inventory_reports_page');
function add_inventory_reports_page() {
    add_submenu_page(
        'edit.php?post_type=inventory_item',
        'Inventory Reports',
        'Reports',
        'manage_inventory',
        'inventory-reports',
        'render_inventory_reports_page'
    );
}

function render_inventory_reports_page() {
    // Get inventory value
    $inventory_items = get_posts([
        'post_type' => 'inventory_item',
        'posts_per_page' => -1
    ]);
    
    $total_value = 0;
    $category_values = [];
    
    foreach ($inventory_items as $item) {
        $quantity = floatval(get_post_meta($item->ID, 'inventory_quantity', true));
        $cost = floatval(get_post_meta($item->ID, 'inventory_cost', true));
        $item_value = $quantity * $cost;
        $total_value += $item_value;
        
        // Category breakdown
        $terms = get_the_terms($item->ID, 'inventory_category');
        if (!empty($terms)) {
            foreach ($terms as $term) {
                if (!isset($category_values[$term->term_id])) {
                    $category_values[$term->term_id] = [
                        'name' => $term->name,
                        'value' => 0
                    ];
                }
                $category_values[$term->term_id]['value'] += $item_value;
            }
        } else {
            if (!isset($category_values['uncategorized'])) {
                $category_values['uncategorized'] = [
                    'name' => 'Uncategorized',
                    'value' => 0
                ];
            }
            $category_values['uncategorized']['value'] += $item_value;
        }
    }
    
    // Sort categories by value
    usort($category_values, function($a, $b) {
        return $b['value'] - $a['value'];
    });
    
    ?>
    <div class="wrap">
        <h1>Inventory Reports</h1>
        
        <div class="inventory-report-section">
            <h2>Inventory Summary</h2>
            <div class="card">
                <h3>Total Inventory Value</h3>
                <p class="inventory-value">$<?php echo number_format($total_value, 2); ?></p>
                <p>Based on <?php echo count($inventory_items); ?> inventory items.</p>
            </div>
        </div>
        
        <div class="inventory-report-section">
            <h2>Value by Category</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Value</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($category_values as $category) : 
                        $percentage = ($category['value'] / $total_value) * 100;
                    ?>
                        <tr>
                            <td><?php echo esc_html($category['name']); ?></td>
                            <td>$<?php echo number_format($category['value'], 2); ?></td>
                            <td>
                                <div class="percentage-bar">
                                    <div class="percentage-fill" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                    <span class="percentage-text"><?php echo number_format($percentage, 1); ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="inventory-report-section">
            <h2>Recent Activity</h2>
            <?php
            // Get recent POs
            $recent_pos = get_posts([
                'post_type' => 'purchase_order',
                'posts_per_page' => 5,
                'orderby' => 'date',
                'order' => 'DESC'
            ]);
            
            if (!empty($recent_pos)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Purchase Order</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_pos as $po) : 
                            $supplier_id = get_post_meta($po->ID, 'po_supplier', true);
                            $supplier = get_post($supplier_id);
                            $status = get_post_meta($po->ID, 'po_status', true);
                            $total = get_post_meta($po->ID, 'po_total', true);
                            
                            $status_class = '';
                            switch ($status) {
                                case 'received':
                                    $status_class = 'status-complete';
                                    break;
                                case 'pending':
                                    $status_class = 'status-pending';
                                    break;
                                case 'ordered':
                                    $status_class = 'status-ordered';
                                    break;
                                case 'cancelled':
                                    $status_class = 'status-cancelled';
                                    break;
                            }
                        ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($po->ID); ?>">
                                        <?php echo esc_html($po->post_title); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo $supplier ? esc_html($supplier->post_title) : 'N/A'; ?>
                                </td>
                                <td>
                                    <span class="status <?php echo $status_class; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>$<?php echo number_format($total, 2); ?></td>
                                <td><?php echo get_the_date('Y-m-d', $po->ID); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No recent purchase orders found.</p>
            <?php endif; ?>
        </div>
    </div>
    <style>
        .inventory-report-section {
            margin-bottom: 30px;
        }
        .card {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 3px;
            margin-bottom: 20px;
        }
        .inventory-value {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }
        .percentage-bar {
            background: #f0f0f0;
            height: 20px;
            width: 100%;
            border-radius: 3px;
            position: relative;
        }
        .percentage-fill {
            background: #2271b1;
            height: 20px;
            border-radius: 3px;
        }
        .percentage-text {
            position: absolute;
            top: 0;
            left: 5px;
            line-height: 20px;
            color: #fff;
            text-shadow: 0 0 2px rgba(0,0,0,0.5);
        }
        .status {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        .status-complete {
            background: #c6e1c6;
            color: #5b841b;
        }
        .status-pending {
            background: #f8dda7;
            color: #94660c;
        }
        .status-ordered {
            background: #c8d7e1;
            color: #2e4453;
        }
        .status-cancelled {
            background: #eba3a3;
            color: #761919;
        }
    </style>
    <?php
}

// Add custom roles for inventory management
add_action('init', 'register_inventory_roles');
function register_inventory_roles() {
    // Check if role already exists
    if (!get_role('restaurant_inventory')) {
        // Add inventory manager role
        add_role(
            'restaurant_inventory',
            'Inventory Manager',
            [
                'read' => true,
                'create_posts' => true,
                'edit_posts' => true,
                'delete_posts' => true,
                'publish_posts' => true,
                'upload_files' => true,
                'manage_inventory' => true,
                'create_purchase_orders' => true,
            ]
        );
    }
}

// Add capabilities to admin and manager roles
add_action('admin_init', 'add_inventory_caps');
function add_inventory_caps() {
    // Add caps to admin
    $admin = get_role('administrator');
    if ($admin) {
        $admin->add_cap('manage_inventory');
        $admin->add_cap('create_purchase_orders');
    }
    
    // Add caps to restaurant manager role if it exists
    $manager = get_role('restaurant_manager');
    if ($manager) {
        $manager->add_cap('manage_inventory');
        $manager->add_cap('create_purchase_orders');
    }
}

// Include JavaScript for purchase orders
add_action('admin_enqueue_scripts', 'inventory_admin_scripts');
function inventory_admin_scripts($hook) {
    global $post;
    
    if ($hook == 'post-new.php' || $hook == 'post.php') {
        if (isset($post) && $post->post_type == 'purchase_order') {
            wp_enqueue_script('inventory-po-script', plugin_dir_url(__FILE__) . 'js/purchase-order.js', ['jquery'], '1.0.0', true);
            
            wp_localize_script('inventory-po-script', 'po_data', [
                'nonce' => wp_create_nonce('inventory_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php')
            ]);
        }
    }
}

// Export inventory to CSV
add_action('admin_post_export_inventory', 'export_inventory_to_csv');
function export_inventory_to_csv() {
    // Check permissions
    if (!current_user_can('manage_inventory')) {
        wp_die('Permission denied');
    }
    
    // Get inventory items
    $items = get_posts([
        'post_type' => 'inventory_item',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=inventory_export_' . date('Y-m-d') . '.csv');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, [
        'ID', 'Item Name', 'SKU', 'Quantity', 'Unit', 'Cost', 'Reorder Level', 'Location', 'Supplier'
    ]);
    
    // Adds data rows
    foreach ($items as $item) {
        $supplier_id = get_post_meta($item->ID, 'inventory_supplier', true);
        $supplier = $supplier_id ? get_the_title($supplier_id) : '';
        
        fputcsv($output, [
            $item->ID,
            $item->post_title,
            get_post_meta($item->ID, 'inventory_sku', true),
            get_post_meta($item->ID, 'inventory_quantity', true),
            get_post_meta($item->ID, 'inventory_unit', true),
            get_post_meta($item->ID, 'inventory_cost', true),
            get_post_meta($item->ID, 'inventory_reorder_level', true),
            get_post_meta($item->ID, 'inventory_location', true),
            $supplier
        ]);
    }
    
    fclose($output);
    exit;
}

// Add export button to inventory list page
add_action('restrict_manage_posts', function($post_type) {
    if ($post_type === 'inventory_item' && current_user_can('manage_inventory')) {
        ?>
        <a href="<?php echo admin_url('admin-post.php?action=export_inventory'); ?>" class="button">
            Export to CSV
        </a>
        <?php
    }
});
// Add shortcode for displaying inventory on front-end pages
add_shortcode('display_inventory', 'inventory_display_shortcode');

function inventory_display_shortcode($atts) {
    // Parses attributes
    $atts = shortcode_atts([
        'category' => '',   // Filter by category slug
        'limit' => 20,      // Number of items to show
        'orderby' => 'title', // Sort by field
        'order' => 'ASC',   // Sort direction
        'show_stock' => 'yes', // Show stock level
        'show_sku' => 'yes', // Show SKU
        'low_stock_only' => 'no' // Show only low stock items
    ], $atts);
    
    // Sets up query args
    $args = [
        'post_type' => 'inventory_item',
        'posts_per_page' => intval($atts['limit']),
        'orderby' => sanitize_text_field($atts['orderby']),
        'order' => sanitize_text_field($atts['order'])
    ];
    
    // Adds category filter if specified
    if (!empty($atts['category'])) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'inventory_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['category'])
            ]
        ];
    }
    
    // Adds low stock filter if required
    if ($atts['low_stock_only'] === 'yes') {
        $args['meta_query'] = [
            'relation' => 'AND',
            [
                'key' => 'inventory_quantity',
                'value' => 'inventory_reorder_level',
                'compare' => '<=',
                'type' => 'NUMERIC'
            ],
            [
                'key' => 'inventory_reorder_level',
                'compare' => 'EXISTS'
            ]
        ];
    }
    
    // Gets inventory items
    $inventory_items = get_posts($args);
    
    // Starts output buffering
    ob_start();
    
    if (!empty($inventory_items)) {
        ?>
        <div class="inventory-display-wrapper">
            <table class="inventory-display-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <?php if ($atts['show_sku'] === 'yes') : ?>
                            <th>SKU</th>
                        <?php endif; ?>
                        <?php if ($atts['show_stock'] === 'yes') : ?>
                            <th>Stock Level</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventory_items as $item) : 
                        $quantity = get_post_meta($item->ID, 'inventory_quantity', true);
                        $unit = get_post_meta($item->ID, 'inventory_unit', true);
                        $reorder_level = get_post_meta($item->ID, 'inventory_reorder_level', true);
                        $sku = get_post_meta($item->ID, 'inventory_sku', true);
                        
                        // Determines if stock is low
                        $is_low_stock = ($quantity <= $reorder_level);
                        $stock_class = $is_low_stock ? 'low-stock' : 'in-stock';
                    ?>
                        <tr>
                            <td><?php echo esc_html($item->post_title); ?></td>
                            <?php if ($atts['show_sku'] === 'yes') : ?>
                                <td><?php echo esc_html($sku); ?></td>
                            <?php endif; ?>
                            <?php if ($atts['show_stock'] === 'yes') : ?>
                                <td class="<?php echo $stock_class; ?>">
                                    <?php echo esc_html($quantity) . ' ' . esc_html($unit); ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <style>
            .inventory-display-table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            .inventory-display-table th, 
            .inventory-display-table td {
                padding: 8px;
                border: 1px solid #ddd;
                text-align: left;
            }
            .inventory-display-table th {
                background-color: #f5f5f5;
                font-weight: bold;
            }
            .inventory-display-table tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .low-stock {
                color: #d63638;
                font-weight: bold;
            }
        </style>
        <?php
    } else {
        echo '<p>No inventory items found.</p>';
    }
    
    // Returns the buffered content
    return ob_get_clean();
}

// Adds a more detailed inventory display
add_shortcode('display_inventory_detailed', 'inventory_display_detailed_shortcode');

function inventory_display_detailed_shortcode($atts) {
    // Parses attributes
    $atts = shortcode_atts([
        'category' => '',    // Filter by category slug
        'limit' => 10,       // Number of items to show
        'orderby' => 'title', // Sort by field
        'order' => 'ASC',    // Sort direction
        'show_supplier' => 'yes', // Show supplier info
        'show_location' => 'yes', // Show storage location
        'show_cost' => 'no'  // Show cost (admin only)
    ], $atts);
    
    // Sets up query args
    $args = [
        'post_type' => 'inventory_item',
        'posts_per_page' => intval($atts['limit']),
        'orderby' => sanitize_text_field($atts['orderby']),
        'order' => sanitize_text_field($atts['order'])
    ];
    
    // Adds a category filter if specified
    if (!empty($atts['category'])) {
        $args['tax_query'] = [
            [
                'taxonomy' => 'inventory_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['category'])
            ]
        ];
    }
    
    // Gets the inventory items
    $inventory_items = get_posts($args);
    
    // Starts the output buffering
    ob_start();
    
    if (!empty($inventory_items)) {
        ?>
        <div class="inventory-detailed-wrapper">
            <?php foreach ($inventory_items as $item) : 
                $quantity = get_post_meta($item->ID, 'inventory_quantity', true);
                $unit = get_post_meta($item->ID, 'inventory_unit', true);
                $reorder_level = get_post_meta($item->ID, 'inventory_reorder_level', true);
                $sku = get_post_meta($item->ID, 'inventory_sku', true);
                $location = get_post_meta($item->ID, 'inventory_location', true);
                $cost = get_post_meta($item->ID, 'inventory_cost', true);
                $supplier_id = get_post_meta($item->ID, 'inventory_supplier', true);
                $supplier = $supplier_id ? get_post($supplier_id) : null;
                
                // Determines if a stock is low
                $is_low_stock = ($quantity <= $reorder_level);
                $stock_class = $is_low_stock ? 'low-stock' : 'in-stock';
                
                // Gets the different categories
                $categories = get_the_terms($item->ID, 'inventory_category');
                $category_names = [];
                if ($categories && !is_wp_error($categories)) {
                    foreach ($categories as $category) {
                        $category_names[] = $category->name;
                    }
                }
            ?>
                <div class="inventory-item-card">
                    <h3 class="item-title"><?php echo esc_html($item->post_title); ?></h3>
                    <div class="item-meta">
                        <div class="meta-row">
                            <span class="meta-label">SKU:</span>
                            <span class="meta-value"><?php echo esc_html($sku); ?></span>
                        </div>
                        
                        <div class="meta-row">
                            <span class="meta-label">Stock Level:</span>
                            <span class="meta-value <?php echo $stock_class; ?>"><?php echo esc_html($quantity) . ' ' . esc_html($unit); ?></span>
                        </div>
                        
                        <?php if (!empty($category_names)) : ?>
                        <div class="meta-row">
                            <span class="meta-label">Categories:</span>
                            <span class="meta-value"><?php echo esc_html(implode(', ', $category_names)); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($atts['show_location'] === 'yes' && !empty($location)) : ?>
                        <div class="meta-row">
                            <span class="meta-label">Storage Location:</span>
                            <span class="meta-value"><?php echo esc_html($location); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($atts['show_supplier'] === 'yes' && $supplier) : ?>
                        <div class="meta-row">
                            <span class="meta-label">Supplier:</span>
                            <span class="meta-value"><?php echo esc_html($supplier->post_title); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($atts['show_cost'] === 'yes' && current_user_can('manage_inventory')) : ?>
                        <div class="meta-row">
                            <span class="meta-label">Cost per Unit:</span>
                            <span class="meta-value">$<?php echo number_format(floatval($cost), 2); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <style>
            .inventory-detailed-wrapper {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                grid-gap: 20px;
                margin: 20px 0;
            }
            .inventory-item-card {
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 15px;
                background-color: #fff;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }
            .item-title {
                margin-top: 0;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            .meta-row {
                margin-bottom: 8px;
                display: flex;
            }
            .meta-label {
                font-weight: bold;
                min-width: 120px;
            }
            .low-stock {
                color: #d63638;
                font-weight: bold;
            }
            .in-stock {
                color: #00a32a;
            }
        </style>
        <?php
    } else {
        echo '<p>No inventory items found.</p>';
    }
    
    // Returns the buffered content
    return ob_get_clean();
}
