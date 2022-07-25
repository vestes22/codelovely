<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts;

use InvalidArgumentException;

/**
 * A contract for email notification recipients.
 */
interface EmailNotificationWithRecipientsContract extends EmailNotificationContract
{
    /**
     * Sets the recipients.
     *
     * @param string[] $value
     * @return self
     * @throws InvalidArgumentException
     */
    public function setRecipients(array $value);

    /**
     * Gets the recipients.
     *
     * @return string[]
     */
    public function getRecipients() : array;
}
