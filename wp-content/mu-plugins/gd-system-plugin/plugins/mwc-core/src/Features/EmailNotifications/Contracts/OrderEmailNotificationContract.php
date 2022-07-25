<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;

/**
 * A contract for email notifications concerning orders.
 */
interface OrderEmailNotificationContract extends WooCommerceEmailNotificationContract
{
    /**
     * Sets the related order.
     *
     * @param Order $value
     * @return self
     */
    public function setOrder(Order $value);

    /**
     * Gets the related order.
     *
     * @return Order|null
     */
    public function getOrder();
}
