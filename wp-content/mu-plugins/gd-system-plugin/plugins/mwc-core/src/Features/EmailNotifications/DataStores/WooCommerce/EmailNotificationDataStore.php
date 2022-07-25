<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\EmailsRepository;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationDataStoreContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\WooCommerceEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataSources\WooCommerce\EmailNotificationAdapter;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\EmailNotificationDataStore as NativeEmailNotificationDataStore;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\AbstractNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailNotificationNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailTemplateNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\InvalidClassNameException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\WooCommerceEmailNotFoundException;
use WC_Email;
use WC_Email_Customer_Refunded_Order;

/**
 * A data store for email notifications that wrap WooCommerce emails.
 */
class EmailNotificationDataStore implements EmailNotificationDataStoreContract
{
    /** @var NativeEmailNotificationDataStore */
    private $dataStore;

    /**
     * WooCommerce email notification data store constructor.
     *
     * @param NativeEmailNotificationDataStore|null $dataStore optional data store instance
     */
    public function __construct(NativeEmailNotificationDataStore $dataStore = null)
    {
        if (! $dataStore) {
            $dataStore = new NativeEmailNotificationDataStore();
        }

        $this->dataStore = $dataStore;
    }

    /**
     * Gets an email notification with the given ID.
     *
     * @param string $id
     * @return EmailNotificationContract
     * @throws AbstractNotFoundException
     * @throws EmailNotificationNotFoundException
     * @throws EmailTemplateNotFoundException
     * @throws InvalidClassNameException
     * @throws WooCommerceEmailNotFoundException
     */
    public function read(string $id) : EmailNotificationContract
    {
        try {
            // try to find a native email notification first
            $emailNotification = $this->dataStore->read($id);
        } catch (AbstractNotFoundException $exception) {
            try {
                // now try to create a generic email notification for a third party WooCommerce email
                return $this->readFromWooCommerceEmails($id);
            } catch (WooCommerceEmailNotFoundException $ignored) {
                // if we can't get a third party WooCommerce email either, throw the original exception
                throw $exception;
            }
        }

        try {
            // try to update the native email notification using information from a matching WooCommerce email
            return EmailNotificationAdapter::for($emailNotification)->convertFromSource($emailNotification);
        } catch (Exception $exception) {
            throw new EmailNotificationNotFoundException($exception->getMessage());
        }
    }

    /**
     * @param $emailId
     * @return EmailNotificationContract
     * @throws InvalidArgumentException
     * @throws EmailTemplateNotFoundException
     * @throws InvalidClassNameException
     * @throws WooCommerceEmailNotFoundException
     */
    protected function readFromWooCommerceEmails($emailId) : EmailNotificationContract
    {
        if (! $email = EmailsRepository::get($emailId)) {
            throw new WooCommerceEmailNotFoundException(sprintf(__('Could not find source WooCommerce email with ID "%s".', 'mwc-core'), $emailId));
        }

        return EmailNotificationAdapter::from($email)->convertFromSource();
    }

    /**
     * Gets a WooCommerce email ID for a given email notification.
     *
     * @param EmailNotificationContract $emailNotification
     * @return string
     * @throws WooCommerceEmailNotFoundException
     */
    protected function getEmailId(EmailNotificationContract $emailNotification) : string
    {
        return EmailNotificationAdapter::getEmailId($emailNotification);
    }

