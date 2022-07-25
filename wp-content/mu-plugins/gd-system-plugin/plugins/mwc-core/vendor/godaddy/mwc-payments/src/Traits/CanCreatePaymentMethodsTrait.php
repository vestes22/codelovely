<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Payments\Events\CreatePaymentMethodEvent;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;

/**
 * Can Create Payment Methods Trait.
 *
 * @since 0.1.0
 */
trait CanCreatePaymentMethodsTrait
{
    use AdaptsPaymentMethodsTrait;
    use AdaptsRequestsTrait;

    /**
     * Creates payment method request
     *
     * @since 0.1.0
     *
     * @param AbstractPaymentMethod $paymentMethod
     *
     * @return AbstractPaymentMethod
     * @throws Exception
     */
    public function create(AbstractPaymentMethod $paymentMethod) : AbstractPaymentMethod
    {
        $request = $this->doAdaptedRequest($paymentMethod, new $this->paymentMethodAdapter($paymentMethod));

        Events::broadcast(new CreatePaymentMethodEvent($request));

        return $request;
    }
}
