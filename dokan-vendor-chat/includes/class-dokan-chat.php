<?php

class Dokan_Chat {
    private static $table_name = 'dokan_chat_messages';

    public static function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::$table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            vendor_id bigint(20) NOT NULL,
            customer_id bigint(20) NOT NULL,
            message text NOT NULL,
            is_customer tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Add these methods for message handling
    public static function save_message($order_id, $vendor_id, $message, $is_customer = true) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'vendor_id' => $vendor_id,
                'customer_id' => get_current_user_id(),
                'message' => $message,
                'is_customer' => $is_customer ? 1 : 0
            ),
            array('%d', '%d', '%d', '%s', '%d')
        );
    }

    public static function get_messages($order_id, $vendor_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . self::$table_name;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE order_id = %d AND vendor_id = %d 
            ORDER BY created_at ASC",
            $order_id,
            $vendor_id
        ));
    }
} 