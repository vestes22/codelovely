<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\WooCommerce;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanFormatDatabaseSettingValuesTrait;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailTemplateContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores\EmailTemplateDataStore as RawEmailTemplateDataStore;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailTemplateNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\InvalidClassNameException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\DefaultEmailTemplate;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Models\EmailNotificationSetting;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanConvertWooCommerceEmailPlaceholdersTrait;
use InvalidArgumentException;

/**
 * Wrapper for Settings Data store.
 */
class EmailTemplateDataStore
{
    use CanFormatDatabaseSettingValuesTrait {
        formatSingleValueFromDatabase as formatSingleValueFromDatabaseWithTrait;
        formatSingleValueForDatabase as formatSingleValueForDatabaseWithTrait;
    }
    use CanConvertWooCommerceEmailPlaceholdersTrait;

    /** @var RawEmailTemplateDataStore */
    protected $dataStore;

    /** @var array[] */
    protected $wooCommerceOptionsMap = [
        'container' => [
            'backgroundColor' => 'woocommerce_email_background_color',
        ],
        'header'    => [
            'image'           => 'woocommerce_email_header_image',
            'backgroundColor' => 'woocommerce_email_base_color',
        ],
        'body'      => [
            'backgroundColor' => 'woocommerce_email_body_background_color',
            'text'            => [
                'color' => 'woocommerce_email_text_color',
            ],
        ],
        'footer'    => [
            'footerText' => 'woocommerce_email_footer_text',
        ],
    ];

    /** @var string[] */
    protected $wooCommerceOptionsDefaultValues = [
        'woocommerce_email_background_color'      => '#f7f7f7',
        'woocommerce_email_header_image'          => '',
        'woocommerce_email_base_color'            => '#96588a',
        'woocommerce_email_body_background_color' => '#ffffff',
        'woocommerce_email_text_color'            => '#3c3c3c',
        'woocommerce_email_footer_text'           => '{site_title} &mdash; Built with WooCommerce',
    ];

    /**
     * Constructor.
     *
     * @param RawEmailTemplateDataStore|null $dataStore
     */
    public function __construct(RawEmailTemplateDataStore $dataStore = null)
    {
        $this->dataStore = $dataStore ?? new RawEmailTemplateDataStore();
    }

    /**
     * Gets an email template with given ID and reads its settings.
     *
     * @param string $id
     * @return EmailTemplateContract
     * @throws InvalidArgumentException|EmailTemplateNotFoundException|InvalidClassNameException
     */
    public function read(string $id) : EmailTemplateContract
    {
        return $this->maybeMapWooCommerceSettings($this->dataStore->read($id), false);
    }

    /**
     * Saves the settings of a given email template object.
     *
     * @param EmailTemplateContract $emailTemplate
     * @return EmailTemplateContract
     * @throws InvalidArgumentException
     */
    public function save(EmailTemplateContract $emailTemplate) : EmailTemplateContract
    {
        return $this->dataStore->save($this->maybeMapWooCommerceSettings($emailTemplate, true));
    }

    /**
     * Deletes the settings of a given email template object.
     *
     * @param EmailTemplateContract $emailTemplate
     * @return EmailTemplateContract
     */
    public function delete(EmailTemplateContract $emailTemplate) : EmailTemplateContract
    {
        return $this->dataStore->delete($emailTemplate);
    }

    /**
     * May map settings with counterpart WooCommerce options.
     *
     * @param EmailTemplateContract $template
     * @param bool $saveSettings
     * @return EmailTemplateContract
     * @throws InvalidArgumentException
     */
    protected function maybeMapWooCommerceSettings(EmailTemplateContract $template, bool $saveSettings) : EmailTemplateContract
    {
        if ('default' === $template->getId()) {
            $template = $saveSettings ? $this->mapSaveWooCommerceSettings($template) : $this->mapReadWooCommerceSettings($template);
        }

        return $template;
    }

    /**
     * Map reading template settings with counterpart WooCommerce options.
     *
     * @param EmailTemplateContract $template
     * @return EmailTemplateContract
     * @throws InvalidArgumentException
     */
    protected function mapReadWooCommerceSettings(EmailTemplateContract $template) : EmailTemplateContract
    {
        foreach ($this->wooCommerceOptionsMap as $subSettingsGroupId => $fieldsMap) {
            if (! $subSettingsGroup = $template->getSettingsSubgroup($subSettingsGroupId)) {
                continue;
            }

            foreach ($fieldsMap as $settingId => $optionName) {
                $this->maySetSubGroupSettingValue($subSettingsGroup, $settingId, $optionName);
            }
        }

        return $template;
    }

