<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationDataStoreContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\WooCommerce\EmailTemplateDataStore;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailNotificationNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailTemplateNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\InvalidClassNameException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\CancelledOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\CompletedOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\CustomerInvoiceEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\CustomerNoteEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\FailedOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\ItemShippedEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\NewAccountEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\NewOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\OrderOnHoldEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\PartiallyRefundedOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\ProcessingOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\RefundedOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\ResetPasswordEmailNotification;
use InvalidArgumentException;

/**
 * Data store for email notifications.
 */
class EmailNotificationDataStore implements EmailNotificationDataStoreContract
{
    /** @var array available email notifications */
    protected $notifications = [
        'cancelled_order'                   => CancelledOrderEmailNotification::class,
        'customer_completed_order'          => CompletedOrderEmailNotification::class,
        'customer_item_shipped'             => ItemShippedEmailNotification::class,
        'customer_new_account'              => NewAccountEmailNotification::class,
        'customer_on_hold_order'            => OrderOnHoldEmailNotification::class,
        'customer_partially_refunded_order' => PartiallyRefundedOrderEmailNotification::class,
        'customer_processing_order'         => ProcessingOrderEmailNotification::class,
        'customer_refunded_order'           => RefundedOrderEmailNotification::class,
        'customer_reset_password'           => ResetPasswordEmailNotification::class,
        'failed_order'                      => FailedOrderEmailNotification::class,
        'new_order'                         => NewOrderEmailNotification::class,
        'customer_note'                     => CustomerNoteEmailNotification::class,
        'customer_invoice'                  => CustomerInvoiceEmailNotification::class,
    ];

    /**
     * Gets an email notification with the given ID.
     *
     * @param string $id
     * @return EmailNotificationContract
     * @throws EmailNotificationNotFoundException|EmailTemplateNotFoundException|InvalidClassNameException
     */
    public function read(string $id) : EmailNotificationContract
    {
        /** @var EmailNotificationContract $notification */
        $notification = $this->getOptionsSettingsDataStore($id)->read($this->getNotificationInstance($id));

        return $this->readContentAndTemplate($notification);
    }

    /**
     * Reads the notification's content & template properties.
     *
     * @param EmailNotificationContract $notification
     * @return EmailNotificationContract
     * @throws EmailTemplateNotFoundException|InvalidClassNameException
     */
    protected function readContentAndTemplate(EmailNotificationContract $notification) : EmailNotificationContract
    {
        // we have a single email template available and all email notifications use it
        $notification->setTemplate($this->getEmailTemplateDataStore()->read('default'));

        // we need to set the template first, so that calling setContent() can also set the inner content of the template
        $notification->setContent($this->getEmailContentDataStore()->read($notification->getId()));

        return $notification;
    }

    /**
     * Gets the notification instance from the given ID.
     *
     * @param string $id
     * @return EmailNotificationContract
     * @throws EmailNotificationNotFoundException|InvalidClassNameException
     */
    protected function getNotificationInstance(string $id) : EmailNotificationContract
    {
        if (! ArrayHelper::exists($this->notifications, $id)) {
            throw new EmailNotificationNotFoundException(sprintf(
                __('No email notification found with the ID %s.', 'mwc-core'),
                $id
            ));
        }

        $class = ArrayHelper::get($this->notifications, $id);

        if (! is_a($class, EmailNotificationContract::class, true)) {
            throw new InvalidClassNameException(sprintf(
                __('The class for %s must implement the EmailNotificationContract interface', 'mwc-core'),
                $id
            ));
        }

        return (new $class())->setId($id);
    }

    /**
     * Saves the given email notification.
     *
     * @param EmailNotificationContract $notification
     * @return EmailNotificationContract
     * @throws InvalidArgumentException
     */
    public function save(EmailNotificationContract $notification) : EmailNotificationContract
    {
        $this->getOptionsSettingsDataStore($notification->getId())->save($notification);

        $this->saveContentAndTemplate($notification);

        return $notification;
    }

    /**
     * Saves the notification content and template.
     *
     * @param EmailNotificationContract $notification
     */
    protected function saveContentAndTemplate(EmailNotificationContract $notification)
    {
        if ($content = $notification->getContent()) {
            $this->getEmailContentDataStore()->save($content);
        }

        if ($template = $notification->getTemplate()) {
            $this->getEmailTemplateDataStore()->save($template);
        }
    }

    /**
     * Gets the email content data store.
     *
     * @return EmailContentDataStore
     */
    protected function getEmailContentDataStore() : EmailContentDataStore
    {
        return new EmailContentDataStore();
    }

    /**
     * Gets the email template data store.
     *
     * @return EmailTemplateDataStore
     */
    protected function getEmailTemplateDataStore() : EmailTemplateDataStore
    {
        return new EmailTemplateDataStore();
    }

    /**
     * Gets the options settings data store for the notification settings.
     *
     * @param string $notificationId
     * @return OptionsSettingsDataStore
     */
    protected function getOptionsSettingsDataStore(string $notificationId) : OptionsSettingsDataStore
    {
        return new OptionsSettingsDataStore($this->getOptionNameTemplate($notificationId));
    }

    /**
     * Gets the option name template.
     *
     * @param string $notificationId
     * @return string
     */
    protected function getOptionNameTemplate(string $notificationId) : string
    {
        return 'mwc_'.$notificationId.'_email_notification_'.OptionsSettingsDataStore::SETTING_ID_MERGE_TAG;
    }

    /**
     * Returns an array of all available EmailNotificationContract objects.
     *
     * @return EmailNotificationContract[]
     * @throws EmailNotificationNotFoundException
     * @throws EmailTemplateNotFoundException
     * @throws InvalidClassNameException
     */
    public function all() : array
    {
        $newNotifications = [];

        foreach (array_keys($this->notifications) as $notification) {
            $newNotifications[] = $this->read($notification);
        }

        return $newNotifications;
    }
}
