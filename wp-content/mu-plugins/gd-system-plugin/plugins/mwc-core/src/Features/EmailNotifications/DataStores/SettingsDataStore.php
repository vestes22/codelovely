<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataStores;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Common\Settings\Models\SettingGroup;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Contracts\EmailNotificationContract;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailNotificationNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\EmailTemplateNotFoundException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Exceptions\InvalidClassNameException;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Settings\GeneralSettings;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Traits\CanGetEmailNotificationDataStoreTrait;
use InvalidArgumentException;

/**
 * Data store for settings.
 */
class SettingsDataStore
{
    use CanGetEmailNotificationDataStoreTrait;

    /** @var array available setting groups */
    protected $settings = [
        GeneralSettings::GROUP_ID => GeneralSettings::class,
    ];

    /**
     * Reads the values of the settings from database.
     *
     * @param string $id
     * @return ConfigurableContract
     * @throws InvalidArgumentException
     * @throws EmailNotificationNotFoundException
     * @throws EmailTemplateNotFoundException
     * @throws InvalidClassNameException
     */
    public function read(string $id) : ConfigurableContract
    {
        $newSubgroups = [];
        foreach ($this->getEmailNotificationDataStore()->all() as $notification) {
            $newSubgroups[] = $this->getSettingGroupInstance($notification);
        }

        $setting = $this->getSettingInstance($id);
        $setting->setSettingsSubgroups(ArrayHelper::combine($setting->getSettingsSubgroups(), $newSubgroups));
        $this->getOptionsSettingsDataStore($id)->read($setting);

        return $setting;
    }

    /**
     * Gets the setting instance from the given id.
     *
     * @param string $id
     * @return ConfigurableContract
     * @throws InvalidArgumentException
     */
    protected function getSettingInstance(string $id) : ConfigurableContract
    {
        if (! ArrayHelper::exists($this->settings, $id)) {
            throw new InvalidArgumentException(sprintf(
                __('No settings found with the ID %s.', 'mwc-core'),
                $id
            ));
        }

        $class = ArrayHelper::get($this->settings, $id);

        if (! is_a($class, ConfigurableContract::class, true)) {
            throw new InvalidArgumentException(sprintf(
                __('The class name for %s must implement ConfigurableContract', 'mwc-core'),
                $id
            ));
        }

        return new $class();
    }

    /**
     * Configures new SettingGroup instances.
     *
     * @param EmailNotificationContract $notification
     * @return ConfigurableContract
     */
    protected function getSettingGroupInstance(EmailNotificationContract $notification) : ConfigurableContract
    {
        $settingGroup = $this->createNewSettingGroupInstance();
        $settingGroup->setId($notification->getId())
                     ->setName($notification->getName())
                     ->setLabel($notification->getLabel());
        $settingGroup->setSettings($notification->getSettings());

        $subSettingGroup = $this->createNewSettingGroupInstance();
        $subSettingGroup->setId('content')
                        ->setName('content')
                        ->setLabel(__('Content', 'mwc-core'));
        if ($content = $notification->getContent()) {
            $subSettingGroup->setSettings($content->getSettings());
        }

        $settingGroup->addSettingsSubgroup($subSettingGroup);

        return $settingGroup;
    }

    /**
     * Creates new SettingGroup instance.
     *
     * @return SettingGroup
     */
    protected function createNewSettingGroupInstance() : SettingGroup
    {
        return new SettingGroup();
    }

    /**
     * Saves the settings values to database.
     *
     * @param ConfigurableContract $generalSettings
     * @return ConfigurableContract
     */
    public function save(ConfigurableContract $generalSettings) : ConfigurableContract
    {
        $this->getOptionsSettingsDataStore($generalSettings->getId())->save($generalSettings);

        return $generalSettings;
    }

    /**
     * Gets the options settings data store for the setting.
     *
     * @param string $settingId
     * @return OptionsSettingsDataStore
     */
    protected function getOptionsSettingsDataStore(string $settingId) : OptionsSettingsDataStore
    {
        return new OptionsSettingsDataStore($this->getOptionNameTemplate($settingId));
    }

    /**
     * Gets the option name template.
     *
     * @param string $settingId
     * @return string
     */
    protected function getOptionNameTemplate(string $settingId) : string
    {
        return 'mwc_'.$settingId.'_'.OptionsSettingsDataStore::SETTING_ID_MERGE_TAG;
    }
}
