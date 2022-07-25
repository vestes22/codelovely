<?php

namespace GoDaddy\WordPress\MWC\Core\Features\GoogleAnalytics\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

/**
 * Google Analytics account connected event class.
 */
class GoogleAnalyticsConnectedEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->resource = 'google_analytics';
        $this->action = 'connect';
    }

    /**
     * Gets the data for the current event.
     *
     * @return array
     */
    public function getData() : array
    {
        return [];
    }
}
