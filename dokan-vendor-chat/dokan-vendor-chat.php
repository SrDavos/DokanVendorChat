<?php
/*
Plugin Name: Dokan Vendor Chat
Description: Adds chat functionality between vendors and customers
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

class DokanVendorChat {
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'create_tables'));
        
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('dokan_get_dashboard_nav', array($this, 'add_chat_menu'));
        add_filter('dokan_query_var_filter', array($this, 'add_chat_endpoint'));
        add_action('dokan_load_custom_template', array($this, 'load_chat_template'));
        
        // Add AJAX handlers
        add_action('wp_ajax_dokan_load_chat_messages', array($this, 'ajax_load_messages'));
        add_action('wp_ajax_dokan_send_chat_message', array($this, 'ajax_send_message'));
        
        // Add customer chat endpoint
        add_action('init', array($this, 'add_customer_chat_endpoint'));
        add_filter('woocommerce_account_menu_items', array($this, 'add_chat_menu_item'));
        add_action('woocommerce_account_chat_endpoint', array($this, 'customer_chat_content'));
    }

    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}dokan_chat_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            sender_id bigint(20) NOT NULL,
            receiver_id bigint(20) NOT NULL,
            message text NOT NULL,
            created_at datetime NOT NULL,
            is_read tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function init() {
        if (!class_exists('WeDevs_Dokan')) {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>Dokan Vendor Chat requires Dokan plugin to be installed and activated.</p></div>';
            });
            return;
        }
    }

    public function enqueue_scripts() {
        if (dokan_is_seller_dashboard() || is_account_page()) {
            wp_enqueue_style('dokan-chat', plugins_url('assets/css/style.css', __FILE__));
            wp_enqueue_script('dokan-chat', plugins_url('assets/js/chat.js', __FILE__), array('jquery'), '1.0', true);
            wp_localize_script('dokan-chat', 'dokan_chat_vars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dokan_chat_nonce')
            ));
        }
    }

    public function add_chat_menu($menu) {
        $menu['chat'] = array(
            'title' => __('Messages', 'dokan'),
            'icon'  => '<i class="fas fa-comments"></i>',
            'url'   => dokan_get_navigation_url('chat'),
            'pos'   => 51
        );
        return $menu;
    }

    public function add_chat_endpoint($query_vars) {
        $query_vars['chat'] = 'chat';
        return $query_vars;
    }

    public function load_chat_template($query_vars) {
        if (isset($query_vars['chat'])) {
            require_once dirname(__FILE__) . '/templates/vendor-chat.php';
            return;
        }
    }

    public function ajax_load_messages() {
        check_ajax_referer('dokan_chat_nonce', 'nonce');
        
        $order_id = intval($_POST['order_id']);
        
        global $wpdb;
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}dokan_chat_messages 
            WHERE order_id = %d 
            ORDER BY created_at ASC",
            $order_id
        ));
        
        // Mark messages as read
        $current_user_id = get_current_user_id();
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}dokan_chat_messages 
            SET is_read = 1 
            WHERE order_id = %d 
            AND receiver_id = %d",
            $order_id, $current_user_id
        ));
        
        wp_send_json_success($messages);
    }

    public function ajax_send_message() {
        check_ajax_referer('dokan_chat_nonce', 'nonce');
        
        $order_id = intval($_POST['order_id']);
        $receiver_id = intval($_POST['receiver_id']);
        $message = sanitize_textarea_field($_POST['message']);
        $sender_id = get_current_user_id();
        
        global $wpdb;
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'dokan_chat_messages',
            array(
                'order_id' => $order_id,
                'sender_id' => $sender_id,
                'receiver_id' => $receiver_id,
                'message' => $message,
                'created_at' => current_time('mysql'),
                'is_read' => 0
            ),
            array('%d', '%d', '%d', '%s', '%s', '%d')
        );
        
        if ($inserted) {
            wp_send_json_success(array(
                'message' => $message,
                'sender_id' => $sender_id,
                'created_at' => current_time('mysql')
            ));
        } else {
            wp_send_json_error('Failed to send message');
        }
    }

    public function add_customer_chat_endpoint() {
        add_rewrite_endpoint('chat', EP_ROOT | EP_PAGES);
    }

    public function add_chat_menu_item($items) {
        $logout = $items['customer-logout'];
        unset($items['customer-logout']);
        
        $items['chat'] = __('Messages', 'dokan');
        $items['customer-logout'] = $logout;
        
        return $items;
    }

    public function customer_chat_content() {
        $current_user_id = get_current_user_id();
        
        global $wpdb;
        $orders = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT 
                o.ID as order_id,
                o.post_date as order_date,
                pm.meta_value as vendor_id,
                u.display_name as vendor_name,
                (SELECT COUNT(*) FROM {$wpdb->prefix}dokan_chat_messages 
                 WHERE order_id = o.ID AND receiver_id = %d AND is_read = 0) as unread_count
            FROM {$wpdb->posts} o
            JOIN {$wpdb->postmeta} pm ON o.ID = pm.post_id AND pm.meta_key = '_dokan_vendor_id'
            JOIN {$wpdb->users} u ON pm.meta_value = u.ID
            WHERE o.post_type = 'shop_order'
            AND o.post_status != 'trash'
            AND EXISTS (
                SELECT 1 FROM {$wpdb->postmeta}
                WHERE post_id = o.ID
                AND meta_key = '_customer_user'
                AND meta_value = %d
            )
            ORDER BY o.post_date DESC
        ", $current_user_id, $current_user_id));
        
        require_once dirname(__FILE__) . '/templates/customer-chat.php';
    }
}

new DokanVendorChat();
