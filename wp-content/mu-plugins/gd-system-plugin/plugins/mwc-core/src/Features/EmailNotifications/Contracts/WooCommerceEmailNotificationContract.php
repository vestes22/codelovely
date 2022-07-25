<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

use WC_Email;

/**
 * An email notification interface for WooCommerce emails.
 */
interface WooCommerceEmailNotificationContract extends EmailNotificationContract
{
    /**
     * Sets the WooCommerce email object.
     *
     * @param WC_Email $value
     * @return self
     */
    public function setWooCommerceEmail(WC_Email $value);

    /**
     * Gets the WooCommerce email object.
     *
     * @return WC_Email|null
     */
    public function getWooCommerceEmail();
}
