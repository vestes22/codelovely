<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses;

use GoDaddy\WordPress\MWC\Common\Contracts\FulfillmentStatusContract;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * Represents a partially fulfilled fulfillment status.
 *
 * @since 0.1.0
 */
class PartiallyFulfilledFulfillmentStatus implements FulfillmentStatusContract
{
    use HasLabelTrait;

    /**
     * Partially fulfilled status constructor.
     *
     * Initializes the status by setting its name and label.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->setName('partially-fulfilled');
        $this->setLabel(__('Partially Fulfilled', 'mwc-shipping'));
    }
}
