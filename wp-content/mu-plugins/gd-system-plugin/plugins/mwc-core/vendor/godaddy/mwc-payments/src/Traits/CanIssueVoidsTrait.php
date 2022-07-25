<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Payments\Events\VoidTransactionEvent;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\VoidTransaction;

/**
 * Can Issue Voids Trait.
 *
 * @since 0.1.0
 */
trait CanIssueVoidsTrait
{
    use AdaptsRequestsTrait;

    /** @var string Void Transaction Adapter class */
    protected $voidTransactionAdapter;

    /**
     * Issues void transaction request.
     *
     * @since 0.1.0
     *
     * @param VoidTransaction $transaction
     *
     * @return VoidTransaction
     * @throws Exception
     */
    public function void(VoidTransaction $transaction) : VoidTransaction
    {
        if ($transaction->getRemoteId()) {
            // void transaction has already been processed, so broadcast and return it
            $updatedTransaction = $transaction;
        } else {
            $updatedTransaction = $this->doAdaptedRequest($transaction, new $this->voidTransactionAdapter($transaction));
        }

        Events::broadcast(new VoidTransactionEvent($updatedTransaction));

        return $updatedTransaction;
    }
}
