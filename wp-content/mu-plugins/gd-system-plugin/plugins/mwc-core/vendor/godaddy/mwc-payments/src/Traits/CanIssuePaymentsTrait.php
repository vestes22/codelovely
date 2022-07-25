<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Payments\Events\PaymentTransactionEvent;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\PaymentTransaction;

/**
 * Can Issue Payments Trait.
 *
 * @since 0.1.0
 */
trait CanIssuePaymentsTrait
{
    use AdaptsRequestsTrait;

    /** @var string payment transaction adapter class name */
    protected $paymentTransactionAdapter;

    /**
     * Creates payment method request
     *
     * @since 0.1.0
     *
     * @param PaymentTransaction $transaction
     *
     * @return PaymentTransaction
     * @throws Exception
     */
    public function pay(PaymentTransaction $transaction) : PaymentTransaction
    {
        $paymentTransaction = $this->doAdaptedRequest($transaction, new $this->paymentTransactionAdapter($transaction));

        Events::broadcast(new PaymentTransactionEvent($paymentTransaction));

        return $paymentTransaction;
    }
}
