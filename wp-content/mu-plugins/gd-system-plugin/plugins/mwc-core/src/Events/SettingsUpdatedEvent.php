<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Helpers\StringHelper;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

class SettingsUpdatedEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var string The group the settings belong to (usually the plugin dasherized ID) */
    protected $group;

    /** @var array The settings values */
    protected $settings;

    /**
     * Constructor.
     *
     * @param string $group
     */
    public function __construct(string $group)
    {
        $this->resource = 'settings';
        $this->action = 'update';
        $this->group = $group;
    }

    /**
     * Sets the settings.
     *
     * @param array $settings
     * @return self
     */
    public function setSettings(array $settings) : self
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * Gets the settings, with sensitive values masked.
     *
     * @return array
     */
    public function getSettings() : array
    {
        array_walk_recursive($this->settings, [$this, 'maskSensitiveInformation']);

        return $this->settings;
    }

    /**
     * Masks sensitive information in a setting.
     */
    protected function maskSensitiveInformation(&$item, $key)
    {
        if (StringHelper::contains($key, ['password', 'secret', 'key', 'token'])) {
            $item = str_repeat('*', strlen($item));
        }
    }

    /**
     * Gets the data for the event.
     *
     * @return array
     */
    public function getData() : array
    {
        return [
            'group'    => $this->group,
            'settings' => $this->getSettings(),
        ];
    }
}
