<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\Settings;

use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;
use GoDaddy\WordPress\MWC\Common\Settings\Models\AbstractSetting;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Contracts\SettingDataStoreContract;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\DataStores\OptionsSettingDataStore;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Events\OnboardingSettingUpdatedEvent;

/**
 * Onboarding setting object that interacts with a data store.
 */
class OnboardingSetting extends AbstractSetting
{
    /** @var string ID of the "First time" setting */
    const SETTING_ID_FIRST_TIME = 'firstTime';

    /** @var string ID of the "Store name" setting */
    const SETTING_ID_STORE_NAME = 'storeName';

    /** @var string ID of the "Store address 1" setting */
    const SETTING_ID_STORE_ADDRESS_FIRST_LINE = 'storeAddress1';

    /** @var string ID of the "Store address 2" setting */
    const SETTING_ID_STORE_ADDRESS_SECOND_LINE = 'storeAddress2';

    /** @var string ID of the "Country Region" setting */
    const SETTING_ID_COUNTRY_REGION = 'countryRegion';

    /** @var string ID of the "City" setting */
    const SETTING_ID_CITY = 'city';

    /** @var string ID of the "Postal code" setting */
    const SETTING_ID_POSTAL_CODE = 'postalCode';

    /** @var string ID of the "Currency" setting */
    const SETTING_ID_CURRENCY = 'currency';

    /** @var string ID of the "Currency position" setting */
    const SETTING_ID_CURRENCY_POSITION = 'currencyPosition';

    /** @var string ID of the "Selling locations" setting */
    const SETTING_ID_SELLING_LOCATIONS = 'sellingLocations';

    /** @var string ID of the "Sell to specific countries" setting */
    const SETTING_ID_SELL_TO_SPECIFIC_COUNTRIES = 'sellToSpecificCountries';

    /** @var string ID of the "Sell to all countries except" setting */
    const SETTING_ID_SELL_TO_ALL_COUNTRIES_EXCEPT = 'sellToAllCountriesExcept';

    /** @var string ID of the "Shipping locations" setting */
    const SETTING_ID_SHIPPING_LOCATIONS = 'shippingLocations';

    /** @var string ID of the "Ship to specific countries" setting */
    const SETTING_ID_SHIP_TO_SPECIFIC_COUNTRIES = 'shipToSpecificCountries';

    /** @var string ID of the "Thousands separator" setting */
    const SETTING_ID_THOUSANDS_SEPARATOR = 'thousandsSeparator';

    /** @var string ID of the "Decimal separator" setting */
    const SETTING_ID_DECIMAL_SEPARATOR = 'decimalSeparator';

    /** @var string ID of the "Number of decimals" setting */
    const SETTING_ID_NUMBER_OF_DECIMALS = 'numberOfDecimals';

    /** @var string ID of the "Weight units" setting */
    const SETTING_ID_WEIGHT_UNITS = 'weightUnits';

    /** @var string ID of the "Dimension units" setting */
    const SETTING_ID_DIMENSION_UNITS = 'dimensionUnits';

    /** @var string ID of the "Enable taxes" setting */
    const SETTING_ID_ENABLE_TAXES = 'enableTaxes';

    /** @var string ID of the "Last onboarding step" setting */
    const SETTING_ID_LAST_ONBOARDING_STEP = 'lastOnboardingStep';

    /** @var string ID of the "Completed" setting */
    const SETTING_ID_COMPLETED = 'completed';

    /**
     * No-op: no such thing as creating a setting.
     *
     * @return null
     */
    public static function create()
    {
    }

    /**
     * Get an OnboardingSetting object from the data store by id.
     *
     * @param string $identifier
     * @return OnboardingSetting|SettingContract
     */
    public static function get($identifier)
    {
        return static::getDataStore()->read($identifier);
    }

    /**
     * Constructor.
     */
    final public function __construct()
    {
        // final constructor used to ensure that all subclasses can be instantiated without parameters
    }

    /**
     * @see save
     * @return SettingContract|OnboardingSetting
     */
    public function update()
    {
        $setting = $this->save();

        Events::broadcast(new OnboardingSettingUpdatedEvent($setting));

        return $setting;
    }

    /**
     * No-op: onboarding settings are not deletable.
     */
    public function delete()
    {
    }

    /**
     * Save this OnboardingSetting to the data store.
     *
     * @return SettingContract|OnboardingSetting
     */
    public function save()
    {
        return static::getDataStore()->save($this);
    }

    /**
     * Seed an OnboardingSetting object with default values.
     *
     * @return OnboardingSetting
     */
    public static function seed()
    {
        return new static();
    }

    /**
     * Get a data store instance.
     *
     * @return SettingDataStoreContract
     */
    protected static function getDataStore() : SettingDataStoreContract
    {
        return new OptionsSettingDataStore();
    }
}
