<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

/**
 * Plugin deactivated event class.
 *
 * @since 2.10.0
 */
class PluginDeactivatedEvent extends AbstractPluginEvent
{
    /** @var string the name of the event action */
    protected $action = 'deactivate';
}
