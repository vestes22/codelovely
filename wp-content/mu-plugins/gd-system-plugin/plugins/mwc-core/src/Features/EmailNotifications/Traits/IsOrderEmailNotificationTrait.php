<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits;

use GoDaddy\WordPress\MWC\Core\Payments\Models\Orders\Order;

/**
 * Trait for order email notifications.
 */
trait IsOrderEmailNotificationTrait
{
    use IsWooCommerceEmailNotificationTrait;

    /** @var Order|null */
    protected $order;

    /**
     * Sets the order for the notification.
     *
     * @param Order $value
     * @return self
     */
    public function setOrder(Order $value)
    {
        $this->order = $value;

        return $this;
    }

    /**
     * Gets the order for the notification.
     *
     * @return Order|null
     */
    public function getOrder()
    {
        return $this->order;
    }
}
