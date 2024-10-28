<?php
    if (!defined('ABSPATH')) exit;
    $current_user_id = get_current_user_id();

    // Get orders directly from WooCommerce
    $order_query = new WC_Order_Query(array(
        'limit' => -1,
        'meta_key' => '_dokan_vendor_id',
        'meta_value' => $current_user_id,
        'status' => array('processing', 'completed', 'on-hold'), // Note: without 'wc-' prefix
        'return' => 'objects'
    ));

    $orders = $order_query->get_orders();
?>

<div class="dokan-dashboard-wrap">
    <div class="dokan-dashboard-content dokan-orders-content">
        <article class="dokan-orders-area">
            <header class="dokan-dashboard-header">
                <h1 class="entry-title"><?php _e('Orders & Messages', 'dokan'); ?></h1>
            </header>

            <table class="dokan-table dokan-table-striped">
                <thead>
                    <tr>
                        <th><?php _e('Order', 'dokan'); ?></th>
                        <th><?php _e('Customer', 'dokan'); ?></th>
                        <th><?php _e('Status', 'dokan'); ?></th>
                        <th><?php _e('Date', 'dokan'); ?></th>
                        <th><?php _e('Total', 'dokan'); ?></th>
                        <th><?php _e('Actions', 'dokan'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (!empty($orders)) {
                        foreach ($orders as $order) {
                            $customer_id = $order->get_customer_id();
                            $user_info = get_userdata($customer_id);
                            $status = $order->get_status();
                            ?>
                            <tr>
                                <td class="dokan-order-id">
                                    <?php echo '#' . $order->get_id(); ?>
                                </td>
                                <td class="dokan-order-customer">
                                    <?php 
                                    if ($user_info) {
                                        echo esc_html($user_info->display_name);
                                        echo '<br><small>' . esc_html($user_info->user_email) . '</small>';
                                    } else {
                                        echo esc_html($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
                                    }
                                    ?>
                                </td>
                                <td class="dokan-order-status">
                                    <span class="order-status status-<?php echo esc_attr($status); ?>">
                                        <?php echo wc_get_order_status_name($status); ?>
                                    </span>
                                </td>
                                <td class="dokan-order-date">
                                    <?php echo esc_html($order->get_date_created()->date_i18n(get_option('date_format'))); ?>
                                </td>
                                <td class="dokan-order-total">
                                    <?php echo $order->get_formatted_order_total(); ?>
                                </td>
                                <td class="dokan-order-action">
                                    <?php if ($customer_id): ?>
                                        <a href="#" class="dokan-btn dokan-btn-default dokan-btn-sm open-chat" 
   data-order-id="<?php echo esc_attr($order->get_id()); ?>"
   data-customer-id="<?php echo esc_attr($customer_id); ?>"
   data-customer-name="<?php echo esc_attr($user_info ? $user_info->display_name : ''); ?>">
                                            <?php _e('Chat', 'dokan'); ?>
                                            <?php 
                                            global $wpdb;
                                            $unread = $wpdb->get_var($wpdb->prepare(
                                                "SELECT COUNT(*) FROM {$wpdb->prefix}dokan_chat_messages 
                                                WHERE order_id = %d AND receiver_id = %d AND is_read = 0",
                                                $order->get_id(), $current_user_id
                                            ));
                                            if ($unread > 0): ?>
                                                <span class="unread-count"><?php echo $unread; ?></span>
                                            <?php endif; ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                    } else { ?>
                        <tr>
                            <td colspan="6"><?php _e('No orders found', 'dokan'); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

            <!-- Chat Messages Area -->
            <div class="dokan-chat-messages">
                <div class="chat-header">
                    <button class="dokan-btn dokan-btn-theme back-to-orders">
                        <?php _e('← Back to Orders', 'dokan'); ?>
                    </button>
                    <h3 class="chat-with"></h3>
                    <span class="order-reference"></span>
                </div>
                <div id="dokan-chat-messages-container">
                    <!-- Messages will be loaded here -->
                </div>
                <div class="dokan-chat-input">
                    <textarea id="dokan-chat-message" placeholder="<?php _e('Type your message...', 'dokan'); ?>"></textarea>
                    <button id="dokan-chat-send" class="dokan-btn dokan-btn-theme">
                        <?php _e('Send', 'dokan'); ?>
                    </button>
                </div>
            </div>
        </article>
    </div>
</div>
