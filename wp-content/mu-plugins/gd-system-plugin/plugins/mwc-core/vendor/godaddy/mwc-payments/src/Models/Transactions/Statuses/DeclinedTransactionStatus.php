<?php

namespace GoDaddy\WordPress\MWC\Payments\Models\Transactions\Statuses;

use GoDaddy\WordPress\MWC\Common\Traits\HasLabelTrait;
use GoDaddy\WordPress\MWC\Payments\Contracts\TransactionStatusContract;

/**
 * Transaction declined status.
 *
 * @since 0.1.0
 */
class DeclinedTransactionStatus implements TransactionStatusContract
{
    use HasLabelTrait;

    /**
     * Sets up the transaction status.
     *
     * @since 0.1.0
     */
    public function __construct()
    {
        $this->setName('declined');
        $this->setLabel(__('Declined', 'mwc-payments'));
    }
}
