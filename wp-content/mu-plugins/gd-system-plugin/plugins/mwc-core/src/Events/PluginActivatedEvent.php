<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

/**
 * Plugin activated event class.
 *
 * @since 2.10.0
 */
class PluginActivatedEvent extends AbstractPluginEvent
{
    /** @var string the name of the event action */
    protected $action = 'activate';
}