    /**
     * May set the given setting value from WooCommerce's counterpart if value not set.
     *
     * @param ConfigurableContract $subSettingsGroup
     * @param string $settingId
     * @param string|array $optionName
     * @throws InvalidArgumentException
     */
    protected function maySetSubGroupSettingValue(ConfigurableContract $subSettingsGroup, string $settingId, $optionName)
    {
        if (ArrayHelper::accessible($optionName) && $subSettingsGroup = $subSettingsGroup->getSettingsSubgroup($settingId)) {
            foreach ($optionName as $subSettingId => $subOptionName) {
                $this->maySetSubGroupSettingValue($subSettingsGroup, $subSettingId, $subOptionName);
            }

            return;
        }

        $setting = $subSettingsGroup->getSetting($settingId);

        if (! $setting->hasValue()) {
            $optionValue = get_option($optionName, null);

            if (! is_null($optionValue) && $optionValue !== $this->getWooCommerceOptionDefault($optionName)) {
                $setting->setValue($this->formatValueFromDatabase($optionValue, $setting));
            }
        }
    }

    /**
     * Gets the known default value for the given WooCommerce option.
     *
     * @param string $optionName
     * @return string
     */
    protected function getWooCommerceOptionDefault(string $optionName) : string
    {
        return ArrayHelper::get($this->wooCommerceOptionsDefaultValues, $optionName, '');
    }

    /**
     * Map saving template settings with counterpart WooCommerce options.
     *
     * @param EmailTemplateContract $template
     * @return EmailTemplateContract
     * @throws InvalidArgumentException
     */
    protected function mapSaveWooCommerceSettings(EmailTemplateContract $template) : EmailTemplateContract
    {
        foreach ($this->wooCommerceOptionsMap as $subSettingsGroupId => $fieldsMap) {
            if (! $subSettingsGroup = $template->getSettingsSubgroup($subSettingsGroupId)) {
                continue;
            }

            foreach ($fieldsMap as $settingId => $optionName) {
                $this->maySaveSubGroupSettingValue($subSettingsGroup, $settingId, $optionName);
            }
        }

        return $template;
    }

    /**
     * May saves the given setting value to its WooCommerce's counterpart.
     *
     * @param ConfigurableContract $subSettingsGroup
     * @param string $settingId
     * @param string|array $optionName
     * @throws InvalidArgumentException
     */
    protected function maySaveSubGroupSettingValue(ConfigurableContract $subSettingsGroup, string $settingId, $optionName)
    {
        if (ArrayHelper::accessible($optionName) && $subSettingsGroup = $subSettingsGroup->getSettingsSubgroup($settingId)) {
            foreach ($optionName as $subSettingId => $subOptionName) {
                $this->maySaveSubGroupSettingValue($subSettingsGroup, $subSettingId, $subOptionName);
            }

            return;
        }

        $setting = $subSettingsGroup->getSetting($settingId);

        if ($setting->hasValue()) {
            update_option($optionName, $this->formatValueForDatabase($setting->getValue(), $setting));
        }
    }

    /**
     * Converts a single setting value from database for setting type consistency.
     *
     * @param bool|float|int|string|array $value
     * @param SettingContract $setting
     * @return bool|float|int|string
     * @throws InvalidArgumentException
     */
    protected function formatSingleValueFromDatabase($value, SettingContract $setting)
    {
        if ($setting->getName() === DefaultEmailTemplate::SETTING_ID_HEADER_IMAGE && $setting->getType() === EmailNotificationSetting::TYPE_ARRAY) {
            $value = ['url' => $value];
        } elseif (EmailNotificationSetting::TYPE_STRING === $setting->getType()) {
            $value = $this->convertPlaceholdersFromSource($value);
        }

        return $this->formatSingleValueFromDatabaseWithTrait($value, $setting);
    }

    /**
     * Converts a setting value for database storage.
     *
     * @param bool|int|float|string|array $value
     * @param SettingContract $setting
     * @return bool|float|int|string
     * @throws InvalidArgumentException
     */
    protected function formatSingleValueForDatabase($value, SettingContract $setting)
    {
        $value = $this->formatSingleValueForDatabaseWithTrait($value, $setting);

        if ($setting->getName() === DefaultEmailTemplate::SETTING_ID_HEADER_IMAGE && $setting->getType() === EmailNotificationSetting::TYPE_ARRAY) {
            return ArrayHelper::get($value, 'url', '');
        }

        if (EmailNotificationSetting::TYPE_STRING === $setting->getType()) {
            return $this->convertPlaceholdersToSource($value);
        }

        return $value;
    }
}
