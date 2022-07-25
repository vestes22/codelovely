<?php

namespace GoDaddy\WordPress\MWC\Common\Models\Orders\Statuses;

use GoDaddy\WordPress\MWC\Common\Contracts\OrderStatusContract;
use GoDaddy\WordPress\MWC\Common\Traits\CanConvertToArrayTrait;
use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * Class HeldOrderStatus.
 */
final class HeldOrderStatus implements OrderStatusContract
{
    use CanConvertToArrayTrait;
    use HasLabelTrait;

    public function __construct()
    {
        $this->setName('held')
            ->setLabel(__('Held', 'mwc-common'));
    }
}
