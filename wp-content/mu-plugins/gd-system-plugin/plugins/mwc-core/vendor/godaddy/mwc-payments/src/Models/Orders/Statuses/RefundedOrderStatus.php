<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\Orders\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * Class RefundedOrderStatus
 */
final class RefundedOrderStatus
{
    use HasLabelTrait;

    public function __construct()
    {
        $this->setName('refunded')
            ->setLabel(__('Refunded', 'mwc-payments'));
    }
}
