<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;

/**
 * Generic debit card brand.
 */
final class DebitCardBrand implements CardBrandContract
{
    use HasLabelTrait;

    /**
     * Generic debit card brand constructor.
     */
    public function __construct()
    {
        $this->setName('debit')
            ->setLabel(__('Debit Card', 'mwc-payments'));
    }
}
