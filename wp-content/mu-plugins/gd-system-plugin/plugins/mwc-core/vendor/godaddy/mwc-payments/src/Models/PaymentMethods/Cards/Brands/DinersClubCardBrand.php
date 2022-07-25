<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;

/**
 * Diners Club card brand.
 */
final class DinersClubCardBrand implements CardBrandContract
{
    use HasLabelTrait;

    /**
     * Diners Club card brand constructor.
     */
    public function __construct()
    {
        $this->setName('diners-club')
            ->setLabel(_x('Diners Club', 'card brand name', 'mwc-payments'));
    }
}
