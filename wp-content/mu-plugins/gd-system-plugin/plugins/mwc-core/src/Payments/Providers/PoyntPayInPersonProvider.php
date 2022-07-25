<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Providers;

use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\PaymentMethodsGateway;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways\TransactionsGateway;
use GoDaddy\WordPress\MWC\Payments\Providers\AbstractProvider;
use GoDaddy\WordPress\MWC\Payments\Traits\HasPaymentMethodsTrait;
use GoDaddy\WordPress\MWC\Payments\Traits\HasTransactionsTrait;

/**
 * Poynt payment method provider.
 *
 * @since 2.10.0
 */
class PoyntPayInPersonProvider extends AbstractProvider
{
    use HasPaymentMethodsTrait;
    use HasTransactionsTrait;

    /** @var string provider label */
    protected $label = 'GoDaddy Payments - Selling in Person';

    /** @var string provider name */
    protected $name = 'godaddy-payments-payinperson';

    public function __construct()
    {
        $this->paymentMethodsGateway = PaymentMethodsGateway::class;
        $this->transactionsGateway = TransactionsGateway::class;
    }
}
