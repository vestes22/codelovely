<?php

namespace GoDaddy\WordPress\MWC\Payments\Events;

use GoDaddy\WordPress\MWC\Payments\Models\Transactions\CaptureTransaction;

/**
 * Capture transaction event.
 *
 * @since 0.1.0
 */
class CaptureTransactionEvent extends AbstractTransactionEvent
{
    /**
     * Sets the capture transaction the event is for.
     *
     * @since 0.1.0
     *
     * @param CaptureTransaction $transaction
     */
    public function __construct(CaptureTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
