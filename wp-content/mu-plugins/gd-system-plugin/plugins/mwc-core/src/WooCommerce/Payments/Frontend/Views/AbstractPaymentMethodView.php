<?php

namespace GoDaddy\WordPress\MWC\Core\WooCommerce\Payments\Frontend\Views;

use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;

/**
 * The abstract payment method view.
 */
abstract class AbstractPaymentMethodView
{
    /** @var AbstractPaymentMethod */
    protected $paymentMethod;

    /**
     * AbstractPaymentMethod constructor.
     *
     * @param AbstractPaymentMethod $paymentMethod
     */
    public function __construct(AbstractPaymentMethod $paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Gets the icon URL, if any.
     *
     * @return string
     */
    public function getIconUrl() : string
    {
        return '';
    }

    /**
     * Gets the payment method object.
     *
     * @return AbstractPaymentMethod
     */
    protected function getPaymentMethod() : AbstractPaymentMethod
    {
        return $this->paymentMethod;
    }
}
