<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\Orders\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * Class CancelledOrderStatus
 */
final class CancelledOrderStatus
{
    use HasLabelTrait;

    public function __construct()
    {
        $this->setName('cancelled')
            ->setLabel(__('Cancelled', 'mwc-payments'));
    }
}
