<?php

namespace GoDaddy\WordPress\MWC\Payments\Events;

use GoDaddy\WordPress\MWC\Common\Events\Contracts\EventContract;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\AbstractTransaction;

/**
 * Payment transaction event abstract class.
 *
 * @since 0.1.0
 */
abstract class AbstractTransactionEvent implements EventContract
{
    /** @var AbstractTransaction */
    protected $transaction;

    /**
     * Gets transaction the event belongs to.
     *
     * @since 0.1.0
     *
     * @return AbstractTransaction
     */
    public function getTransaction() : AbstractTransaction
    {
        return $this->transaction;
    }
}
