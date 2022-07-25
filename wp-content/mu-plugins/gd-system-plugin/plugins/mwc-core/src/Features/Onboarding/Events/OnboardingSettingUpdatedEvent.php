<?php

namespace GoDaddy\WordPress\MWC\Core\Features\Onboarding\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;
use GoDaddy\WordPress\MWC\Core\Features\Onboarding\Settings\OnboardingSetting;

class OnboardingSettingUpdatedEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var OnboardingSetting */
    protected $setting;

    /**
     * Constructor.
     *
     * @param OnboardingSetting $setting
     */
    public function __construct(OnboardingSetting $setting)
    {
        $this->resource = 'settings';
        $this->action = 'update';
        $this->setting = $setting;
    }

    /**
     * Gets the data for the event.
     *
     * @return array
     */
    public function getData() : array
    {
        return [
            'resource' => [
                'id'    => $this->setting->getId(),
                'value' => $this->setting->getValue(),
            ],
        ];
    }
}
