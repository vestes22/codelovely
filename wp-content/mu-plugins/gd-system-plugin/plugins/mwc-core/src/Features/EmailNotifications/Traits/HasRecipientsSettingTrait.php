<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Models\Control;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationWithRecipientsContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotificationSetting;
use InvalidArgumentException;

/**
 * A trait for objects implementing a recipients setting.
 *
 * @see EmailNotificationWithRecipientsContract
 */
trait HasRecipientsSettingTrait
{
    /** @var string */
    protected $recipientsSettingId = 'recipients';

    /**
     * Gets a setting's value.
     *
     * @param string $name
     * @return int|float|string|bool|array
     * @throws InvalidArgumentException
     */
    abstract public function getSettingValue(string $name);

    /**
     * Updates a setting's value.
     *
     * Will validate a value to be set against the setting type and any options, if set.
     *
     * @param string $name
     * @param mixed $value
     * @throws InvalidArgumentException
     */
    abstract public function updateSettingValue(string $name, $value);

    /**
     * Sets the recipients.
     *
     * @param string[] $value
     * @return self
     * @throws InvalidArgumentException
     */
    public function setRecipients(array $value)
    {
        $this->updateSettingValue($this->recipientsSettingId, $value);

        return $this;
    }

    /**
     * Gets the recipients.
     *
     * @return string[]
     */
    public function getRecipients() : array
    {
        return ArrayHelper::wrap($this->getSettingValue($this->recipientsSettingId));
    }

    /**
     * Gets a setting object for recipients.
     *
     * @return EmailNotificationSetting
     */
    public function getRecipientsSettingObject() : EmailNotificationSetting
    {
        return (new EmailNotificationSetting())
            ->setId($this->recipientsSettingId)
            ->setName($this->recipientsSettingId)
            ->setLabel(__('Recipients', 'mwc-core'))
            ->setDescription(__('Enter recipients (comma-separated) for this email', 'mwc-core'))
            ->setType(EmailNotificationSetting::TYPE_EMAIL)
            ->setIsMultivalued(true)
            ->setIsRequired(true)
            ->setDefault(get_option('admin_email'))
            ->setControl((new Control())
                ->setType(Control::TYPE_TEXT)
            );
    }
}
