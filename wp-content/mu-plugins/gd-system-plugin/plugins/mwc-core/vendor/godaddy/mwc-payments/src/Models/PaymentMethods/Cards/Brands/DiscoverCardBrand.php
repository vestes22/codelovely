<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;

/**
 * Discover card brand.
 */
final class DiscoverCardBrand implements CardBrandContract
{
    use HasLabelTrait;

    /**
     * Discover card brand constructor.
     */
    public function __construct()
    {
        $this->setName('discover')
            ->setLabel(_x('Discover', 'card brand name', 'mwc-payments'));
    }
}
