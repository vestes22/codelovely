<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\Contracts;

use GoDaddy\WordPress\MWC\Common\Settings\Contracts\SettingContract;

interface SettingDataStoreContract
{
    /**
     * Reads a setting from the data store.
     *
     * @param string $identifier
     * @return SettingContract
     */
    public function read(string $identifier): SettingContract;

    /**
     * Saves a setting to the data store.
     *
     * @param SettingContract $setting
     * @return SettingContract
     */
    public function save(SettingContract $setting): SettingContract;

    /**
     * Deletes a setting from the data store.
     *
     * @param SettingContract $setting
     * @return SettingContract
     */
    public function delete(SettingContract $setting): SettingContract;
}