    /**
     * Gets a EmailNotification ID for a given WooCommerce email.
     *
     * @param WC_Email $email
     * @return string|null
     */
    protected function getEmailNotificationId(WC_Email $email)
    {
        try {
            return EmailNotificationAdapter::getEmailNotificationId($email);
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * Gets a WooCommerce email for a given ID.
     *
     * @param string $emailId
     * @return WC_Email
     * @throws Exception
     */
    protected function getEmail(string $emailId) : WC_Email
    {
        $email = EmailsRepository::get($emailId);

        if (! $email) {
            throw new WooCommerceEmailNotFoundException(sprintf(
            /* translator: Placeholder: %s - Email notification ID */
                __('No WooCommerce email found for "%s"', 'mwc-core'),
                $emailId
            ));
        }

        return $email;
    }

    /**
     * Adapts an email notification to a WooCommerce email and returns the updated instance.
     *
     * @param WC_Email $email
     * @param EmailNotificationContract|null $emailNotification
     * @return EmailNotificationContract
     * @throws InvalidArgumentException
     * @throws EmailTemplateNotFoundException
     * @throws InvalidClassNameException
     */
    protected function getAdaptedEmailNotification(WC_Email $email, $emailNotification) : EmailNotificationContract
    {
        return EmailNotificationAdapter::from($email)->convertFromSource($emailNotification);
    }

    /**
     * Adapts a WooCommerce email from an email notification and returns the updated instance.
     *
     * @param WC_Email $email
     * @param EmailNotificationContract $emailNotification
     * @return WC_Email
     * @throws WooCommerceEmailNotFoundException
     */
    protected function getAdaptedEmail(WC_Email $email, EmailNotificationContract $emailNotification) : WC_Email
    {
        return $this->getEmailNotificationAdapter($email)->convertToSource($emailNotification);
    }

    /**
     * Gets a new email notification adapter instance for a given email.
     *
     * @param WC_Email $email
     * @return EmailNotificationAdapter
     */
    protected function getEmailNotificationAdapter(WC_Email $email) : EmailNotificationAdapter
    {
        return new EmailNotificationAdapter($email);
    }

    /**
     * Saves the given email notification.
     *
     * @param EmailNotificationContract $notification
     * @return EmailNotificationContract
     */
    public function save(EmailNotificationContract $notification) : EmailNotificationContract
    {
        try {
            $email = EmailNotificationAdapter::for($notification)->convertToSource($notification);

            if (is_a($notification, WooCommerceEmailNotificationContract::class)) {
                $notification->setWooCommerceEmail($email);
            }
        } catch (Exception $exception) {
            // either way we let the parent data store to take care of an email notification that may not be linked to a WooCommerce email
        }

        return $this->dataStore->save($notification);
    }

    /**
     * Gets an array of all available email notification objects.
     *
     * @return EmailNotificationContract[]
     * @throws InvalidArgumentException
     * @throws EmailNotificationNotFoundException
     * @throws EmailTemplateNotFoundException
     * @throws InvalidClassNameException
     */
    public function all() : array
    {
        $emailNotificationsMap = $this->getEmailNotifications();

        return array_map(function (WC_Email $email) use ($emailNotificationsMap) {
            $emailNotificationId = $this->getEmailNotificationId($email);

            if ($emailNotification = ArrayHelper::get($emailNotificationsMap, $emailNotificationId ?: '')) {
                $adapted = $this->getAdaptedEmailNotification($email, $emailNotification);
            }

            if (! isset($adapted)) {
                $adapted = $this->getAdaptedEmailNotification($email, null);
            }

            return $adapted;
        }, $this->getWooCommerceEmails());
    }

    /**
     * Gets all registered WooCommerce emails.
     *
     * Also inserts a copy of  the refunded order email modified to look like a partially refunded order email.
     * The copy allows the adapter to process the Partially Refunded Order Email Notification.
     *
     * @return WC_Email[]
     * @throws Exception
     */
    protected function getWooCommerceEmails() : array
    {
        $emailsMap = array_reduce(EmailsRepository::all(), static function ($hasmap, WC_Email $email) {
            $hasmap[$email->id] = $email;

            return $hasmap;
        });

        if ($refundedOrderEmail = ArrayHelper::get($emailsMap, 'customer_refunded_order')) {
            /** @var WC_Email_Customer_Refunded_Order */
            $partiallyRefundedOrderEmail = clone $refundedOrderEmail;
            $partiallyRefundedOrderEmail->partial_refund = true;

            $emailsMap = ArrayHelper::insertBefore(
                $emailsMap,
                ['customer_partially_refunded_order' => $partiallyRefundedOrderEmail],
                'customer_refunded_order'
            );
        }

        return array_values($emailsMap);
    }

    /**
     * Gets all registered Email Notifications indexed by their IDs.
     *
     * @return EmailNotificationContract[]
     * @throws EmailNotificationNotFoundException
     * @throws EmailTemplateNotFoundException
     * @throws InvalidClassNameException
     */
    protected function getEmailNotifications() : array
    {
        return array_reduce($this->dataStore->all(), static function ($hashmap, EmailNotificationContract $emailNotification) {
            $hashmap[$emailNotification->getId()] = $emailNotification;

            return $hashmap;
        }, []);
    }
}
