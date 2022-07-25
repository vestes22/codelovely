<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;

/**
 * Visa card brand.
 */
final class VisaCardBrand implements CardBrandContract
{
    use HasLabelTrait;

    /**
     * Visa card brand constructor.
     */
    public function __construct()
    {
        $this->setName('visa')
            ->setLabel(_x('Visa', 'card brand name', 'mwc-payments'));
    }
}
