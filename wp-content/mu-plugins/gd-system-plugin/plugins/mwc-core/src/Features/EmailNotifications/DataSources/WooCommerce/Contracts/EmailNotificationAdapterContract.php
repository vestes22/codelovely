<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataSources\WooCommerce\Contracts;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailTemplateNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\InvalidClassNameException;
use InvalidArgumentException;
use WC_Email;

interface EmailNotificationAdapterContract extends DataSourceAdapterContract
{
    /**
     * Initializes with a WooCommerce email as the source.
     *
     * @param WC_Email $source
     */
    public function __construct(WC_Email $source);

    /**
     * Converts the source WooCommerce Email into an email notification object.
     *
     * @param EmailNotificationContract|null $emailNotification optional
     * @return EmailNotificationContract
     * @throws InvalidArgumentException
     * @throws EmailTemplateNotFoundException
     * @throws InvalidClassNameException
     */
    public function convertFromSource(EmailNotificationContract $emailNotification = null) : EmailNotificationContract;

    /**
     * Converts an email notification object to a WooCommerce email.
     *
     * @param EmailNotificationContract $emailNotification email notification object
     * @return WC_Email
     * @throws InvalidArgumentException
     * @throws WooCommerceEmailNotFoundException
     */
    public function convertToSource(EmailNotificationContract $emailNotification = null) : WC_Email;
}
