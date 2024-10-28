<?php
defined('ABSPATH') || exit;

// Get customer orders using WooCommerce's standard approach
$customer_orders = wc_get_orders(array(
    'customer' => get_current_user_id(),
    'limit'    => -1,
    'orderby'  => 'date',
    'order'    => 'DESC',
));
?>

<div class="dokan-chat-wrap">
    <!-- Orders Table Section -->
    <div id="dokan-orders-section">
        <h2><?php esc_html_e('My Orders & Messages', 'dokan'); ?></h2>
        
        <?php if (!empty($customer_orders)) : ?>
            <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customer_orders as $order) : 
                        $vendor_id = dokan_get_seller_id_by_order($order->get_id());
                        $vendor = dokan()->vendor->get($vendor_id);
                        ?>
                        <tr>
                            <td>#<?php echo $order->get_order_number(); ?></td>
                            <td><?php echo $order->get_date_created()->format('Y-m-d'); ?></td>
                            <td><?php echo wc_get_order_status_name($order->get_status()); ?></td>
                            <td><?php echo $order->get_formatted_order_total(); ?></td>
                            <td>
                                <button type="button" 
                                        class="button dokan-chat-btn" 
                                        data-order-id="<?php echo esc_attr($order->get_id()); ?>"
                                        data-vendor-id="<?php echo esc_attr($vendor_id); ?>"
                                        data-vendor-name="<?php echo esc_attr($vendor->get_shop_name()); ?>">
                                    <?php esc_html_e('Chat', 'dokan'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php esc_html_e('No orders found.', 'dokan'); ?></p>
        <?php endif; ?>
    </div>

    <!-- Chat Interface (Initially Hidden) -->
    <div class="dokan-chat-interface">
        <div class="chat-header">
            <div class="chat-header-info">
                <span id="vendor-name" class="vendor-name"></span>
                <span id="order-number" class="order-number"></span>
            </div>
            <button id="dokan-chat-close" class="close-btn">&times;</button>
        </div>
        
        <div id="dokan-chat-messages" class="chat-messages"></div>
        
        <div class="chat-footer">
            <div class="chat-input-wrapper">
                <textarea id="dokan-chat-input" 
                         class="chat-input" 
                         placeholder="<?php esc_attr_e('Type your message...', 'dokan'); ?>"
                         rows="1"></textarea>
                <button id="dokan-chat-send" class="dokan-btn dokan-btn-theme send-btn">
                    <?php esc_html_e('Send', 'dokan'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
