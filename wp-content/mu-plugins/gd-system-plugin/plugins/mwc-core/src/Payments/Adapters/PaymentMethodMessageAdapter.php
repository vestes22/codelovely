<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Payments\Models\PaymentMethods\AbstractPaymentMethod;

/**
 * Payment method message adapter.
 */
class PaymentMethodMessageAdapter implements DataSourceAdapterContract
{
    /** @var AbstractPaymentMethod source */
    private $source;

    /**
     * PaymentMethodMessageAdapter constructor.
     *
     * @param AbstractPaymentMethod $paymentMethod
     */
    public function __construct(AbstractPaymentMethod $paymentMethod)
    {
        $this->source = $paymentMethod;
    }

    /**
     * Converts from Data Source format.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function convertFromSource() : string
    {
        return '';
    }

    /**
     * Converts to Data Source format.
     *
     * @since 1.0.0
     *
     * @return AbstractPaymentMethod
     */
    public function convertToSource() : AbstractPaymentMethod
    {
        return $this->source;
    }
}
