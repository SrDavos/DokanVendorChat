<?php

class Dokan_Chat_Controller extends WP_REST_Controller {
    
    public function register_routes() {
        register_rest_route('dokan/v1', '/chat/send', array(
            'methods' => 'POST',
            'callback' => array($this, 'send_message'),
            'permission_callback' => array($this, 'check_permission')
        ));

        register_rest_route('dokan/v1', '/chat/messages', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_messages'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }

    public function send_message($request) {
        $order_id = intval($request['order_id']);
        $vendor_id = intval($request['vendor_id']);
        $message = sanitize_textarea_field($request['message']);

        if (empty($message)) {
            return new WP_Error('empty_message', 'Message cannot be empty', array('status' => 400));
        }

        $result = Dokan_Chat::save_message($order_id, $vendor_id, $message);
        
        if ($result) {
            return array(
                'success' => true,
                'message' => 'Message sent successfully'
            );
        }

        return new WP_Error('send_failed', 'Failed to send message', array('status' => 500));
    }

    public function get_messages($request) {
        $order_id = intval($request['order_id']);
        $vendor_id = intval($request['vendor_id']);

        $messages = Dokan_Chat::get_messages($order_id, $vendor_id);
        
        return array_map(function($msg) {
            return array(
                'id' => $msg->id,
                'message' => $msg->message,
                'is_customer' => (bool)$msg->is_customer,
                'created_at' => $msg->created_at
            );
        }, $messages);
    }

    public function check_permission() {
        return is_user_logged_in();
    }
} 