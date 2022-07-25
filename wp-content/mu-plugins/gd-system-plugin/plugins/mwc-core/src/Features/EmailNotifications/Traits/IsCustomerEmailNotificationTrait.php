<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits;

use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;

/**
 * Trait for customer email notifications.
 */
trait IsCustomerEmailNotificationTrait
{
    /**
     * Gets the greeting for the email.
     *
     * @param Order $order the order object associated with this email
     * @return string
     */
    protected function getGreeting(Order $order) : string
    {
        ob_start(); ?>
        
        <?php printf(
            /* translators: %s: Customer first name */
            esc_html__('Hi %s,', 'woocommerce'),
            esc_html($order->getBillingAddress()->getFirstName())
        ); ?>

        <?php

        return ob_get_clean();
    }
}
