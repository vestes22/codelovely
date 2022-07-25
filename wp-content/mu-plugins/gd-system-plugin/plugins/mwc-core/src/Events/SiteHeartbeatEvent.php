<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

/**
 * Site heartbeat event class.
 *
 * @since 2.11.0
 */
class SiteHeartbeatEvent extends AbstractSiteEvent
{
    /** @var string the name of the event action */
    protected $action = 'heartbeat';
}
