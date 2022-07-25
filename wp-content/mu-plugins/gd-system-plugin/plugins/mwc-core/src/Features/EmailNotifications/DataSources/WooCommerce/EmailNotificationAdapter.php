<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataSources\WooCommerce;

use Exception;
use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Repositories\WooCommerce\EmailsRepository;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationWithRecipientsContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\OrderEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\WooCommerceEmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataSources\WooCommerce\Contracts\EmailNotificationAdapterContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\WooCommerce\EmailTemplateDataStore;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailNotificationNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailTemplateNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\InvalidClassNameException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\WooCommerceEmailNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\DefaultEmailContent;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\PartiallyRefundedOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\RefundedOrderEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\ThirdPartyWooCommerceEmailContent;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\ThirdPartyWooCommerceEmailNotification;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanConvertWooCommerceEmailPlaceholdersTrait;
use GoDaddy\WordPress\MWC\Core\Payments\Adapters\OrderAdapter;
use InvalidArgumentException;
use WC_Email;
use WC_Order;

/**
 * An adapter for WooCommerce emails.
 */
class EmailNotificationAdapter implements EmailNotificationAdapterContract
{
    use CanConvertWooCommerceEmailPlaceholdersTrait;

    /** @var WC_Email */
    protected $source;

    /**
     * Initializes with a WooCommerce email as the source.
     *
     * @param WC_Email $source
     */
    public function __construct(WC_Email $source)
    {
        $this->source = $source;
    }

    /**
     * Creates a new adapter for the given WooCommerce email as the source.
     *
     * @param WC_Email $source
     * @return self
     */
    public static function from(WC_Email $source)
    {
        if (static::isPartiallyRefundedOrderEmail($source)) {
            return new PartiallyRefundedOrderEmailNotificationAdapter($source);
        }

        if (static::isRefundedOrderEmail($source)) {
            return new RefundedOrderEmailNotificationAdapter($source);
        }

        if (static::isCustomerNoteEmail($source)) {
            return new CustomerNoteEmailNotificationAdapter($source);
        }

        if (! static::hasEmailNotification($source->id)) {
            return new ThirdPartyEmailNotificationAdapter($source);
        }

        return new static($source);
    }

    /**
     * Determines whether the given WooCommerce email is the partially refunded order email.
     *
     * @param WC_Email $email
     * @return bool
     */
    protected static function isPartiallyRefundedOrderEmail(WC_Email $email) : bool
    {
        if ($email->id === 'customer_partially_refunded_order') {
            return true;
        }

        return $email->id === 'customer_refunded_order' && ! empty($email->partial_refund);
    }

    /**
     * Determines whether the given WooCommerce email is the fully refunded order email.
     *
     * @param WC_Email $email
     * @return bool
     */
    protected static function isRefundedOrderEmail(WC_Email $email) : bool
    {
        return $email->id === 'customer_refunded_order' && empty($email->partial_refund);
    }

    /**
     * Determines whether the given WooCommerce email is the customer note email.
     *
     * @param WC_Email $email
     * @return bool
     */
    protected static function isCustomerNoteEmail(WC_Email $email) : bool
    {
        return $email->id === 'customer_note';
    }

    /**
     * Creates a new adapter suitable to adapt the given Email Notification.
     *
     * @param EmailNotificationContract $emailNotification
     * @return self
     * @throws WooCommerceEmailNotFoundException
     */
    public static function for(EmailNotificationContract $emailNotification)
    {
        $email = static::getEmail($emailNotification);

        if ($emailNotification instanceof PartiallyRefundedOrderEmailNotification) {
            return new PartiallyRefundedOrderEmailNotificationAdapter($email);
        }

        if ($emailNotification instanceof RefundedOrderEmailNotification) {
            return new RefundedOrderEmailNotificationAdapter($email);
        }

        if ($emailNotification instanceof ThirdPartyWooCommerceEmailNotification) {
            return new ThirdPartyEmailNotificationAdapter($email);
        }

        return new static($email);
    }

