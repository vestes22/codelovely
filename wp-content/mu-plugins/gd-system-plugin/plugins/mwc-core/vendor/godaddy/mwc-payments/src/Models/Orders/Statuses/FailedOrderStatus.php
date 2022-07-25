<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\Orders\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * Class FailedOrderStatus
 */
final class FailedOrderStatus
{
    use HasLabelTrait;

    public function __construct()
    {
        $this->setName('failed')
            ->setLabel(__('Failed', 'mwc-payments'));
    }
}
