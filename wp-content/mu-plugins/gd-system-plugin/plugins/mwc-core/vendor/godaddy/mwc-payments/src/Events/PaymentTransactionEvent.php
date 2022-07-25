<?php

namespace GoDaddy\WordPress\MWC\Payments\Events;

use GoDaddy\WordPress\MWC\Payments\Models\Transactions\PaymentTransaction;

/**
 * Payment transaction event.
 *
 * @since 0.1.0
 */
class PaymentTransactionEvent extends AbstractTransactionEvent
{
    /**
     * Sets the payment transaction the event is for.
     *
     * @since 0.1.0
     *
     * @param PaymentTransaction $transaction
     */
    public function __construct(PaymentTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
