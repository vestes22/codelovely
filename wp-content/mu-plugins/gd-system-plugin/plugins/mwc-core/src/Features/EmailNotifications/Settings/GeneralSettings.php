<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Settings;

use GoDaddy\WordPress\MWC\Common\Repositories\WordPress\SiteRepository;
use GoDaddy\WordPress\MWC\Common\Settings\Models\Control;
use GoDaddy\WordPress\MWC\Common\Settings\Models\SettingGroup;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotificationSetting;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetWooCommerceSettingsDataStoreTrait;
use InvalidArgumentException;

/**
 * The general settings group.
 *
 * @since 2.15.0
 */
class GeneralSettings extends SettingGroup
{
    use CanGetWooCommerceSettingsDataStoreTrait;

    /** @var string ID of the settings group */
    const GROUP_ID = 'email_notifications';

    /** @var string ID of the "Sender name" setting */
    const SETTING_ID_SENDER_NAME = 'sender_name';

    /** @var string ID of the "Sender address" setting */
    const SETTING_ID_SENDER_ADDRESS = 'sender_address';

    /**
     * GeneralSettings constructor.
     *
     * @since 2.15.0
     */
    public function __construct()
    {
        $this->id = $this->name = static::GROUP_ID;

        $this->label = __('Email Notifications', 'mwc-core');
    }

    /**
     * Gets the initial settings.
     *
     * @return EmailNotificationSetting[]
     */
    protected function getInitialSettings() : array
    {
        return [
            // "Sender name" setting
            (new EmailNotificationSetting())
                ->setId(static::SETTING_ID_SENDER_NAME)
                ->setName(static::SETTING_ID_SENDER_NAME)
                ->setLabel(__('Sender name', 'mwc-core'))
                ->setIsRequired(true)
                ->setType(EmailNotificationSetting::TYPE_STRING)
                ->setDefault(SiteRepository::getTitle())
                ->setControl((new Control())
                    ->setType(Control::TYPE_TEXT)
                ),

            // "Sender address" setting
            (new EmailNotificationSetting())
                ->setId(static::SETTING_ID_SENDER_ADDRESS)
                ->setName(static::SETTING_ID_SENDER_ADDRESS)
                ->setLabel(__('Sender address', 'mwc-core'))
                ->setIsRequired(true)
                ->setType(EmailNotificationSetting::TYPE_EMAIL)
                ->setDefault((string) get_option('admin_email'))
                ->setControl((new Control())
                    ->setType(Control::TYPE_EMAIL)
                ),
        ];
    }

    /**
     * Gets the value or the default value of the sender name setting.
     *
     * Can also return null if there is a problem trying to retrieve the setting object.
     *
     * @return string|null
     */
    public function getSenderName()
    {
        return $this->getSettingValueOrDefault(static::SETTING_ID_SENDER_NAME);
    }

    /**
     * Gets the value or the default value of a setting.
     *
     * Can also return null if there is a problem trying to retrieve the setting object.
     *
     * @return mixed|null
     */
    protected function getSettingValueOrDefault(string $name)
    {
        try {
            $setting = $this->getSetting($name);
        } catch (InvalidArgumentException $exception) {
            return null;
        }

        return $setting->hasValue() ? $setting->getValue() : $setting->getDefault();
    }

    /**
     * Gets the value or the default value of the sender address setting.
     *
     * Can also return null if there is a problem trying to retrieve the setting object.
     *
     * @return string|null
     */
    public function getSenderAddress()
    {
        return $this->getSettingValueOrDefault(static::SETTING_ID_SENDER_ADDRESS);
    }
}
