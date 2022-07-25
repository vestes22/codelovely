<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataSources\WooCommerce;

use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\CustomerNoteEmailNotification;
use WC_Email;

/**
 * An adapter for the customer note email notification.
 */
class CustomerNoteEmailNotificationAdapter extends EmailNotificationAdapter
{
    /**
     * {@inheritdoc}
     */
    public function convertFromSource(EmailNotificationContract $emailNotification = null) : EmailNotificationContract
    {
        $emailNotification = parent::convertFromSource($emailNotification);

        $this->setAdaptedEmailNotificationCustomerNote($this->source, $emailNotification);

        return $emailNotification;
    }

    /**
     * Sets a customer note on an adapted email notification that represents a WooCommerce customer note email.
     *
     * @param WC_Email $email
     * @param EmailNotificationContract $emailNotification
     * @return EmailNotificationContract
     */
    protected function setAdaptedEmailNotificationCustomerNote(WC_Email $email, EmailNotificationContract $emailNotification) : EmailNotificationContract
    {
        if ($emailNotification instanceof CustomerNoteEmailNotification) {
            $emailNotification->setCustomerNote((string) ($email->customer_note ?? ''));
        }

        return $emailNotification;
    }
}
