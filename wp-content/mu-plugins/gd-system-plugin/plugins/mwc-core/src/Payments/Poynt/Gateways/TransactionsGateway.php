<?php

namespace GoDaddy\WordPress\MWC\Core\Payments\Poynt\Gateways;

use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\CaptureTransactionAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\PaymentTransactionAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\RefundTransactionAdapter;
use GoDaddy\WordPress\MWC\Core\Payments\Poynt\Http\Adapters\VoidTransactionAdapter;
use GoDaddy\WordPress\MWC\Payments\Gateways\AbstractGateway;
use GoDaddy\WordPress\MWC\Payments\Traits\CanIssueCapturesTrait;
use GoDaddy\WordPress\MWC\Payments\Traits\CanIssuePaymentsTrait;
use GoDaddy\WordPress\MWC\Payments\Traits\CanIssueRefundsTrait;
use GoDaddy\WordPress\MWC\Payments\Traits\CanIssueVoidsTrait;

/**
 * The transactions gateway.
 *
 * @since 2.10.0
 */
class TransactionsGateway extends AbstractGateway
{
    use CanIssueCapturesTrait;
    use CanIssuePaymentsTrait;
    use CanIssueRefundsTrait;
    use CanIssueVoidsTrait;

    /**
     * The transactions gateway constructor.
     */
    public function __construct()
    {
        $this->captureTransactionAdapter = CaptureTransactionAdapter::class;
        $this->paymentTransactionAdapter = PaymentTransactionAdapter::class;
        $this->refundTransactionAdapter = RefundTransactionAdapter::class;
        $this->voidTransactionAdapter = VoidTransactionAdapter::class;
    }
}
