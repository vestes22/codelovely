<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

/**
 * Product created event class.
 */
class ProductCreatedEvent extends AbstractProductEvent
{
    /**
     * ProductCreatedEvent constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->action = 'create';
    }
}
