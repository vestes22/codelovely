<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataSources\WooCommerce;

use WC_Email;

/**
 * An adapter for the refunded order email.
 */
class RefundedOrderEmailNotificationAdapter extends EmailNotificationAdapter
{
    /**
     * Gets the name of the option that stores the subject of the WooCommerce email.
     *
     * @param WC_Email $email
     * @return string
     */
    protected function getEmailSubjectOptionName(WC_Email $email) : string
    {
        return 'subject_full';
    }

    /**
     * Gets the name of the option that stores the heading of the WooCommerce email.
     *
     * @param WC_Email $email
     * @return string
     */
    protected function getEmailHeadingOptionName(WC_Email $email) : string
    {
        return 'heading_full';
    }

    /**
     * Gets the value for the section parameter included in the URL of the WooCommerce settings screen for the refunded order email.
     *
     * @return string
     */
    protected function getEmailSettingsSection(WC_Email $email) : string
    {
        return 'wc_email_customer_refunded_order';
    }
}