    /**
     * Gets the corresponding email notification ID from a WooCommerce email.
     *
     * @param WC_Email $email
     * @return string
     * @throws EmailNotificationNotFoundException
     */
    public static function getEmailNotificationId(WC_Email $email) : string
    {
        if (static::isPartiallyRefundedOrderEmail($email)) {
            return 'customer_partially_refunded_order';
        }

        foreach (static::getMappedData() as $emailNotificationId => $data) {
            if (isset($data['emailId']) && $email->id === $data['emailId']) {
                return $emailNotificationId;
            }
        }

        throw new EmailNotificationNotFoundException(sprintf(__('Could not match an email notification ID to a "%s" WooCommerce email ID.', 'mwc-core'), $email->id));
    }

    /**
     * Determines whether a given email notification exists for a WooCommerce email, by ID.
     *
     * @param string $emailId
     * @return bool
     */
    public static function hasEmailNotification(string $emailId) : bool
    {
        foreach (static::getMappedData() as $data) {
            if (isset($data['emailId']) && $emailId === $data['emailId']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the corresponding WooCommerce email ID from an email notification ID.
     *
     * @param EmailNotificationContract $emailNotification
     * @return string
     * @throws WooCommerceEmailNotFoundException
     */
    public static function getEmailId(EmailNotificationContract $emailNotification) : string
    {
        if (! isset(static::getMappedData()[$emailNotification->getId()]['emailId'])) {
            throw new WooCommerceEmailNotFoundException(sprintf(__('Could not match a WooCommerce email ID to a "%s" email notification ID.', 'mwc-core'), $emailNotification->getId()));
        }

        return static::getMappedData()[$emailNotification->getId()]['emailId'];
    }

    /**
     * Parses WooCommerce email recipients into an array of recipients' email addresses.
     *
     * @param WC_Email $email
     * @return string[]
     */
    public static function getEmailRecipients(WC_Email $email) : array
    {
        $recipient = $email->get_recipient();

        return is_string($recipient) ? array_map('trim', explode(',', $recipient)) : [];
    }

    /**
     * Gets the sender address from a WooCommerce email.
     *
     * @param WC_Email $email
     * @return string
     */
    public static function getEmailSenderAddress(WC_Email $email) : string
    {
        $sender = $email->get_from_address();

        return is_string($sender) ? $sender : '';
    }

    /**
     * Gets a WooCommerce email additional headers.
     *
     * @param WC_Email $email
     * @return array
     */
    public static function getEmailHeaders(WC_Email $email) : array
    {
        $originalHeaders = $email->get_headers();

        if (! is_string($originalHeaders)) {
            return [];
        }

        $parsedHeaders = [];

        foreach (explode("\r\n", $originalHeaders) as $additionalHeader) {
            $key = trim(StringHelper::before($additionalHeader, ':'));

            if ($key && $key !== $additionalHeader) {
                $header = trim(StringHelper::after($additionalHeader, ':'));

                if ($header && $header !== $additionalHeader) {
                    $parsedHeaders[$key] = trim($header);
                }
            }
        }

        return $parsedHeaders;
    }

    /**
     * Gets a WooCommerce email attachments.
     *
     * @param WC_Email $email
     * @return array
     */
    public static function getEmailAttachments(WC_Email $email) : array
    {
        return ArrayHelper::wrap($email->get_attachments());
    }

    /**
     * Gets the URL of the WooCommerce settings screen for the email object associated with the given email notification.
     *
     * @param EmailNotification $emailNotification
     * @return string|null
     */
    public static function getLegacySettingsUrl(EmailNotificationContract $emailNotification)
    {
        try {
            return static::for($emailNotification)->getEmailSettingsUrl();
        } catch (WooCommerceEmailNotFoundException $exception) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function convertFromSource(EmailNotificationContract $emailNotification = null) : EmailNotificationContract
    {
        // @TODO based on $this->source->partial_refund value we can adjust the email notification instance to get, when the concrete objects will be available {unfulvio 2021-09-24}
        if (null === $emailNotification) {
            $emailNotification = $this->createGenericEmail($this->source);
        }

        $emailNotification = $this->adaptEmailEnabled($this->source, $emailNotification);
        $emailNotification = $this->adaptEmailSubject($this->source, $emailNotification);
        $emailNotification = $this->adaptEmailRecipients($this->source, $emailNotification);
        $emailNotification = $this->adaptEmailContent($this->source, $emailNotification);

        $emailNotification = $this->setAdaptedEmailNotificationCategories($emailNotification);
        $emailNotification = $this->setAdaptedEmailNotificationWooCommerceEmail($this->source, $emailNotification);
        $emailNotification = $this->setAdaptedEmailNotificationOrder($this->source, $emailNotification);

        return $emailNotification;
    }

    /**
     * Creates a generic email notification from 3rd party emails.
     *
     * @param WC_Email $email
     * @return EmailNotification
     * @throws InvalidArgumentException|EmailTemplateNotFoundException|InvalidClassNameException
     */
    protected function createGenericEmail(WC_Email $email) : EmailNotification
    {
        $emailNotification = (new ThirdPartyWooCommerceEmailNotification())
            ->setId($email->id)
            ->setName($email->id)
            ->setLabel($email->get_title() ?? '')
            ->setDescription($email->get_description() ?? '')
            ->setTemplate($this->getEmailTemplateDataStore()->read('default'));

        return $emailNotification->setContent(new ThirdPartyWooCommerceEmailContent($emailNotification));
    }

    /**
     * Gets an instance of the WooCommerce email template data store.
     *
     * @return EmailTemplateDataStore
     */
    protected function getEmailTemplateDataStore() : EmailTemplateDataStore
    {
        return new EmailTemplateDataStore();
    }

    /**
     * Gets a filtered property value for a given WooCommerce email option.
     *
     * This method ensures that the retrieved value is not formatted.
     *
     * @see WC_Email::get_subject()
     * @see WC_Email::get_heading()
     * @see WC_Email::get_additional_content()
     *
     * @param string $filterPrefix prefix for the email ID in the hook name usually equal to the optionKey
     * @param string $optionKey
     * @param string $defaultValue
     * @param WC_Email $email
     * @return string
     */
    protected function adaptUnformattedFilteredProperty(string $filterPrefix, string $optionKey, string $defaultValue, WC_Email $email) : string
    {
        return (string) apply_filters("woocommerce_email_{$filterPrefix}_{$email->id}", $email->get_option($optionKey, $defaultValue), $email);
    }

    /**
     * Adapts an email notification enabled status based on the enabled status of a WooCommerce email.
     *
     * @param WC_Email $email
     * @param EmailNotificationContract $emailNotification
     * @return EmailNotificationContract
     */
    protected function adaptEmailEnabled(WC_Email $email, EmailNotificationContract $emailNotification) : EmailNotificationContract
    {
        try {
            $emailNotification->setEnabled((bool) $email->is_enabled());
        } catch (InvalidArgumentException $exception) {
            // do nothing
        }

        return $emailNotification;
    }

    /**
     * Adapts an email notification subject based on the subject of a WooCommerce email.
     *
     * @param WC_Email $email
     * @param EmailNotificationContract $emailNotification
     * @return EmailNotificationContract
     */
    protected function adaptEmailSubject(WC_Email $email, EmailNotificationContract $emailNotification) : EmailNotificationContract
    {
        if (! $emailNotification->getSubject()) {
            $subject = $this->adaptUnformattedFilteredProperty(
                'subject',
                $this->getEmailSubjectOptionName($email),
                $this->getEmailSubjectDefaultValue($email),
                $email
            );

            try {
                $emailNotification->setSubject($this->convertPlaceholdersFromSource($subject));
            } catch (InvalidArgumentException $exception) {
                // do nothing
            }
        }

        return $emailNotification;
    }

    /**
     * Gets the name of the option that stores the subject of the WooCommerce email.
     *
     * @param WC_Email $email
     * @return string
     */
    protected function getEmailSubjectOptionName(WC_Email $email) : string
    {
        return 'subject';
    }

    /**
     * Gets the default value for the subject of the WooCommerce email.
     *
     * @param WC_Email $email
     * @return string
     */
    protected function getEmailSubjectDefaultValue(WC_Email $email) : string
    {
        return (string) $email->get_default_subject();
    }

    /**
     * Adapts an email notification's recipients from the recipients of a WooCommerce email.
     *
     * @param WC_Email $email
     * @param EmailNotificationContract $emailNotification
     * @return EmailNotificationContract
     */
    protected function adaptEmailRecipients(WC_Email $email, EmailNotificationContract $emailNotification) : EmailNotificationContract
    {
        if ($emailNotification instanceof EmailNotificationWithRecipientsContract && empty($emailNotification->getRecipients())) {
            try {
                $emailNotification->setRecipients(static::getEmailRecipients($email));
            } catch (InvalidArgumentException $exception) {
                // do nothing
            }
        }

        return $emailNotification;
    }

    /**
     * Adapts an email notification's content values from a corresponding WooCommerce email's content values.
     *
     * @param WC_Email $email
     * @param EmailNotificationContract $emailNotification
     * @return EmailNotificationContract
     */
    protected function adaptEmailContent(WC_Email $email, EmailNotificationContract $emailNotification) : EmailNotificationContract
    {
        if ($content = $emailNotification->getContent()) {
            foreach ($content->getSettings() as $setting) {
                if (! $setting->hasValue()) {
                    if (DefaultEmailContent::SETTING_ID_HEADING === $setting->getId()) {
                        $heading = $this->adaptUnformattedFilteredProperty(
                            'heading',
                            $this->getEmailHeadingOptionName($email),
                            $this->getEmailHeadingDefaultValue($email),
                            $email
                        );

                        try {
                            $setting->setValue($this->convertPlaceholdersFromSource($heading));
                        } catch (InvalidArgumentException $exception) {
                            // do nothing
                        }
                    } elseif (DefaultEmailContent::SETTING_ID_ADDITIONAL_CONTENT === $setting->getId()) {
                        $additionalContent = $this->adaptUnformattedFilteredProperty('additional_content', 'additional_content', '', $email);
                        try {
                            $setting->setValue($this->convertPlaceholdersFromSource($additionalContent));
                        } catch (InvalidArgumentException $exception) {
                            // do nothing
                        }
                    }
                }
            }
        }

        return $emailNotification;
    }

    /**
     * Gets the name of the option that stores the heading of the WooCommerce email.
     *
     * @param WC_Email $email
     * @return string
     */
    protected function getEmailHeadingOptionName(WC_Email $email) : string
    {
        return 'heading';
    }

    /**
     * Gets the default value for the heading of the WooCommerce email.
     *
     * @param WC_Email $email
     * @return string
     */
    protected function getEmailHeadingDefaultValue(WC_Email $email) : string
    {
        return (string) $email->get_default_heading();
    }

    /**
     * Sets categories on an adapted email notification.
     *
     * @param EmailNotificationContract $emailNotification
     * @return EmailNotificationContract
     */
    protected function setAdaptedEmailNotificationCategories(EmailNotificationContract $emailNotification) : EmailNotificationContract
    {
        $category = static::getMappedData()[$emailNotification->getId()]['category'] ?? null;

        if ($category) {
            try {
                $emailNotification->setCategories(ArrayHelper::wrap($category));
            } catch (InvalidArgumentException $exception) {
                // do nothing
            }
        }

        return $emailNotification;
    }

    /**
     * Sets a WooCommerce email on an adapted email notification that is intended as a WooCommerce email wrapper.
     *
     * @param WC_Email $email
     * @param EmailNotificationContract $emailNotification
     * @return EmailNotificationContract
     */
    protected function setAdaptedEmailNotificationWooCommerceEmail(WC_Email $email, EmailNotificationContract $emailNotification) : EmailNotificationContract
    {
        if ($emailNotification instanceof WooCommerceEmailNotificationContract) {
            $emailNotification->setWooCommerceEmail($email);
        }

        return $emailNotification;
    }

    /**
     * Sets a converted WooCommerce order on a {@see OrderEmailNotificationContract} instance.
     *
     * @param WC_Email $email
     * @param EmailNotificationContract $emailNotification
     * @return EmailNotificationContract
     */
    protected function setAdaptedEmailNotificationOrder(WC_Email $email, EmailNotificationContract $emailNotification) : EmailNotificationContract
    {
        if (isset($email->object) && $email->object instanceof WC_Order && $emailNotification instanceof OrderEmailNotificationContract) {
            try {
                $emailNotification->setOrder($this->makeOrderAdapter($email->object)->convertFromSource());
            } catch (Exception $exception) {
                // there was an error trying to adapt the order object
            }
        }

        return $emailNotification;
    }

    /**
     * Makes and returns an {@see OrderAdapter} instance.
     *
     * @param WC_Order $order
     * @return OrderAdapter
     */
    protected function makeOrderAdapter(WC_Order $order) : OrderAdapter
    {
        return new OrderAdapter($order);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToSource(EmailNotificationContract $emailNotification = null) : WC_Email
    {
        if (! $emailNotification) {
            throw new InvalidArgumentException('Cannot convert a null email notification into a WooCommerce email instance.');
        }

        return $this->convertEmailNotificationToSource($emailNotification);
    }

    /**
     * Converts an email notification object to a WooCommerce email.
     *
     * @param EmailNotificationContract $emailNotification email notification object
     * @return WC_Email
     * @throws WooCommerceEmailNotFoundException
     */
    protected function convertEmailNotificationToSource(EmailNotificationContract $emailNotification) : WC_Email
    {
        $email = static::getEmail($emailNotification);

        $email = $this->adaptEmailNotificationEnabled($emailNotification, $email);
        $email = $this->adaptEmailNotificationSubject($emailNotification, $email);
        $email = $this->adaptEmailNotificationRecipients($emailNotification, $email);
        $email = $this->adaptEmailNotificationContent($emailNotification, $email);
        $email = $this->adaptEmailNotificationRefund($emailNotification, $email);

        return $email;
    }

    /**
     * Gets a WooCommerce email object for the given Email Notification object.
     *
     * @param EmailNotificationContract $emailNotifcation
     * @return WC_Email
     * @throws WooCommerceEmailNotFoundException
     */
    protected static function getEmail(EmailNotificationContract $emailNotification) : WC_Email
    {
        if ($emailNotification instanceof WooCommerceEmailNotificationContract && $emailNotification->getWooCommerceEmail()) {
            return $emailNotification->getWooCommerceEmail();
        }

        try {
            $emailId = static::getEmailId($emailNotification);
        } catch (WooCommerceEmailNotFoundException $exception) {
            $emailId = $emailNotification->getId();
        }

        if (! $email = EmailsRepository::get($emailId)) {
            throw new WooCommerceEmailNotFoundException(sprintf(__('Could not find source WooCommerce email with ID "%s".', 'mwc-core'), $emailId));
        }

        return $email;
    }

    /**
     * Adapts a WooCommerce email enabled status from that of a corresponding email notification.
     *
     * @param EmailNotificationContract $emailNotification
     * @param WC_Email $email
     * @return WC_Email
     */
    protected function adaptEmailNotificationEnabled(EmailNotificationContract $emailNotification, WC_Email $email) : WC_Email
    {
        $email->enabled = $emailNotification->isEnabled();
        $email->update_option('enabled', $emailNotification->isEnabled() ? 'yes' : 'no');

        return $email;
    }

    /**
     * Adapts a WooCommerce email subject from that of a corresponding email notification.
     *
     * @param EmailNotificationContract $emailNotification
     * @param WC_Email $email
     * @return WC_Email
     */
    protected function adaptEmailNotificationSubject(EmailNotificationContract $emailNotification, WC_Email $email) : WC_Email
    {
        if ($subject = $emailNotification->getSubject()) {
            $email->subject = $subject;
            $email->update_option($this->getEmailSubjectOptionName($email), $this->convertPlaceholdersToSource($subject));
        } else {
            $email->update_option($this->getEmailSubjectOptionName($email), null);
            $email->subject = $email->get_option($this->getEmailSubjectOptionName($email));
        }

        return $email;
    }

    /**
     * Adapts a WooCommerce email's recipients from an email notification's recipients.
     *
     * @param EmailNotificationContract $emailNotification
     * @param WC_Email $email
     * @return WC_Email
     */
    protected function adaptEmailNotificationRecipients(EmailNotificationContract $emailNotification, WC_Email $email) : WC_Email
    {
        if (is_a($emailNotification, EmailNotificationWithRecipientsContract::class, true)) {
            $recipients = implode(', ', $emailNotification->getRecipients());
            if (! empty($recipients)) {
                $email->recipient = $recipients;
                $email->update_option('recipient', $recipients);
            } else {
                $email->update_option('recipient', null);
                $email->recipient = $email->get_option('recipient', get_option('admin_email'));
            }
        }

        return $email;
    }

    /**
     * Adapts a WooCommerce email content values from the content values of an email notification.
     *
     * @param EmailNotificationContract $emailNotification
     * @param WC_Email $email
     * @return WC_Email
     */
    protected function adaptEmailNotificationContent(EmailNotificationContract $emailNotification, WC_Email $email) : WC_Email
    {
        if ($content = $emailNotification->getContent()) {
            foreach ($content->getSettings() as $setting) {
                if ($setting->hasValue()) {
                    if (DefaultEmailContent::SETTING_ID_HEADING === $setting->getId()) {
                        $email->heading = $setting->getValue();
                        $email->update_option($this->getEmailHeadingOptionName($email), $this->convertPlaceholdersToSource($setting->getValue()));
                    } elseif (DefaultEmailContent::SETTING_ID_ADDITIONAL_CONTENT === $setting->getId()) {
                        $email->update_option('additional_content', $this->convertPlaceholdersToSource($setting->getValue()));
                    }
                } else {
                    if (DefaultEmailContent::SETTING_ID_HEADING === $setting->getId()) {
                        $email->update_option($this->getEmailHeadingOptionName($email), null);
                        $email->heading = $email->get_option($this->getEmailHeadingOptionName($email));
                    } elseif (DefaultEmailContent::SETTING_ID_ADDITIONAL_CONTENT === $setting->getId()) {
                        $email->update_option('additional_content', null);
                    }
                }
            }
        }

        return $email;
    }

    /**
     * Adapts a WooCommerce email refund status based on the type of email notification, if applicable.
     *
     * @param EmailNotificationContract $emailNotification
     * @param WC_Email $email
     * @return WC_Email
     */
    protected function adaptEmailNotificationRefund(EmailNotificationContract $emailNotification, WC_Email $email) : WC_Email
    {
        if ('customer_refunded_order' === $email->id) {
            if ('fully_refunded_order' === $emailNotification->getId()) {
                $email->partial_refund = false;
            } elseif ('partially_refunded_order' === $emailNotification->getId()) {
                $email->partial_refund = true;
            }
        }

        return $email;
    }

    /**
     * Gets the URL of the WooCommerce settings screen for the source email.
     *
     * @return string|null
     */
    public function getEmailSettingsUrl()
    {
        if (! $section = $this->getEmailSettingsSection($this->source)) {
            return null;
        }

        return admin_url('admin.php').'?'.ArrayHelper::query([
            'page'    => 'wc-settings',
            'tab'     => 'email',
            'section' => $section,
        ]);
    }

    /**
     * Gets the value for the section parameter included in the URL of the WooCommerce settings screen for the given email.
     *
     * @return string|null
     */
    protected function getEmailSettingsSection(WC_Email $email)
    {
        try {
            $mailer = EmailsRepository::mailer();
        } catch (Exception $e) {
            return null;
        }

        if (! $mailer) {
            return null;
        }

        // we use get_emails() directly because the index defined when the email was
        // registered is not present in the result of EmailsRepository::all()
        foreach ($mailer->get_emails() as $index => $object) {
            if ($email === $object) {
                return strtolower($index);
            }
        }

        return null;
    }

    /**
     * Gets an array of mapped data between email notifications and WooCommerce emails.
     *
     * @return array
     */
    public static function getMappedData() : array
    {
        return [
            'cancelled_order'                   => [
                'emailId'  => 'cancelled_order',
                'category' => 'admin',
            ],
            'customer_completed_order'          => [
                'emailId'  => 'customer_completed_order',
                'category' => 'order',
            ],
            'customer_invoice'                  => [
                'emailId'  => 'customer_invoice',
                'category' => 'order',
            ],
            'customer_note'                     => [
                'emailId'  => 'customer_note',
                'category' => 'order',
            ],
            'failed_order'                      => [
                'emailId'  => 'failed_order',
                'category' => 'admin',
            ],
            'customer_item_shipped'             => [
                'emailId'  => 'mwc_item_shipped_email',
                'category' => 'order',
            ],
            'customer_refunded_order'           => [
                'emailId'  => 'customer_refunded_order',
                'category' => 'order',
            ],
            'customer_on_hold_order'            => [
                'emailId'  => 'customer_on_hold_order',
                'category' => 'order',
            ],
            'customer_processing_order'         => [
                'emailId'  => 'customer_processing_order',
                'category' => 'order',
            ],
            'customer_new_account'              => [
                'emailId'  => 'customer_new_account',
                'category' => 'customer',
            ],
            'new_order'                         => [
                'emailId'  => 'new_order',
                'category' => 'admin',
            ],
            'customer_partially_refunded_order' => [
                'emailId'  => 'customer_refunded_order',
                'category' => 'order',
            ],
            'customer_reset_password'           => [
                'emailId'  => 'customer_reset_password',
                'category' => 'customer',
            ],
        ];
    }
}
