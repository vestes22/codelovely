<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use GoDaddy\WordPress\MWC\Payments\Gateways\AbstractGateway;

/**
 * Has payment methods trait.
 *
 * @since 0.1.0
 */
trait HasPaymentMethodsTrait
{
    /** @var string payment methods gateway class name */
    protected $paymentMethodsGateway;

    /**
     * Gets the payment methods gateway instance.
     *
     * @since 0.1.0
     *
     * @return AbstractGateway
     */
    public function paymentMethods() : AbstractGateway
    {
        return new $this->paymentMethodsGateway();
    }
}
