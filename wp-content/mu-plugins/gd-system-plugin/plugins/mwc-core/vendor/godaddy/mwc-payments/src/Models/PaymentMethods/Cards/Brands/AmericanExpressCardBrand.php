<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;

/**
 * American Express card brand.
 */
final class AmericanExpressCardBrand implements CardBrandContract
{
    use HasLabelTrait;

    /**
     * American Express card brand constructor.
     */
    public function __construct()
    {
        $this->setName('american-express')
            ->setLabel(_x('American Express', 'card brand name', 'mwc-payments'));
    }
}
