<?php
// Enqueues the styles
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', ['parent-style']);
});

// Includes the reservation functionality
require_once get_stylesheet_directory() . "/reservations.php";

// Includes additional custom logic 
foreach (['roles', 'dashboard', 'ajax', 'emails'] as $file) {
    $file_path = get_stylesheet_directory() . "/inc/{$file}.php";
    if (file_exists($file_path)) {
        require_once $file_path;
    }
