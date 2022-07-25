<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventBridgeEventContract;
use GoDaddy\WordPress\MWC\Common\Extensions\Types\PluginExtension;
use GoDaddy\WordPress\MWC\Common\Traits\IsEventBridgeEventTrait;

/**
 * Abstract plugin event class.
 *
 * @since 2.10.0
 */
abstract class AbstractPluginEvent implements EventBridgeEventContract
{
    use IsEventBridgeEventTrait;

    /** @var PluginExtension */
    protected $plugin;

    /**
     * AbstractPluginEvent constructor.
     *
     * @since 2.10.0
     *
     * @param PluginExtension $plugin
     */
    public function __construct(PluginExtension $plugin)
    {
        $this->resource = 'plugin';
        $this->plugin = $plugin;
    }

    /**
     * Gets the data for the current event.
     *
     * @since 2.10.0
     *
     * @return array
     */
    public function getData() : array
    {
        return [
            'plugin' => [
                'slug' => $this->plugin->getSlug(),
            ],
        ];
    }
}
