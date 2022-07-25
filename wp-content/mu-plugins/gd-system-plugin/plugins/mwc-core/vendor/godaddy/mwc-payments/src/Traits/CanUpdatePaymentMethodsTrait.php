<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;

/**
 * Can Update Payment Methods Trait.
 *
 * @since 0.1.0
 */
trait CanUpdatePaymentMethodsTrait
{
    use AdaptsPaymentMethodsTrait;
    use AdaptsRequestsTrait;

    /**
     * Performs payment method update request
     *
     * @since 0.1.0
     *
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return AbstractPaymentMethod
     * @throws Exception
     */
    public function update(AbstractPaymentMethod $paymentMethod) : AbstractPaymentMethod
    {
        return $this->doAdaptedRequest($paymentMethod, new $this->paymentMethodAdapter($paymentMethod));
    }
}
