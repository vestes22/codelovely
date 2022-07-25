<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailNotificationNotFoundException;

/**
 * The Email Notification Data Store contract.
 */
interface EmailNotificationDataStoreContract
{
    /**
     * Gets an email notification with the given ID.
     *
     * @param string $id
     * @return EmailNotificationContract
     * @throws EmailNotificationNotFoundException
     */
    public function read(string $id) : EmailNotificationContract;

    /**
     * Saves the given email notification.
     *
     * @param EmailNotificationContract $notification
     * @return EmailNotificationContract
     */
    public function save(EmailNotificationContract $notification) : EmailNotificationContract;

    /**
     * Returns an array of all available EmailNotificationContract objects.
     *
     * @return EmailNotificationContract[]
     */
    public function all() : array;
}
