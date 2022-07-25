<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses;

use GoDaddy\WordPress\MWC\Common\Contracts\OrderStatusContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * Class PendingOrderStatus.
 */
final class PendingOrderStatus implements OrderStatusContract
{
    use CanConvertToArrayTrait;
    use HasLabelTrait;

    public function __construct()
    {
        $this->setName('pending')
            ->setLabel(__('Pending payment', 'mwc-common'));
    }
}
