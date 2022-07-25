<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits;

use WC_Email;

/**
 * A trait for email notifications handling WooCommerce Emails.
 */
trait IsWooCommerceEmailNotificationTrait
{
    /** @var WC_Email|null */
    protected $wooCommerceEmail;

    /**
     * Sets the WooCommerce email object.
     *
     * @param WC_Email $value
     * @return self
     */
    public function setWooCommerceEmail(WC_Email $value)
    {
        $this->wooCommerceEmail = $value;

        return $this;
    }

    /**
     * Gets the WooCommerce email object.
     *
     * @return WC_Email|null
     */
    public function getWooCommerceEmail()
    {
        return $this->wooCommerceEmail;
    }
}
