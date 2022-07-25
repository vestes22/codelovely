<?php

namespace GoDaddy\WordPress\MWC\Payments\Events;

use GoDaddy\WordPress\MWC\Payments\Models\Transactions\RefundTransaction;

/**
 * Refund transaction event.
 *
 * @since 0.1.0
 */
class RefundTransactionEvent extends AbstractTransactionEvent
{
    /**
     * Sets the refund transaction the event is for.
     *
     * @since 0.1.0
     *
     * @param RefundTransaction $transaction
     */
    public function __construct(RefundTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
