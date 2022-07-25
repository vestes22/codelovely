<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses;

use GoDaddy\WordPress\MWC\Common\Contracts\FulfillmentStatusContract;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * Represents a fulfilled fulfillment status.
 *
 * @since 0.1.0
 */
class FulfilledFulfillmentStatus implements FulfillmentStatusContract
{
    use HasLabelTrait;

    /**
     * Fulfilled status constructor.
     *
     * Initializes the status by setting its name and label.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->setName('fulfilled');
        $this->setLabel(__('Fulfilled', 'mwc-shipping'));
    }
}
