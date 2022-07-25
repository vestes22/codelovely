<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\Orders\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * Class PendingOrderStatus
 */
final class PendingOrderStatus
{
    use HasLabelTrait;

    public function __construct()
    {
        $this->setName('pending')
            ->setLabel(__('Pending payment', 'mwc-payments'));
    }
}
