<?php
/*
Plugin Name: Dokan Chat
Description: Chat functionality for Dokan vendors and customers
Version: 1.0.0
*/

defined('ABSPATH') || exit;

// Register activation hook
register_activation_hook(__FILE__, array('Dokan_Chat', 'create_tables'));

// Initialize the plugin
add_action('plugins_loaded', 'dokan_chat_init');

function dokan_chat_init() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-dokan-chat.php';
    require_once plugin_dir_path(__FILE__) . 'includes/api/class-dokan-chat-controller.php';
    
    // Initialize REST API
    add_action('rest_api_init', function() {
        $controller = new Dokan_Chat_Controller();
        $controller->register_routes();
    });

    // Add chat template to Dokan dashboard
    add_filter('dokan_get_dashboard_nav', 'add_chat_menu');
    add_action('dokan_load_custom_template', 'load_chat_template');
}

function add_chat_menu($menus) {
    $menus['messages'] = array(
        'title' => __('Messages', 'dokan'),
        'icon'  => '<i class="far fa-comments"></i>',
        'url'   => dokan_get_navigation_url('messages'),
        'pos'   => 55
    );
    return $menus;
}

function load_chat_template($query_vars) {
    if (isset($query_vars['messages'])) {
        require_once plugin_dir_path(__FILE__) . 'templates/customer-chat.php';
    }
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'dokan_chat_scripts', 100);

function dokan_chat_scripts() {
    if (dokan_is_seller_dashboard() || (isset($_GET['messages']) && is_account_page())) {
        // Dequeue potentially conflicting styles
        wp_dequeue_style('dokan-style');
        wp_dequeue_style('dokan-fontawesome');
        
        // Enqueue our styles with higher priority
        wp_enqueue_style(
            'dokan-chat-reset',
            plugins_url('assets/css/chat-reset.css', __FILE__),
            array(),
            '1.0.0'
        );

        wp_enqueue_style(
            'dokan-chat-style',
            plugins_url('assets/css/chat.css', __FILE__),
            array('dokan-chat-reset'),
            '1.0.0'
        );

        wp_enqueue_script(
            'dokan-chat-script',
            plugins_url('assets/js/chat.js', __FILE__),
            array('jquery'),
            '1.0.0',
            true
        );

        wp_localize_script('dokan-chat-script', 'dokanChat', array(
            'rest' => array(
                'root' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest')
            )
        ));
    }
} 