<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\Cards\Brands;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Payments\Contracts\CardBrandContract;

/**
 * Maestro card brand
 */
final class MaestroCardBrand implements CardBrandContract
{
    use HasLabelTrait;

    /**
     * Maestro card brand constructor.
     */
    public function __construct()
    {
        $this->setName('maestro')
            ->setLabel(_x('Maestro', 'card brand name', 'mwc-payments'));
    }
}
