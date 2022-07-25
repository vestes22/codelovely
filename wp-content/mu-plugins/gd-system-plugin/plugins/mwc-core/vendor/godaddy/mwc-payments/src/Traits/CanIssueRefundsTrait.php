<?php

namespace GoDaddy\WordPress\MWC\Payments\Traits;

use Exception;
use GoDaddy\WordPress\MWC\Common\Events\Events;
use GoDaddy\WordPress\MWC\Payments\Events\RefundTransactionEvent;
use GoDaddy\WordPress\MWC\Payments\Models\Transactions\RefundTransaction;

/**
 * Can Issue Refunds Trait.
 *
 * @since 0.1.0
 */
trait CanIssueRefundsTrait
{
    use AdaptsRequestsTrait;

    /** @var string refund transaction adapter class name */
    protected $refundTransactionAdapter;

    /**
     * Creates refund request.
     *
     * @since 0.1.0
     *
     * @param RefundTransaction $transaction
     *
     * @return RefundTransaction
     * @throws Exception
     */
    public function refund(RefundTransaction $transaction) : RefundTransaction
    {
        if ($transaction->getRemoteId()) {
            // refund transaction has already been processed, so broadcast and return it
            $updatedTransaction = $transaction;
        } else {
            $updatedTransaction = $this->doAdaptedRequest($transaction, new $this->refundTransactionAdapter($transaction));
        }

        Events::broadcast(new RefundTransactionEvent($updatedTransaction));

        return $updatedTransaction;
    }
}
