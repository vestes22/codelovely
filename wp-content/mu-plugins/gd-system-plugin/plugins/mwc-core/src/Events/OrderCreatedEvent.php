<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

/**
 * Order created event class.
 *
 * @since 2.13.0
 */
class OrderCreatedEvent extends AbstractOrderEvent
{
    /**
     * OrderCreatedEvent constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->action = 'create';
    }
}
