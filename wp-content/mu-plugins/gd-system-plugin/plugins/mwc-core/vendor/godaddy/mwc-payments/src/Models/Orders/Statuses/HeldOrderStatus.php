<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\Orders\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * Class HeldOrderStatus
 */
final class HeldOrderStatus
{
    use HasLabelTrait;

    public function __construct()
    {
        $this->setName('held')
            ->setLabel(__('Held', 'mwc-payments'));
    }
}
