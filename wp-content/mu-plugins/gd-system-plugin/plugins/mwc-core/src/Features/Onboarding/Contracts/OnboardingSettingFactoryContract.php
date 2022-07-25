<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\Contracts;

use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Settings\OnboardingSetting;

/**
 * Factory for the OnboardingSetting class.
 */
interface OnboardingSettingFactoryContract
{
    /**
     * Builds the object that represents the First time setting.
     *
     * @return OnboardingSetting
     */
    public function buildFirstTimeSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Store name setting.
     *
     * @return OnboardingSetting
     */
    public function buildStoreNameSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Store address 1 setting.
     *
     * @return OnboardingSetting
     */
    public function buildStoreAddressFirstLineSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Store address 2 setting.
     *
     * @return OnboardingSetting
     */
    public function buildStoreAddressSecondLineSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Country Region setting.
     *
     * @return OnboardingSetting
     */
    public function buildCountryRegionSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the City setting.
     *
     * @return OnboardingSetting
     */
    public function buildCitySetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Postal code setting.
     *
     * @return OnboardingSetting
     */
    public function buildPostalCodeSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Currency setting.
     *
     * @return OnboardingSetting
     */
    public function buildCurrencySetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Currency position setting.
     *
     * @return OnboardingSetting
     */
    public function buildCurrencyPositionSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Selling locations setting.
     *
     * @return OnboardingSetting
     */
    public function buildSellingLocationsSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Sell to specific countries setting.
     *
     * @return OnboardingSetting
     */
    public function buildSellToSpecificCountriesSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Sell to all countries except setting.
     *
     * @return OnboardingSetting
     */
    public function buildSellToAllCountriesExceptSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Shipping locations setting.
     *
     * @return OnboardingSetting
     */
    public function buildShippingLocationsSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Ship to specific countries setting.
     *
     * @return OnboardingSetting
     */
    public function buildShipToSpecificCountriesSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Thousands separator setting.
     *
     * @return OnboardingSetting
     */
    public function buildThousandsSeparatorSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Decimal separator setting.
     *
     * @return OnboardingSetting
     */
    public function buildDecimalSeparatorSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Number of decimals setting.
     *
     * @return OnboardingSetting
     */
    public function buildNumberOfDecimalsSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Weight units setting.
     *
     * @return OnboardingSetting
     */
    public function buildWeightUnitsSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Dimension units setting.
     *
     * @return OnboardingSetting
     */
    public function buildDimensionUnitsSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Enable taxes setting.
     *
     * @return OnboardingSetting
     */
    public function buildEnableTaxesSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Last onboarding step setting.
     *
     * @return OnboardingSetting
     */
    public function buildLastOnboardingStepSetting() : OnboardingSetting;

    /**
     * Builds the object that represents the Completed setting.
     *
     * @return OnboardingSetting
     */
    public function buildCompletedSetting() : OnboardingSetting;
}
