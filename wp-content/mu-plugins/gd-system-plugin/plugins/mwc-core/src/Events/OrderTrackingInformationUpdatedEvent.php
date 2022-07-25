<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

/**
 * Order tracking information created event class.
 */
class OrderTrackingInformationUpdatedEvent extends AbstractOrderTrackingInformationEvent
{
    /**
     * OrderTrackingInformationUpdatedEvent constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->action = 'update';
    }
}
