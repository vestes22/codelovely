<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\Settings;

use GoDaddy\WordPress\MWC\Common\Settings\Models\SettingGroup;

/**
 * Onboarding settings class.
 */
class Settings extends SettingGroup
{
    /** @var string ID of the settings group */
    const GROUP_ID = 'onboarding';

    /**
     * Settings constructor.
     */
    public function __construct()
    {
        $this->id = $this->name = static::GROUP_ID;

        $this->label = __('Onboarding', 'mwc-core');
    }

    /**
     * Gets the settings for the onboarding wizard.
     *
     * @return OnboardingSetting[]
     */
    protected function getInitialSettings() : array
    {
        return [
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_FIRST_TIME),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_STORE_NAME),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_STORE_ADDRESS_FIRST_LINE),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_STORE_ADDRESS_SECOND_LINE),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_COUNTRY_REGION),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_CITY),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_POSTAL_CODE),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_CURRENCY),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_CURRENCY_POSITION),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_SELLING_LOCATIONS),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_SELL_TO_SPECIFIC_COUNTRIES),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_SELL_TO_ALL_COUNTRIES_EXCEPT),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_SHIPPING_LOCATIONS),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_SHIP_TO_SPECIFIC_COUNTRIES),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_THOUSANDS_SEPARATOR),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_DECIMAL_SEPARATOR),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_NUMBER_OF_DECIMALS),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_WEIGHT_UNITS),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_DIMENSION_UNITS),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_ENABLE_TAXES),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_LAST_ONBOARDING_STEP),
            OnboardingSetting::get(OnboardingSetting::SETTING_ID_COMPLETED),
        ];
    }
}
