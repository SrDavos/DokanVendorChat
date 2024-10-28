<?php if (!defined('ABSPATH')) exit; ?>

<div class="dokan-dashboard-wrap">
    <div class="dokan-dashboard-content dokan-orders-content">
        <article class="dokan-orders-area">
            <header class="dokan-dashboard-header">
                <h1 class="entry-title"><?php _e('My Orders & Messages', 'dokan'); ?></h1>
            </header>

            <?php if (current_user_can('administrator')): ?>
            <div class="debug-info" style="background: #f5f5f5; padding: 10px; margin-bottom: 20px; font-family: monospace;">
                <p>Current User ID: <?php echo get_current_user_id(); ?></p>
                <p>Number of Orders Found: <?php echo count($orders); ?></p>
                <?php if (empty($orders)): ?>
                    <p>Last SQL Query: <?php global $wpdb; echo esc_html($wpdb->last_query); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <table class="dokan-table dokan-table-striped">
                <thead>
                    <tr>
                        <th><?php _e('Order', 'dokan'); ?></th>
                        <th><?php _e('Vendor', 'dokan'); ?></th>
                        <th><?php _e('Status', 'dokan'); ?></th>
                        <th><?php _e('Date', 'dokan'); ?></th>
                        <th><?php _e('Total', 'dokan'); ?></th>
                        <th><?php _e('Actions', 'dokan'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($orders) {
                        foreach ($orders as $order) {
                            $order_obj = wc_get_order($order->order_id);
                            if (!$order_obj) continue;
                            ?>
                            <tr>
                                <td class="dokan-order-id">
                                    <?php echo '#' . $order->order_id; ?>
                                </td>
                                <td class="dokan-order-vendor">
                                    <?php echo esc_html($order->vendor_name); ?>
                                </td>
                                <td class="dokan-order-status">
                                    <span class="order-status status-<?php echo esc_attr($order_obj->get_status()); ?>">
                                        <?php echo wc_get_order_status_name($order_obj->get_status()); ?>
                                    </span>
                                </td>
                                <td class="dokan-order-date">
                                    <?php echo date_i18n(get_option('date_format'), strtotime($order->order_date)); ?>
                                </td>
                                <td class="dokan-order-total">
                                    <?php echo $order_obj->get_formatted_order_total(); ?>
                                </td>
                                <td class="dokan-order-action">
                                    <a href="#" class="dokan-btn dokan-btn-default dokan-btn-sm open-chat" 
                                       data-order-id="<?php echo esc_attr($order->order_id); ?>"
                                       data-vendor-id="<?php echo esc_attr($order->vendor_id); ?>"
                                       data-vendor-name="<?php echo esc_attr($order->vendor_name); ?>">
                                        <?php _e('Chat', 'dokan'); ?>
                                        <?php if ($order->unread_count > 0): ?>
                                            <span class="unread-count"><?php echo $order->unread_count; ?></span>
                                        <?php endif; ?>
                                    </a>
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
                    <textarea id="dokan-chat-message" 
                              placeholder="<?php _e('Type your message...', 'dokan'); ?>"></textarea>
                    <button id="dokan-chat-send" class="dokan-btn dokan-btn-theme">
                        <?php _e('Send', 'dokan'); ?>
                    </button>
                </div>
            </div>
        </article>
    </div>
</div>
