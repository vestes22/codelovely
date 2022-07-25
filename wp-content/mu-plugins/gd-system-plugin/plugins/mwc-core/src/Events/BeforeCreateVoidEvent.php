<?php

namespace GoDaddy\WordPress\MWC\Core\Events;

use GoDaddy\WordPress\MWC\Payments\Events\AbstractTransactionEvent;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\VoidTransaction;

class BeforeCreateVoidEvent extends AbstractTransactionEvent
{
    /**
     * Constructor.
     *
     * @param VoidTransaction $transaction the void transaction
     */
    public function __construct(VoidTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
