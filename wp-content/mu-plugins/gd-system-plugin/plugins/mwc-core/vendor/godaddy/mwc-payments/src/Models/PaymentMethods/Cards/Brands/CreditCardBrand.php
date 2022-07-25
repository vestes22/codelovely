<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;

/**
 * Generic credit card brand.
 */
final class CreditCardBrand implements CardBrandContract
{
    use HasLabelTrait;

    /**
     * Generic credit card brand constructor.
     */
    public function __construct()
    {
        $this->setName('credit')
            ->setLabel(__('Credit Card', 'mwc-payments'));
    }
}
