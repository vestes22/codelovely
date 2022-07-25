<?php

namespace GoDaddy\WordPress\MWC\Payments\Events;

use GoDaddy\WordPress\MWC\Payments\Models\Transactions\VoidTransaction;

/**
 * Void transaction event.
 *
 * @since 0.1.0
 */
class VoidTransactionEvent extends AbstractTransactionEvent
{
    /**
     * Sets the void transaction the event is for.
     *
     * @since 0.1.0
     *
     * @param VoidTransaction $transaction
     */
    public function __construct(VoidTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
