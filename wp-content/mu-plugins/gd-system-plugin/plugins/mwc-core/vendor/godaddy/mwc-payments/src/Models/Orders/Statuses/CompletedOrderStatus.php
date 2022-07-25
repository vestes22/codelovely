<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\Orders\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * Class CompletedOrderStatus
 */
final class CompletedOrderStatus
{
    use HasLabelTrait;

    public function __construct()
    {
        $this->setName('completed')
            ->setLabel(__('Completed', 'mwc-payments'));
    }
}
