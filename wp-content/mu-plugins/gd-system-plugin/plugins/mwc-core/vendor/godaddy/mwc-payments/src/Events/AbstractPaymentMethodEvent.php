<?php

namespace GoDaddy\WordPress\MWC\Payments\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;

/**
 * Payment method event abstract class.
 *
 * @since 0.1.0
 */
abstract class AbstractPaymentMethodEvent implements EventContract
{
    /** @var AbstractPaymentMethod */
    protected $paymentMethod;

    /**
     * Gets the payment method the event belongs to.
     *
     * @since 0.1.0
     *
     * @return AbstractPaymentMethod
     */
    public function getPaymentMethod() : AbstractPaymentMethod
    {
        return $this->paymentMethod;
    }
}
