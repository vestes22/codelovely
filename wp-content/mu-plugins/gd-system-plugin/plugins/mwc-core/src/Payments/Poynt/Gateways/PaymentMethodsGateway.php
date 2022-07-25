<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways;

use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\CardPaymentMethodAdapter;
use GoDaddy\WordPress\MWC\Payments\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Payments\Traits\CanCreatePaymentMethodsTrait;

/**
 * The payment methods gateway.
 *
 * @since 2.10.0
 */
class PaymentMethodsGateway extends AbstractGateway
{
    use CanCreatePaymentMethodsTrait;

    /**
     * The payment methods gateway constructor.
     */
    public function __construct()
    {
        $this->paymentMethodAdapter = CardPaymentMethodAdapter::class;
    }
}
