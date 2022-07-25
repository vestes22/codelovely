<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataSources\WooCommerce;

use WC_Email;

/**
 * An adapter for the partially refunded order email.
 */
class PartiallyRefundedOrderEmailNotificationAdapter extends EmailNotificationAdapter
{
    /**
     * Gets the name of the option that stores the subject of the WooCommerce email.
     *
     * @param WC_Email $email
     * @return string
     */
    protected function getEmailSubjectOptionName(WC_Email $email) : string
    {
        return 'subject_partial';
    }

    /**
     * Gets the default value for the subject of the WooCommerce email.
     *
     * @param WC_Email $email
     * @return string
     */
    protected function getEmailSubjectDefaultValue(WC_Email $email) : string
    {
        return (string) $email->get_default_subject(true);
    }

    /**
     * Gets the name of the option that stores the heading of the WooCommerce email.
     *
     * @param WC_Email $email
     * @return string
     */
    protected function getEmailHeadingOptionName(WC_Email $email) : string
    {
        return 'heading_partial';
    }

    /**
     * Gets the default value for the heading of the WooCommerce email.
     *
     * @param WC_Email $email
     * @return string
     */
    protected function getEmailHeadingDefaultValue(WC_Email $email) : string
    {
        return (string) $email->get_default_heading(true);
    }

    /**
     * Gets the value for the section parameter included in the URL of the WooCommerce settings screen for the partially refunded order email.
     *
     * @return string
     */
    protected function getEmailSettingsSection(WC_Email $email) : string
    {
        return 'wc_email_customer_refunded_order';
    }
}
