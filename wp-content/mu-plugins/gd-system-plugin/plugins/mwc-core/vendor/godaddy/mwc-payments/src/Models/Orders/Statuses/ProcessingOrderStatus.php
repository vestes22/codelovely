<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\Orders\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;

/**
 * Class ProcessingOrderStatus
 */
final class ProcessingOrderStatus
{
    use HasLabelTrait;

    public function __construct()
    {
        $this->setName('processing')
            ->setLabel(__('Processing', 'mwc-payments'));
    }
}
