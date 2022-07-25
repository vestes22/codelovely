<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use GoDaddy\WordPress\MWC\Payments\Events\AbstractTransactionEvent;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\RefundTransaction;

class BeforeCreateRefundEvent extends AbstractTransactionEvent
{
    /**
     * Constructor.
     *
     * @param RefundTransaction $transaction the refund transaction
     */
    public function __construct(RefundTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
