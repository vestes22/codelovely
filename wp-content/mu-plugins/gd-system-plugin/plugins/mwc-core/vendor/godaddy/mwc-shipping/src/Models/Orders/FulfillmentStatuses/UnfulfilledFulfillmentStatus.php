<?php

namespace GoDaddy\WordPress\MWC\Shipping\Models\Orders\FulfillmentStatuses;

use GoDaddy\WordPress\MWC\Common\Contracts\FulfillmentStatusContract;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * Represents an unfulfilled fulfillment status.
 *
 * @since 0.1.0
 */
class UnfulfilledFulfillmentStatus implements FulfillmentStatusContract
{
    use HasLabelTrait;

    /**
     * Unfulfilled status constructor.
     *
     * Initializes the status by setting its name and label.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->setName('unfulfilled');
        $this->setLabel(__('Unfulfilled', 'mwc-shipping'));
    }
}
