<?php

namespace GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Events;

use GoDaddy\WordPress\MWC\Common\Helpers\ArrayHelper;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Settings\Contracts\ConfigurableContract;
use GoDaddy\WordPress\MWC\Core\Events\SettingsUpdatedEvent;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\DataProviders\SiteDataProvider;
use GoDaddy\WordPress\MWC\Core\Features\EmailNotifications\Settings\GeneralSettings;

class EmailNotificationsSettingsUpdatedEvent extends SettingsUpdatedEvent
{
    /** @var string[] list of most commonly used free email providers */
    protected $freeEmailProviders = [
        // Google
        'gmail.com',
        // Microsoft
        'outlook.com',
        'live.com',
        'hotmail.com',
        // Apple iCloud
        'icloud.com',
        'privaterelay.appleid.com',
        // Yahoo
        'yahoo.com',
        'yahoo.co',
        // Zoho
        'zohomail.com',
        // GMX
        'gmx.net',
        'gmx.at',
        'gmx.ch',
        // AOL
        'aol.com',
        // ProtonMail
        'protonmail.com',
        // WebMail
        'webmail.co',
        // FastMail
        'fastmail.',
        // Tutanota
        'tutanota.com',
        // Firefox Relay
        'relay.firefox.com',
    ];

    /**
     * Constructor.
     *
     * @param ConfigurableContract $generalSettings
     */
    public function __construct(ConfigurableContract $generalSettings)
    {
        parent::__construct('email_notifications');

        $this->setGeneralSettings($generalSettings);
    }

    /**
     * Sets the general settings to pull event data from.
     *
     * @param ConfigurableContract $generalSettings
     * @return self
     */
    protected function setGeneralSettings(ConfigurableContract $generalSettings) : EmailNotificationsSettingsUpdatedEvent
    {
        $this->setSettings($this->getTopLevelSettingsValues($generalSettings));

        return $this;
    }

    /**
     * Gets the top level settings values as associative array.
     *
     * @param ConfigurableContract $generalSettings
     * @return array
     */
    protected function getTopLevelSettingsValues(ConfigurableContract $generalSettings) : array
    {
        $settings = [];
        foreach ($generalSettings->getSettings() as $setting) {
            $settings[$setting->getName()] = $setting->getValue();
        }

        return $settings;
    }

    /**
     * Determines if the given email address is from one of the free email providers.
     *
     * @param string $emailAddress
     * @return bool
     */
    protected function isFreeEmailAddress(string $emailAddress) : bool
    {
        // is the address same as site's?
        if (StringHelper::contains($emailAddress, $this->getCurrentSiteAddress())) {
            return false;
        }

        // is the address from the commonly used email providers?
        if (StringHelper::contains($emailAddress, $this->freeEmailProviders)) {
            return true;
        }

        // so it is properly not free
        return false;
    }

    /**
     * Gets current site address/domain.
     *
     * @return string
     */
    protected function getCurrentSiteAddress() : string
    {
        // TODO: use WordPressRepository::getSiteDomain() when it becomes available {wvega 2021-10-13}
        return (string) ArrayHelper::get((new SiteDataProvider())->getData(), 'site_address', '');
    }

    /**
     * {@inheritdoc}
     */
    public function getData() : array
    {
        return array_merge(parent::getData(), [
            'address_type' => $this->isFreeEmailAddress((string) ArrayHelper::get($this->getSettings(), GeneralSettings::SETTING_ID_SENDER_ADDRESS, '')) ? 'free' : 'branded',
        ]);
    }
}
