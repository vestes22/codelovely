<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;

/**
 * Mastercard card brand.
 */
final class MastercardCardBrand implements CardBrandContract
{
    use HasLabelTrait;

    /**
     * Mastercard card brand constructor.
     */
    public function __construct()
    {
        $this->setName('mastercard')
            ->setLabel(_x('Mastercard', 'card brand name', 'mwc-payments'));
    }
}
