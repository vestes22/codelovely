<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

/**
 * Order updated event class.
 *
 * @since 2.13.0
 */
class OrderUpdatedEvent extends AbstractOrderEvent
{
    /**
     * OrderUpdatedEvent constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->action = 'update';
    }
}
